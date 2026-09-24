<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php'); exit;
}

$error = '';
$info  = '';
$view  = $_GET['view'] ?? 'login';

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_login'])) {
    verifyCsrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        $st = db()->prepare('SELECT id, name, password FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $user = $st->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']      = $user['id'];
            $_SESSION['admin_name']    = $user['name'];
            $_SESSION['last_activity'] = time();
            header('Location: ' . BASE_URL . '/admin/dashboard.php'); exit;
        }
    }
    $error = 'Invalid email or password.';
    $view  = 'login';
}

// Request reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_forgot'])) {
    verifyCsrf();
    $email = trim($_POST['email'] ?? '');
    $view  = 'forgot';
    $st = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $user = $st->fetch();
    if ($user) {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        db()->prepare("INSERT INTO settings (setting_key,setting_value) VALUES ('pw_reset_token',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$token,$token]);
        db()->prepare("INSERT INTO settings (setting_key,setting_value) VALUES ('pw_reset_expires',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$expires,$expires]);
        db()->prepare("INSERT INTO settings (setting_key,setting_value) VALUES ('pw_reset_uid',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$user['id'],$user['id']]);

        $resetLink = BASE_URL . '/admin/login.php?view=reset&token=' . $token;
        $s = getAllSettings();

        require_once __DIR__ . '/../includes/mailer.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            if (($s['smtp_enabled'] ?? '0') === '1') {
                $mail->isSMTP();
                $mail->Host       = $s['smtp_host'] ?? '';
                $mail->SMTPAuth   = true;
                $mail->Username   = $s['smtp_username'] ?? '';
                $mail->Password   = $s['smtp_password'] ?? '';
                $enc = $s['smtp_encryption'] ?? 'tls';
                $mail->SMTPSecure = $enc === 'ssl' ? 'ssl' : 'tls';
                $mail->Port       = (int)($s['smtp_port'] ?? 587);
            }
            $fromEmail = $s['smtp_from_email'] ?: ($s['contact_email'] ?? 'noreply@localhost');
            $fromName  = $s['smtp_from_name']  ?: 'HAILE';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email);
            $mail->Subject = 'Password Reset — HAILE Admin';
            $mail->isHTML(true);
            $mail->Body = '
<div style="font-family:system-ui,sans-serif;max-width:480px;margin:0 auto;padding:2rem;background:#0f0f0f;color:#eee;border-radius:12px">
  <p style="font-size:1.1rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#fff;margin:0 0 0.25rem">HAILE</p>
  <p style="font-size:0.75rem;color:#666;letter-spacing:0.12em;text-transform:uppercase;margin:0 0 2rem">Admin Panel</p>
  <p style="color:#aaa;font-size:0.95rem;line-height:1.7">You requested a password reset. Click the button below — this link expires in <strong style="color:#fff">1 hour</strong>.</p>
  <a href="' . $resetLink . '" style="display:inline-block;margin:1.5rem 0;background:#c9a84c;color:#000;padding:0.85rem 2rem;border-radius:8px;font-weight:700;text-decoration:none;letter-spacing:0.05em">Reset Password</a>
  <p style="color:#555;font-size:0.78rem">If you did not request this, ignore this email.</p>
</div>';
            $mail->AltBody = "Reset your password: $resetLink\n\nExpires in 1 hour.";
            $mail->send();
        } catch (\Exception $e) { /* silent */ }
    }
    $info = 'If that email exists, a reset link has been sent. Check your inbox.';
}

