<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';
startSession();
$viewer = getCurrentUser();
$db = getDB();
$isAdminViewer = $viewer && in_array($viewer['role'],['admin','owner']);
$unreadNotif = $viewer ? getUnreadCount($viewer['id']) : 0;

$msg = '';
if ($viewer && $_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action']??'';
    if ($action==='upload_photo' && isset($_FILES['photo'])) {
        $file=$_FILES['photo'];
        $allowed=['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'],$allowed)) { $msg='error:Only JPG, PNG, GIF, WEBP allowed.'; }
        elseif ($file['size']>5*1024*1024) { $msg='error:Max 5MB.'; }
        else {
            $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
            $dir=__DIR__.'/../uploads/avatars/'; if(!is_dir($dir))mkdir($dir,0755,true);
            $fn='avatar_'.$viewer['id'].'.'.$ext;
            if (move_uploaded_file($file['tmp_name'],$dir.$fn)) {
                $zd=$_POST['zoom_data']??'';
                $db->prepare("UPDATE users SET profile_photo=?,logo_zoom=? WHERE id=?")->execute(['uploads/avatars/'.$fn,$zd,$viewer['id']]);
                $msg='success:Logo updated!'; $viewer=getCurrentUser();
            } else { $msg='error:Upload failed.'; }
        }
    } elseif ($action==='remove_photo') {
        $db->prepare("UPDATE users SET profile_photo=NULL,logo_zoom=NULL WHERE id=?")->execute([$viewer['id']]); $msg='success:Logo removed.'; $viewer=getCurrentUser();
    } elseif ($action==='edit_profile') {
        $nu=trim($_POST['username']??''); $na=trim($_POST['airline_name']??''); $np=$_POST['new_password']??''; $cp=$_POST['confirm_password']??''; $op=$_POST['old_password']??'';
        if (!$nu||!$na) { $msg='error:Fields required.'; }
        else {
            $ck=$db->prepare("SELECT id FROM users WHERE username=? AND id!=?"); $ck->execute([$nu,$viewer['id']]);
            if ($ck->fetch()) { $msg='error:Username taken.'; }
            elseif ($np && !$op) { $msg='error:Please enter your current password to change it.'; }
            elseif ($np && !password_verify($op, $viewer['password_hash'])) { $msg='error:Current password is incorrect.'; }
            elseif ($np && $np !== $cp) { $msg='error:New passwords do not match.'; }
            elseif ($np && strlen($np) < 6) { $msg='error:New password must be at least 6 characters.'; }
            else {
                if ($np) $db->prepare("UPDATE users SET username=?,airline_name=?,password_hash=? WHERE id=?")->execute([$nu,$na,password_hash($np,PASSWORD_BCRYPT),$viewer['id']]);
                else $db->prepare("UPDATE users SET username=?,airline_name=? WHERE id=?")->execute([$nu,$na,$viewer['id']]);
                $msg='success:Profile updated!'; $viewer=getCurrentUser();
            }
        }
    }
}

$profileId=(int)($_GET['id']??($viewer?$viewer['id']:0));
if (!$profileId) { header('Location: '.SITE_URL.'/index.php'); exit; }
$stmt=$db->prepare("SELECT id,airline_name,username,country,alliance,role,created_at,profile_photo,logo_zoom FROM users WHERE id=? AND is_banned=0");
$stmt->execute([$profileId]); $profile=$stmt->fetch();
if (!$profile) { header('Location: '.SITE_URL.'/index.php?error=User+not+found'); exit; }

$isOwnProfile = $viewer && $viewer['id']===$profile['id'];
$photoUrl = $profile['profile_photo'] ? SITE_URL.'/'.htmlspecialchars($profile['profile_photo']) : null;
$zd = $profile['logo_zoom'] ? json_decode($profile['logo_zoom'],true) : null;
$ps=$zd['scale']??1; $px=$zd['offsetX']??0; $py=$zd['offsetY']??0;
$alRow=null; if ($profile['alliance']) { $als=$db->prepare("SELECT id FROM alliances WHERE name=?"); $als->execute([$profile['alliance']]); $alRow=$als->fetch(); }
?><!DOCTYPE html>
<html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= htmlspecialchars($profile['username']) ?>'s Profile</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <?php lightThemeCSS(); darkModeScript(); ?>
  <style>
    .avatar-circle{border-radius:50%;overflow:hidden;position:relative;border:3px solid rgba(59,130,246,0.4);box-shadow:0 0 0 4px rgba(59,130,246,0.1);}
    .modal-bg{display:none;position:fixed;inset:0;z-index:200;background:rgba(15,23,42,0.45);backdrop-filter:blur(4px);align-items:center;justify-content:center;}
    .modal-bg.open{display:flex;}
    .modal-box{
      background:var(--bg-card);
      border:1px solid var(--border);
      border-radius:24px;padding:28px;max-width:420px;width:92%;max-height:90vh;overflow-y:auto;
      box-shadow:var(--panel-shadow);
      transition:var(--t);
    }
    .info-tile{
      background:var(--bg-hover);
      border:1px solid var(--border);
      border-radius:14px;padding:14px;
      transition:var(--t);
    }
  </style>
