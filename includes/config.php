<?php
// ============================================================
// Configuration — values loaded from .env
// IMPORTANT: Never commit .env to version control.
// ============================================================

$_envFile = __DIR__ . '/../.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if ($_line[0] === '#' || !str_contains($_line, '=')) continue;
        [$_k, $_v] = array_map('trim', explode('=', $_line, 2));
        if (!empty($_k) && !isset($_ENV[$_k])) {
            $_ENV[$_k] = $_v;
            putenv("$_k=$_v");
        }
    }
}
unset($_envFile, $_line, $_k, $_v);

define('DB_HOST',    $_ENV['DB_HOST']    ?? 'localhost');
define('DB_NAME',    $_ENV['DB_NAME']    ?? 'haile');
define('DB_USER',    $_ENV['DB_USER']    ?? 'root');
define('DB_PASS',    $_ENV['DB_PASS']    ?? '');
define('DB_CHARSET', 'utf8mb4');

// Auto-detect BASE_URL from the request so it works on any host/IP
if (!empty($_ENV['BASE_URL'])) {
    define('BASE_URL', rtrim($_ENV['BASE_URL'], '/'));
} else {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $scheme . '://' . $host . '/haile');
}
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL',  BASE_URL . '/uploads/');

define('MAX_IMAGE_SIZE', (int)($_ENV['MAX_IMAGE_SIZE'] ?? 31457280));
define('MAX_VIDEO_SIZE', (int)($_ENV['MAX_VIDEO_SIZE'] ?? 2147483647)); // no practical limit

define('ALLOWED_IMAGE_EXTS',  ['jpg', 'jpeg', 'png', 'webp', 'avif']);
define('ALLOWED_VIDEO_EXTS',  ['mp4', 'webm', 'mkv', 'mov', 'avi', 'wmv', 'm4v', 'ogv', 'flv', '3gp']);
define('ALLOWED_IMAGE_MIMES', ['image/jpeg', 'image/png', 'image/webp', 'image/avif']);
define('ALLOWED_VIDEO_MIMES', [
    'video/mp4', 'video/webm', 'video/x-matroska', 'video/quicktime',
    'video/x-msvideo', 'video/x-ms-wmv', 'video/mp4', 'video/ogg',
    'video/x-flv', 'video/3gpp', 'application/octet-stream',
]);

define('SESSION_TIMEOUT', (int)($_ENV['SESSION_TIMEOUT'] ?? 7200));

if (session_status() === PHP_SESSION_NONE) session_start();
