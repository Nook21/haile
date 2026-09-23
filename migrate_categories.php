<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$stmts = [
    "ALTER TABLE `categories` ADD COLUMN IF NOT EXISTS `description` varchar(300) DEFAULT NULL AFTER `slug`",
    "ALTER TABLE `categories` ADD COLUMN IF NOT EXISTS `image` varchar(255) DEFAULT NULL AFTER `description`",
];

echo '<pre>';
foreach ($stmts as $sql) {
    try { db()->exec($sql); echo "OK: $sql\n"; }
    catch (PDOException $e) { echo "ERR: " . $e->getMessage() . "\n"; }
}
echo 'Done.</pre>';
