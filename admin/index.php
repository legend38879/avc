<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';
$user = requireAdmin();
checkBanned($user);
$db = getDB();
$isOwner = ($user['role'] === 'owner');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['approve_app','reject_app'])) {
        $appId=(int)($_POST['app_id']??0);$status=$action==='approve_app'?'approved':'rejected';
        $db->prepare("UPDATE applications SET status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?")->execute([$status,$user['id'],$appId]);
        if($status==='approved'){$ar=$db->query("SELECT a.user_id,al.name as aname FROM applications a JOIN alliances al ON al.id=a.alliance_id WHERE a.id=$appId")->fetch();if($ar){$db->prepare("UPDATE users SET alliance=? WHERE id=?")->execute([$ar['aname'],$ar['user_id']]);addNotification($ar['user_id'],"Your application was approved! Welcome to {$ar['aname']}.");}}
        else{$ar=$db->query("SELECT user_id FROM applications WHERE id=$appId")->fetch();if($ar)addNotification($ar['user_id'],"Your application was rejected.");}
        $msg="Application $status.";
    }
    if($action==='ban_user'){$uid=(int)($_POST['user_id']??0);$r=sanitize($_POST['ban_reason']??'No reason');$db->prepare("UPDATE users SET is_banned=1 WHERE id=? AND role='user'")->execute([$uid]);$db->prepare("INSERT INTO bans(user_id,reason,banned_by)VALUES(?,?,?)")->execute([$uid,$r,$user['id']]);addNotification($uid,"Your account has been banned.");$msg="User banned.";}
    if($action==='unban_user'){$uid=(int)($_POST['user_id']??0);$db->prepare("UPDATE users SET is_banned=0 WHERE id=?")->execute([$uid]);addNotification($uid,"Your ban has been lifted. Welcome back!");$msg="User unbanned.";}
    if(in_array($action,['approve_appeal','reject_appeal'])&&$isOwner){$aid=(int)($_POST['appeal_id']??0);$s=$action==='approve_appeal'?'approved':'rejected';$ap=$db->query("SELECT * FROM appeals WHERE id=$aid")->fetch();$db->prepare("UPDATE appeals SET status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?")->execute([$s,$user['id'],$aid]);if($s==='approved'&&$ap){$db->prepare("UPDATE users SET is_banned=0 WHERE id=?")->execute([$ap['user_id']]);addNotification($ap['user_id'],"Your appeal was approved!");}elseif($ap){addNotification($ap['user_id'],"Your appeal was rejected.");}$msg="Appeal $s.";}
    if($action==='update_alliance'){$aid=(int)($_POST['alliance_id']??0);$db->prepare("UPDATE alliances SET name=?,value=?,member_count=?,rank=?,tag=? WHERE id=?")->execute([sanitize($_POST['alliance_name']??''),sanitize($_POST['alliance_value']??''),(int)($_POST['member_count']??0),(int)($_POST['alliance_rank']??0),sanitize($_POST['alliance_tag']??''),$aid]);$msg="Alliance updated.";}
    if($action==='update_social_links'){$du=trim($_POST['discord_url']??'');$iu=trim($_POST['instagram_url']??'');try{$db->prepare("INSERT INTO site_settings(key_name,value)VALUES('discord_url',?)ON DUPLICATE KEY UPDATE value=?")->execute([$du,$du]);$db->prepare("INSERT INTO site_settings(key_name,value)VALUES('instagram_url',?)ON DUPLICATE KEY UPDATE value=?")->execute([$iu,$iu]);$msg="Social links updated!";}catch(Exception $e){$msg="Error: run migration first.";}}
    if($action==='add_custom_member'){$aid=(int)($_POST['alliance_id']??0);$an=sanitize($_POST['cm_airline']??'');$sv=sanitize($_POST['cm_share_value']??'');$ac=(int)($_POST['cm_aircraft']??0);if($aid&&$an){$db->prepare("INSERT INTO alliance_custom_members(alliance_id,airline_name,share_value,aircraft_count,added_by)VALUES(?,?,?,?,?)")->execute([$aid,$an,$sv,$ac,$user['id']]);$msg="Member added.";}}
    if($action==='remove_custom_member'){$db->prepare("DELETE FROM alliance_custom_members WHERE id=?")->execute([(int)($_POST['cm_id']??0)]);$msg="Member removed.";}
    if($action==='make_admin'&&$isOwner){$db->prepare("UPDATE users SET role='admin' WHERE id=? AND role='user'")->execute([(int)($_POST['user_id']??0)]);$msg="Promoted to admin.";}
    if($action==='revoke_admin'&&$isOwner){$db->prepare("UPDATE users SET role='user' WHERE id=? AND role='admin'")->execute([(int)($_POST['user_id']??0)]);$msg="Admin revoked.";}
    if($action==='kick_from_alliance'){$uid=(int)($_POST['user_id']??0);$u2=$db->prepare("SELECT alliance FROM users WHERE id=?");$u2->execute([$uid]);$u2=$u2->fetch();if($u2&&$u2['alliance']){$db->prepare("UPDATE alliances SET member_count=GREATEST(0,member_count-1) WHERE name=?")->execute([$u2['alliance']]);$db->prepare("UPDATE users SET alliance=NULL WHERE id=?")->execute([$uid]);addNotification($uid,"You have been removed from the alliance by an admin.");$msg="User kicked from alliance.";}}
    if($action==='set_alliance'){
        $uid=(int)($_POST['user_id']??0);$newAl=sanitize($_POST['alliance_name']??'');
        if($uid){
            $cu=$db->prepare("SELECT alliance FROM users WHERE id=?");$cu->execute([$uid]);$cu=$cu->fetch();
            if($cu!==false){
                if($cu['alliance']) $db->prepare("UPDATE alliances SET member_count=GREATEST(0,member_count-1) WHERE name=?")->execute([$cu['alliance']]);
                if($newAl){
                    $db->prepare("UPDATE users SET alliance=? WHERE id=?")->execute([$newAl,$uid]);
                    $db->prepare("UPDATE alliances SET member_count=member_count+1 WHERE name=?")->execute([$newAl]);
                    addNotification($uid,"You have been assigned to {$newAl} by an admin.");
                } else {
                    $db->prepare("UPDATE users SET alliance=NULL WHERE id=?")->execute([$uid]);
                    if($cu['alliance']) addNotification($uid,"You have been removed from your alliance by an admin.");
                }
                $msg="Alliance updated.";
            }
        }
    }
    if($action==='create_alliance'&&$isOwner){
        $name=sanitize($_POST['new_name']??'');$tag=sanitize($_POST['new_tag']??'');
        $tc=sanitize($_POST['new_tag_color']??'#6366f1');$gf=sanitize($_POST['new_grad_from']??'#4f46e5');$gt=sanitize($_POST['new_grad_to']??'#6366f1');
        $val=sanitize($_POST['new_value']??'$0');$mx=(int)($_POST['new_max']??60);$rk=(int)($_POST['new_rank']??99);
        $reqs=sanitize($_POST['new_reqs']??'');
        if($name&&$tag){
            $db->prepare("INSERT INTO alliances(name,tag,tag_color,value,member_count,max_members,rank,requirements,gradient_from,gradient_to,border_color,status) VALUES(?,?,?,?,0,?,?,?,?,?,?,?)")->execute([$name,$tag,$tc,$val,$mx,$rk,$reqs,$gf,$gt,'rgba(99,102,241,0.15)','recruiting']);
            $msg="Alliance '{$name}' created!";
        } else { $msg="Name and tag are required."; }
    }
}

