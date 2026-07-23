<?php
// ============================================================
// CONFIGURATION — Edit these before uploading to InfinityFree
// ============================================================

define('DB_HOST', 'sql100.infinityfree.com'); // Your InfinityFree DB host
define('DB_USER', 'if0_40433583');          // Your DB username (e.g. if3691234_user)
define('DB_PASS', 'ameer197aloo');          // Your DB password
define('DB_NAME', 'if0_40433583_aerovibescentral');              // Your DB name (e.g. if3691234_am4)

define('SITE_URL', 'https://avc.page.gd'); // Your site URL (no trailing slash)
define('SESSION_NAME', 'am4alliance_sess');

// ============================================================
// DATABASE CONNECTION (PDO)
// ============================================================
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed.']));
        }
    }
    return $pdo;
}
