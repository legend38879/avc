<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ui.php';
startSession();
$user = getCurrentUser();
if ($user && $user['is_banned']) { header('Location: '.SITE_URL.'/pages/banned.php'); exit; }
$db = getDB();
$alliances   = $db->query("SELECT * FROM alliances ORDER BY rank ASC")->fetchAll();
$unreadNotif = $user ? getUnreadCount($user['id']) : 0;
$totalUsers  = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_banned=0")->fetchColumn();
function getSetting($db,$k,$d=''){try{$s=$db->prepare("SELECT value FROM site_settings WHERE key_name=?");$s->execute([$k]);$r=$s->fetchColumn();return $r!==false?$r:$d;}catch(Exception $e){return $d;}}
$discordUrl   = getSetting($db,'discord_url','#');
$instagramUrl = getSetting($db,'instagram_url','#');
$isAdmin = $user && in_array($user['role'],['admin','owner']);
$allianceRooms = ['SKY TEAM 2.0','Aura Union','Prime United'];
$userHasAllianceChat = $user && in_array($user['alliance']??'',$allianceRooms);
?><!doctype html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Aero Vibes Central</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <?php lightThemeCSS(); darkModeScript(); ?>
  <style>
    .hero-badge {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 6px 14px; border-radius: 99px;
      background: rgba(37,99,235,0.1); border: 1px solid rgba(37,99,235,0.25);
      font-size: 12px; font-weight: 700; color: #2563eb;
      margin-bottom: 20px; letter-spacing: 0.03em;
    }
    [data-theme="dark"] .hero-badge {
      background: rgba(56,139,253,0.1); border-color: rgba(56,139,253,0.25); color: #58a6ff;
    }
    .welcome-badge {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 6px 14px; border-radius: 99px; margin-bottom: 14px;
      background: rgba(5,150,105,0.08); border: 1px solid rgba(5,150,105,0.25);
      font-size: 13px; font-weight: 600; color: #059669;
    }
    [data-theme="dark"] .welcome-badge {
      background: rgba(5,150,105,0.1); border-color: rgba(5,150,105,0.2); color: #34d399;
    }
  </style>
</head>
<body class="h-full">
<div class="w-full min-h-full">

<?= lightNavHTML($user, $isAdmin, 0, $unreadNotif) ?>

<!-- Hero -->
<section style="background:var(--bg-alt);border-bottom:1px solid var(--section-border);padding:56px 24px;">
  <div style="max-width:700px;margin:0 auto;text-align:center;">
    <div class="hero-badge">
      <span class="live-dot"></span> AERO VIBES CENTRAL
    </div>
    <?php if ($user): ?>
    <div class="welcome-badge">
      ✈ Welcome back, <?= htmlspecialchars($user['airline_name']) ?>
    </div>
    <?php endif; ?>
    <h1 style="font-family:'Outfit',sans-serif;font-size:clamp(1.75rem,5vw,3.25rem);font-weight:800;color:var(--text-heading);line-height:1.15;margin-bottom:16px;">Unite, Fly, Dominate<br>the Skies Together.</h1>
    <p style="font-size:clamp(14px,2.5vw,17px);color:var(--text-muted);max-width:520px;margin:0 auto 28px;line-height:1.65;">The premier hub for airline simulation alliances. Join elite teams, track performance, and build your aviation empire.</p>
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:10px;">
      <a href="#alliances" class="btn-primary px-6 py-2.5 rounded-xl font-semibold text-sm flex items-center gap-2" style="text-decoration:none;font-family:'Outfit',sans-serif;">
        <i data-lucide="compass" style="width:15px;height:15px;"></i> Explore Alliances
      </a>
      <?php if (!$user): ?>
      <a href="<?= SITE_URL ?>/pages/login.php?tab=signup" class="btn-ghost px-6 py-2.5 rounded-xl font-semibold text-sm flex items-center gap-2" style="text-decoration:none;font-family:'Outfit',sans-serif;">
        <i data-lucide="user-plus" style="width:15px;height:15px;"></i> Get Started Free
      </a>
      <?php else: ?>
      <a href="#features" class="btn-ghost px-6 py-2.5 rounded-xl font-semibold text-sm flex items-center gap-2" style="text-decoration:none;font-family:'Outfit',sans-serif;">
        <i data-lucide="layout-grid" style="width:15px;height:15px;"></i> See Features
      </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Alliances -->
