<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';
startSession();
$user = requireLogin();
checkBanned($user);
$db = getDB();
$alliances = $db->query("SELECT * FROM alliances ORDER BY rank ASC")->fetchAll();
$isAdmin = in_array($user['role'],['admin','owner']);
$unreadNotif = getUnreadCount($user['id']);
$alreadyIn = !empty($user['alliance']);
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && !$alreadyIn) {
    $aid=(int)($_POST['alliance_id']??0);$pv=sanitize($_POST['previous_alliances']??'');$sv=sanitize($_POST['share_value']??'');$ac=(int)($_POST['aircraft_count']??0);
    if(!$aid||!$sv||!$ac){$error='Please fill in all required fields.';}
    elseif($ac<1){$error='Aircraft count must be at least 1.';}
    else {
        $ck=$db->prepare("SELECT id FROM applications WHERE user_id=? AND status='pending'");$ck->execute([$user['id']]);
        if($ck->fetch()){$error='You already have a pending application.';}
        else {
            $alS=$db->prepare("SELECT * FROM alliances WHERE id=?");$alS->execute([$aid]);$al=$alS->fetch();
            if(!$al){$error='Invalid alliance.';}
            else {
                $db->prepare("INSERT INTO applications(user_id,alliance_id,airline_name,previous_alliances,share_value,aircraft_count) VALUES(?,?,?,?,?,?)")->execute([$user['id'],$aid,$user['airline_name'],$pv,$sv,$ac]);
                // Notify admins
                $admins=$db->query("SELECT id FROM users WHERE role IN('admin','owner')")->fetchAll();
                foreach($admins as $adm) addNotification($adm['id'],"New application from {$user['airline_name']} to {$al['name']}.");
                $success='Application submitted! We\'ll notify you when a decision is made.';
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Join Alliance</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <?php lightThemeCSS(); darkModeScript(); ?>
</head><body>
<div>
<?= lightNavHTML($user,$isAdmin,0,$unreadNotif) ?>

<div class="max-w-3xl mx-auto px-4 py-10">
  <div class="mb-6"><a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:6px;color:var(--text-muted);font-size:13px;text-decoration:none;"><i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back</a></div>
  <h1 class="font-heading text-2xl mb-6" style="font-weight:800;color:var(--text-heading);">Apply to Join an Alliance</h1>

  <?php if($alreadyIn): ?>
  <div class="glass rounded-2xl p-8 text-center">
    <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#dcfce7;"><i data-lucide="check-circle" style="width:28px;height:28px;color:#16a34a;"></i></div>
    <h2 class="font-heading text-xl mb-2" style="font-weight:700;color:var(--text-heading);">You're Already in an Alliance</h2>
    <p class="text-sm mb-4" style="color:var(--text-muted);">You are a member of <strong><?= htmlspecialchars($user['alliance']) ?></strong>.</p>
    <a href="<?= SITE_URL ?>/index.php" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-semibold" style="text-decoration:none;">Go Home</a>
  </div>
  <?php else: ?>

  <?php if($error): ?><div class="mb-5 px-4 py-3 rounded-xl text-sm font-medium" style="background:#fff1f2;border:1px solid #fecdd3;color:#e11d48;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if($success): ?><div class="mb-5 px-4 py-3 rounded-xl text-sm font-medium" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <?php
    $alIcons=['shield','globe','award'];
    $alAccents=[['#3b82f6','#1d4ed8','#eff6ff'],['#6366f1','#4338ca','#eef2ff'],['#10b981','#059669','#ecfdf5']];
    foreach($alliances as $i=>$al):
      $acc=$alAccents[$i%3];$pct=$al['max_members']>0?round($al['member_count']/$al['max_members']*100):0;
    ?>
    <div class="glass card-glow rounded-2xl p-5 relative overflow-hidden">
      <div class="absolute top-0 right-0 w-20 h-20 rounded-bl-full" style="background:linear-gradient(to bottom left,<?= htmlspecialchars($al['gradient_from']) ?>18,transparent);"></div>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
        <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,<?= htmlspecialchars($al['gradient_from']) ?>,<?= htmlspecialchars($al['gradient_to']) ?>);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i data-lucide="<?= $alIcons[$i%3] ?>" style="width:18px;height:18px;color:white;"></i></div>
        <div><h3 style="font-family:'Outfit',sans-serif;font-weight:700;color:var(--text-heading);font-size:14px;"><?= htmlspecialchars($al['name']) ?></h3><span style="font-size:11px;font-weight:600;color:<?= $acc[0] ?>;"><?= htmlspecialchars($al['tag']) ?></span></div>
      </div>
      <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;">Members: <strong style="color:var(--text-heading);"><?= $al['member_count'] ?>/<?= $al['max_members'] ?></strong></div>
      <div class="stat-bar-light" style="margin-bottom:8px;"><div class="stat-bar-fill-light" style="width:<?= $pct ?>%;background:linear-gradient(to right,<?= htmlspecialchars($al['gradient_from']) ?>,<?= htmlspecialchars($al['gradient_to']) ?>);"></div></div>
      <div style="font-size:12px;color:var(--text-muted);">Value: <strong style="color:#059669;"><?= htmlspecialchars($al['value']) ?></strong></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="glass rounded-2xl p-6">
    <h2 class="font-heading text-lg mb-5" style="font-weight:700;color:var(--text-heading);">Submit Application</h2>
    <form method="POST" class="space-y-4">
      <div>
        <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Alliance *</label>
        <select name="alliance_id" class="input-light" required>
          <option value="">Select an alliance…</option>
          <?php foreach($alliances as $al): ?><option value="<?= $al['id'] ?>"><?= htmlspecialchars($al['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Share Value *</label><input type="text" name="share_value" class="input-light" placeholder="e.g. $500M" required></div>
      <div><label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Aircraft Count *</label><input type="number" name="aircraft_count" class="input-light" placeholder="Number of aircraft" min="1" required></div>
      <div><label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Previous Alliances</label><input type="text" name="previous_alliances" class="input-light" placeholder="Leave blank if none"></div>
      <button type="submit" class="btn-primary w-full py-3 rounded-xl font-semibold text-sm flex items-center justify-center gap-2"><i data-lucide="send" style="width:15px;height:15px;"></i> Submit Application</button>
    </form>
  </div>
  <?php endif; ?>
</div>
</div>
<script>lucide.createIcons();</script>
</body></html>
