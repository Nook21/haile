<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
$affected = db()->exec("UPDATE projects SET price = REPLACE(price, '$', 'ETB ')");
echo "Updated $affected rows.";
