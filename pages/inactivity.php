<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';
startSession();
$user = requireLogin();
checkBanned($user);
$isAdmin = in_array($user['role'],['admin','owner']);
$unreadNotif = getUnreadCount($user['id']);
$db = getDB();

$allianceRooms=['SKY TEAM 2.0','Aura Union','Prime United'];
if(!$user['alliance']||!in_array($user['alliance'],$allianceRooms)){header('Location: '.SITE_URL.'/index.php?error=Alliance+members+only');exit;}

$error='';$success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $days=(int)($_POST['days']??0);$reason=sanitize($_POST['reason']??'');$msg=sanitize($_POST['message']??'');
    if($days<1||$days>365){$error='Enter a valid number of days (1-365).';}
    elseif(!$reason){$error='Please provide a reason.';}
    else{
        $db->prepare("INSERT INTO inactivity_notices(user_id,airline_name,alliance,days,reason,message) VALUES(?,?,?,?,?,?)")->execute([$user['id'],$user['airline_name'],$user['alliance'],$days,$reason,$msg?:null]);
        $admins=$db->query("SELECT id FROM users WHERE role IN('admin','owner')")->fetchAll();
        foreach($admins as $adm) addNotification($adm['id'],"{$user['airline_name']} will be inactive for {$days} day(s).");
        $success='Inactivity notice submitted!';
    }
}

$notices=$db->prepare("SELECT n.*,u.username FROM inactivity_notices n JOIN users u ON u.id=n.user_id WHERE n.alliance=? ORDER BY n.created_at DESC LIMIT 20");
$notices->execute([$user['alliance']]); $notices=$notices->fetchAll();
?><!DOCTYPE html>
<html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Inactivity Notice</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <?php lightThemeCSS(); darkModeScript(); ?>
  <style>
    .page-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; transition: var(--t); }
    .notice-row {
      display: flex; align-items: center; justify-content: space-between; gap: 12px;
      padding: 12px 14px; border-radius: 10px;
      border: 1px solid rgba(217,119,6,0.25);
      background: rgba(217,119,6,0.06);
      transition: var(--t);
    }
    [data-theme="dark"] .notice-row {
      border-color: rgba(217,119,6,0.2);
      background: rgba(217,119,6,0.08);
    }
  </style>
</head><body>
<div class="w-full min-h-screen">
<?= lightNavHTML($user,$isAdmin,0,$unreadNotif) ?>

<div class="max-w-2xl mx-auto px-4 py-10">
  <div class="mb-5">
    <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-muted);font-size:13px;text-decoration:none;font-weight:500;">
      <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back
    </a>
  </div>
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
    <div style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:rgba(217,119,6,0.1);border:1px solid rgba(217,119,6,0.25);">
      <i data-lucide="clock" style="width:20px;height:20px;color:#d97706;"></i>
    </div>
    <div>
      <h1 style="font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;color:var(--text-heading);">Inactivity Notice</h1>
      <p style="font-size:13px;color:var(--text-muted);"><?= htmlspecialchars($user['alliance']) ?></p>
    </div>
  </div>

  <?php if($error): ?>
  <div class="mb-4 px-4 py-3 rounded-xl text-sm font-medium" style="background:#fff1f2;border:1px solid #fecdd3;color:#e11d48;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if($success): ?>
  <div class="mb-4 px-4 py-3 rounded-xl text-sm font-medium" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="page-card p-6 mb-6">
    <h2 style="font-family:'Outfit',sans-serif;font-weight:700;font-size:15px;color:var(--text-heading);margin-bottom:16px;">Submit Notice</h2>
    <form method="POST" class="space-y-4">
      <div>
        <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:5px;">Days Away *</label>
        <input type="number" name="days" class="input-light" min="1" max="365" placeholder="e.g. 7" required>
      </div>
      <div>
        <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:5px;">Reason *</label>
        <select name="reason" class="input-light" required>
          <option value="">Select reason…</option>
          <option>Vacation</option>
          <option>Work / School</option>
          <option>Medical</option>
          <option>Personal</option>
          <option>Other</option>
        </select>
      </div>
      <div>
        <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:5px;">Message (optional)</label>
        <textarea name="message" class="input-light" rows="3" placeholder="Add any extra details…" style="resize:none;"></textarea>
      </div>
      <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-semibold">Submit Notice</button>
    </form>
  </div>

  <?php if($notices): ?>
  <div class="page-card p-6">
    <h2 style="font-family:'Outfit',sans-serif;font-weight:700;font-size:15px;color:var(--text-heading);margin-bottom:14px;">Alliance Notices</h2>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <?php foreach($notices as $n):
        $active = time() < strtotime($n['created_at']) + ($n['days']*86400);
      ?>
      <?php if($active): ?>
      <div class="notice-row">
        <div style="flex:1;min-width:0;">
          <p style="font-weight:600;font-size:14px;color:var(--text-heading);margin-bottom:1px;"><?= htmlspecialchars($n['airline_name']) ?></p>
          <p style="font-size:12px;color:var(--text-subtle);">@<?= htmlspecialchars($n['username']) ?> · <?= htmlspecialchars($n['reason']) ?></p>
          <?php if($n['message']): ?><p style="font-size:12px;color:var(--text-muted);margin-top:3px;"><?= htmlspecialchars($n['message']) ?></p><?php endif; ?>
        </div>
        <div style="flex-shrink:0;text-align:right;">
          <span style="display:inline-block;padding:2px 9px;border-radius:99px;font-size:11px;font-weight:700;background:rgba(217,119,6,0.1);color:#d97706;border:1px solid rgba(217,119,6,0.25);"><?= $n['days'] ?> day<?= $n['days']!=1?'s':'' ?></span>
          <p style="font-size:11px;color:var(--text-subtle);margin-top:3px;"><?= date('M j',strtotime($n['created_at'])) ?></p>
        </div>
      </div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
</div>
<script>lucide.createIcons();</script>
</body></html>