</head><body>
<div class="w-full min-h-screen">
<?= lightNavHTML($viewer,$isAdminViewer,0,$unreadNotif) ?>

<div class="relative z-10 pt-8 pb-16 px-4 max-w-xl mx-auto">
  <?php if ($msg): ?>
    <?php [$mt,$mx]=explode(':',$msg,2); ?>
    <div class="mb-5 px-4 py-3 rounded-xl text-sm font-medium" style="background:<?= $mt==='success'?'#ecfdf5':'#fff1f2' ?>;border:1px solid <?= $mt==='success'?'#a7f3d0':'#fecdd3' ?>;color:<?= $mt==='success'?'#059669':'#e11d48' ?>;"><?= htmlspecialchars($mx) ?></div>
  <?php endif; ?>

  <div class="glass rounded-3xl overflow-hidden shadow-xl">
    <!-- Banner -->
    <div class="h-24 relative" style="background:linear-gradient(135deg,<?= $profile['role']==='owner'?'#d97706,#f59e0b':($profile['role']==='admin'?'#6366f1,#8b5cf6':'#3b82f6,#6366f1') ?>);">
      <div style="position:absolute;bottom:8px;right:14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.45);"><?= htmlspecialchars($profile['role']) ?></div>
    </div>
    <div class="px-8 pb-8">
      <div class="flex items-end justify-between -mt-11 mb-5">
        <div style="position:relative;">
          <div class="avatar-circle" style="width:86px;height:86px;">
            <?php if ($photoUrl): ?>
              <img src="<?= $photoUrl ?>" style="position:absolute;width:<?= $ps*100 ?>%;height:<?= $ps*100 ?>%;object-fit:cover;top:50%;left:50%;transform:translate(calc(-50% + <?= $px ?>px),calc(-50% + <?= $py ?>px));">
            <?php else: ?>
              <div style="width:100%;height:100%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;color:white;font-family:Outfit,sans-serif;"><?= strtoupper(substr($profile['airline_name'],0,2)) ?></div>
            <?php endif; ?>
          </div>
          <?php if ($isOwnProfile): ?>
          <button onclick="document.getElementById('logo-modal').classList.add('open')" style="position:absolute;bottom:0;right:0;width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);border:2px solid var(--bg-card);display:flex;align-items:center;justify-content:center;cursor:pointer;">
            <i data-lucide="camera" style="width:10px;height:10px;color:white;"></i>
          </button>
          <?php endif; ?>
        </div>
        <?php $rb=['owner'=>['#d97706','rgba(217,119,6,0.1)','rgba(217,119,6,0.3)'],'admin'=>['#6366f1','rgba(99,102,241,0.1)','rgba(99,102,241,0.3)'],'user'=>['var(--text-muted)','var(--bg-hover)','var(--border)']][$profile['role']]??['var(--text-muted)','var(--bg-hover)','var(--border)']; ?>
        <span style="padding:4px 12px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase;color:<?= $rb[0] ?>;background:<?= $rb[1] ?>;border:1px solid <?= $rb[2] ?>;"><?= ucfirst($profile['role']) ?></span>
      </div>

      <div class="flex items-center gap-2 mb-1">
        <h1 class="font-heading text-2xl" style="font-weight:800;color:var(--text-heading);"><?= htmlspecialchars($profile['username']) ?></h1>
        <?php if ($isOwnProfile): ?>
        <button onclick="document.getElementById('edit-modal').classList.add('open')" style="padding:3px 9px;border-radius:8px;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.15);display:flex;align-items:center;gap:4px;cursor:pointer;font-size:11px;font-weight:600;color:#2563eb;">
          <i data-lucide="pencil" style="width:10px;height:10px;"></i> Edit
        </button>
        <?php endif; ?>
      </div>
      <p class="mb-5 text-sm" style="color:var(--text-muted);"><?= htmlspecialchars($profile['airline_name']) ?></p>

      <div class="grid grid-cols-2 gap-3 mb-5">
        <div class="info-tile"><p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--text-subtle);">Country</p><p class="text-sm font-semibold" style="color:var(--text-heading);"><?= htmlspecialchars($profile['country']) ?></p></div>
        <div class="info-tile"><p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--text-subtle);">Alliance</p><p class="text-sm font-semibold" style="color:#2563eb;"><?= $profile['alliance'] ? htmlspecialchars($profile['alliance']) : '<span style="color:var(--text-subtle)">None</span>' ?></p></div>
        <div class="info-tile" style="grid-column:span 2"><p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--text-subtle);">Member Since</p><p class="text-sm font-semibold" style="color:var(--text-heading);"><?= date('F j, Y',strtotime($profile['created_at'])) ?></p></div>
      </div>

      <div class="flex gap-2">
        <?php if ($alRow): ?><a href="<?= SITE_URL ?>/pages/alliance.php?id=<?= $alRow['id'] ?>" class="flex-1 btn-primary py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2" style="text-decoration:none;"><i data-lucide="users" style="width:14px;height:14px;"></i> View Alliance</a><?php endif; ?>
        <?php if (!$isOwnProfile && $viewer): ?>
        <a href="<?= SITE_URL ?>/pages/dm.php?user=<?= $profile['id'] ?>" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2" style="text-decoration:none;"><i data-lucide="send" style="width:14px;height:14px;"></i> Message</a>
        <?php endif; ?>
        <a href="javascript:history.back()" class="btn-ghost px-4 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2" style="text-decoration:none;"><i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back</a>
      </div>
    </div>
  </div>
