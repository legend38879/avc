<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';
startSession();
$user = getCurrentUser();
if ($user) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}
$error = ''; $success = ''; $tab = $_GET['tab'] ?? 'login';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action==='login') {
        $username = sanitize($_POST['username']??'');
        $password = $_POST['password']??'';
        if (!$username||!$password) { $error='Please fill in all fields.'; }
        else {
            $db=getDB();
            $stmt=$db->prepare("SELECT * FROM users WHERE username=?"); $stmt->execute([$username]); $u=$stmt->fetch();
            if ($u && password_verify($password,$u['password_hash'])) {
                if ($u['is_banned']) { $error='Your account is banned.'; }
                else {
                    $_SESSION['user_id']=$u['id']; $_SESSION['username']=$u['username']; $_SESSION['role']=$u['role'];
                    header('Location: '.SITE_URL.'/index.php'); exit;
                }
            } else { $error='Invalid username or password.'; }
        }
    }
    if ($action==='signup') {
        $airline=sanitize($_POST['airline_name']??''); $username=sanitize($_POST['username']??''); $password=$_POST['password']??''; $confirm=$_POST['confirm_password']??''; $country=sanitize($_POST['country']??'');
        if (!$airline||!$username||!$password||!$country) { $error='Please fill in all required fields.'; }
        elseif (strlen($username)<3||strlen($username)>30) { $error='Username must be 3–30 characters.'; }
        elseif (!preg_match('/^[a-zA-Z0-9_]+$/',$username)) { $error='Username: letters, numbers, underscores only.'; }
        elseif (strlen($password)<8) { $error='Password must be at least 8 characters.'; }
        elseif ($password!==$confirm) { $error='Passwords do not match.'; }
        else {
            $db=getDB(); $check=$db->prepare("SELECT id FROM users WHERE username=?"); $check->execute([$username]);
            if ($check->fetch()) { $error='Username already taken.'; }
            else {
                $hash=password_hash($password,PASSWORD_BCRYPT);
                $db->prepare("INSERT INTO users (airline_name,username,password_hash,country) VALUES (?,?,?,?)")->execute([$airline,$username,$hash,$country]);
                $success='Account created! You can now log in.'; $tab='login';
            }
        }
    }
}
?><!doctype html>
<html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Sign In / Create Account</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <?php lightThemeCSS(); darkModeScript(); ?>
</head><body class="h-full">
<div class="w-full min-h-screen flex flex-col">
<?= lightNavHTML(null,false) ?>

