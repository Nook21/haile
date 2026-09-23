<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

require_once __DIR__ . '/../../includes/functions.php';

// Session timeout check
if (!empty($_SESSION['admin_id'])) {
    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/admin/login?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
} else {
    header('Location: ' . BASE_URL . '/admin/login');
    exit;
}
