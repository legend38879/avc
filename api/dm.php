<?php
require_once __DIR__ . '/../includes/auth.php';
error_reporting(0); ini_set('display_errors',0);
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache');

$user = getCurrentUser();
if (!$user) { echo json_encode(['error'=>'Not logged in']); exit; }
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $withId = (int)($_GET['with'] ?? 0);
    $after  = (int)($_GET['after']  ?? 0);
    if (!$withId || $withId === $user['id']) { echo json_encode(['messages'=>[]]); exit; }

    // Verify other user exists and not banned
    $chk = $db->prepare("SELECT id FROM users WHERE id=? AND is_banned=0");
    $chk->execute([$withId]);
    if (!$chk->fetch()) { echo json_encode(['messages'=>[]]); exit; }

    // Fetch messages in this thread
    $stmt = $db->prepare("
        SELECT dm.id, dm.from_id, dm.body, dm.created_at,
               u.airline_name AS from_airline, u.username AS from_username
        FROM direct_messages dm
        JOIN users u ON u.id = dm.from_id
        WHERE ((dm.from_id=? AND dm.to_id=?) OR (dm.from_id=? AND dm.to_id=?))
          AND dm.id > ?
        ORDER BY dm.id ASC
        LIMIT 80
    ");
    $stmt->execute([$user['id'],$withId,$withId,$user['id'],$after]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark incoming as read
    $db->prepare("UPDATE direct_messages SET is_read=1 WHERE from_id=? AND to_id=? AND is_read=0")
       ->execute([$withId,$user['id']]);

    $msgs = [];
    foreach ($rows as $r) {
        $msgs[] = [
            'id'           => (int)$r['id'],
            'from_id'      => (int)$r['from_id'],
            'from_airline' => $r['from_airline'],
            'from_username'=> $r['from_username'],
            'body'         => $r['body'],
            'time'         => date('H:i', strtotime($r['created_at'])),
        ];
    }
    echo json_encode(['messages'=>$msgs]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body  = json_decode(file_get_contents('php://input'), true);
    $toId  = (int)($body['to_id'] ?? 0);
    $msg   = trim($body['body'] ?? '');

    if (!$toId || $toId === $user['id']) { echo json_encode(['error'=>'Invalid recipient']); exit; }
    if (!$msg || mb_strlen($msg) > 1000)  { echo json_encode(['error'=>'Invalid message']); exit; }

    // Verify recipient exists
    $chk = $db->prepare("SELECT id FROM users WHERE id=? AND is_banned=0");
    $chk->execute([$toId]);
    if (!$chk->fetch()) { echo json_encode(['error'=>'Recipient not found']); exit; }

    $db->prepare("INSERT INTO direct_messages (from_id, to_id, body) VALUES (?,?,?)")
       ->execute([$user['id'], $toId, $msg]);

    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['error'=>'Method not allowed']);