</div>

<?php if ($isOwnProfile): ?>
<div id="edit-modal" class="modal-bg">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-5">
      <h2 class="font-heading text-base" style="font-weight:700;color:var(--text-heading);display:flex;align-items:center;gap:8px;"><i data-lucide="user-cog" style="width:15px;height:15px;color:#2563eb;"></i> Edit Profile</h2>
      <button onclick="document.getElementById('edit-modal').classList.remove('open')" style="color:var(--text-subtle);background:none;border:none;cursor:pointer;"><i data-lucide="x" style="width:15px;height:15px;"></i></button>
    </div>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="action" value="edit_profile">
      <div><label class="text-xs font-semibold block mb-1" style="color:var(--text-muted);">Username</label><input type="text" name="username" class="input-light" value="<?= htmlspecialchars($profile['username']) ?>" required></div>
      <div><label class="text-xs font-semibold block mb-1" style="color:var(--text-muted);">Airline Name</label><input type="text" name="airline_name" class="input-light" value="<?= htmlspecialchars($profile['airline_name']) ?>" required></div>
      <hr style="border-color:var(--border);margin:4px 0;">
      <p class="text-xs" style="color:var(--text-subtle);">Leave password fields blank to keep current password</p>
      <div><label class="text-xs font-semibold block mb-1" style="color:var(--text-muted);">Current Password</label><input type="password" name="old_password" class="input-light" placeholder="Required to change password"></div>
      <div><label class="text-xs font-semibold block mb-1" style="color:var(--text-muted);">New Password</label><input type="password" name="new_password" class="input-light" placeholder="Min 6 characters"></div>
      <div><label class="text-xs font-semibold block mb-1" style="color:var(--text-muted);">Confirm New Password</label><input type="password" name="confirm_password" class="input-light" placeholder="Repeat new password"></div>
      <div class="flex gap-3"><button type="submit" class="flex-1 btn-primary py-2.5 rounded-xl text-sm font-semibold">Save</button><button type="button" onclick="document.getElementById('edit-modal').classList.remove('open')" class="px-4 py-2.5 rounded-xl text-sm btn-ghost">Cancel</button></div>
    </form>
  </div>
