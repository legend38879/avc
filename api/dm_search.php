<?php
require_once __DIR__ . '/../includes/auth.php';
error_reporting(0); ini_set('display_errors',0);
header('Content-Type: application/json');

$user = getCurrentUser();
if (!$user) { echo json_encode(['users'=>[]]); exit; }
$db = getDB();

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) { echo json_encode(['users'=>[]]); exit; }

$stmt = $db->prepare("
    SELECT id, airline_name, username, country
    FROM users
    WHERE is_banned=0 AND id != ?
      AND (airline_name LIKE ? OR username LIKE ?)
    ORDER BY airline_name ASC
    LIMIT 15
");
$stmt->execute([$user['id'], "%$q%", "%$q%"]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$users = [];
foreach ($rows as $r) {
    $users[] = [
        'id'           => (int)$r['id'],
        'airline_name' => $r['airline_name'],
        'username'     => $r['username'],
        'country'      => $r['country'] ?? '',
        'initials'     => strtoupper(substr($r['airline_name']??$r['username']??'?',0,2)),
    ];
}
echo json_encode(['users'=>$users]);
