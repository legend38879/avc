<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';
$user = requireLogin();
checkBanned($user);
$isAdmin = in_array($user['role'],['admin','owner']);
$unreadNotif = getUnreadCount($user['id']);

$room = $_GET['room'] ?? 'public';
$allowedRooms  = ['public','SKY TEAM 2.0','Aura Union','Prime United'];
$allianceRooms = ['SKY TEAM 2.0','Aura Union','Prime United'];
if (in_array($room,$allianceRooms) && $user['alliance']!==$room) { $room='public'; $accessError='You can only access your own alliance chat.'; }
if (!in_array($room,$allowedRooms)) $room='public';
?><!DOCTYPE html>
<html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= $room==='public'?'Global Chat':'Alliance Chat' ?></title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <?php lightThemeCSS(); darkModeScript(); ?>
  <style>
    body{height:100vh;overflow:hidden;}
    .chat-layout{display:flex;flex-direction:column;height:calc(100vh - 64px);}
    .chat-main{flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:12px;}
    .chat-input-bar{padding:12px 16px;border-top:1px solid var(--chat-bar-border);background:var(--chat-bar-bg);backdrop-filter:blur(16px);}
    .msg-input-light{width:100%;padding:11px 16px;border-radius:14px;font-size:14px;background:var(--chat-input-bg);border:1px solid var(--chat-input-border);color:var(--text);outline:none;font-family:'Plus Jakarta Sans',sans-serif;transition:all 0.2s;}
    .msg-input-light:focus{border-color:rgba(59,130,246,0.4);box-shadow:0 0 0 3px rgba(59,130,246,0.08);}
    .msg-input-light::placeholder{color:var(--text-subtle);}
    .chat-box::-webkit-scrollbar{width:4px;}
    .chat-box::-webkit-scrollbar-thumb{background:rgba(59,130,246,0.15);border-radius:2px;}
    .pill-tab-light{padding:7px 16px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.2s;text-decoration:none;color:#64748b;border:1px solid transparent;}
    .pill-tab-light.active{background:rgba(59,130,246,0.1);color:#2563eb;border-color:rgba(59,130,246,0.2);}
    .pill-tab-light:hover:not(.active){background:rgba(59,130,246,0.05);color:#475569;}
    .logo-circle{border-radius:50%;overflow:hidden;position:relative;flex-shrink:0;}
    .logo-circle img{position:absolute;object-fit:cover;top:50%;left:50%;}
  </style>
</head><body>
<div style="position:sticky;top:0;z-index:50;"><?= lightNavHTML($user,$isAdmin,0,$unreadNotif) ?></div>

<div class="chat-layout">
  <div class="max-w-3xl w-full mx-auto px-4 flex flex-col h-full py-3">
    <?php if (isset($accessError)): ?>
    <div class="mb-3 px-4 py-3 rounded-xl text-sm font-medium" style="background:#fff1f2;border:1px solid #fecdd3;color:#e11d48;"><?= htmlspecialchars($accessError) ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="flex gap-2 mb-3 flex-wrap">
      <a href="?room=public" class="pill-tab-light <?= $room==='public'?'active':'' ?>">🌍 Public</a>
      <?php if (in_array($user['alliance']??'',$allianceRooms)): ?>
        <a href="?room=<?= urlencode($user['alliance']) ?>" class="pill-tab-light <?= $room===$user['alliance']?'active':'' ?>">🔒 <?= htmlspecialchars($user['alliance']) ?></a>
      <?php endif; ?>
    </div>

    <!-- Header -->
    <div class="glass rounded-2xl px-4 py-3 mb-3 flex items-center justify-between">
      <div>
        <p class="font-heading text-sm font-semibold text-slate-800"><?= $room==='public'?'🌍 Global Chat':'🔒 '.htmlspecialchars($room).' Alliance Chat' ?></p>
        <p class="text-xs text-slate-400"><?= $room==='public'?'Open to all members':'Private channel for alliance members' ?></p>
      </div>
      <div class="flex items-center gap-1.5"><div style="width:7px;height:7px;border-radius:50%;background:#22c55e;animation:pulse2 1.5s ease-in-out infinite;"></div><span class="text-xs text-slate-400">Live</span></div>
    </div>

    <!-- Messages -->
    <div id="chat-box" class="chat-box flex-1 rounded-2xl p-4 mb-3" style="background:rgba(255,255,255,0.45);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.65);overflow-y:auto;min-height:100px;max-height:calc(100vh - 300px);">
      <div id="msgs-inner" class="flex flex-col gap-3"></div>
      <div id="chat-loading" class="text-center py-10 text-slate-400 text-sm">Loading messages…</div>
    </div>

    <!-- Input -->
    <div class="chat-input-bar rounded-2xl flex gap-2">
      <input type="text" id="msg-input" class="msg-input-light flex-1" placeholder="Type a message…" maxlength="500" onkeydown="if(event.key==='Enter')sendMsg()">
      <button onclick="sendMsg()" class="btn-primary px-5 py-2.5 rounded-xl font-semibold text-sm flex-shrink-0">Send</button>
    </div>
  </div>
</div>

<script>
const ROOM="<?= addslashes(strtolower($_GET['room']??'public')) ?>";
const MY_USER="<?= htmlspecialchars($user['username'],ENT_QUOTES) ?>";
const SITE_URL="<?= SITE_URL ?>";
let lastId=0;

function esc(t){return(t||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");}

function makeAvatar(m,size){
  if(m.profile_photo){
    const s=m.logo_scale||1,ox=m.logo_offset_x||0,oy=m.logo_offset_y||0;
    return `<div style="width:${size}px;height:${size}px;border-radius:50%;overflow:hidden;position:relative;flex-shrink:0;border:2px solid rgba(59,130,246,0.2);">
      <img src="${SITE_URL}/${m.profile_photo}" style="position:absolute;width:${s*100}%;height:${s*100}%;object-fit:cover;top:50%;left:50%;transform:translate(calc(-50% + ${ox}px),calc(-50% + ${oy}px));" loading="lazy">
    </div>`;
  }
  const ini=(m.airline_name||'?').substring(0,2).toUpperCase();
  const fs=Math.round(size/2.8);
  return `<div style="width:${size}px;height:${size}px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:${fs}px;font-weight:800;color:white;flex-shrink:0;font-family:Outfit,sans-serif;">${ini}</div>`;
}

function loadMsgs(){
  fetch(`${SITE_URL}/api/messages.php?room=${encodeURIComponent(ROOM)}&after=${lastId}`)
    .then(r=>r.json()).then(data=>{
      if(!data||!Array.isArray(data.messages))return;
      document.getElementById('chat-loading').style.display='none';
      const box=document.getElementById('msgs-inner');
      data.messages.forEach(m=>{
        if(document.getElementById('msg-'+m.id))return;
        const isMe=m.username===MY_USER;
        const div=document.createElement('div');
        div.id='msg-'+m.id;
        div.style.cssText=`display:flex;align-items:flex-end;gap:8px;${isMe?'justify-content:flex-end':'justify-content:flex-start'}`;
        const av=makeAvatar(m,32);
        const bubble=`<div style="max-width:72%;">
          ${!isMe?`<p style="font-size:11px;font-weight:600;color:#64748b;margin-bottom:3px;">${esc(m.airline_name)}</p>`:''}
          <div class="${isMe?'bubble-me':'bubble-other'}" style="padding:10px 14px;font-size:13px;word-break:break-word;">${esc(m.message)}</div>
          <p style="font-size:10px;color:#94a3b8;margin-top:3px;${isMe?'text-align:right':''}">${m.time}</p>
        </div>`;
        div.innerHTML=isMe?bubble:av+bubble;
        box.appendChild(div);
        lastId=Math.max(lastId,parseInt(m.id));
      });
      const cb=document.getElementById('chat-box');
      cb.scrollTop=cb.scrollHeight;
    }).catch(()=>{});
}
function sendMsg(){
  const inp=document.getElementById('msg-input');
  const msg=inp.value.trim();
  if(!msg)return;
  inp.value='';
  fetch(`${SITE_URL}/api/messages.php`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({room:ROOM,message:msg})}).then(()=>loadMsgs()).catch(()=>{});
}
loadMsgs();setInterval(loadMsgs,2000);
lucide.createIcons();
</script>
</body></html>
