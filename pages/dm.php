<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';
$user = requireLogin();
checkBanned($user);
$db = getDB();
$isAdmin = in_array($user['role'],['admin','owner']);
$unreadNotif = getUnreadCount($user['id']);

$withId = isset($_GET['with']) ? (int)$_GET['with'] : (isset($_GET['user']) ? (int)$_GET['user'] : 0);
$other = null;
if ($withId && $withId !== $user['id']) {
    $s = $db->prepare("SELECT id,airline_name,username,country,profile_photo,logo_zoom FROM users WHERE id=? AND is_banned=0");
    $s->execute([$withId]);
    $other = $s->fetch();
}

// Conversations list (for sidebar/inbox)
$convos = $db->prepare("
    SELECT u.id, u.airline_name, u.username, u.profile_photo, u.logo_zoom,
      (SELECT body FROM direct_messages
         WHERE (from_id=? AND to_id=u.id) OR (from_id=u.id AND to_id=?)
         ORDER BY created_at DESC LIMIT 1) AS last_msg,
      (SELECT created_at FROM direct_messages
         WHERE (from_id=? AND to_id=u.id) OR (from_id=u.id AND to_id=?)
         ORDER BY created_at DESC LIMIT 1) AS last_at,
      (SELECT COUNT(*) FROM direct_messages WHERE from_id=u.id AND to_id=? AND is_read=0) AS unread_count
    FROM users u
    WHERE u.id != ? AND u.is_banned=0
      AND EXISTS (SELECT 1 FROM direct_messages
                  WHERE (from_id=? AND to_id=u.id) OR (from_id=u.id AND to_id=?))
    ORDER BY last_at DESC
");
$convos->execute(array_fill(0,8,$user['id']));
$conversations = $convos->fetchAll();
// Also add $other to list if not already there (new thread)
if ($other) {
    $found = false;
    foreach ($conversations as $c) { if ($c['id']==$other['id']) { $found=true; break; } }
    if (!$found) array_unshift($conversations, array_merge($other,['last_msg'=>null,'last_at'=>null,'unread_count'=>0]));
}

// Mark messages as read when opening thread
if ($other) {
    $db->prepare("UPDATE direct_messages SET is_read=1 WHERE from_id=? AND to_id=? AND is_read=0")->execute([$other['id'],$user['id']]);
}

function dmAvatar($u, $size=42) {
    $zd = $u['logo_zoom'] ? json_decode($u['logo_zoom'],true) : null;
    $s  = $zd['scale']   ?? 1;
    $ox = $zd['offsetX'] ?? 0;
    $oy = $zd['offsetY'] ?? 0;
    $initials = strtoupper(substr($u['airline_name']??$u['username']??'?',0,2));
    if (!empty($u['profile_photo'])) {
        return "<div style='width:{$size}px;height:{$size}px;border-radius:50%;overflow:hidden;position:relative;flex-shrink:0;border:2px solid rgba(59,130,246,0.25);'>
            <img src='".SITE_URL."/".htmlspecialchars($u['profile_photo'])."'
                 style='position:absolute;width:".($s*100)."%;height:".($s*100)."%;object-fit:cover;top:50%;left:50%;transform:translate(calc(-50% + {$ox}px),calc(-50% + {$oy}px));' loading='lazy'>
        </div>";
    }
    $fs = round($size/3);
    return "<div style='width:{$size}px;height:{$size}px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:{$fs}px;font-weight:800;color:white;flex-shrink:0;font-family:Outfit,sans-serif;flex-shrink:0;'>{$initials}</div>";
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">
  <title>Direct Messages</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <?php lightThemeCSS(); darkModeScript(); ?>
  <style>
    html,body{height:100%;overflow:hidden;}
    .dm-layout{display:flex;height:calc(100vh - 64px);}
    /* Lift input bar above phone navigation gestures / home bar */
    .dm-input-bar{
      padding-bottom: max(14px, env(safe-area-inset-bottom, 14px)) !important;
    }
    @media(max-width:640px){
      .dm-layout{height:calc(100dvh - 64px);}
      .dm-input-bar{
        padding-bottom: max(22px, calc(env(safe-area-inset-bottom, 0px) + 18px)) !important;
      }
    }

    /* Sidebar */
    .dm-sidebar{width:300px;flex-shrink:0;border-right:1px solid rgba(255,255,255,0.55);display:flex;flex-direction:column;background:rgba(255,255,255,0.45);backdrop-filter:blur(16px);}
    .dm-sidebar-header{padding:16px;border-bottom:1px solid rgba(255,255,255,0.5);display:flex;align-items:center;justify-content:space-between;}
    .dm-sidebar-list{flex:1;overflow-y:auto;padding:8px;}
    .dm-convo-item{display:flex;align-items:center;gap:11px;padding:10px 11px;border-radius:14px;text-decoration:none;transition:all 0.18s;margin-bottom:3px;}
    .dm-convo-item:hover{background:rgba(59,130,246,0.07);}
    .dm-convo-item.active{background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.15);}

    /* Thread */
    .dm-thread{flex:1;display:flex;flex-direction:column;min-width:0;}
    .dm-thread-header{padding:12px 18px;border-bottom:1px solid rgba(255,255,255,0.55);display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.55);backdrop-filter:blur(12px);}
    .dm-messages{flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:10px;}
    .dm-input-bar{padding:12px 16px;border-top:1px solid rgba(255,255,255,0.55);background:rgba(255,255,255,0.6);backdrop-filter:blur(12px);display:flex;gap:10px;align-items:flex-end;}
    .dm-input{flex:1;padding:10px 15px;border-radius:22px;font-size:14px;background:rgba(255,255,255,0.85);border:1px solid rgba(203,213,225,0.55);color:#1e293b;outline:none;font-family:'Plus Jakarta Sans',sans-serif;transition:all 0.2s;resize:none;max-height:110px;overflow-y:auto;}
    .dm-input:focus{border-color:rgba(59,130,246,0.4);box-shadow:0 0 0 3px rgba(59,130,246,0.09);}
    .dm-send-btn{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all 0.2s;}
    .dm-send-btn:hover{box-shadow:0 4px 14px rgba(59,130,246,0.35);transform:scale(1.05);}
    .dm-empty-thread{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:#94a3b8;}

    /* Mobile: hide sidebar when thread open, hide thread when no thread */
    @media(max-width:640px){
      .dm-sidebar{width:100%;border-right:none;}
      .dm-thread{display:none;}
      .dm-layout.has-thread .dm-sidebar{display:none;}
      .dm-layout.has-thread .dm-thread{display:flex;}
    }

    /* New DM modal */
    .modal-overlay{display:none;position:fixed;inset:0;z-index:200;background:rgba(15,23,42,0.42);backdrop-filter:blur(5px);align-items:center;justify-content:center;}
    .modal-overlay.open{display:flex;}
    .modal-box{background:rgba(255,255,255,0.97);border-radius:22px;padding:22px;max-width:370px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,0.14);}
    .user-result{display:flex;align-items:center;gap:10px;padding:9px 11px;border-radius:11px;cursor:pointer;transition:background 0.15s;text-decoration:none;}
    .user-result:hover{background:rgba(59,130,246,0.06);}

    ::-webkit-scrollbar{width:4px;}
    ::-webkit-scrollbar-thumb{background:rgba(59,130,246,0.18);border-radius:2px;}
  </style>
</head>
<body>
<div style="display:flex;flex-direction:column;height:100%;background:var(--bg);">

<?= lightNavHTML($user,$isAdmin,0,$unreadNotif) ?>

<div class="dm-layout<?= $other ? ' has-thread' : '' ?>">

  <!-- Sidebar: conversation list -->
  <div class="dm-sidebar">
    <div class="dm-sidebar-header">
      <span class="font-heading font-bold text-slate-800" style="font-size:15px;">Messages</span>
      <button onclick="openNewDM()" title="New message" style="width:32px;height:32px;border-radius:9px;background:rgba(59,130,246,0.1);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#2563eb;">
        <i data-lucide="pencil" style="width:15px;height:15px;"></i>
      </button>
    </div>
    <div class="dm-sidebar-list">
      <?php if (!$conversations): ?>
      <div style="padding:30px 12px;text-align:center;">
        <p style="font-size:13px;color:#94a3b8;">No messages yet.</p>
        <button onclick="openNewDM()" class="btn-primary mt-3 px-4 py-2 rounded-xl text-sm font-semibold" style="display:inline-flex;align-items:center;gap:6px;border:none;cursor:pointer;">
          <i data-lucide="plus" style="width:14px;height:14px;"></i> Start chat
        </button>
      </div>
      <?php else: ?>
      <?php foreach ($conversations as $c):
        $zd = $c['logo_zoom'] ? json_decode($c['logo_zoom'],true) : null;
        $s  = $zd['scale']   ?? 1;
        $ox = $zd['offsetX'] ?? 0;
        $oy = $zd['offsetY'] ?? 0;
        $initials = strtoupper(substr($c['airline_name']??'?',0,2));
        $preview = $c['last_msg'] ? (mb_strlen($c['last_msg'])>38?mb_substr($c['last_msg'],0,38).'…':$c['last_msg']) : 'Start a conversation';
        $timeAgo = '';
        if ($c['last_at']) { $diff=time()-strtotime($c['last_at']); if($diff<60)$timeAgo='now'; elseif($diff<3600)$timeAgo=floor($diff/60).'m'; elseif($diff<86400)$timeAgo=floor($diff/3600).'h'; else $timeAgo=date('M j',strtotime($c['last_at'])); }
        $isActive = $other && $other['id']==$c['id'];
      ?>
      <a href="<?= SITE_URL ?>/pages/dm.php?with=<?= $c['id'] ?>" class="dm-convo-item<?= $isActive?' active':'' ?>">
        <?php if (!empty($c['profile_photo'])): ?>
        <div style="width:40px;height:40px;border-radius:50%;overflow:hidden;position:relative;flex-shrink:0;border:2px solid rgba(59,130,246,0.2);">
          <img src="<?= SITE_URL ?>/<?= htmlspecialchars($c['profile_photo']) ?>"
               style="position:absolute;width:<?= $s*100 ?>%;height:<?= $s*100 ?>%;object-fit:cover;top:50%;left:50%;transform:translate(calc(-50% + <?= $ox ?>px),calc(-50% + <?= $oy ?>px));" loading="lazy">
        </div>
        <?php else: ?>
        <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:white;flex-shrink:0;font-family:'Outfit',sans-serif;"><?= htmlspecialchars($initials) ?></div>
        <?php endif; ?>
        <div style="flex:1;min-width:0;">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:1px;">
            <p style="font-size:13px;font-weight:<?= $c['unread_count']>0?'700':'600' ?>;color:<?= $c['unread_count']>0?'#1e293b':'#334155' ?>;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($c['airline_name']) ?></p>
            <span style="font-size:10px;color:#94a3b8;flex-shrink:0;"><?= $timeAgo ?></span>
          </div>
          <p style="font-size:11px;color:<?= $c['unread_count']>0?'#475569':'#94a3b8' ?>;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:<?= $c['unread_count']>0?'600':'400' ?>;"><?= htmlspecialchars($preview) ?></p>
        </div>
        <?php if ($c['unread_count'] > 0): ?>
        <span style="background:#3b82f6;color:white;border-radius:99px;font-size:10px;font-weight:700;min-width:18px;height:18px;display:flex;align-items:center;justify-content:center;padding:0 4px;flex-shrink:0;"><?= $c['unread_count'] ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Thread panel -->
  <div class="dm-thread">
    <?php if ($other): ?>
    <!-- Thread header -->
    <div class="dm-thread-header">
      <a href="<?= SITE_URL ?>/pages/dm.php" style="display:none;color:#94a3b8;margin-right:4px;text-decoration:none;" class="sm:hidden" id="back-btn">
        <i data-lucide="arrow-left" style="width:20px;height:20px;"></i>
      </a>
      <a href="<?= SITE_URL ?>/pages/profile.php?id=<?= $other['id'] ?>" style="text-decoration:none;display:flex;align-items:center;gap:10px;">
        <?= dmAvatar($other, 38) ?>
        <div>
          <p style="font-size:14px;font-weight:700;color:#1e293b;"><?= htmlspecialchars($other['airline_name']) ?></p>
          <p style="font-size:11px;color:#94a3b8;">@<?= htmlspecialchars($other['username']) ?><?= $other['country'] ? ' · '.htmlspecialchars($other['country']) : '' ?></p>
        </div>
      </a>
      <a href="<?= SITE_URL ?>/pages/profile.php?id=<?= $other['id'] ?>" style="margin-left:auto;color:#94a3b8;text-decoration:none;padding:6px;border-radius:8px;transition:background 0.15s;" title="View profile">
        <i data-lucide="external-link" style="width:15px;height:15px;"></i>
      </a>
    </div>
    <!-- Messages area -->
    <div id="dm-messages" class="dm-messages">
      <div id="dm-loading" style="text-align:center;padding:30px 0;color:#94a3b8;font-size:13px;">Loading…</div>
    </div>
    <!-- Input -->
    <div class="dm-input-bar">
      <textarea id="dm-input" class="dm-input" placeholder="Message <?= htmlspecialchars($other['airline_name']) ?>…" rows="1"
        onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendDM();}"
        oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,110)+'px';"></textarea>
      <button class="dm-send-btn" onclick="sendDM()" title="Send">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      </button>
    </div>

    <?php else: ?>
    <!-- No thread selected (desktop) -->
    <div class="dm-empty-thread">
      <div style="width:56px;height:56px;border-radius:50%;background:rgba(59,130,246,0.08);display:flex;align-items:center;justify-content:center;">
        <i data-lucide="send" style="width:24px;height:24px;color:#3b82f6;"></i>
      </div>
      <p style="font-weight:600;color:#475569;font-size:15px;">Your Messages</p>
      <p style="font-size:13px;color:#94a3b8;text-align:center;max-width:220px;">Select a conversation or start a new one.</p>
      <button onclick="openNewDM()" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold mt-2" style="border:none;cursor:pointer;display:inline-flex;align-items:center;gap:7px;">
        <i data-lucide="plus" style="width:15px;height:15px;"></i> New Message
      </button>
    </div>
    <?php endif; ?>
  </div>