$pendingApps=$db->query("SELECT a.*,al.name as alliance_name,u.username FROM applications a JOIN alliances al ON al.id=a.alliance_id JOIN users u ON u.id=a.user_id WHERE a.status='pending' ORDER BY a.created_at DESC")->fetchAll();
$pendingAppeals=$isOwner?$db->query("SELECT ap.*,u.username FROM appeals ap JOIN users u ON u.id=ap.user_id WHERE ap.status='pending' ORDER BY ap.created_at DESC")->fetchAll():[];
$alliances=$db->query("SELECT * FROM alliances ORDER BY rank ASC")->fetchAll();
$users=$db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$customMembers=$db->query("SELECT cm.*,al.name as alliance_name FROM alliance_custom_members cm JOIN alliances al ON al.id=cm.alliance_id ORDER BY cm.added_at DESC")->fetchAll();
$totalUsers=count($users);
try{$inactivityNotices=$db->query("SELECT n.*,u.username FROM inactivity_notices n JOIN users u ON u.id=n.user_id ORDER BY n.created_at DESC LIMIT 50")->fetchAll();
// Filter to only active (non-expired) notices
$inactivityNotices = array_filter($inactivityNotices, function($n){ return time() < strtotime($n['created_at']) + ($n['days']*86400); });
$inactivityNotices = array_values($inactivityNotices);
}catch(Exception $e){$inactivityNotices=[];}
$bannedCount=(int)$db->query("SELECT COUNT(*) FROM users WHERE is_banned=1")->fetchColumn();
function getSetting($db,$k,$d=''){try{$s=$db->prepare("SELECT value FROM site_settings WHERE key_name=?");$s->execute([$k]);$r=$s->fetchColumn();return $r!==false?$r:$d;}catch(Exception $e){return $d;}}
$discordUrl=getSetting($db,'discord_url','');$instagramUrl=getSetting($db,'instagram_url','');
$unreadNotif=getUnreadCount($user['id']);
?>
<!DOCTYPE html>
<html lang="en"><head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Admin Panel</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <?php lightThemeCSS(); darkModeScript(); ?>
  <style>
    .tab-btn{padding:6px 12px;border-radius:9px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid transparent;white-space:nowrap;transition:all 0.2s;background:transparent;color:var(--text-muted);font-family:'Plus Jakarta Sans',sans-serif;}
    .tab-active{background:rgba(59,130,246,0.1)!important;color:#2563eb!important;border-color:rgba(59,130,246,0.2)!important;}
    .ia{width:100%;padding:9px 13px;border-radius:10px;background:var(--bg-input);border:1px solid var(--border-input);font-size:13px;color:var(--text);outline:none;font-family:'Plus Jakarta Sans',sans-serif;transition:all 0.25s;}
    .ia:focus{border-color:var(--border-focus);box-shadow:0 0 0 3px var(--input-focus-ring);}
    .ia::placeholder{color:var(--text-subtle);}
    .bs{padding:5px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:'Plus Jakarta Sans',sans-serif;transition:all 0.2s;}
    .b-ok{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}.b-no{background:#fff1f2;color:#9f1239;border:1px solid #fecdd3;}
    .b-ban{background:#fff1f2;color:#e11d48;border:1px solid #fecdd3;}.b-unban{background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;}
    .b-prm{background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;}.b-rev{background:#fff1f2;color:#e11d48;border:1px solid #fecdd3;}
    .at{width:100%;border-collapse:collapse;min-width:580px;}
    .at th{font-size:11px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:var(--text-subtle);padding:10px 14px;border-bottom:1px solid var(--border);background:var(--bg-hover);text-align:left;white-space:nowrap;transition:var(--t);}
    .at td{padding:10px 14px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text);vertical-align:middle;transition:var(--t);}
    .at tr:hover td{background:var(--bg-hover);}
    .sc{background:var(--bg-card);border:1px solid var(--border);border-radius:20px;overflow:hidden;margin-bottom:16px;transition:var(--t);}
    .sh{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;transition:var(--t);}
    .modal-bg{display:none;position:fixed;inset:0;z-index:200;background:rgba(15,23,42,0.45);backdrop-filter:blur(4px);align-items:center;justify-content:center;}
    .modal-bg.open{display:flex;}
    .mbox{background:var(--bg-card);backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:24px;padding:28px;max-width:400px;width:92%;box-shadow:var(--panel-shadow);transition:var(--t);}
  </style>
</head><body>
<div class="w-full min-h-screen bg-mesh">
<?= lightNavHTML($user,true,0,$unreadNotif) ?>
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

  <!-- Admin Welcome Hero Banner -->
  <div style="position:relative;border-radius:20px;overflow:hidden;margin-bottom:24px;background:linear-gradient(135deg,#1e40af 0%,#3b82f6 45%,#6366f1 100%);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;">
    <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,0.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.04) 1px,transparent 1px);background-size:32px 32px;pointer-events:none;"></div>
    <div style="position:absolute;width:160px;height:160px;border-radius:50%;background:radial-gradient(circle,rgba(99,102,241,0.3),transparent 70%);top:-50px;right:80px;pointer-events:none;"></div>
    <div style="position:relative;z-index:2;flex:1;">
      <div style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:99px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);font-size:10px;font-weight:700;color:rgba(255,255,255,0.9);letter-spacing:0.07em;text-transform:uppercase;margin-bottom:8px;">
        <span style="width:5px;height:5px;border-radius:50%;background:#4ade80;display:inline-block;animation:pulse2 1.4s ease-in-out infinite;"></span>
        <?= ucfirst($user['role']) ?> Access
      </div>
      <h1 style="font-family:'Outfit',sans-serif;font-size:clamp(1.1rem,2.5vw,1.5rem);font-weight:800;color:white;line-height:1.2;margin-bottom:3px;">Welcome, <span style="color:rgba(186,230,253,0.95);"><?= htmlspecialchars($user['airline_name']) ?></span></h1>
      <p style="font-size:12px;color:rgba(255,255,255,0.6);">Aero Vibes Central · Full Alliance Management</p>
    </div>
  </div>
  <style>
    #admin-plane{animation:adminPlaneFloat 5s ease-in-out infinite;}
    @keyframes adminPlaneFloat{
      0%,100%{transform:translateY(0px) rotate(-1deg);}
      25%{transform:translateY(-6px) rotate(1.5deg);}
      50%{transform:translateY(-3px) rotate(0deg);}
      75%{transform:translateY(-8px) rotate(2deg);}
    }
    #trail1{animation:trailPulse 1.8s ease-in-out infinite;}
    #trail2{animation:trailPulse 1.8s ease-in-out infinite 0.2s;}
    #trail3{animation:trailPulse 1.8s ease-in-out infinite 0.4s;}
    @keyframes trailPulse{0%,100%{opacity:0.4;}50%{opacity:0.9;}}
  </style>

  <?php if($msg): ?><div style="margin-bottom:20px;padding:12px 16px;border-radius:12px;font-size:13px;font-weight:500;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <!-- Stats -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" style="margin-bottom:20px;">
    <?php foreach([['Pending Apps',count($pendingApps),'#3b82f6','clipboard-list'],['Total Members',$totalUsers,'#22c55e','users'],['Banned',$bannedCount,'#ef4444','user-x'],['Appeals',count($pendingAppeals),'#f59e0b','shield-alert']] as [$l,$v,$c,$ic]): ?>
    <div class="glass rounded-xl p-3 text-center">
      <div style="width:30px;height:30px;border-radius:8px;background:<?= $c ?>18;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;"><i data-lucide="<?= $ic ?>" style="width:14px;height:14px;color:<?= $c ?>;"></i></div>
      <p class="font-heading text-xl" style="font-weight:800;color:<?= $c ?>;"><?= $v ?></p>
      <p style="color:var(--text-subtle);" class="text-xs mt-0.5"><?= $l ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Tabs -->
  <div class="glass rounded-2xl p-1.5" style="display:flex;gap:4px;margin-bottom:24px;overflow-x:auto;">
    <button class="tab-btn tab-active" onclick="showTab('apps',this)">📋 Apps (<?= count($pendingApps) ?>)</button>
    <button class="tab-btn" onclick="showTab('alliances',this)">🏆 Alliances</button>
    <button class="tab-btn" onclick="showTab('users',this)">👥 Users (<?= $totalUsers ?>)</button>
    <button class="tab-btn" onclick="showTab('inactivity',this)">⏱ Inactivity (<?= count($inactivityNotices) ?>)</button>
    <?php if($isOwner): ?><button class="tab-btn" onclick="showTab('appeals',this)">🔓 Appeals (<?= count($pendingAppeals) ?>)</button><?php endif; ?>
    <button class="tab-btn" onclick="showTab('social',this)">🔗 Social</button>
  </div>

  <!-- Applications -->
  <div id="tab-apps">
    <div class="sc"><div class="sh"><h2 style="font-family:'Outfit',sans-serif;font-weight:700;color:var(--text-heading);">Pending Applications</h2></div>
    <?php if(!$pendingApps): ?><div style="padding:40px;text-align:center;color:var(--text-subtle);font-size:13px;">No pending applications.</div>
    <?php else: ?><div style="overflow-x:auto;"><table class="at">
      <thead><tr><th>Airline</th><th>User</th><th>Alliance</th><th>Value</th><th>Aircraft</th><th>Previous</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody><?php foreach($pendingApps as $a): ?>
      <tr>
        <td style="font-weight:600;color:var(--text-heading);"><?= htmlspecialchars($a['airline_name']) ?></td>
        <td style="color:var(--text-muted);">@<?= htmlspecialchars($a['username']) ?></td>
        <td><span style="padding:2px 8px;border-radius:99px;font-size:11px;font-weight:600;background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;"><?= htmlspecialchars($a['alliance_name']) ?></span></td>
        <td style="color:#059669;font-weight:600;"><?= htmlspecialchars($a['share_value']) ?></td>
        <td><?= $a['aircraft_count'] ?></td>
        <td style="color:var(--text-subtle);font-size:12px;"><?= htmlspecialchars($a['previous_alliances']?:'—') ?></td>
        <td style="color:var(--text-subtle);font-size:12px;"><?= date('M j',strtotime($a['created_at'])) ?></td>
        <td><div style="display:flex;gap:5px;">
          <form method="POST" style="display:inline"><input type="hidden" name="action" value="approve_app"><input type="hidden" name="app_id" value="<?= $a['id'] ?>"><button class="bs b-ok">✓ Approve</button></form>
          <form method="POST" style="display:inline"><input type="hidden" name="action" value="reject_app"><input type="hidden" name="app_id" value="<?= $a['id'] ?>"><button class="bs b-no">✕ Reject</button></form>
        </div></td>
      </tr>
      <?php endforeach; ?></tbody>
    </table></div><?php endif; ?></div>
  </div>

  <!-- Alliances -->
  <div id="tab-alliances" class="hidden">
    <?php foreach($alliances as $al): ?>
    <div class="sc p-6">
      <h3 class="font-heading text-lg mb-4" style="font-weight:700;color:var(--text-heading);"><?= htmlspecialchars($al['name']) ?></h3>
      <form method="POST" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:20px;align-items:flex-end;">
        <input type="hidden" name="action" value="update_alliance"><input type="hidden" name="alliance_id" value="<?= $al['id'] ?>">
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Name</label><input type="text" name="alliance_name" class="ia" value="<?= htmlspecialchars($al['name']) ?>"></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Tag</label><input type="text" name="alliance_tag" class="ia" value="<?= htmlspecialchars($al['tag']) ?>"></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Value</label><input type="text" name="alliance_value" class="ia" value="<?= htmlspecialchars($al['value']) ?>"></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Members (max 60)</label><input type="number" name="member_count" class="ia" value="<?= $al['member_count'] ?>" min="0" max="60" oninput="if(this.value>60)this.value=60;if(this.value<0)this.value=0;"></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Rank</label><input type="number" name="alliance_rank" class="ia" value="<?= $al['rank'] ?>"></div>
        <div><button type="submit" class="btn-primary w-full py-2 rounded-xl text-sm font-semibold">Save</button></div>
      </form>
      <h4 style="font-size:13px;font-weight:600;color:var(--text-muted);margin-bottom:12px;">Custom Members</h4>
      <?php $cms=array_filter($customMembers,fn($c)=>$c['alliance_id']==$al['id']); ?>
      <?php if($cms): ?><div style="overflow-x:auto;margin-bottom:12px;"><table class="at" style="min-width:350px;"><thead><tr><th>Airline</th><th>Value</th><th>Aircraft</th><th></th></tr></thead><tbody>
        <?php foreach($cms as $cm): ?><tr>
          <td style="font-weight:600;"><?= htmlspecialchars($cm['airline_name']) ?></td><td style="color:#059669;"><?= htmlspecialchars($cm['share_value']?:'—') ?></td><td><?= $cm['aircraft_count'] ?></td>
          <td><form method="POST" style="display:inline"><input type="hidden" name="action" value="remove_custom_member"><input type="hidden" name="cm_id" value="<?= $cm['id'] ?>"><button class="bs b-no">Remove</button></form></td>
        </tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
      <form method="POST" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
        <input type="hidden" name="action" value="add_custom_member"><input type="hidden" name="alliance_id" value="<?= $al['id'] ?>">
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px;">Airline *</label><input type="text" name="cm_airline" class="ia" style="width:160px;" placeholder="Airline name" required></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px;">Value (numbers only)</label><input type="number" name="cm_share_value" class="ia" style="width:120px;" placeholder="e.g. 500" min="0" step="1"></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px;">Aircraft</label><input type="number" name="cm_aircraft" class="ia" style="width:90px;" placeholder="0" min="0"></div>
        <button type="submit" class="btn-primary px-4 py-2.5 rounded-xl text-sm font-semibold">+ Add</button>
      </form>
    </div>
    <?php endforeach; ?>

    <?php if($isOwner): ?>
    <!-- Create Alliance (owner only) -->
    <div class="sc p-6" style="border:2px dashed var(--border);background:rgba(37,99,235,0.03);">
      <div class="sh" style="margin-bottom:18px;">
        <h3 style="font-family:'Outfit',sans-serif;font-size:16px;font-weight:700;color:var(--text-heading);">✦ Create New Alliance</h3>
        <span style="padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;background:#fffbeb;color:#d97706;border:1px solid #fde68a;">Owner Only</span>
      </div>
      <form method="POST" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;align-items:flex-end;">
        <input type="hidden" name="action" value="create_alliance">
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Alliance Name *</label><input type="text" name="new_name" class="ia" placeholder="e.g. Nova Wing" required></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Tag *</label><input type="text" name="new_tag" class="ia" placeholder="e.g. NOVA" required></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Tag Color</label><div style="display:flex;align-items:center;gap:6px;"><input type="color" name="new_tag_color" value="#6366f1" style="width:36px;height:32px;border:1px solid var(--border);border-radius:8px;padding:2px;background:var(--bg-input);cursor:pointer;"><span style="font-size:11px;color:var(--text-subtle);">(hex)</span></div></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Gradient From</label><input type="color" name="new_grad_from" value="#4f46e5" style="width:100%;height:36px;border:1px solid var(--border);border-radius:8px;padding:2px;background:var(--bg-input);cursor:pointer;"></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Gradient To</label><input type="color" name="new_grad_to" value="#6366f1" style="width:100%;height:36px;border:1px solid var(--border);border-radius:8px;padding:2px;background:var(--bg-input);cursor:pointer;"></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Starting Value</label><input type="text" name="new_value" class="ia" value="$0" placeholder="e.g. $0"></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Max Members</label><input type="number" name="new_max" class="ia" value="60" min="1" max="200"></div>
        <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Rank</label><input type="number" name="new_rank" class="ia" value="<?= count($alliances)+1 ?>" min="1"></div>
        <div style="grid-column:1/-1;"><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Requirements <span style="font-weight:400;">(pipe-separated, e.g. Min $50M value|30+ routes)</span></label><input type="text" name="new_reqs" class="ia" style="width:100%;" placeholder="Min $50M value|30+ routes|Active daily"></div>
        <div style="grid-column:1/-1;display:flex;justify-content:flex-end;"><button type="submit" class="btn-primary" style="padding:10px 28px;border-radius:12px;font-size:14px;font-weight:700;">Create Alliance</button></div>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <!-- Users — ALL users including admins/owner -->
  <div id="tab-users" class="hidden">
    <div class="sc"><div class="sh">
      <h2 style="font-family:'Outfit',sans-serif;font-weight:700;color:var(--text-heading);">All Users</h2>
      <input type="text" id="user-search" class="ia" style="width:190px;padding:7px 12px;" placeholder="🔍 Search…" oninput="filterUsers(this.value)">
    </div>
    <div style="overflow-x:auto;"><table class="at" id="users-table">
      <thead><tr><th>Username</th><th>Airline</th><th>Country</th><th>Alliance</th><th>Role</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($users as $u): ?>
        <tr class="user-row" data-search="<?= strtolower(htmlspecialchars($u['username'].' '.$u['airline_name'])) ?>">
          <td><a href="<?= SITE_URL ?>/pages/profile.php?id=<?= $u['id'] ?>" style="font-weight:600;color:#2563eb;text-decoration:none;">@<?= htmlspecialchars($u['username']) ?></a></td>
          <td style="color:var(--text-muted);"><?= htmlspecialchars($u['airline_name']) ?></td>
          <td style="color:var(--text-subtle);font-size:12px;"><?= htmlspecialchars($u['country']) ?></td>
          <td><?php if($u['alliance']): ?><span style="padding:2px 8px;border-radius:99px;font-size:11px;font-weight:600;background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;"><?= htmlspecialchars($u['alliance']) ?></span><?php else: ?><span style="color:var(--text-subtle);">—</span><?php endif; ?></td>
          <td><span style="padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase;background:<?= $u['role']=='owner'?'#fffbeb':($u['role']=='admin'?'#eef2ff':'#f8fafc') ?>;color:<?= $u['role']=='owner'?'#d97706':($u['role']=='admin'?'#4338ca':'#64748b') ?>;border:1px solid <?= $u['role']=='owner'?'#fde68a':($u['role']=='admin'?'#c7d2fe':'#e2e8f0') ?>;"><?= $u['role'] ?></span></td>
          <td style="color:var(--text-subtle);font-size:12px;"><?= date('M j, Y',strtotime($u['created_at'])) ?></td>
          <td><?php if($u['is_banned']): ?><span style="padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;background:#fff1f2;color:#e11d48;border:1px solid #fecdd3;">BANNED</span><?php else: ?><span style="padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;">ACTIVE</span><?php endif; ?></td>
          <td><div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center;">
            <?php if($u['id']!==$user['id']): ?>
              <?php if(!$u['is_banned']&&$u['role']==='user'): ?><button onclick="showBanModal(<?= $u['id'] ?>,'<?= htmlspecialchars(addslashes($u['username'])) ?>')" class="bs b-ban">Ban</button><?php endif; ?>
              <?php if($u['is_banned']): ?><form method="POST" style="display:inline"><input type="hidden" name="action" value="unban_user"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><button class="bs b-unban">Unban</button></form><?php endif; ?>
              <?php if($isOwner&&$u['role']==='user'): ?><form method="POST" style="display:inline"><input type="hidden" name="action" value="make_admin"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><button class="bs b-prm">→ Admin</button></form><?php endif; ?>
              <?php if($isOwner&&$u['role']==='admin'): ?><form method="POST" style="display:inline"><input type="hidden" name="action" value="revoke_admin"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><button class="bs b-rev">Revoke</button></form><?php endif; ?>
              <?php if($u['alliance']): ?><form method="POST" style="display:inline" onsubmit="return confirm('Kick from alliance?')"><input type="hidden" name="action" value="kick_from_alliance"><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><button class="bs" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;">⚡ Kick</button></form><?php endif; ?>
            <?php endif; ?>
            <form method="POST" style="display:inline-flex;align-items:center;gap:4px;">
              <input type="hidden" name="action" value="set_alliance">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <select name="alliance_name" class="ia" style="padding:3px 7px;font-size:11px;height:26px;min-width:90px;">
                <option value="">No Alliance</option>
                <?php foreach($alliances as $al): ?>
                <option value="<?= htmlspecialchars($al['name']) ?>" <?= $u['alliance']===$al['name']?'selected':'' ?>><?= htmlspecialchars($al['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="bs" style="background:rgba(37,99,235,0.1);color:#2563eb;border:1px solid rgba(37,99,235,0.25);padding:3px 8px;font-size:11px;height:26px;">Set</button>
            </form>
          </div></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div></div>
  </div>

  <!-- Inactivity -->
  <div id="tab-inactivity" class="hidden">
    <div class="sc"><div class="sh"><h2 style="font-family:'Outfit',sans-serif;font-weight:700;color:var(--text-heading);">Inactivity Notices</h2><span style="padding:3px 12px;border-radius:99px;font-size:11px;font-weight:600;background:#fffbeb;color:#d97706;border:1px solid #fde68a;"><?= count($inactivityNotices) ?> total</span></div>
    <?php if(!$inactivityNotices): ?><div style="padding:40px;text-align:center;color:var(--text-subtle);font-size:13px;">No notices yet.</div>
    <?php else: ?><div style="overflow-x:auto;"><table class="at"><thead><tr><th>Airline</th><th>Alliance</th><th>Days</th><th>Reason</th><th>Message</th><th>Submitted</th></tr></thead><tbody>
    <?php foreach($inactivityNotices as $n): ?><tr>
      <td><span style="font-weight:600;color:var(--text-heading);"><?= htmlspecialchars($n['airline_name']) ?></span><br><span style="font-size:11px;color:var(--text-subtle);">@<?= htmlspecialchars($n['username']) ?></span></td>
      <td><span style="padding:2px 8px;border-radius:99px;font-size:11px;font-weight:600;background:#fffbeb;color:#d97706;border:1px solid #fde68a;"><?= htmlspecialchars($n['alliance']) ?></span></td>
      <td style="font-weight:700;color:#d97706;"><?= $n['days'] ?> day<?= $n['days']!=1?'s':'' ?></td>
      <td style="color:var(--text-muted);"><?= htmlspecialchars($n['reason']) ?></td>
      <td style="color:var(--text-subtle);font-size:12px;max-width:180px;"><?= $n['message']?htmlspecialchars($n['message']):'—' ?></td>
      <td style="color:var(--text-subtle);font-size:12px;"><?= date('M j, Y H:i',strtotime($n['created_at'])) ?></td>
    </tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?></div>
  </div>

  <!-- Appeals -->
  <?php if($isOwner): ?>
  <div id="tab-appeals" class="hidden">
    <div class="sc"><div class="sh"><h2 style="font-family:'Outfit',sans-serif;font-weight:700;color:var(--text-heading);">Unban Appeals</h2></div>
    <?php if(!$pendingAppeals): ?><div style="padding:40px;text-align:center;color:var(--text-subtle);font-size:13px;">No pending appeals.</div>
    <?php else: ?><div class="p-5 space-y-4">
      <?php foreach($pendingAppeals as $ap): ?>
      <div class="glass rounded-2xl p-5" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
        <div style="flex:1;"><p style="font-weight:600;color:var(--text-heading);"><?= htmlspecialchars($ap['username']) ?></p><p style="font-size:11px;color:var(--text-subtle);margin-bottom:6px;"><?= date('M j, Y H:i',strtotime($ap['created_at'])) ?></p><p style="font-size:13px;color:var(--text-muted);"><?= htmlspecialchars($ap['reason']) ?></p></div>
        <div style="display:flex;gap:8px;flex-shrink:0;">
          <form method="POST"><input type="hidden" name="action" value="approve_appeal"><input type="hidden" name="appeal_id" value="<?= $ap['id'] ?>"><button class="bs b-ok">✓ Approve</button></form>
          <form method="POST"><input type="hidden" name="action" value="reject_appeal"><input type="hidden" name="appeal_id" value="<?= $ap['id'] ?>"><button class="bs b-no">✕ Reject</button></form>
        </div>
      </div>
      <?php endforeach; ?>
    </div><?php endif; ?></div>
  </div>
  <?php endif; ?>

  <!-- Social Links -->
  <div id="tab-social" class="hidden">
    <div class="sc p-6">
      <h2 class="font-heading text-lg mb-5" style="font-weight:700;color:var(--text-heading);">Social Media Links</h2>
      <form method="POST" style="max-width:420px;" class="space-y-4">
        <input type="hidden" name="action" value="update_social_links">
        <div><label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Discord Server URL</label><input type="url" name="discord_url" class="ia" value="<?= htmlspecialchars($discordUrl) ?>" placeholder="https://discord.gg/..."></div>
        <div><label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">Instagram Profile URL</label><input type="url" name="instagram_url" class="ia" value="<?= htmlspecialchars($instagramUrl) ?>" placeholder="https://instagram.com/..."></div>
        <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-semibold">Save Links</button>
      </form>
    </div>
  </div>

</div>

<!-- Ban Modal -->
<div id="ban-modal" class="modal-bg">
  <div class="mbox">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <h3 style="font-family:'Outfit',sans-serif;font-weight:700;font-size:15px;color:var(--text-heading);">Ban User: <span id="ban-username" style="color:#e11d48;"></span></h3>
      <button onclick="document.getElementById('ban-modal').classList.remove('open')" style="background:none;border:none;cursor:pointer;color:var(--text-subtle);"><i data-lucide="x" style="width:15px;height:15px;"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="ban_user">
      <input type="hidden" name="user_id" id="ban-user-id">
      <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:5px;">Reason for ban</label>
      <input type="text" name="ban_reason" class="ia" style="margin-bottom:16px;" placeholder="Enter reason…" required>
      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn-danger flex-1 py-2.5 rounded-xl text-sm font-semibold">Confirm Ban</button>
        <button type="button" onclick="document.getElementById('ban-modal').classList.remove('open')" class="btn-ghost px-5 py-2.5 rounded-xl text-sm font-semibold">Cancel</button>
      </div>
    </form>
  </div>
</div>

</div>
<script>
function showTab(t,btn){document.querySelectorAll('[id^="tab-"]').forEach(el=>el.classList.add('hidden'));document.querySelectorAll('.tab-btn').forEach(b=>b.className='tab-btn');document.getElementById('tab-'+t).classList.remove('hidden');btn.className='tab-btn tab-active';}
function showBanModal(uid,un){document.getElementById('ban-user-id').value=uid;document.getElementById('ban-username').textContent=un;document.getElementById('ban-modal').classList.add('open');}
function filterUsers(q){q=q.toLowerCase();document.querySelectorAll('.user-row').forEach(r=>{r.style.display=r.dataset.search.includes(q)?'':'none';});}
lucide.createIcons();
</script>
</body>
</html>