<section id="alliances" style="padding:56px 24px;max-width:1100px;margin:0 auto;">
  <div style="text-align:center;margin-bottom:40px;">
    <h2 style="font-family:'Outfit',sans-serif;font-size:1.75rem;font-weight:800;color:var(--text-heading);margin-bottom:8px;">Elite Alliances</h2>
    <p style="color:var(--text-muted);font-size:14px;">Choose your alliance and climb the global rankings together.</p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    <?php
    $alIcons = ['shield','globe','award'];
    foreach ($alliances as $i => $al):
      $pct = $al['max_members']>0 ? round($al['member_count']/$al['max_members']*100) : 0;
      $reqs = array_filter(explode('|',$al['requirements']??''));
    ?>
    <div class="alliance-card">
      <div style="position:absolute;top:0;right:0;width:120px;height:120px;border-radius:0 18px 0 100%;background:linear-gradient(to bottom left,<?= htmlspecialchars($al['gradient_from']) ?>18,transparent);pointer-events:none;"></div>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
        <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,<?= htmlspecialchars($al['gradient_from']) ?>,<?= htmlspecialchars($al['gradient_to']) ?>);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i data-lucide="<?= $alIcons[$i%3] ?>" style="width:20px;height:20px;color:white;"></i>
        </div>
        <div>
          <h3 style="font-family:'Outfit',sans-serif;font-size:15px;font-weight:700;color:#0f172a;margin-bottom:3px;"><?= htmlspecialchars($al['name']) ?></h3>
          <span style="padding:2px 9px;border-radius:99px;font-size:11px;font-weight:600;color:<?= htmlspecialchars($al['tag_color']) ?>;background:<?= htmlspecialchars($al['tag_color']) ?>15;border:1px solid <?= htmlspecialchars($al['tag_color']) ?>33;"><?= htmlspecialchars($al['tag']) ?></span>
        </div>
      </div>
      <div style="margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;font-size:12px;color:#94a3b8;margin-bottom:6px;">
          <span>Members</span><span style="font-weight:600;color:#475569;"><?= $al['member_count'] ?>/<?= $al['max_members'] ?></span>
        </div>
        <div class="stat-bar-light"><div class="stat-bar-fill-light" style="width:<?= $pct ?>%;background:linear-gradient(to right,<?= htmlspecialchars($al['gradient_from']) ?>,<?= htmlspecialchars($al['gradient_to']) ?>);"></div></div>
        <div style="display:flex;justify-content:space-between;margin-top:10px;">
          <div><p style="font-size:11px;color:var(--text-subtle);margin-bottom:2px;">Value</p><p style="font-size:13px;font-weight:700;color:var(--text-heading);"><?= htmlspecialchars($al['value']) ?></p></div>
          <div style="text-align:right;"><p style="font-size:11px;color:var(--text-subtle);margin-bottom:2px;">Global Rank</p><p style="font-size:13px;font-weight:700;color:#d97706;">#<?= $al['rank'] ?></p></div>
        </div>
      </div>
      <?php if ($reqs): ?>
      <div style="margin-bottom:16px;">
        <div style="display:flex;flex-wrap:wrap;gap:5px;">
          <?php foreach(array_slice($reqs,0,3) as $req): ?>
          <span style="padding:3px 9px;border-radius:99px;font-size:11px;font-weight:500;background:#f8fafc;color:#475569;border:1px solid #e2e8f0;"><?= htmlspecialchars(trim($req)) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($user): ?>
        <?php if (!$user['alliance']): ?>
        <a href="<?= SITE_URL ?>/pages/join.php" style="display:flex;width:100%;padding:9px;border-radius:10px;font-size:13px;font-weight:600;align-items:center;justify-content:center;gap:6px;color:white;background:linear-gradient(135deg,<?= htmlspecialchars($al['gradient_from']) ?>,<?= htmlspecialchars($al['gradient_to']) ?>);text-decoration:none;font-family:'Outfit',sans-serif;">
          <i data-lucide="send" style="width:13px;height:13px;"></i> Request to Join
        </a>
        <?php elseif ($user['alliance']===$al['name']): ?>
        <a href="<?= SITE_URL ?>/pages/alliance.php?id=<?= $al['id'] ?>" style="display:flex;width:100%;padding:9px;border-radius:10px;font-size:13px;font-weight:600;align-items:center;justify-content:center;gap:6px;color:#059669;background:#f0fdf4;border:1px solid #a7f3d0;text-decoration:none;font-family:'Outfit',sans-serif;">
          <i data-lucide="check-circle" style="width:13px;height:13px;"></i> Your Alliance — View
        </a>
        <?php else: ?>
        <a href="<?= SITE_URL ?>/pages/alliance.php?id=<?= $al['id'] ?>" style="display:flex;width:100%;padding:9px;border-radius:10px;font-size:13px;font-weight:600;align-items:center;justify-content:center;gap:6px;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;text-decoration:none;font-family:'Outfit',sans-serif;">View Alliance</a>
        <?php endif; ?>
      <?php else: ?>
      <a href="<?= SITE_URL ?>/pages/login.php" style="display:flex;width:100%;padding:9px;border-radius:10px;font-size:13px;font-weight:600;align-items:center;justify-content:center;gap:6px;color:white;background:linear-gradient(135deg,<?= htmlspecialchars($al['gradient_from']) ?>,<?= htmlspecialchars($al['gradient_to']) ?>);text-decoration:none;font-family:'Outfit',sans-serif;">
        <i data-lucide="send" style="width:13px;height:13px;"></i> Sign in to Join
      </a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Features -->