</div><!-- end dm-layout -->

<!-- New DM Modal -->
<div id="new-dm-modal" class="modal-overlay">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
      <h3 class="font-heading text-slate-800" style="font-weight:700;font-size:16px;">New Message</h3>
      <button onclick="closeNewDM()" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;display:flex;"><i data-lucide="x" style="width:17px;height:17px;"></i></button>
    </div>
    <div style="position:relative;margin-bottom:10px;">
      <i data-lucide="search" style="width:15px;height:15px;color:#94a3b8;position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;"></i>
      <input type="text" id="dm-search-input" placeholder="Search pilot or airline…"
             oninput="searchUsers(this.value)"
             style="width:100%;padding:9px 13px 9px 34px;border-radius:11px;background:rgba(255,255,255,0.88);border:1px solid rgba(203,213,225,0.6);font-size:14px;color:#1e293b;outline:none;font-family:'Plus Jakarta Sans',sans-serif;">
    </div>
    <div id="dm-user-results" style="max-height:260px;overflow-y:auto;"></div>
    <p id="dm-search-hint" style="text-align:center;font-size:12px;color:#94a3b8;padding:14px 0;">Start typing to find a pilot…</p>
  </div>
</div>

<script>
const SITE_URL = "<?= SITE_URL ?>";
const MY_USER = "<?= htmlspecialchars($user['username'],ENT_QUOTES) ?>";
<?php if ($other): ?>
const OTHER_ID = <?= $other['id'] ?>;
<?php endif; ?>
let lastDmId = 0;
lucide.createIcons();

