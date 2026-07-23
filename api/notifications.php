<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

$user = getCurrentUser();
if (!$user) { echo json_encode(['notifications' => [], 'unread' => 0]); exit; }

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mark all as read
    $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$user['id']]);
    echo json_encode(['success' => true]); exit;
}

// GET notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$user['id']]);
$notifs = $stmt->fetchAll();

$unread = count(array_filter($notifs, fn($n) => !$n['is_read']));

echo json_encode([
    'notifications' => array_map(fn($n) => [
        'id'      => (int)$n['id'],
        'message' => $n['message'],
        'is_read' => (bool)$n['is_read'],
        'time'    => date('M j, g:i a', strtotime($n['created_at'])),
    ], $notifs),
    'unread' => $unread
]);
