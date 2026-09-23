<?php
/**
 * SETUP SCRIPT — Run once after importing database.sql
 * Access: http://localhost/pro/setup.php
 * DELETE THIS FILE after use.
 */

// Prevent running on production if a lock file exists
if (file_exists(__DIR__ . '/setup.lock')) {
    die('Setup already completed. Delete setup.lock to run again.');
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$done   = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $name     = trim($_POST['name']     ?? 'Amanuel Yohannes');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($password) < 8)  $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $existing = db()->prepare('SELECT id FROM users WHERE email = ?');
        $existing->execute([$email]);
        if ($existing->fetch()) {
            db()->prepare('UPDATE users SET name=?, password=? WHERE email=?')->execute([$name, $hash, $email]);
        } else {
            db()->prepare('INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)')->execute([$name, $email, $hash, 'admin']);
        }
        // Write lock file
        file_put_contents(__DIR__ . '/setup.lock', date('Y-m-d H:i:s'));
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Portfolio Setup</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
body { background:#0f0f0f; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Segoe UI',sans-serif; }
.card { background:#1a1a1a; border:1px solid #2a2a2a; border-radius:12px; padding:2.5rem; width:100%; max-width:420px; color:#e8e8e8; }
h1 { font-size:1.1rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; margin-bottom:0.25rem; }
.sub { font-size:0.75rem; color:#666; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:2rem; }
label { font-size:0.78rem; color:#aaa; text-transform:uppercase; letter-spacing:0.06em; }
input { background:#111; border:1px solid #333; color:#fff; border-radius:8px; padding:0.7rem 1rem; width:100%; margin-top:0.3rem; margin-bottom:1rem; font-size:0.9rem; }
input:focus { outline:none; border-color:#555; }
button { background:#fff; color:#000; border:none; border-radius:8px; padding:0.75rem; width:100%; font-weight:600; font-size:0.9rem; cursor:pointer; }
button:hover { background:#e0e0e0; }
.err { background:#2a0f0f; border:1px solid #4a1a1a; color:#f08080; border-radius:8px; padding:0.75rem 1rem; font-size:0.85rem; margin-bottom:1rem; }
.ok  { background:#0f2a1a; border:1px solid #1a4a2a; color:#4caf7d; border-radius:8px; padding:1rem; font-size:0.9rem; text-align:center; }
</style>
</head>
<body>
<div class="card">
    <div>
        <h1>Portfolio CMS</h1>
        <div class="sub">Initial Setup</div>
    </div>

    <?php if ($done): ?>
    <div class="ok">
        <strong>Setup complete.</strong><br>
        Admin account created.<br><br>
        <strong>Delete setup.php from your server.</strong><br><br>
        <a href="<?= BASE_URL ?>/admin/login.php" style="color:#4caf7d;">Go to Admin Login →</a>
    </div>
    <?php else: ?>

    <?php if ($errors): ?>
    <div class="err"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Full Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? 'Amanuel Yohannes') ?>" required>

        <label>Admin Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

        <label>Password (min 8 characters)</label>
        <input type="password" name="password" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm" required>

        <button type="submit">Create Admin Account</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