<section id="features" style="background:var(--bg-alt);border-top:1px solid var(--section-border);border-bottom:1px solid var(--section-border);padding:56px 24px;">
  <div style="max-width:900px;margin:0 auto;">
    <div style="text-align:center;margin-bottom:36px;">
      <h2 style="font-family:'Outfit',sans-serif;font-size:1.75rem;font-weight:800;color:var(--text-heading);margin-bottom:8px;">Powerful Features</h2>
      <p style="color:var(--text-muted);font-size:14px;">Everything you need to manage and grow your alliance.</p>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <?php if ($isAdmin): ?>
      <a href="<?= SITE_URL ?>/admin/index.php" class="feature-card">
        <div class="feat-icon-blue" style="width:44px;height:44px;border-radius:12px;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;"><i data-lucide="settings" style="width:20px;height:20px;color:#2563eb;"></i></div>
        <h4 style="font-family:'Outfit',sans-serif;font-size:13px;font-weight:700;color:var(--text-heading);margin-bottom:4px;">Admin Panel</h4>
        <p style="font-size:12px;color:#94a3b8;">Full management</p>
      </a>
      <?php endif; ?>
      <a href="<?= $user ? SITE_URL.'/pages/fuel.php' : SITE_URL.'/pages/login.php' ?>" class="feature-card">
        <div style="width:44px;height:44px;border-radius:12px;background:#fffbeb;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;"><i data-lucide="fuel" style="width:20px;height:20px;color:#d97706;"></i></div>
        <h4 style="font-family:'Outfit',sans-serif;font-size:13px;font-weight:700;color:var(--text-heading);margin-bottom:4px;">Fuel Forecast</h4>
        <p style="font-size:12px;color:#94a3b8;">Cost predictions</p>
      </a>
      <?php $allianceChatUrl = ($user && $user['alliance']) ? SITE_URL.'/pages/messages.php?room='.urlencode($user['alliance']) : SITE_URL.'/pages/login.php'; ?>
      <a href="<?= $allianceChatUrl ?>" class="feature-card">
        <div style="width:44px;height:44px;border-radius:12px;background:#eef2ff;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;"><i data-lucide="message-circle" style="width:20px;height:20px;color:#6366f1;"></i></div>
        <h4 style="font-family:'Outfit',sans-serif;font-size:13px;font-weight:700;color:var(--text-heading);margin-bottom:4px;">Alliance Chat</h4>
        <p style="font-size:12px;color:#94a3b8;">Private messaging</p>
      </a>
      <a href="<?= SITE_URL ?>/pages/messages.php?room=public" class="feature-card">
        <div style="width:44px;height:44px;border-radius:12px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;"><i data-lucide="messages-square" style="width:20px;height:20px;color:#10b981;"></i></div>
        <h4 style="font-family:'Outfit',sans-serif;font-size:13px;font-weight:700;color:var(--text-heading);margin-bottom:4px;">Public Chat</h4>
        <p style="font-size:12px;color:#94a3b8;">Cross-alliance</p>
      </a>
      <?php $inactivityUrl = ($user && $user['alliance']) ? SITE_URL.'/pages/inactivity.php' : SITE_URL.'/pages/login.php'; ?>
      <a href="<?= $inactivityUrl ?>" class="feature-card">
        <div style="width:44px;height:44px;border-radius:12px;background:#fff1f2;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;"><i data-lucide="clock" style="width:20px;height:20px;color:#e11d48;"></i></div>
        <h4 style="font-family:'Outfit',sans-serif;font-size:13px;font-weight:700;color:var(--text-heading);margin-bottom:4px;">Inactivity</h4>
        <p style="font-size:12px;color:#94a3b8;">Manage AFK status</p>
      </a>
      <a href="<?= SITE_URL ?>/pages/members.php" class="feature-card">
        <div style="width:44px;height:44px;border-radius:12px;background:#f5f3ff;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;"><i data-lucide="users" style="width:20px;height:20px;color:#8b5cf6;"></i></div>
        <h4 style="font-family:'Outfit',sans-serif;font-size:13px;font-weight:700;color:var(--text-heading);margin-bottom:4px;">Members Hub</h4>
        <p style="font-size:12px;color:#94a3b8;">Browse all pilots</p>
      </a>
      <?php if ($user): ?>
      <a href="<?= SITE_URL ?>/pages/dm.php" class="feature-card">
        <div style="width:44px;height:44px;border-radius:12px;background:#f0f9ff;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;"><i data-lucide="send" style="width:20px;height:20px;color:#0ea5e9;"></i></div>
        <h4 style="font-family:'Outfit',sans-serif;font-size:13px;font-weight:700;color:var(--text-heading);margin-bottom:4px;">Direct Messages</h4>
        <p style="font-size:12px;color:#94a3b8;">Private pilot DMs</p>
      </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Community + Footer merged -->
