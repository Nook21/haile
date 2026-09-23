<?php
// Run once then delete
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$sql = file_get_contents(__DIR__ . '/migrate_realestate.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));
$errors = []; $ok = 0;
foreach ($statements as $stmt) {
    if (!$stmt) continue;
    try { db()->exec($stmt); $ok++; } catch (PDOException $e) { $errors[] = $e->getMessage(); }
}
echo '<pre>';
echo "Done: $ok statements\n";
if ($errors) echo "Errors:\n" . implode("\n", $errors);
echo '</pre>';
