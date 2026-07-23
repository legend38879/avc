<?php
require_once __DIR__ . '/../includes/auth.php';
startSession();
session_destroy();
header('Location: ' . SITE_URL . '/index.php');
exit;
