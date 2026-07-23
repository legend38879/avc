<?php
require_once __DIR__ . '/../includes/auth.php';
error_reporting(0); ini_set('display_errors',0);
header('Content-Type: application/json');

$user = getCurrentUser();
if (!$user) { echo json_encode(['error'=>'Not logged in']); exit; }

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $room  = strtolower(trim($_GET['room'] ?? 'public'));
    $after = (int)($_GET['after'] ?? 0);
    $allowed = ['public','sky team 2.0','aura union','prime united'];
    $allianceRooms = ['sky team 2.0','aura union','prime united'];
    if (!in_array($room,$allowed)) $room='public';
    if (in_array($room,$allianceRooms) && strtolower($user['alliance'])!==$room) {
        echo json_encode(['messages'=>[]]); exit;
    }

    $stmt = $db->prepare("
        SELECT m.id, m.message, m.created_at,
               u.username, u.airline_name, u.profile_photo, u.logo_zoom
        FROM messages m
        JOIN users u ON u.id=m.user_id
        WHERE LOWER(m.room)=? AND m.id>?
        ORDER BY m.id ASC LIMIT 60
    ");
    $stmt->execute([$room,$after]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $msgs = [];
    foreach ($rows as $r) {
        $zd = $r['logo_zoom'] ? json_decode($r['logo_zoom'],true) : null;
        $msgs[] = [
            'id'             => (int)$r['id'],
            'message'        => $r['message'],
            'username'       => $r['username'],
            'airline_name'   => $r['airline_name'],
            'profile_photo'  => $r['profile_photo'],
            'logo_scale'     => $zd['scale']   ?? 1,
            'logo_offset_x'  => $zd['offsetX'] ?? 0,
            'logo_offset_y'  => $zd['offsetY'] ?? 0,
            'time'           => date('H:i', strtotime($r['created_at']))
        ];
    }
    echo json_encode(['messages'=>$msgs]); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body    = json_decode(file_get_contents('php://input'),true);
    $room    = strtolower(trim($body['room'] ?? 'public'));
    $message = trim($body['message'] ?? '');
    if (!$message || strlen($message)>500) { echo json_encode(['error'=>'Invalid message']); exit; }
    $allowed=['public','sky team 2.0','aura union','prime united'];
    if (!in_array($room,$allowed)) { echo json_encode(['error'=>'Invalid room']); exit; }
    if (in_array($room,['sky team 2.0','aura union','prime united']) && strtolower($user['alliance'])!==$room) {
        echo json_encode(['error'=>'Access denied']); exit;
    }
    $db->prepare("INSERT INTO messages (user_id,room,message) VALUES (?,?,?)")->execute([$user['id'],$room,$message]);
    echo json_encode(['success'=>true]); exit;
}

echo json_encode(['error'=>'Method not allowed']);
