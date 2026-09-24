<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Settings';

// ── General settings ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_general'])) {
    verifyCsrf();
    foreach (['site_title','designer_title','site_description','copyright_text'] as $key) {
        $val = trim($_POST[$key] ?? '');
        db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$key, $val, $val]);
    }
    // Favicon
    if (!empty($_FILES['favicon']['name'])) {
        $favExt = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
        if ($favExt === 'ico') {
            $dir = UPLOAD_PATH . 'profile/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $filename = uniqid('favicon_', true) . '.ico';
            if (move_uploaded_file($_FILES['favicon']['tmp_name'], $dir . $filename)) {
                $path = 'uploads/profile/' . $filename;
                db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('favicon',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$path, $path]);
                copy(__DIR__ . '/../' . $path, $_SERVER['DOCUMENT_ROOT'] . '/favicon.ico');
            }
        } else {
            $result = saveUpload($_FILES['favicon'], 'profile');
            if ($result) {
                db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('favicon',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$result['path'], $result['path']]);
                copy(__DIR__ . '/../' . $result['path'], $_SERVER['DOCUMENT_ROOT'] . '/favicon.ico');
            }
        }
    }
    // Site logo
    if (!empty($_POST['site_logo_cropped'])) {
        $dataUrl = $_POST['site_logo_cropped'];
        if (preg_match('/^data:image\/(png|jpeg|webp);base64,/', $dataUrl, $m)) {
            $ext     = $m[1] === 'jpeg' ? 'jpg' : $m[1];
            $imgData = base64_decode(preg_replace('/^data:image\/[a-z]+;base64,/', '', $dataUrl));
            $dir     = UPLOAD_PATH . 'profile/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $filename = uniqid('logo_', true) . '.' . $ext;
            file_put_contents($dir . $filename, $imgData);
            $logoPath = 'uploads/profile/' . $filename;
            $old = db()->query("SELECT setting_value FROM settings WHERE setting_key='site_logo'")->fetchColumn();
            if ($old) deleteFile($old);
            db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_logo',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$logoPath, $logoPath]);
        }
    }
    if (!empty($_POST['remove_site_logo'])) {
        $old = db()->query("SELECT setting_value FROM settings WHERE setting_key='site_logo'")->fetchColumn();
        if ($old) deleteFile($old);
        db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_logo','') ON DUPLICATE KEY UPDATE setting_value=''")->execute([]);
    }
    flash('success', 'General settings saved.');
    header('Location: ' . BASE_URL . '/admin/settings.php'); exit;
}

// ── Contact Info settings ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_contact'])) {
    verifyCsrf();
    foreach (['contact_phone','contact_phone_2','contact_whatsapp','contact_telegram','contact_location','contact_location_url'] as $key) {
        $val = trim($_POST[$key] ?? '');
        db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$key, $val, $val]);
    }
    flash('success', 'Contact info saved.');
    header('Location: ' . BASE_URL . '/admin/settings.php#contact'); exit;
}

// ── Social Links settings ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_social'])) {
    verifyCsrf();
    foreach (['social_instagram','social_facebook','social_telegram','social_whatsapp'] as $key) {
        $val = trim($_POST[$key] ?? '');
        db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$key, $val, $val]);
    }
    flash('success', 'Social links saved.');
    header('Location: ' . BASE_URL . '/admin/settings.php#social'); exit;
}

// ── SMTP settings ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_smtp'])) {
    verifyCsrf();
    $smtpKeys = ['smtp_host','smtp_port','smtp_username','smtp_encryption','smtp_from_name','smtp_from_email','contact_email'];
    foreach ($smtpKeys as $key) {
        $val = trim($_POST[$key] ?? '');
        db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$key, $val, $val]);
    }
    if (($_POST['smtp_password'] ?? '') !== '') {
        $p = $_POST['smtp_password'];
        db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('smtp_password',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$p, $p]);
    }
    $enabled = isset($_POST['smtp_enabled']) ? '1' : '0';
    db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('smtp_enabled',?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$enabled, $enabled]);
    flash('success', 'Email settings saved.');
    header('Location: ' . BASE_URL . '/admin/settings.php#smtp'); exit;
}

