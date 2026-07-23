<?php
// includes/nav.php — Shared navigation bar for all pages
// Usage: include this after $user is available and session is started
// Requires: $user (from getCurrentUser), SITE_URL, navAvatar() helper

if (!function_exists('navAvatar')) {
    function navAvatar($u, $size = 28) {
        if (!$u) return '';
        $zd = isset($u['logo_zoom']) && $u['logo_zoom'] ? json_decode($u['logo_zoom'], true) : null;
        $s  = $zd['scale']   ?? 1;
        $ox = $zd['offsetX'] ?? 0;
        $oy = $zd['offsetY'] ?? 0;
        if (!empty($u['profile_photo'])) {
            $url = SITE_URL . '/' . htmlspecialchars($u['profile_photo']);
            return "<div style='width:{$size}px;height:{$size}px;border-radius:50%;overflow:hidden;position:relative;flex-shrink:0;border:2px solid rgba(99,102,241,0.4);'>
                <img src='$url' style='position:absolute;width:".($s*100)."%;height:".($s*100)."%;object-fit:cover;top:50%;left:50%;transform:translate(calc(-50% + {$ox}px),calc(-50% + {$oy}px));'>
            </div>";
        }
        $initials = strtoupper(substr($u['airline_name'], 0, 2));
        $fs = round($size / 2.8, 1);
        return "<div style='width:{$size}px;height:{$size}px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a855f7);display:flex;align-items:center;justify-content:center;font-size:{$fs}px;font-weight:900;color:white;flex-shrink:0;font-family:Outfit,sans-serif;'>{$initials}</div>";
    }
}

