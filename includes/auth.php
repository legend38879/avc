<?php
require_once __DIR__ . '/config.php';

// Start secure session
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// Get current user from session (returns array or null)
function getCurrentUser() {
    startSession();
    if (!isset($_SESSION['user_id'])) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// Require login — redirect to login page if not authenticated
function requireLogin() {
    $user = getCurrentUser();
    if (!$user) {
        header('Location: ' . SITE_URL . '/pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    return $user;
}

// Require admin or owner role
function requireAdmin() {
    $user = requireLogin();
    if (!in_array($user['role'], ['admin', 'owner'])) {
        header('Location: ' . SITE_URL . '/index.php?error=access_denied');
        exit;
    }
    return $user;
}

// Require owner role
function requireOwner() {
    $user = requireLogin();
    if ($user['role'] !== 'owner') {
        header('Location: ' . SITE_URL . '/index.php?error=access_denied');
        exit;
    }
    return $user;
}

// Check if user is banned
function checkBanned($user) {
    if ($user && $user['is_banned']) {
        header('Location: ' . SITE_URL . '/pages/banned.php');
        exit;
    }
}

// Clean input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Add notification for a user
function addNotification($userId, $message) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$userId, $message]);
}

// Get unread notification count
function getUnreadCount($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// Security headers — prevent cross-origin framing (fixes chrome-error:// iframe issue)
function sendSecurityHeaders() {
    if (!headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