// ── Account credentials ───────────────────────────────────
$accountErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_account'])) {
    verifyCsrf();
    $currentPassword = $_POST['current_password'] ?? '';
    $newEmail        = trim($_POST['new_email'] ?? '');
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $user = db()->prepare('SELECT * FROM users WHERE id = ?');
    $user->execute([$_SESSION['admin_id']]);
    $user = $user->fetch();

    if (!password_verify($currentPassword, $user['password'])) {
        $accountErrors[] = 'Current password is incorrect.';
    }
    if ($newEmail && $newEmail !== $user['email']) {
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $accountErrors[] = 'New email address is not valid.';
        } else {
            $taken = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $taken->execute([$newEmail, $user['id']]);
            if ($taken->fetch()) $accountErrors[] = 'That email is already in use.';
        }
    }
    if ($newPassword !== '') {
        if (strlen($newPassword) < 8) $accountErrors[] = 'New password must be at least 8 characters.';
        elseif ($newPassword !== $confirmPassword) $accountErrors[] = 'New passwords do not match.';
    }
    if (empty($accountErrors)) {
        $updateEmail    = $newEmail ?: $user['email'];
        $updatePassword = $newPassword !== '' ? password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]) : $user['password'];
        db()->prepare('UPDATE users SET email = ?, password = ? WHERE id = ?')->execute([$updateEmail, $updatePassword, $user['id']]);
        flash('success', 'Account credentials updated.');
        header('Location: ' . BASE_URL . '/admin/settings.php'); exit;
    }
}

$s = getAllSettings();
$currentUser = db()->prepare('SELECT name, email FROM users WHERE id = ?');
$currentUser->execute([$_SESSION['admin_id']]);
$currentUser = $currentUser->fetch();
$siteLogo = $s['site_logo'] ?? '';

$extraHead = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">';

include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<div class="page-header"><h2>Site Settings</h2></div>

<!-- ── General ───────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="admin-card">
                <div class="admin-card-header"><span class="admin-card-title">General</span></div>
                <div class="mb-3"><label class="form-label">Site Title <span class="form-text d-inline">(your name / studio name — used in nav &amp; footer)</span></label><input type="text" name="site_title" class="form-control" value="<?= e($s['site_title'] ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label">Designer Title <span class="form-text d-inline">(e.g. Graphic Designer — shown under name in nav, footer &amp; hero)</span></label><input type="text" name="designer_title" class="form-control" value="<?= e($s['designer_title'] ?? 'Graphic Designer') ?>"></div>

                <!-- ── Site Logo ── -->
                <div class="mb-3">
                    <label class="form-label">Site Logo <span class="form-text d-inline">(replaces the text name in the nav header)</span></label>
                    <?php if ($siteLogo): ?>
                    <div class="logo-current-wrap mb-2" style="display:flex;align-items:center;gap:1rem;padding:0.75rem 1rem;background:var(--surface2);border-radius:6px;border:1px solid var(--border)">
                        <img src="<?= e(assetUrl($siteLogo)) ?>" style="height:40px;max-width:180px;object-fit:contain" alt="Current logo">
                        <div style="flex:1;font-size:0.78rem;color:var(--text-muted)">Current logo</div>
                        <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.8rem;color:var(--text-muted);cursor:pointer">
                            <input type="checkbox" name="remove_site_logo" value="1" class="form-check-input" style="margin:0"> Remove
                        </label>
                    </div>
                    <?php endif; ?>
                    <input type="file" id="logoFileInput" class="form-control" accept="image/*" style="margin-bottom:0.5rem">
                    <input type="hidden" name="site_logo_cropped" id="siteLogoCropped">
                    <div class="form-text">PNG, SVG, JPG, WEBP — any size. You can crop after selecting.</div>
                    <!-- Crop preview strip -->
                    <div id="logoCropPreviewWrap" style="display:none;margin-top:0.75rem;padding:0.75rem;background:var(--surface2);border-radius:6px;border:1px solid var(--border)">
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:0.5rem">Cropped preview:</div>
                        <img id="logoCropPreview" src="" style="max-height:60px;max-width:240px;object-fit:contain;display:block" alt="">
                        <button type="button" id="reopenCropBtn" class="btn-admin-secondary" style="margin-top:0.5rem;padding:0.3rem 0.75rem;font-size:0.78rem"><i class="bi bi-crop"></i> Re-crop</button>
                    </div>
                </div>

                <div class="mb-3"><label class="form-label">Site Description</label><textarea name="site_description" class="form-control" rows="3"><?= e($s['site_description'] ?? '') ?></textarea></div>
                <div class="mb-3"><label class="form-label">Copyright Text</label><input type="text" name="copyright_text" class="form-control" value="<?= e($s['copyright_text'] ?? '') ?>"></div>
                <div class="mt-3 mb-1"><button type="submit" name="save_general" class="btn-admin-primary"><i class="bi bi-check-lg"></i> Save General</button></div>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="admin-card">
                <div class="admin-card-header"><span class="admin-card-title">Favicon</span></div>
                <?php if (!empty($s['favicon'])): ?>
                <img src="<?= e(assetUrl($s['favicon'])) ?>" style="width:48px;height:48px;object-fit:contain;margin-bottom:0.75rem;display:block" alt="">
                <?php endif; ?>
                <input type="file" name="favicon" class="form-control" accept=".jpg,.jpeg,.png,.webp,.ico">
                <div class="form-text mt-1">PNG or ICO recommended. 32×32 or 64×64px.</div>
                <div class="mt-3 mb-1"><button type="submit" name="save_general" class="btn-admin-primary"><i class="bi bi-check-lg"></i> Save Favicon</button></div>
            </div>
        </form>
    </div>
