<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';
startSession();
$user = getCurrentUser();
if ($user && $user['is_banned']) { header('Location: '.SITE_URL.'/pages/banned.php'); exit; }
$db = getDB();
$isAdmin = $user && in_array($user['role'],['admin','owner']);
$unreadNotif = $user ? getUnreadCount($user['id']) : 0;

$search = trim($_GET['q'] ?? '');
$filterAlliance = trim($_GET['alliance'] ?? '');

if ($search || $filterAlliance) {
    $sql = "SELECT id, airline_name, username, country, alliance, profile_photo, logo_zoom, created_at FROM users WHERE is_banned=0";
    $params = [];
    if ($search) { $sql .= " AND (airline_name LIKE ? OR username LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    if ($filterAlliance === 'none') { $sql .= " AND (alliance IS NULL OR alliance = '')"; }
    elseif ($filterAlliance) { $sql .= " AND alliance = ?"; $params[] = $filterAlliance; }
    $sql .= " ORDER BY airline_name ASC";
    $stmt = $db->prepare($sql); $stmt->execute($params); $members = $stmt->fetchAll();
} else {
    $members = $db->query("SELECT id, airline_name, username, country, alliance, profile_photo, logo_zoom, created_at FROM users WHERE is_banned=0 ORDER BY airline_name ASC")->fetchAll();
}

$alliances = $db->query("SELECT id, name FROM alliances ORDER BY rank ASC")->fetchAll();
?><!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Members Hub</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <?php lightThemeCSS(); darkModeScript(); ?>
  <style>
    .member-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 14px 16px;
      display: flex;
      align-items: center;
      gap: 13px;
      cursor: pointer;
      transition: border-color 0.18s, box-shadow 0.18s, transform 0.15s, background-color 0.25s;
      position: relative;
    }
    .member-card:hover {
      border-color: var(--card-hover-border);
      box-shadow: var(--card-hover-shadow);
      transform: translateY(-1px);
    }
    .member-avatar {
      width: 44px; height: 44px; border-radius: 50%;
      overflow: hidden; position: relative; flex-shrink: 0;
      border: 2px solid var(--border);
      transition: border-color 0.25s;
    }
    .member-avatar img { position: absolute; top: 50%; left: 50%; object-fit: cover; }
    .member-avatar-initials {
      width: 44px; height: 44px; border-radius: 50%;
      background: linear-gradient(135deg, #3b82f6, #6366f1);
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; font-weight: 800; color: white; flex-shrink: 0;
      font-family: 'Outfit', sans-serif;
    }
    .alliance-badge {
      display: inline-block; padding: 2px 9px; border-radius: 99px;
      font-size: 11px; font-weight: 600;
    }
    .member-action-btn {
      display: flex; align-items: center; justify-content: center;
      width: 32px; height: 32px; border-radius: 8px;
      border: 1px solid var(--border); background: var(--bg-hover);
      color: var(--text-muted); text-decoration: none; flex-shrink: 0;
      transition: all 0.18s; cursor: pointer;
    }
    .member-action-btn:hover { border-color: #bfdbfe; background: #eff6ff; color: #2563eb; }
    .member-action-btn.msg:hover { border-color: #c7d2fe; background: #eef2ff; color: #4f46e5; }
    [data-theme="dark"] .member-action-btn:hover { background: rgba(37,99,235,0.15); border-color: rgba(37,99,235,0.4); }
    [data-theme="dark"] .member-action-btn.msg:hover { background: rgba(99,102,241,0.15); border-color: rgba(99,102,241,0.4); }
    .search-input {
      width: 100%; padding: 10px 14px 10px 40px; border-radius: 10px;
      background: var(--bg-input); border: 1px solid var(--border-input);
      font-size: 14px; color: var(--text); outline: none;
      font-family: 'Plus Jakarta Sans', sans-serif;
      transition: border-color 0.2s, box-shadow 0.2s, background-color 0.25s, color 0.25s;
    }
    .search-input:focus { border-color: var(--border-focus); box-shadow: 0 0 0 3px var(--input-focus-ring); }
    .search-input::placeholder { color: var(--text-subtle); }
    .filter-select {
      padding: 10px 14px; border-radius: 10px;
      background: var(--bg-input); border: 1px solid var(--border-input);
      font-size: 14px; color: var(--text); outline: none;
      font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer;
      transition: var(--t);
    }
    .filter-select:focus { border-color: var(--border-focus); }
    .page-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; transition: var(--t); }
  </style>
</head>
<body>
<div class="w-full min-h-screen">
<?= lightNavHTML($user, $isAdmin, 0, $unreadNotif) ?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-12">

  <!-- Header -->
  <div class="mb-8 text-center">
    <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-4" style="background:rgba(37,99,235,0.1);border:1px solid rgba(37,99,235,0.2);">
      <i data-lucide="users" style="width:22px;height:22px;color:#2563eb;"></i>
    </div>
    <h1 style="font-family:'Outfit',sans-serif;font-size:2rem;font-weight:800;color:var(--text-heading);margin-bottom:6px;">Members Hub</h1>
    <p style="color:var(--text-muted);font-size:14px;">Browse every pilot in the Aero Vibes Central network.</p>
    <div class="mt-3 inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold" style="background:var(--bg-hover);color:var(--text-muted);border:1px solid var(--border);">
      <i data-lucide="users" style="width:13px;height:13px;"></i>
      <?= count($members) ?> pilot<?= count($members)!==1?'s':'' ?><?php if ($search||$filterAlliance): ?> found<?php endif; ?>
    </div>
  </div>

  <!-- Search & Filter -->
  <div class="page-card p-4 mb-5">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1">
        <i data-lucide="search" style="width:15px;height:15px;color:var(--text-subtle);position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;"></i>
        <input type="text" name="q" class="search-input" placeholder="Search airline or username…" value="<?= htmlspecialchars($search) ?>">
      </div>
      <select name="alliance" onchange="this.form.submit()" class="filter-select">
        <option value="">All Alliances</option>
        <option value="none" <?= $filterAlliance==='none'?'selected':'' ?>>No Alliance</option>
        <?php foreach ($alliances as $al): ?>
        <option value="<?= htmlspecialchars($al['name']) ?>" <?= $filterAlliance===$al['name']?'selected':'' ?>><?= htmlspecialchars($al['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap">Search</button>
      <?php if ($search||$filterAlliance): ?>
      <a href="<?= SITE_URL ?>/pages/members.php" class="btn-ghost px-5 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap" style="text-decoration:none;text-align:center;">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Members Grid -->
  <?php if (!$members): ?>
  <div class="page-card p-12 text-center">
    <i data-lucide="users" style="width:36px;height:36px;color:var(--text-faint);margin:0 auto 12px;display:block;"></i>
    <p style="color:var(--text-muted);font-weight:500;font-size:14px;">No pilots found.</p>
    <?php if ($search||$filterAlliance): ?><a href="<?= SITE_URL ?>/pages/members.php" style="color:#2563eb;font-size:13px;margin-top:8px;display:inline-block;text-decoration:none;">Clear filters</a><?php endif; ?>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <?php foreach ($members as $m):
      $zd = $m['logo_zoom'] ? json_decode($m['logo_zoom'],true) : null;
      $s  = $zd['scale']   ?? 1;
      $ox = $zd['offsetX'] ?? 0;
      $oy = $zd['offsetY'] ?? 0;
      $initials = strtoupper(substr($m['airline_name']??'?',0,2));

      $allianceColors = [
        'SKY TEAM 2.0'  => ['rgba(37,99,235,0.1)','#2563eb'],
        'Aura Union'    => ['rgba(109,40,217,0.1)','#6d28d9'],
        'Prime United'  => ['rgba(217,119,6,0.1)','#d97706'],
      ];
      $alCol = isset($m['alliance']) && isset($allianceColors[$m['alliance']]) ? $allianceColors[$m['alliance']] : ['var(--bg-hover)','var(--text-muted)'];

      $alId = null;
      foreach ($alliances as $al) { if ($al['name']===$m['alliance']) { $alId=$al['id']; break; } }
    ?>
    <div class="member-card" onclick="window.location='<?= SITE_URL ?>/pages/profile.php?id=<?= $m['id'] ?>'">

      <!-- Avatar -->
      <?php if (!empty($m['profile_photo'])): ?>
      <div class="member-avatar">
        <img src="<?= SITE_URL ?>/<?= htmlspecialchars($m['profile_photo']) ?>"
             style="width:<?= $s*100 ?>%;height:<?= $s*100 ?>%;transform:translate(calc(-50% + <?= $ox ?>px),calc(-50% + <?= $oy ?>px));"
             loading="lazy">
      </div>
      <?php else: ?>
      <div class="member-avatar-initials"><?= htmlspecialchars($initials) ?></div>
      <?php endif; ?>

      <!-- Info -->
      <div style="flex:1;min-width:0;">
        <p style="font-family:'Outfit',sans-serif;font-weight:700;color:var(--text-heading);font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:2px;"><?= htmlspecialchars($m['airline_name']) ?></p>
        <p style="color:var(--text-subtle);font-size:12px;margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">@<?= htmlspecialchars($m['username']) ?><?= $m['country'] ? ' · '.htmlspecialchars($m['country']) : '' ?></p>
        <?php if ($m['alliance']): ?>
        <span class="alliance-badge" style="background:<?= $alCol[0] ?>;color:<?= $alCol[1] ?>;border:1px solid <?= $alCol[1] ?>44;"><?= htmlspecialchars($m['alliance']) ?></span>
        <?php else: ?>
        <span class="alliance-badge" style="background:var(--bg-hover);color:var(--text-subtle);border:1px solid var(--border);">No Alliance</span>
        <?php endif; ?>
      </div>

      <!-- Action buttons INSIDE the card -->
      <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;" onclick="event.stopPropagation()">
        <a href="<?= SITE_URL ?>/pages/profile.php?id=<?= $m['id'] ?>" class="member-action-btn" title="Visit Profile">
          <i data-lucide="user" style="width:14px;height:14px;"></i>
        </a>
        <?php if ($user && $user['id'] !== $m['id']): ?>
        <a href="<?= SITE_URL ?>/pages/dm.php?user=<?= $m['id'] ?>" class="member-action-btn msg" title="Send Message">
          <i data-lucide="send" style="width:14px;height:14px;"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>
</div>
<script>lucide.createIcons();</script>
</body>
</html>
