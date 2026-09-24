<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
$rows = db()->query("SELECT id, title, price FROM projects")->fetchAll();
foreach ($rows as $r) echo $r['id'] . ' | ' . $r['title'] . ' | ' . $r['price'] . "\n";