<footer style="background:var(--bg-alt);border-top:1px solid var(--section-border);padding:56px 24px 32px;">
  <!-- Community -->
  <div style="max-width:600px;margin:0 auto 40px;text-align:center;">
    <h2 style="font-family:'Outfit',sans-serif;font-size:1.75rem;font-weight:800;color:var(--text-heading);margin-bottom:8px;">Join the Community</h2>
    <p style="color:var(--text-muted);font-size:14px;margin-bottom:28px;">Connect with pilots worldwide on your favorite platforms.</p>
    <div style="display:flex;align-items:center;justify-content:center;gap:12px;">
      <a href="<?= htmlspecialchars($discordUrl) ?>" target="_blank" rel="noopener" style="width:56px;height:56px;border-radius:14px;background:var(--bg-card);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;transition:border-color 0.18s,box-shadow 0.18s,background-color 0.25s;text-decoration:none;" onmouseover="this.style.borderColor='#c7d2fe';this.style.boxShadow='0 4px 14px rgba(99,102,241,0.1)';" onmouseout="this.style.borderColor='var(--border)';this.style.boxShadow='none';">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="#6366f1"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.947 2.418-2.157 2.418z"/></svg>
      </a>
      <a href="<?= htmlspecialchars($instagramUrl) ?>" target="_blank" rel="noopener" style="width:56px;height:56px;border-radius:14px;background:var(--bg-card);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;transition:border-color 0.18s,box-shadow 0.18s,background-color 0.25s;text-decoration:none;" onmouseover="this.style.borderColor='#fbcfe8';this.style.boxShadow='0 4px 14px rgba(236,72,153,0.1)';" onmouseout="this.style.borderColor='var(--border)';this.style.boxShadow='none';">
        <i data-lucide="instagram" style="width:24px;height:24px;color:#ec4899;"></i>
      </a>
    </div>
  </div>

  <!-- Divider -->
  <div style="max-width:1100px;margin:0 auto;border-top:1px solid var(--section-border);padding-top:28px;display:flex;flex-direction:column;gap:16px;align-items:center;text-align:center;">
    <div style="display:flex;align-items:center;gap:8px;">
      <div style="width:24px;height:24px;border-radius:7px;background:#2563eb;display:flex;align-items:center;justify-content:center;"><i data-lucide="plane" style="width:12px;height:12px;color:white;"></i></div>
      <span style="font-family:'Outfit',sans-serif;font-weight:700;font-size:14px;color:var(--text-heading);">Aero Vibes Central</span>
    </div>
    <p style="font-size:13px;color:var(--text-subtle);">© 2026 Aero Vibes Central · Falcon Dev. Team</p>
    <div style="display:flex;align-items:center;gap:20px;font-size:12px;">
      <?php if ($user): ?>
        <a href="<?= SITE_URL ?>/pages/profile.php?id=<?= $user['id'] ?>" style="color:var(--text-subtle);text-decoration:none;">Profile</a>
        <a href="<?= SITE_URL ?>/pages/members.php" style="color:var(--text-subtle);text-decoration:none;">Members</a>
        <a href="<?= SITE_URL ?>/pages/logout.php" style="color:var(--text-subtle);text-decoration:none;">Logout</a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/pages/login.php" style="color:var(--text-subtle);text-decoration:none;">Sign In</a>
        <a href="<?= SITE_URL ?>/pages/login.php?tab=signup" style="color:var(--text-subtle);text-decoration:none;">Join Free</a>
      <?php endif; ?>
    </div>
  </div>
</footer>

</div>
<script>
<?= showToastJS() ?>
lucide.createIcons();
<?php if (isset($_GET['error'])): ?>showToast('<?= htmlspecialchars(addslashes($_GET['error'])) ?>');<?php endif; ?>
</script>
</body>
</html>