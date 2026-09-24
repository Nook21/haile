<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
$st = db()->query("SELECT setting_key, setting_value FROM settings WHERE setting_key = 'favicon'");
$row = $st->fetch();
echo '<pre>';
if ($row) {
    echo 'DB value: ' . htmlspecialchars($row['setting_value']) . "\n";
    $path = __DIR__ . '/' . ltrim($row['setting_value'], '/');
    echo 'File exists: ' . (file_exists($path) ? 'YES' : 'NO') . "\n";
    echo 'Full path: ' . $path;
} else {
    echo 'No favicon row found in settings table.';
}
echo '</pre>';