// Back button show on mobile when thread active
<?php if ($other): ?>
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('back-btn');
  if (btn && window.innerWidth <= 640) btn.style.display = 'flex';
});
<?php endif; ?>

// ── DM thread polling ──────────────────────────────────────────────────────
<?php if ($other): ?>
function esc(t){ return (t||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function makeAvatar(airline, size){
  const ini = (airline||'?').substring(0,2).toUpperCase();
  const fs = Math.round(size/3);
  return `<div style="width:${size}px;height:${size}px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:${fs}px;font-weight:800;color:white;flex-shrink:0;font-family:Outfit,sans-serif;">${ini}</div>`;
}

function loadDMs(){
  fetch(`${SITE_URL}/api/dm.php?with=${OTHER_ID}&after=${lastDmId}`)
    .then(r=>r.json())
    .then(data=>{
      if(!data||!Array.isArray(data.messages)) return;
      document.getElementById('dm-loading').style.display='none';
      const box = document.getElementById('dm-messages');
      const wasAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;
      data.messages.forEach(m=>{
        if(document.getElementById('dm-'+m.id)) return;
        const isMe = m.from_id == <?= $user['id'] ?>;
        const div = document.createElement('div');
        div.id = 'dm-'+m.id;
        div.style.cssText = `display:flex;align-items:flex-end;gap:8px;${isMe?'justify-content:flex-end':'justify-content:flex-start'}`;
        const bubble = `<div style="max-width:68%;">
          <div class="${isMe?'bubble-me':'bubble-other'}" style="padding:9px 14px;font-size:13px;line-height:1.45;word-break:break-word;white-space:pre-wrap;">${esc(m.body)}</div>
          <p style="font-size:10px;color:#94a3b8;margin-top:3px;${isMe?'text-align:right':''}">${m.time}</p>
        </div>`;
        div.innerHTML = isMe ? bubble : (makeAvatar(m.from_airline,28)+bubble);
        box.appendChild(div);
        lastDmId = Math.max(lastDmId, parseInt(m.id));
      });
      if(wasAtBottom || lastDmId===0) box.scrollTop = box.scrollHeight;
    }).catch(()=>{});
}

function sendDM(){
  const inp = document.getElementById('dm-input');
  const msg = inp.value.trim();
  if(!msg) return;
  inp.value=''; inp.style.height='auto';
  fetch(`${SITE_URL}/api/dm.php`, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({to_id: OTHER_ID, body: msg})
  }).then(()=>loadDMs()).catch(()=>{});
}

loadDMs();
setInterval(loadDMs, 2500);
<?php endif; ?>

// ── New DM modal ───────────────────────────────────────────────────────────
function openNewDM(){ document.getElementById('new-dm-modal').classList.add('open'); setTimeout(()=>document.getElementById('dm-search-input').focus(),80); }
function closeNewDM(){ document.getElementById('new-dm-modal').classList.remove('open'); document.getElementById('dm-search-input').value=''; document.getElementById('dm-user-results').innerHTML=''; document.getElementById('dm-search-hint').style.display='block'; }
document.getElementById('new-dm-modal').addEventListener('click', e=>{ if(e.target===document.getElementById('new-dm-modal')) closeNewDM(); });

let _searchTimer;
function searchUsers(q){
  clearTimeout(_searchTimer);
  const results = document.getElementById('dm-user-results');
  const hint = document.getElementById('dm-search-hint');
  if(!q.trim()){ results.innerHTML=''; hint.style.display='block'; return; }
  hint.style.display='none';
  _searchTimer = setTimeout(()=>{
    fetch(`${SITE_URL}/api/dm_search.php?q=${encodeURIComponent(q)}`)
      .then(r=>r.json())
      .then(data=>{
        if(!data.users||!data.users.length){ results.innerHTML='<p style="text-align:center;font-size:13px;color:#94a3b8;padding:16px 0;">No pilots found.</p>'; return; }
        results.innerHTML = data.users.map(u=>`
          <a href="${SITE_URL}/pages/dm.php?with=${u.id}" class="user-result" style="display:flex;align-items:center;gap:10px;padding:9px 11px;border-radius:11px;text-decoration:none;transition:background 0.15s;" onmouseover="this.style.background='rgba(59,130,246,0.06)'" onmouseout="this.style.background=''">
            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:white;font-family:Outfit,sans-serif;flex-shrink:0;">${u.initials}</div>
            <div>
              <p style="font-size:13px;font-weight:600;color:#1e293b;">${u.airline_name}</p>
              <p style="font-size:11px;color:#94a3b8;">@${u.username}${u.country?' · '+u.country:''}</p>
            </div>
          </a>`).join('');
      }).catch(()=>{ results.innerHTML='<p style="text-align:center;font-size:13px;color:#94a3b8;padding:12px 0;">Error loading results.</p>'; });
  }, 280);
}
</script>
</div>
</body>
</html>