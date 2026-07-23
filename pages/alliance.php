<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';
startSession();
$user = getCurrentUser();
if($user&&$user['is_banned']){header('Location: '.SITE_URL.'/pages/banned.php');exit;}
$isAdmin = $user && in_array($user['role'],['admin','owner']);
$unreadNotif = $user ? getUnreadCount($user['id']) : 0;
$db = getDB();

$id=(int)($_GET['id']??0);
if(!$id){header('Location: '.SITE_URL.'/index.php');exit;}
$al=$db->prepare("SELECT * FROM alliances WHERE id=?");$al->execute([$id]);$al=$al->fetch();
if(!$al){header('Location: '.SITE_URL.'/index.php?error=Alliance+not+found');exit;}

$ALLIANCE_MAX = 60;

// Handle Leave Alliance
$leaveError = '';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='leave_alliance' && $user) {
    if($user['alliance']===$al['name']) {
        try {
            $db->prepare("UPDATE users SET alliance=NULL WHERE id=?")->execute([$user['id']]);
            $db->prepare("UPDATE alliances SET member_count=GREATEST(0,member_count-1) WHERE id=?")->execute([$al['id']]);
            $_SESSION['alliance'] = null;
            header('Location: '.SITE_URL.'/pages/alliance.php?id='.$id.'&left=1');
            exit;
        } catch(Exception $e) {
            $leaveError = 'Could not leave alliance. Please try again.';
        }
    }
}
$justLeft = isset($_GET['left']);

// Re-fetch alliance and user after potential changes
$al=$db->prepare("SELECT * FROM alliances WHERE id=?");$al->execute([$id]);$al=$al->fetch();
if($user) { $stmt=$db->prepare("SELECT * FROM users WHERE id=?");$stmt->execute([$user['id']]);$user=$stmt->fetch()?:$user; }

// Define these BEFORE using them
$isFull = $al['member_count'] >= $ALLIANCE_MAX;
$isMyAlliance = $user && $user['alliance']===$al['name'];

$pct = $al['max_members'] > 0 ? round($al['member_count'] / $al['max_members'] * 100) : 0;

$members=$db->prepare("SELECT id,airline_name,username,profile_photo,logo_zoom,country,created_at FROM users WHERE alliance=? AND is_banned=0 ORDER BY airline_name ASC");
$members->execute([$al['name']]);$members=$members->fetchAll();
$customMembers=$db->prepare("SELECT * FROM alliance_custom_members WHERE alliance_id=? ORDER BY airline_name ASC");
$customMembers->execute([$al['id']]);$customMembers=$customMembers->fetchAll();
$notices=$db->prepare("SELECT n.*,u.username FROM inactivity_notices n JOIN users u ON u.id=n.user_id WHERE n.alliance=? ORDER BY n.created_at DESC LIMIT 10");
$notices->execute([$al['name']]);$notices=$notices->fetchAll();