</div>

<!-- ── Contact Info ─────────────────────────────────────────── -->
<form method="POST" id="contact">
    <?= csrfField() ?>
    <div class="admin-card mb-4">
        <div class="admin-card-header"><span class="admin-card-title">Contact Info</span></div>
        <div class="mb-3"><label class="form-label">Phone 1</label><input type="text" name="contact_phone" class="form-control" value="<?= e($s['contact_phone'] ?? '') ?>" placeholder="+251 968 401 515"></div>
        <div class="mb-3"><label class="form-label">Phone 2 <span class="form-text d-inline">(optional second number)</span></label><input type="text" name="contact_phone_2" class="form-control" value="<?= e($s['contact_phone_2'] ?? '') ?>" placeholder="+251 911 338 550"></div>
        <div class="mb-3"><label class="form-label">WhatsApp Number / Handle</label><input type="text" name="contact_whatsapp" class="form-control" value="<?= e($s['contact_whatsapp'] ?? '') ?>" placeholder="haile_property"><div class="form-text">Used for the WhatsApp quick-contact button.</div></div>
        <div class="mb-3"><label class="form-label">Telegram Handle</label><input type="text" name="contact_telegram" class="form-control" value="<?= e($s['contact_telegram'] ?? '') ?>" placeholder="@Hailee_12"><div class="form-text">Used for the Telegram quick-contact button.</div></div>
        <div class="mb-3">
            <label class="form-label">Location <span class="form-text d-inline">(display text)</span></label>
            <input type="text" name="contact_location" class="form-control" value="<?= e($s['contact_location'] ?? '') ?>" placeholder="e.g. Addis Ababa, Ethiopia">
        </div>
        <div class="mb-3">
            <label class="form-label">Google Maps URL</label>
            <input type="url" name="contact_location_url" class="form-control" value="<?= e($s['contact_location_url'] ?? '') ?>" placeholder="https://maps.google.com/?q=...">
            <div class="form-text">Open <a href="https://maps.google.com" target="_blank">Google Maps</a>, search your location, click <strong>Share &rarr; Copy link</strong>, paste here.</div>
        </div>
        <div class="mt-3 mb-1"><button type="submit" name="save_contact" class="btn-admin-primary"><i class="bi bi-check-lg"></i> Save Contact Info</button></div>
    </div>
</form>

<!-- ── Social Links ──────────────────────────────────────────── -->
<form method="POST" id="social">
    <?= csrfField() ?>
    <div class="admin-card mb-4">
        <div class="admin-card-header"><span class="admin-card-title">Social Links</span></div>
        <div class="mb-3"><label class="form-label"><i class="bi bi-instagram me-1"></i>Instagram URL</label><input type="url" name="social_instagram" class="form-control" value="<?= e($s['social_instagram'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label"><i class="bi bi-facebook me-1"></i>Facebook URL</label><input type="url" name="social_facebook" class="form-control" value="<?= e($s['social_facebook'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label"><i class="bi bi-telegram me-1"></i>Telegram URL</label><input type="url" name="social_telegram" class="form-control" value="<?= e($s['social_telegram'] ?? '') ?>" placeholder="https://t.me/username"></div>
        <div class="mb-3"><label class="form-label"><i class="bi bi-whatsapp me-1"></i>WhatsApp URL</label><input type="url" name="social_whatsapp" class="form-control" value="<?= e($s['social_whatsapp'] ?? '') ?>" placeholder="https://wa.me/251..."></div>
        <div class="mt-3 mb-1"><button type="submit" name="save_social" class="btn-admin-primary"><i class="bi bi-check-lg"></i> Save Social Links</button></div>
    </div>
</form>