// Reset password
$resetToken = $_GET['token'] ?? '';
if ($view === 'reset' && $resetToken) {
    $s       = getAllSettings();
    $stored  = $s['pw_reset_token']   ?? '';
    $expires = $s['pw_reset_expires'] ?? '';
    if (!hash_equals($stored, $resetToken) || strtotime($expires) < time()) {
        $error = 'This reset link is invalid or has expired.';
        $view  = 'login';
        $resetToken = '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_reset'])) {
    verifyCsrf();
    $token   = $_POST['token'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $view    = 'reset';
    $s       = getAllSettings();
    $stored  = $s['pw_reset_token']   ?? '';
    $expires = $s['pw_reset_expires'] ?? '';

    if (!hash_equals($stored, $token) || strtotime($expires) < time()) {
        $error = 'Reset link is invalid or expired. Please request a new one.';
        $view  = 'login';
    } elseif (strlen($newPass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($newPass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $uid  = (int)($s['pw_reset_uid'] ?? 0);
        $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $uid]);
        foreach (['pw_reset_token','pw_reset_expires','pw_reset_uid'] as $k) {
            db()->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=''")->execute([$k,'']);
        }
        $info = 'Password updated! You can now sign in.';
        $view = 'login';
    }
}

$timeout = isset($_GET['timeout']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin — Haile Real Estate Advisor</title>
<?php $favicon = getSetting('favicon'); if ($favicon): ?>
<link rel="icon" href="<?= e(assetUrl($favicon)) ?>">
<?php endif; ?>
<link rel="preload" as="image" href="../insight-3.jpg" fetchpriority="high">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Segoe UI', system-ui, sans-serif;
  overflow: hidden;
  position: relative;
  background: #080808 url('../insight-3.jpg') center / cover no-repeat;
}

/* ── Background image ── */
.bg-image {
  position: fixed;
  inset: 0;
  z-index: 0;
  background: url('../insight-3.jpg') center / cover no-repeat;
  animation: kenBurns 22s ease-in-out infinite alternate;
  transform-origin: center;
}
@keyframes kenBurns {
  from { transform: scale(1) translate(0, 0); }
  to   { transform: scale(1.12) translate(-2%, -2%); }
}

/* ── Dark blur overlay ── */
.bg-overlay {
  position: fixed;
  inset: 0;
  z-index: 1;
  background: rgba(5, 4, 2, 0.65);
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
}

/* ── Gold orbs ── */
.bg-orbs {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 2;
}
.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(90px);
  opacity: 0;
  animation: orbFloat 9s ease-in-out infinite;
}
.orb1 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(201,168,76,0.25), transparent 70%);
  top: -10%; left: -10%;
  animation-delay: 0s;
}
.orb2 {
  width: 380px; height: 380px;
  background: radial-gradient(circle, rgba(201,168,76,0.15), transparent 70%);
  bottom: -10%; right: -10%;
  animation-delay: -4s;
}
.orb3 {
  width: 280px; height: 280px;
  background: radial-gradient(circle, rgba(232,201,106,0.12), transparent 70%);
  top: 40%; left: 55%;
  animation-delay: -7s;
}
@keyframes orbFloat {
  0%,100% { opacity: 0; transform: scale(0.85) translate(0, 0); }
  30%     { opacity: 1; }
  50%     { opacity: 1; transform: scale(1.1) translate(18px, -18px); }
  70%     { opacity: 1; }
}

/* ── Film grain ── */
.bg-grain {
  position: fixed;
  inset: 0;
  z-index: 3;
  pointer-events: none;
  opacity: 0.035;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
  background-size: 180px;
  animation: grainShift 0.5s steps(1) infinite;
}
@keyframes grainShift {
  0%  { background-position: 0 0; }
  25% { background-position: -30px 20px; }
  50% { background-position: 20px -30px; }
  75% { background-position: -20px -20px; }
}