// Unread counts
$_navUnreadNotif = 0;
$_navUnreadDM    = 0;
if ($user) {
    $_navUnreadNotif = getUnreadCount($user['id']);
    try {
        $dmStmt = getDB()->prepare("SELECT COUNT(*) FROM direct_messages WHERE to_id=? AND is_read=0");
        $dmStmt->execute([$user['id']]);
        $_navUnreadDM = (int)$dmStmt->fetchColumn();
    } catch(Exception $e) {}
}
$_navIsAdmin = $user && in_array($user['role'], ['admin','owner']);
?>
<style>
.avc-nav{position:fixed;top:0;left:0;width:100%;z-index:9998;backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);background:rgba(8,8,14,0.88);border-bottom:1px solid rgba(255,255,255,0.07);}
.avc-nav-inner{max-width:1400px;margin:0 auto;padding:0 16px;display:flex;align-items:center;justify-content:space-between;height:60px;gap:8px;}
.avc-nav-logo{display:flex;align-items:center;gap:8px;text-decoration:none;flex-shrink:0;}
.avc-nav-logo-icon{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#6366f1,#a855f7);flex-shrink:0;box-shadow:0 0 14px rgba(99,102,241,0.3);}
.avc-nav-logo-text{color:#fff;font-family:'Outfit',sans-serif;font-weight:800;font-size:15px;white-space:nowrap;}
.avc-nav-actions{display:flex;align-items:center;gap:6px;}
.avc-btn{display:flex;align-items:center;gap:6px;padding:7px 12px;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);color:#9ca3af;font-family:'Outfit',sans-serif;font-size:13px;font-weight:500;text-decoration:none;cursor:pointer;transition:all 0.2s ease;white-space:nowrap;position:relative;}
.avc-btn:hover{background:rgba(255,255,255,0.09);color:#e5e7eb;transform:translateY(-1px);}
.avc-btn-back{background:rgba(99,102,241,0.08);border-color:rgba(99,102,241,0.2);color:#a78bfa;}
.avc-btn-back:hover{background:rgba(99,102,241,0.14);color:#c4b5fd;}
.avc-btn-admin{background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(168,85,247,0.15));border-color:rgba(139,92,246,0.25);color:#a78bfa;}
.avc-nav-badge{position:absolute;top:-5px;right:-5px;min-width:16px;height:16px;border-radius:8px;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 3px;border:2px solid #08080e;}
.avc-hamburger{display:none;padding:8px;border-radius:9px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);cursor:pointer;color:#9ca3af;}
.avc-mobile-menu{display:none;flex-direction:column;gap:4px;padding:12px 16px;background:#08080e;border-bottom:1px solid rgba(255,255,255,0.06);}
.avc-mobile-menu.open{display:flex;}
.avc-mobile-item{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:12px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);color:#d1d5db;font-family:'Outfit',sans-serif;font-size:14px;font-weight:500;text-decoration:none;cursor:pointer;transition:background 0.15s;}
.avc-mobile-item:hover{background:rgba(255,255,255,0.07);}
@media(max-width:700px){
  .avc-desktop-only{display:none!important;}
  .avc-hamburger{display:flex;align-items:center;justify-content:center;}
  .avc-nav-logo-text{font-size:11px;}
  .avc-nav-logo-icon{width:28px;height:28px;border-radius:8px;}
  .avc-nav-logo{gap:5px;}
}
@media(max-width:360px){
  .avc-nav-logo-text{font-size:10px;}
}
@media(min-width:701px){.avc-mobile-menu{display:none!important;}}
</style>

<nav class="avc-nav" id="avc-nav">
  <div class="avc-nav-inner">
    <!-- Logo -->
    <a href="<?= SITE_URL ?>/index.php" class="avc-nav-logo">
      <div class="avc-nav-logo-icon">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21 4 21 4s-2 0-3.5 1.5L14 9 5.8 7.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 3.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
      </div>
      <span class="avc-nav-logo-text">Aero Vibes Central</span>
    </a>

    <!-- Desktop actions -->
    <div class="avc-nav-actions avc-desktop-only">
      <!-- Back button -->
      <button onclick="history.back()" class="avc-btn avc-btn-back">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back
      </button>
      <a href="<?= SITE_URL ?>/index.php" class="avc-btn">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Home
      </a>

      <?php if ($user): ?>
        <!-- Notifications -->
        <button onclick="avcToggleNotif()" class="avc-btn" id="avc-notif-btn" style="overflow:visible;">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <?php if ($_navUnreadNotif > 0): ?>
            <span class="avc-nav-badge" style="background:#ef4444;"><?= $_navUnreadNotif ?></span>
          <?php endif; ?>
        </button>

        <!-- Mail -->
        <a href="<?= SITE_URL ?>/pages/mail.php" class="avc-btn" style="overflow:visible;">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <?php if ($_navUnreadDM > 0): ?>
            <span class="avc-nav-badge" style="background:#a855f7;"><?= $_navUnreadDM ?></span>
          <?php endif; ?>
        </a>

        <!-- Profile with avatar -->
        <a href="<?= SITE_URL ?>/pages/profile.php?id=<?= $user['id'] ?>" class="avc-btn" style="padding:5px 10px;">
          <?= navAvatar($user, 26) ?>
          <span class="hidden lg:inline" style="display:none;max-width:120px;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($user['airline_name']) ?></span>
        </a>

        <?php if ($_navIsAdmin): ?>
          <a href="<?= SITE_URL ?>/admin/index.php" class="avc-btn avc-btn-admin">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Admin
          </a>
        <?php endif; ?>

        <a href="<?= SITE_URL ?>/pages/logout.php" class="avc-btn">Logout</a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/pages/login.php" class="avc-btn">Sign In</a>
        <a href="<?= SITE_URL ?>/pages/login.php?tab=signup" class="avc-btn" style="background:linear-gradient(135deg,rgba(99,102,241,0.2),rgba(168,85,247,0.2));color:#a78bfa;border-color:rgba(139,92,246,0.25);">Join Free</a>
      <?php endif; ?>
    </div>

    <!-- Mobile right: avatar + hamburger -->
    <div class="flex items-center gap-2" style="display:flex;align-items:center;gap:8px;">
      <?php if ($user): ?>
        <a href="<?= SITE_URL ?>/pages/profile.php?id=<?= $user['id'] ?>" class="avc-hamburger" style="padding:4px;" id="avc-mob-avatar">
          <?= navAvatar($user, 32) ?>
        </a>
      <?php endif; ?>
      <button class="avc-hamburger" id="avc-ham-btn" onclick="avcToggleMobileMenu()">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div class="avc-mobile-menu" id="avc-mobile-menu">
    <?php if ($user): ?>
      <a href="<?= SITE_URL ?>/pages/profile.php?id=<?= $user['id'] ?>" class="avc-mobile-item">
        <?= navAvatar($user, 38) ?>
        <div>
          <div style="font-weight:700;color:#fff;"><?= htmlspecialchars($user['airline_name']) ?></div>
          <div style="font-size:12px;color:#6b7280;">@<?= htmlspecialchars($user['username']) ?><?= $user['alliance'] ? ' · '.htmlspecialchars($user['alliance']) : '' ?></div>
        </div>
      </a>
    <?php endif; ?>

    <button onclick="history.back()" class="avc-mobile-item" style="background:rgba(99,102,241,0.08);border-color:rgba(99,102,241,0.18);color:#a78bfa;">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      Go Back
    </button>
    <a href="<?= SITE_URL ?>/index.php" class="avc-mobile-item">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Home
    </a>

    <?php if ($user): ?>
      <a href="<?= SITE_URL ?>/pages/mail.php" class="avc-mobile-item">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        Messages
        <?php if ($_navUnreadDM > 0): ?><span style="margin-left:auto;background:#a855f7;color:#fff;border-radius:8px;padding:1px 7px;font-size:11px;font-weight:700;"><?= $_navUnreadDM ?></span><?php endif; ?>
      </a>
      <a href="<?= SITE_URL ?>/pages/messages.php?room=public" class="avc-mobile-item">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        Global Chat
      </a>
      <button onclick="avcToggleNotif()" class="avc-mobile-item">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        Notifications
        <?php if ($_navUnreadNotif > 0): ?><span style="margin-left:auto;background:#ef4444;color:#fff;border-radius:8px;padding:1px 7px;font-size:11px;font-weight:700;"><?= $_navUnreadNotif ?></span><?php endif; ?>
      </button>
      <?php if ($_navIsAdmin): ?>
        <a href="<?= SITE_URL ?>/admin/index.php" class="avc-mobile-item" style="background:rgba(99,102,241,0.1);border-color:rgba(139,92,246,0.2);color:#a78bfa;">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Admin Panel
        </a>
      <?php endif; ?>
      <a href="<?= SITE_URL ?>/pages/logout.php" class="avc-mobile-item" style="color:#6b7280;">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Logout
      </a>
    <?php else: ?>
      <a href="<?= SITE_URL ?>/pages/login.php" class="avc-mobile-item">Sign In</a>
      <a href="<?= SITE_URL ?>/pages/login.php?tab=signup" class="avc-mobile-item" style="color:#a78bfa;">Create Account</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Shared Notification Panel -->
<div id="avc-notif-panel" style="display:none;position:fixed;top:68px;right:16px;width:300px;z-index:9997;border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,0.6);background:rgba(10,10,18,0.98);border:1px solid rgba(255,255,255,0.1);max-height:70vh;overflow-y:auto;">
  <div style="padding:14px 16px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.06);">
    <span style="font-family:'Outfit',sans-serif;font-size:13px;font-weight:700;color:#fff;display:flex;align-items:center;gap:6px;">
      <svg width="13" height="13" fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      Notifications
    </span>
    <div style="display:flex;gap:8px;align-items:center;">
      <?php if ($user): ?><button onclick="avcMarkAllRead()" style="font-family:'Outfit',sans-serif;font-size:11px;color:#6b7280;background:none;border:none;cursor:pointer;">Mark all read</button><?php endif; ?>
      <button onclick="avcCloseNotif()" style="background:none;border:none;cursor:pointer;color:#6b7280;display:flex;">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
  </div>
  <div id="avc-notif-list" style="padding:10px;">
    <?php if (!$user): ?>
      <p style="text-align:center;font-size:12px;color:#6b7280;padding:16px;">Sign in to see notifications</p>
    <?php else: ?>
      <p style="text-align:center;font-size:12px;color:#6b7280;padding:16px;">Loading…</p>
    <?php endif; ?>
  </div>
</div>

<script>
function avcToggleMobileMenu(){document.getElementById('avc-mobile-menu').classList.toggle('open');}
let _avcNotifOpen=false;
function avcToggleNotif(){_avcNotifOpen=!_avcNotifOpen;document.getElementById('avc-notif-panel').style.display=_avcNotifOpen?'block':'none';if(_avcNotifOpen)avcLoadNotifs();}
function avcCloseNotif(){_avcNotifOpen=false;document.getElementById('avc-notif-panel').style.display='none';}
document.addEventListener('click',function(e){if(_avcNotifOpen&&!document.getElementById('avc-notif-panel').contains(e.target)&&!document.getElementById('avc-notif-btn')?.contains(e.target)){avcCloseNotif();}});
<?php if ($user): ?>
function avcLoadNotifs(){
  fetch('<?= SITE_URL ?>/api/notifications.php')
    .then(r=>r.json())
    .then(data=>{
      const list=document.getElementById('avc-notif-list');
      if(!data.notifications||!data.notifications.length){list.innerHTML='<p style="text-align:center;font-size:12px;color:#6b7280;padding:16px;">No notifications yet.</p>';return;}
      list.innerHTML=data.notifications.map(n=>`
        <div style="display:flex;align-items:flex-start;gap:10px;padding:10px;border-radius:12px;margin-bottom:6px;background:rgba(${n.is_read?'255,255,255,0.02':'99,102,241,0.07'});">
          <div style="width:28px;height:28px;border-radius:8px;background:rgba(99,102,241,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
            <svg width="12" height="12" fill="none" stroke="#818cf8" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          </div>
          <div>
            <p style="font-size:12px;color:#fff;font-family:'Outfit',sans-serif;font-weight:500;">${n.message}</p>
            <p style="font-size:11px;color:#4b5563;margin-top:2px;font-family:'Outfit',sans-serif;">${n.time}</p>
          </div>
        </div>`).join('');
    }).catch(()=>{});
}
function avcMarkAllRead(){
  fetch('<?= SITE_URL ?>/api/notifications.php',{method:'POST'})
    .then(r=>r.json())
    .then(()=>{
      // Clear the badge dot on the bell button
      const badge=document.querySelector('#avc-notif-btn .avc-nav-badge');
      if(badge) badge.remove();
      // Also clear mobile badge if present
      document.querySelectorAll('.avc-mobile-item .avc-nav-badge, .avc-mobile-item span[style*="background:#ef4444"]').forEach(b=>b.remove());
      avcLoadNotifs();
    }).catch(()=>{});
}
<?php endif; ?>
</script>