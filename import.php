<?php
// One-time database import script — DELETE AFTER USE
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $sql = file_get_contents(__DIR__ . '/database.sql');

    // Split on semicolons but skip empty statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $ok = 0; $errors = [];
    foreach ($statements as $stmt) {
        if (!$stmt || str_starts_with($stmt, '--')) continue;
        try {
            $pdo->exec($stmt);
            $ok++;
        } catch (PDOException $e) {
            $errors[] = htmlspecialchars($e->getMessage()) . '<br><small>' . htmlspecialchars(substr($stmt, 0, 120)) . '</small>';
        }
    }

    echo '<style>body{font-family:sans-serif;max-width:700px;margin:3rem auto;padding:0 1rem;}</style>';
    echo '<h2>Database Import</h2>';
    echo '<p style="color:green">✓ ' . $ok . ' statements executed successfully.</p>';
    if ($errors) {
        echo '<p style="color:orange">⚠ ' . count($errors) . ' non-fatal errors (usually safe to ignore):</p><ul>';
        foreach ($errors as $e) echo '<li style="font-size:0.85rem;margin-bottom:0.5rem">' . $e . '</li>';
        echo '</ul>';
    }
    echo '<hr><p><strong>Database <code>haile</code> is ready.</strong></p>';
    echo '<p>Now visit <a href="/haile/">http://localhost/haile/</a> to see your site.</p>';
    echo '<p style="color:red;font-weight:bold">⚠ Delete this file (import.php) after use!</p>';

} catch (PDOException $e) {
    echo '<p style="color:red">Connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