/* ── Card ── */
.login-wrap {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 420px;
  padding: 1rem;
}
.login-card {
  background: rgba(8, 7, 4, 0.78);
  border: 1px solid rgba(201, 168, 76, 0.22);
  border-radius: 16px;
  padding: 2.5rem 2.25rem;
  backdrop-filter: blur(36px) saturate(1.4);
  -webkit-backdrop-filter: blur(36px) saturate(1.4);
  box-shadow:
    0 0 0 1px rgba(201,168,76,0.06),
    0 1px 0 rgba(201,168,76,0.18) inset,
    0 40px 100px rgba(0,0,0,0.8);
  animation: cardIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes cardIn {
  from { opacity: 0; transform: translateY(28px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* ── Brand ── */
.login-brand { margin-bottom: 2rem; }
.login-logo {
  font-size: 1.3rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 0.55rem;
  margin-bottom: 0.35rem;
}
.login-logo-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: linear-gradient(135deg, #c9a84c, #e8c96a);
  animation: dotPulse 2.2s ease-in-out infinite;
  flex-shrink: 0;
}
@keyframes dotPulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(201,168,76,0.7); }
  50%     { box-shadow: 0 0 0 7px rgba(201,168,76,0); }
}
.login-tagline {
  font-size: 0.7rem;
  color: #666;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.login-tagline::before {
  content: '';
  display: inline-block;
  width: 20px; height: 1px;
  background: linear-gradient(to right, #c9a84c, #e8c96a);
}

/* ── View title ── */
.view-title {
  font-size: 0.92rem;
  font-weight: 600;
  color: #ddd;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.6rem;
}
.view-title i { color: #c9a84c; }

/* ── Fields ── */
.field { margin-bottom: 1.25rem; }
.field label {
  display: block;
  font-size: 0.72rem;
  color: #666;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
}
.field input {
  width: 100%;
  background: rgba(0,0,0,0.5);
  border: 1px solid #2a2a2a;
  color: #fff;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 0.25s, box-shadow 0.25s;
}
.field input:focus {
  border-color: #c9a84c;
  box-shadow: 0 0 0 3px rgba(201,168,76,0.15);
}
.field input::placeholder { color: #444; }

/* ── Button ── */
.btn-primary-login {
  width: 100%;
  background: linear-gradient(135deg, #c9a84c, #e8c96a);
  color: #0d0d0a;
  border: none;
  border-radius: 8px;
  padding: 0.82rem;
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  position: relative;
  overflow: hidden;
}
.btn-primary-login::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,0.18), transparent);
  opacity: 0;
  transition: opacity 0.2s;
}
.btn-primary-login:hover { opacity: 0.9; transform: translateY(-1px); }
.btn-primary-login:hover::after { opacity: 1; }
.btn-primary-login:active { transform: translateY(0); }

.btn-ghost {
  background: none;
  border: none;
  color: #666;
  font-size: 0.8rem;
  cursor: pointer;
  padding: 0;
  letter-spacing: 0.04em;
  transition: color 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  text-decoration: none;
}
.btn-ghost:hover { color: #c9a84c; }

/* ── Alerts ── */
.alert {
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
  margin-bottom: 1.25rem;
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  animation: alertIn 0.4s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes alertIn {
  from { opacity: 0; transform: translateY(-8px); }
  to   { opacity: 1; transform: translateY(0); }
}
.alert-err  { background: rgba(200,50,50,0.12);  border: 1px solid rgba(200,50,50,0.25);  color: #f88; }
.alert-info { background: rgba(80,180,100,0.1);  border: 1px solid rgba(80,180,100,0.2);  color: #8d8; }
.alert-warn { background: rgba(200,150,50,0.1);  border: 1px solid rgba(200,150,50,0.2);  color: #fc8; }

/* ── Divider ── */
.divider {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 1.25rem 0;
  color: #333;
  font-size: 0.72rem;
  letter-spacing: 0.1em;
}
.divider::before, .divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #222;
}

/* ── View animation ── */
.view { animation: viewIn 0.4s cubic-bezier(0.16,1,0.3,1) both; }
@keyframes viewIn {
  from { opacity: 0; transform: translateX(12px); }
  to   { opacity: 1; transform: translateX(0); }
}
</style>
</head>
<body>

<div class="bg-image"></div>
<div class="bg-overlay"></div>
<div class="bg-orbs">
  <div class="orb orb1"></div>
  <div class="orb orb2"></div>
  <div class="orb orb3"></div>
</div>
<div class="bg-grain"></div>

<div class="login-wrap">
  <div class="login-card">

    <div class="login-brand">
      <div class="login-logo">
        <span class="login-logo-dot"></span>
        HAILE
      </div>
      <div class="login-tagline">Real Estate Advisor</div>
    </div>

    <?php if ($timeout): ?>
    <div class="alert alert-warn"><i class="bi bi-clock"></i> Session expired. Please sign in again.</div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-err"><i class="bi bi-exclamation-circle"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($info): ?>
    <div class="alert alert-info"><i class="bi bi-check-circle"></i> <?= e($info) ?></div>
    <?php endif; ?>

    <?php if ($view === 'login'): ?>
    <div class="view">
      <div class="view-title"><i class="bi bi-shield-lock"></i> Admin Access</div>
      <form method="POST" novalidate>
        <?= csrfField() ?>
        <div class="field">
          <label>Email</label>
          <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com" required autofocus>
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" name="do_login" class="btn-primary-login">
          Sign In <i class="bi bi-arrow-right"></i>
        </button>
      </form>
      <div class="divider">or</div>
      <div style="text-align:center">
        <a href="?view=forgot" class="btn-ghost"><i class="bi bi-key"></i> Forgot password?</a>
      </div>
    </div>

    <?php elseif ($view === 'forgot'): ?>
    <div class="view">
      <div class="view-title"><i class="bi bi-envelope-open"></i> Reset Password</div>
      <p style="font-size:0.83rem;color:#666;margin-bottom:1.25rem;line-height:1.7">Enter your admin email and we'll send a reset link.</p>
      <form method="POST" novalidate>
        <?= csrfField() ?>
        <div class="field">
          <label>Email</label>
          <input type="email" name="email" placeholder="you@example.com" required autofocus>
        </div>
        <button type="submit" name="do_forgot" class="btn-primary-login">
          Send Reset Link <i class="bi bi-send"></i>
        </button>
      </form>
      <div class="divider">or</div>
      <div style="text-align:center">
        <a href="?view=login" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back to sign in</a>
      </div>
    </div>

    <?php elseif ($view === 'reset' && $resetToken): ?>
    <div class="view">
      <div class="view-title"><i class="bi bi-lock-fill"></i> Set New Password</div>
      <form method="POST" novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="token" value="<?= e($resetToken) ?>">
        <div class="field">
          <label>New Password</label>
          <input type="password" name="new_password" placeholder="Min. 8 characters" required autofocus minlength="8">
        </div>
        <div class="field">
          <label>Confirm Password</label>
          <input type="password" name="confirm_password" placeholder="Repeat password" required>
        </div>
        <button type="submit" name="do_reset" class="btn-primary-login">
          Update Password <i class="bi bi-check-lg"></i>
        </button>
      </form>
      <div class="divider">or</div>
      <div style="text-align:center">
        <a href="?view=login" class="btn-ghost"><i class="bi bi-arrow-left"></i> Back to sign in</a>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

</body>
</html>