</div>
<div id="logo-modal" class="modal-bg">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-5">
      <h2 class="font-heading text-base" style="font-weight:700;color:var(--text-heading);display:flex;align-items:center;gap:8px;"><i data-lucide="image" style="width:15px;height:15px;color:#2563eb;"></i> Airline Logo</h2>
      <button onclick="document.getElementById('logo-modal').classList.remove('open')" style="color:var(--text-subtle);background:none;border:none;cursor:pointer;"><i data-lucide="x" style="width:15px;height:15px;"></i></button>
    </div>
    <div class="flex justify-center mb-5"><div>
      <p class="text-xs text-center mb-2" style="color:var(--text-subtle);">Drag to reposition · Scroll to zoom</p>
      <div id="logo-vp" style="width:130px;height:130px;border-radius:50%;overflow:hidden;position:relative;cursor:grab;border:3px solid rgba(59,130,246,0.4);box-shadow:0 0 0 4px rgba(59,130,246,0.1);margin:0 auto;">
        <?php if ($photoUrl): ?>
          <img id="pv-img" src="<?= $photoUrl ?>" draggable="false" style="position:absolute;width:<?= $ps*100 ?>%;height:<?= $ps*100 ?>%;object-fit:cover;top:50%;left:50%;transform:translate(calc(-50% + <?= $px ?>px),calc(-50% + <?= $py ?>px));pointer-events:none;">
          <div id="pv-ph" style="display:none;"></div>
        <?php else: ?>
          <div id="pv-ph" style="width:100%;height:100%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:white;font-family:Outfit,sans-serif;"><?= strtoupper(substr($profile['airline_name'],0,2)) ?></div>
          <img id="pv-img" src="" draggable="false" style="display:none;position:absolute;width:100%;height:100%;object-fit:cover;top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none;">
        <?php endif; ?>
      </div>
      <div style="display:flex;align-items:center;gap:8px;margin-top:12px;padding:0 8px;">
        <i data-lucide="zoom-out" style="width:12px;height:12px;color:var(--text-subtle);"></i>
        <input type="range" id="zoom-sl" min="100" max="300" value="<?= $ps*100 ?>" style="flex:1;accent-color:#3b82f6;height:4px;">
        <i data-lucide="zoom-in" style="width:12px;height:12px;color:var(--text-subtle);"></i>
      </div>
      <p class="text-center text-xs mt-1" style="color:var(--text-subtle);">520x520 recommended</p>
    </div></div>
    <form method="POST" enctype="multipart/form-data" id="logo-form">
      <input type="hidden" name="action" value="upload_photo">
      <input type="hidden" name="zoom_data" id="zoom-data">
      <label style="display:block;border:2px dashed rgba(59,130,246,0.25);background:rgba(59,130,246,0.03);border-radius:14px;padding:14px;text-align:center;cursor:pointer;transition:border-color 0.2s;" onmouseover="this.style.borderColor='rgba(59,130,246,0.5)'" onmouseout="this.style.borderColor='rgba(59,130,246,0.25)'">
        <i data-lucide="upload-cloud" style="width:22px;height:22px;color:var(--text-subtle);margin:0 auto 5px;"></i>
        <p class="text-sm font-medium" style="color:var(--text-muted);">Upload logo from device</p>
        <p class="text-xs mt-0.5" style="color:var(--text-subtle);">JPG, PNG, WEBP up to 5MB</p>
        <input id="logo-file" type="file" name="photo" accept="image/*" class="hidden" onchange="pvFile(this)">
      </label>
      <div style="display:flex;gap:10px;margin-top:14px;">
        <button type="button" onclick="submitLogo()" class="btn-primary flex-1 py-2.5 rounded-xl text-sm font-semibold">Save Logo</button>
        <?php if ($photoUrl): ?>
        <button type="button" onclick="rmLogo()" style="padding:10px 14px;border-radius:12px;background:#fff1f2;color:#e11d48;border:1px solid #fecdd3;font-size:13px;font-weight:600;cursor:pointer;">Remove</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
<script>
let sc=<?= $ps ?>,ox=<?= $px ?>,oy=<?= $py ?>;let drag=false,sx,sy;
const vp=document.getElementById('logo-vp'),img=document.getElementById('pv-img'),sl=document.getElementById('zoom-sl');
function applyT(){if(!img||img.style.display==='none')return;img.style.width=(sc*100)+'%';img.style.height=(sc*100)+'%';img.style.transform=`translate(calc(-50% + ${ox}px),calc(-50% + ${oy}px))`;}
sl.addEventListener('input',()=>{sc=parseInt(sl.value)/100;applyT();});
vp.addEventListener('mousedown',e=>{drag=true;sx=e.clientX-ox;sy=e.clientY-oy;e.preventDefault();});
document.addEventListener('mousemove',e=>{if(!drag)return;ox=e.clientX-sx;oy=e.clientY-sy;applyT();});
document.addEventListener('mouseup',()=>drag=false);
vp.addEventListener('touchstart',e=>{drag=true;sx=e.touches[0].clientX-ox;sy=e.touches[0].clientY-oy;},{passive:true});
document.addEventListener('touchmove',e=>{if(!drag)return;ox=e.touches[0].clientX-sx;oy=e.touches[0].clientY-sy;applyT();},{passive:true});
document.addEventListener('touchend',()=>drag=false);
vp.addEventListener('wheel',e=>{e.preventDefault();sc=Math.max(1,Math.min(3,sc-e.deltaY*0.001));sl.value=sc*100;applyT();},{passive:false});
function pvFile(inp){if(!inp.files[0])return;const r=new FileReader();r.onload=e=>{const ph=document.getElementById('pv-ph');if(ph)ph.style.display='none';img.src=e.target.result;img.style.display='block';sc=1;ox=0;oy=0;sl.value=100;applyT();};r.readAsDataURL(inp.files[0]);}
function submitLogo(){document.getElementById('zoom-data').value=JSON.stringify({scale:sc,offsetX:ox,offsetY:oy});document.getElementById('logo-form').submit();}
function rmLogo(){if(!confirm('Remove logo?'))return;const f=document.createElement('form');f.method='POST';f.innerHTML='<input name="action" value="remove_photo">';document.body.appendChild(f);f.submit();}
</script>
<?php endif; ?>
<script>lucide.createIcons();</script>
</body></html>