$alIcons=['shield','globe','award'];
$idx=0;
$allList=$db->query("SELECT id,name FROM alliances ORDER BY rank ASC")->fetchAll();
foreach($allList as $i=>$a){if($a['id']==$id){$idx=$i%3;break;}}
?><!DOCTYPE html>
<html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= htmlspecialchars($al['name']) ?></title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <?php lightThemeCSS(); darkModeScript(); ?>
  <style>
    .page-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; transition: var(--t); }
    .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text-subtle); margin-bottom: 10px; }
    .member-row {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px; border-radius: 10px;
      border: 1px solid var(--border); background: var(--bg-hover);
      text-decoration: none; transition: border-color 0.15s, background 0.15s;
    }
    .member-row:hover { border-color: rgba(37,99,235,0.3); background: var(--bg-hover); }
    .progress-bar { height: 4px; background: var(--border); border-radius: 2px; overflow: hidden; margin-bottom: 5px; }
    .progress-fill { height: 100%; border-radius: 2px; }
    .leave-btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: 10px; font-size: 12px;
      font-weight: 700; background: #fff1f2; color: #e11d48;
      border: 1px solid #fecdd3; cursor: pointer;
      font-family: 'Outfit', sans-serif; transition: all 0.18s;
    }
    .leave-btn:hover { background: #ffe4e6; border-color: #fda4af; }
    .notice-row {
      display: flex; align-items: center; justify-content: space-between; gap: 8px;
      padding: 10px 12px; border-radius: 10px;
      border: 1px solid rgba(217,119,6,0.25); background: rgba(217,119,6,0.06);
    }
  </style>
</head>
<body>
<div class="w-full min-h-screen">
<?= lightNavHTML($user,$isAdmin,0,$unreadNotif) ?>

<div class="max-w-4xl mx-auto px-4 py-10">

  <!-- Back -->
  <div class="mb-5">
    <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-muted);font-size:13px;text-decoration:none;font-weight:500;">
      <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back
    </a>
  </div>

  <!-- Alliance Header -->
  <div class="page-card p-7 mb-5">
    <div style="display:flex;align-items:flex-start;gap:18px;flex-wrap:wrap;">
      <!-- Icon -->
      <div style="width:60px;height:60px;border-radius:16px;background:linear-gradient(135deg,<?= htmlspecialchars($al['gradient_from']) ?>,<?= htmlspecialchars($al['gradient_to']) ?>);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i data-lucide="<?= $alIcons[$idx] ?>" style="width:26px;height:26px;color:white;"></i>
      </div>

      <!-- Info -->
      <div style="flex:1;min-width:0;">
        <h1 style="font-family:'Outfit',sans-serif;font-size:1.75rem;font-weight:800;color:var(--text-heading);margin-bottom:8px;"><?= htmlspecialchars($al['name']) ?></h1>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
          <span style="padding:3px 12px;border-radius:99px;font-size:12px;font-weight:700;color:<?= htmlspecialchars($al['tag_color']) ?>;background:<?= htmlspecialchars($al['tag_color']) ?>18;border:1px solid <?= htmlspecialchars($al['tag_color']) ?>44;"><?= htmlspecialchars($al['tag']) ?></span>
          <span style="font-size:13px;color:var(--text-muted);">Rank <strong style="color:#d97706;">#<?= $al['rank'] ?></strong></span>
          <span style="font-size:13px;color:var(--text-muted);">Value <strong style="color:#059669;"><?= htmlspecialchars($al['value']) ?></strong></span>
        </div>
        <div class="progress-bar" style="max-width:280px;">
          <div class="progress-fill" style="width:<?= $pct ?>%;background:linear-gradient(to right,<?= htmlspecialchars($al['gradient_from']) ?>,<?= htmlspecialchars($al['gradient_to']) ?>);"></div>
        </div>
        <p style="font-size:12px;color:var(--text-subtle);"><?= $al['member_count'] ?> / <?= $ALLIANCE_MAX ?> members<?= $isFull ? ' · <span style="color:#d97706;font-weight:600;">Full</span>' : '' ?></p>
      </div>

      <!-- Action area -->
      <div style="flex-shrink:0;display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
        <?php if($justLeft): ?>
          <span style="padding:8px 16px;border-radius:10px;font-size:13px;font-weight:700;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;">✓ Left Alliance</span>
        <?php elseif($isMyAlliance): ?>
          <span style="padding:8px 16px;border-radius:10px;font-size:13px;font-weight:700;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;">✓ Your Alliance</span>
          <form method="POST" action="" onsubmit="return confirm('Are you sure you want to leave <?= htmlspecialchars(addslashes($al['name'])) ?>?');" style="margin:0;">
            <input type="hidden" name="action" value="leave_alliance">
            <button type="submit" class="leave-btn">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              Leave Alliance
            </button>
          </form>
          <?php if($leaveError): ?>
          <p style="font-size:12px;color:#e11d48;"><?= htmlspecialchars($leaveError) ?></p>
          <?php endif; ?>
        <?php elseif($user && !$user['alliance']): ?>
          <?php if($isFull): ?>
            <span style="padding:8px 16px;border-radius:10px;font-size:13px;font-weight:600;background:#fff7ed;color:#d97706;border:1px solid #fed7aa;">Full (<?= $ALLIANCE_MAX ?>/<?= $ALLIANCE_MAX ?>)</span>
          <?php else: ?>
            <a href="<?= SITE_URL ?>/pages/join.php" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2" style="text-decoration:none;">
              <i data-lucide="send" style="width:14px;height:14px;"></i> Apply to Join
            </a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Requirements -->
    <?php if($al['requirements']): ?>
    <div style="border-top:1px solid var(--border);padding-top:16px;margin-top:16px;">
      
      <div style="display:flex;flex-wrap:wrap;gap:6px;">
        <?php foreach(array_filter(explode('|',$al['requirements'])) as $req): ?>
         </span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Bottom grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    <!-- Website Members -->
    <div class="page-card p-5">
      <p class="section-label" style="display:flex;align-items:center;gap:6px;">
        <i data-lucide="users" style="width:13px;height:13px;color:#2563eb;"></i>
        Website Members (<?= count($members) ?>)
      </p>
      <?php if($members): ?>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <?php foreach($members as $m):
          $zd=$m['logo_zoom']?json_decode($m['logo_zoom'],true):null;
          $ms=$zd['scale']??1;$mox=$zd['offsetX']??0;$moy=$zd['offsetY']??0;
        ?>
        <a href="<?= SITE_URL ?>/pages/profile.php?id=<?= $m['id'] ?>" class="member-row">
          <div style="width:34px;height:34px;border-radius:50%;overflow:hidden;position:relative;flex-shrink:0;border:2px solid var(--border);">
            <?php if($m['profile_photo']): ?>
              <img src="<?= SITE_URL.'/'.htmlspecialchars($m['profile_photo']) ?>" style="position:absolute;width:<?= $ms*100 ?>%;height:<?= $ms*100 ?>%;object-fit:cover;top:50%;left:50%;transform:translate(calc(-50% + <?= $mox ?>px),calc(-50% + <?= $moy ?>px));" loading="lazy">
            <?php else: ?>
              <div style="width:100%;height:100%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:white;"><?= strtoupper(substr($m['airline_name'],0,2)) ?></div>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0;">
            <p style="font-size:13px;font-weight:600;color:var(--text-heading);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($m['airline_name']) ?></p>
            <p style="font-size:11px;color:var(--text-subtle);">@<?= htmlspecialchars($m['username']) ?> · <?= htmlspecialchars($m['country']) ?></p>
          </div>
          <i data-lucide="chevron-right" style="width:14px;height:14px;color:var(--text-faint);flex-shrink:0;"></i>
        </a>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p style="text-align:center;color:var(--text-subtle);font-size:13px;padding:20px 0;">No website members yet.</p>
      <?php endif; ?>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Custom/External members -->
      <?php if($customMembers): ?>
      <div class="page-card p-5">
        <p class="section-label" style="display:flex;align-items:center;gap:6px;">
          <i data-lucide="user-check" style="width:13px;height:13px;color:#6366f1;"></i>
          External Members (<?= count($customMembers) ?>)
        </p>
        <div style="display:flex;flex-direction:column;gap:6px;">
          <?php foreach($customMembers as $cm): ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-radius:10px;border:1px solid var(--border);background:var(--bg-hover);">
            <span style=" style="font-size:13px;font-weight:600;color:var(--text-heading);"><?= htmlspecialchars($cm['airline_name']) ?></span>
            <span style="font-size:12px;color:#059669;font-weight:600;"><?= htmlspecialchars($cm['share_value']?:'') ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Inactivity Notices (no reason shown) -->
      <?php
      $activeNotices = array_filter($notices, function($n) {
        return time() < strtotime($n['created_at']) + ($n['days'] * 86400);
      });
      if($activeNotices): ?>
      <div class="page-card p-5">
        <p class="section-label" style="display:flex;align-items:center;gap:6px;">
          <i data-lucide="clock" style="width:13px;height:13px;color:#d97706;"></i>
          Inactivity Notices
        </p>
        <div style="display:flex;flex-direction:column;gap:6px;">
          <?php foreach($activeNotices as $n): ?>
          <div class="notice-row">
            <p style=" style="font-size:13px;font-weight:600;color:var(--text-heading);"><?= htmlspecialchars($n['airline_name']) ?></p>
            <span style="padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;background:#fffbeb;color:#d97706;border:1px solid #fde68a;flex-shrink:0;"><?= (int)$n['days'] ?>d</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>
<script>lucide.createIcons();</script>
</body></html>