<!-- ── Email / SMTP Settings ──────────────────────────────── -->
<div class="admin-card mb-4" id="smtp">
    <div class="admin-card-header">
        <span class="admin-card-title">Email &amp; SMTP Settings</span>
        <?php if (($s['smtp_enabled'] ?? '0') === '1'): ?>
        <span style="font-size:0.78rem;color:var(--success)"><i class="bi bi-check-circle"></i> SMTP Active</span>
        <?php else: ?>
        <span style="font-size:0.78rem;color:var(--text-muted)"><i class="bi bi-exclamation-circle"></i> Using PHP mail() — may land in spam</span>
        <?php endif; ?>
    </div>

    <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:1.5rem;line-height:1.8;padding:0.85rem 1rem;background:var(--surface2);border-radius:6px;border-left:2px solid #444;">
        <strong style="color:var(--accent-dim)">Gmail setup:</strong>
        Host <code style="color:#ccc">smtp.gmail.com</code> &nbsp;·&nbsp; Port <code style="color:#ccc">587</code> &nbsp;·&nbsp; Encryption <code style="color:#ccc">TLS</code><br>
        Username = your Gmail address &nbsp;·&nbsp;
        Password = <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:#aaa;text-decoration:underline">App Password</a> (not your Gmail login password — requires 2FA enabled)<br>
        <strong style="color:var(--accent-dim)">cPanel hosting:</strong> use the SMTP details from cPanel &rarr; Email Accounts &rarr; Connect Devices.
    </div>

    <form method="POST">
        <?= csrfField() ?>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Receiving Email <span style="color:#e05555">*</span></label>
                <input type="email" name="contact_email" class="form-control"
                    value="<?= e($s['contact_email'] ?? '') ?>"
                    placeholder="your@email.com" required>
                <div class="form-text">Contact form messages are delivered to this address.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">From Name</label>
                <input type="text" name="smtp_from_name" class="form-control"
                    value="<?= e($s['smtp_from_name'] ?? 'Amanuel Yohannes') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">From Email</label>
                <input type="email" name="smtp_from_email" class="form-control"
                    value="<?= e($s['smtp_from_email'] ?? '') ?>"
                    placeholder="noreply@yourdomain.com">
            </div>
        </div>

        <hr class="divider">

        <div class="form-check mb-3">
            <input type="checkbox" name="smtp_enabled" id="smtpEnabled" class="form-check-input"
                <?= ($s['smtp_enabled'] ?? '0') === '1' ? 'checked' : '' ?>
                onchange="document.getElementById('smtpFields').style.display=this.checked?'block':'none'">
            <label for="smtpEnabled" class="form-check-label">Enable SMTP <span style="color:var(--text-muted);font-size:0.8rem">(recommended — required for Gmail)</span></label>
        </div>

        <div id="smtpFields" style="display:<?= ($s['smtp_enabled'] ?? '0') === '1' ? 'block' : 'none' ?>">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control"
                        value="<?= e($s['smtp_host'] ?? '') ?>"
                        placeholder="smtp.gmail.com">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Port</label>
                    <input type="number" name="smtp_port" class="form-control"
                        value="<?= e($s['smtp_port'] ?? '587') ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Encryption</label>
                    <select name="smtp_encryption" class="form-select">
                        <option value="tls"  <?= ($s['smtp_encryption'] ?? 'tls') === 'tls'  ? 'selected' : '' ?>>TLS — port 587 (recommended)</option>
                        <option value="ssl"  <?= ($s['smtp_encryption'] ?? '') === 'ssl'  ? 'selected' : '' ?>>SSL — port 465</option>
                        <option value="none" <?= ($s['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None — port 25</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">SMTP Username</label>
                    <input type="text" name="smtp_username" class="form-control"
                        value="<?= e($s['smtp_username'] ?? '') ?>"
                        placeholder="your@gmail.com" autocomplete="off">
                </div>
                <div class="col-md-6">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" name="smtp_password" class="form-control"
                        placeholder="<?= !empty($s['smtp_password']) ? 'Saved — leave blank to keep unchanged' : 'App password or SMTP password' ?>"
                        autocomplete="new-password">
                    <?php if (!empty($s['smtp_password'])): ?>
                    <div class="form-text">A password is saved. Leave blank to keep it.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" name="save_smtp" class="btn-admin-primary">
                <i class="bi bi-envelope-check"></i> Save Email Settings
            </button>
        </div>
    </form>
</div>

<!-- ── Account Credentials ────────────────────────────────── -->
<div class="admin-card">
    <div class="admin-card-header">
        <span class="admin-card-title">Account Credentials</span>
        <span style="font-size:0.78rem;color:var(--text-muted)">Signed in as <?= e($currentUser['email']) ?></span>
    </div>

    <?php if (!empty($accountErrors)): ?>
    <div class="alert alert-danger mb-3"><?= implode('<br>', array_map('e', $accountErrors)) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <?= csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Current Password <span style="color:#e05555">*</span></label>
                <input type="password" name="current_password" class="form-control" required autocomplete="current-password" placeholder="Required to make any changes">
            </div>
        </div>
        <hr class="divider">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">New Email Address</label>
                <input type="email" name="new_email" class="form-control" value="<?= e($currentUser['email']) ?>" autocomplete="off">
                <div class="form-text">Leave unchanged to keep current email.</div>
            </div>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" autocomplete="new-password" placeholder="Leave blank to keep current password" minlength="8">
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" autocomplete="new-password" placeholder="Repeat new password">
            </div>
        </div>
        <div class="form-text mt-1 mb-3">Minimum 8 characters. Leave both password fields blank to change only the email.</div>
        <button type="submit" name="save_account" class="btn-admin-primary">
            <i class="bi bi-shield-lock"></i> Update Credentials
        </button>
    </form>
</div>

<!-- Crop Modal -->
<div id="cropModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.88);align-items:center;justify-content:center;flex-direction:column">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1.5rem;max-width:92vw;width:700px;max-height:90vh;display:flex;flex-direction:column;gap:1rem">
        <span style="font-size:0.9rem;font-weight:600">Crop Logo</span>
        <div style="overflow:auto;max-height:60vh;background:#111;border-radius:6px;display:flex;align-items:center;justify-content:center">
            <img id="cropperImg" src="" style="max-width:100%;display:block">
        </div>
        <div style="display:flex;gap:0.75rem;justify-content:flex-end;flex-wrap:wrap">
            <button type="button" id="cropCancelBtn" class="btn-admin-secondary">Cancel</button>
            <button type="button" id="cropSkipBtn" class="btn-admin-secondary"><i class="bi bi-skip-forward"></i> Use original</button>
            <button type="button" id="cropConfirmBtn" class="btn-admin-primary"><i class="bi bi-check-lg"></i> Apply crop</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
(function(){
    const fileInput   = document.getElementById('logoFileInput');
    const hiddenInput = document.getElementById('siteLogoCropped');
    const previewWrap = document.getElementById('logoCropPreviewWrap');
    const previewImg  = document.getElementById('logoCropPreview');
    const reopenBtn   = document.getElementById('reopenCropBtn');
    const modal       = document.getElementById('cropModal');
    const cropperImg  = document.getElementById('cropperImg');
    const confirmBtn  = document.getElementById('cropConfirmBtn');
    const cancelBtn   = document.getElementById('cropCancelBtn');
    const skipBtn     = document.getElementById('cropSkipBtn');
    let cropper = null, originalDataUrl = null;

    function openModal(src) {
        cropperImg.src = src;
        modal.style.display = 'flex';
        if (cropper) { cropper.destroy(); cropper = null; }
        setTimeout(() => {
            cropper = new Cropper(cropperImg, {
                viewMode: 1, autoCropArea: 1,
                movable: true, zoomable: true,
                rotatable: false, scalable: false, background: false
            });
        }, 100);
    }
    function closeModal() {
        modal.style.display = 'none';
        if (cropper) { cropper.destroy(); cropper = null; }
    }

    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => { originalDataUrl = e.target.result; openModal(originalDataUrl); };
        reader.readAsDataURL(file);
    });
    confirmBtn.addEventListener('click', function() {
        if (!cropper) return;
        const dataUrl = cropper.getCroppedCanvas({ maxWidth: 1200, maxHeight: 600, imageSmoothingQuality: 'high' }).toDataURL('image/png');
        hiddenInput.value = dataUrl;
        previewImg.src = dataUrl;
        previewWrap.style.display = 'block';
        closeModal();
    });
    skipBtn.addEventListener('click', function() {
        hiddenInput.value = originalDataUrl;
        previewImg.src = originalDataUrl;
        previewWrap.style.display = 'block';
        closeModal();
    });
    cancelBtn.addEventListener('click', function() {
        fileInput.value = ''; hiddenInput.value = '';
        previewWrap.style.display = 'none';
        closeModal();
    });
    if (reopenBtn) reopenBtn.addEventListener('click', () => { if (originalDataUrl) openModal(originalDataUrl); });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
