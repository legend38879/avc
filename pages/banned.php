<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';
startSession();
$user = getCurrentUser();
?><!DOCTYPE html>
<html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Account Banned</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <?php lightThemeCSS(); darkModeScript(); ?>
</head><body>
<div class="w-full min-h-screen flex items-center justify-center p-4">
  <div class="glass rounded-3xl p-10 max-w-md w-full text-center shadow-xl">
    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5" style="background:#fff1f2;border:1px solid #fecdd3;"><i data-lucide="shield-x" style="width:32px;height:32px;color:#e11d48;"></i></div>
    <h1 class="font-heading text-2xl mb-3" style="font-weight:800;color:var(--text-heading);">Account Suspended</h1>
    <p class="text-sm mb-6 leading-relaxed" style="color:var(--text-muted);">Your account has been suspended for violating community guidelines. If you believe this is a mistake, you may submit an appeal below.</p>
    <?php
    $db=getDB();
    $ban=null;
    if($user){$bs=$db->prepare("SELECT * FROM bans WHERE user_id=? ORDER BY banned_at DESC LIMIT 1");$bs->execute([$user['id']]);$ban=$bs->fetch();}
    ?>
    <?php if($ban): ?>
    <div class="mb-6 p-4 rounded-xl text-left" style="background:#fff1f2;border:1px solid #fecdd3;">
      <p class="text-xs font-semibold mb-1" style="color:var(--text-muted);">Ban Reason</p>
      <p class="text-sm font-medium" style="color:#e11d48;"><?= htmlspecialchars($ban['reason']) ?></p>
    </div>
    <?php endif; ?>
    <?php
    $hasAppeal=false;
    if($user){$as=$db->prepare("SELECT id FROM appeals WHERE user_id=? AND status='pending'");$as->execute([$user['id']]);$hasAppeal=$as->fetch();}
    ?>
    <?php if(!$hasAppeal&&$user): ?>
    <form method="POST" action="<?= SITE_URL ?>/pages/banned.php" class="mb-4">
      <?php
      if($_SERVER['REQUEST_METHOD']==='POST'){
          $r=sanitize($_POST['reason']??'');
          if($r&&strlen($r)>=10){$db->prepare("INSERT INTO appeals(user_id,reason)VALUES(?,?) ON DUPLICATE KEY UPDATE reason=?,status='pending'")->execute([$user['id'],$r,$r]);echo '<div class="mb-4 px-4 py-3 rounded-xl text-sm font-medium" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;">Appeal submitted. We will review it shortly.</div>';}
      }
      ?>
      <textarea name="reason" class="input-light mb-3" rows="3" placeholder="Explain why your account should be unsuspended (min 10 chars)…" required style="resize:none;"></textarea>
      <button type="submit" class="btn-primary w-full py-2.5 rounded-xl text-sm font-semibold">Submit Appeal</button>
    </form>
    <?php elseif($hasAppeal): ?>
    <div class="mb-4 px-4 py-3 rounded-xl text-sm font-medium" style="background:#fffbeb;border:1px solid #fde68a;color:#d97706;">⏳ Your appeal is under review.</div>
    <?php endif; ?>
    <a href="<?= SITE_URL ?>/pages/logout.php" style="color:var(--text-subtle);font-size:13px;text-decoration:none;">Logout</a>
  </div>
</div>
<script>lucide.createIcons();</script>
</body></html>
