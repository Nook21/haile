<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
$rows = [
    ['hero_eyebrow', 'Senior Property Consultant / Managing Director'],
    ['hero_bg_image', ''],
];
foreach ($rows as [$k, $v]) {
    db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=setting_value")->execute([$k, $v]);
}
echo 'Done. <a href="/haile/">View site</a> — delete this file.';