<div class="flex-1 flex items-center justify-center p-4 py-12">
  <div class="w-full max-w-md">
    <!-- Tabs -->
    <div class="glass rounded-2xl p-1.5 flex gap-1 mb-6">
      <a href="?tab=login" class="flex-1 py-2.5 rounded-xl text-center text-sm font-semibold transition-all <?= $tab==='login' ? 'btn-primary' : '' ?>" style="text-decoration:none;<?= $tab!=='login' ? 'color:var(--text-muted);' : '' ?>">Sign In</a>
      <a href="?tab=signup" class="flex-1 py-2.5 rounded-xl text-center text-sm font-semibold transition-all <?= $tab!=='login' ? 'btn-primary' : '' ?>" style="text-decoration:none;<?= $tab==='login' ? 'color:var(--text-muted);' : '' ?>">Create Account</a>
    </div>

    <?php if ($error): ?>
    <div class="mb-4 px-4 py-3 rounded-xl text-sm font-medium" style="background:#fff1f2;border:1px solid #fecdd3;color:#e11d48;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="mb-4 px-4 py-3 rounded-xl text-sm font-medium" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($tab==='login'): ?>
    <div class="glass rounded-3xl p-8 shadow-xl">
      <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-2xl btn-primary flex items-center justify-center mx-auto mb-3"><i data-lucide="log-in" style="width:22px;height:22px;color:white;"></i></div>
        <h3 class="font-heading text-xl" style="font-weight:700;color:var(--text-heading);">Welcome Back</h3>
        <p class="text-xs mt-1" style="color:var(--text-subtle);">Sign in to your account</p>
      </div>
      <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="login">
        <div><label class="text-xs font-semibold block mb-1" style="color:var(--text-muted);">Username</label><input type="text" name="username" class="input-light" placeholder="your_username" required></div>
        <div><label class="text-xs font-semibold block mb-1" style="color:var(--text-muted);">Password</label><input type="password" name="password" class="input-light" placeholder="••••••••" required></div>
        <button type="submit" class="btn-primary w-full py-2.5 rounded-xl text-sm font-semibold">Sign In</button>
      </form>
      <p class="text-center text-xs mt-4" style="color:var(--text-subtle);">No account? <a href="?tab=signup" style="color:#2563eb;text-decoration:none;font-weight:600;">Create one free</a></p>
    </div>

    <?php else: ?>
    <div class="glass rounded-3xl p-8 shadow-xl">
      <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-3" style="background:linear-gradient(135deg,#6366f1,#3b82f6);"><i data-lucide="user-plus" style="width:22px;height:22px;color:white;"></i></div>
        <h3 class="font-heading text-xl" style="font-weight:700;color:var(--text-heading);">Create Account</h3>
        <p class="text-xs mt-1" style="color:var(--text-subtle);">Join the aviation community</p>
      </div>
      <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="signup">
        <div><label class="text-xs font-semibold block mb-1" style="color:var(--text-muted);">Airline Name</label><input type="text" name="airline_name" class="input-light" placeholder="Your Airline Inc." required autocomplete="off"></div>
        <div><label class="text-xs font-semibold block mb-1" style="color:var(--text-muted);">Username</label><input type="text" name="username" class="input-light" placeholder="your_callsign" required autocomplete="off"></div>
        <div><label class="text-xs font-semibold block mb-1" style="color:var(--text-muted);">Country</label>
          <select name="country" class="input-light" required>
            <option value="">Select your country…</option>
            <?php foreach(['Afghanistan','Albania','Algeria','Argentina','Australia','Austria','Azerbaijan','Bangladesh','Belgium','Bolivia','Bosnia','Brazil','Bulgaria','Cambodia','Canada','Chile','China','Colombia','Croatia','Czech Republic','Denmark','Ecuador','Egypt','Estonia','Ethiopia','Finland','France','Georgia','Germany','Ghana','Global','Greece','Guatemala','Honduras','Hungary','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy','Japan','Jordan','Kazakhstan','Kenya','Kuwait','Latvia','Lebanon','Libya','Lithuania','Malaysia','Mexico','Morocco','Myanmar','Nepal','Netherlands','New Zealand','Nigeria','North Korea','Norway','Oman','Pakistan','Palestine','Panama','Peru','Philippines','Poland','Portugal','Qatar','Romania','Russia','Saudi Arabia','Serbia','Singapore','Slovakia','South Africa','South Korea','Spain','Sri Lanka','Sweden','Switzerland','Syria','Taiwan','Thailand','Turkey','Ukraine','United Arab Emirates','United Kingdom','United States','Uruguay','Uzbekistan','Venezuela','Vietnam','Yemen','Zimbabwe'] as $c): ?>
            <option value="<?= $c ?>"><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label class="text-xs font-semibold block mb-1" style="color:var(--text-muted);">Password</label><input type="password" name="password" class="input-light" placeholder="Min 8 characters" required></div>
        <div><label class="text-xs font-semibold block mb-1" style="color:var(--text-muted);">Confirm Password</label><input type="password" name="confirm_password" class="input-light" placeholder="••••••••" required></div>
        <button type="submit" class="btn-primary w-full py-2.5 rounded-xl text-sm font-semibold">Create Account</button>
      </form>
      <p class="text-center text-xs mt-4" style="color:var(--text-subtle);">Already have an account? <a href="?tab=login" style="color:#2563eb;text-decoration:none;font-weight:600;">Sign in</a></p>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>
<script>lucide.createIcons();</script>
</body></html>
