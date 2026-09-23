<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'About';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name    = trim($_POST['name'] ?? '');
    $title   = trim($_POST['title'] ?? '');
    $bio     = trim($_POST['biography'] ?? '');

    $about = db()->query('SELECT * FROM about LIMIT 1')->fetch();
    $profileImage     = $about['profile_image'] ?? null;
    $profileImageDark = $about['profile_image_dark'] ?? null;

    if (!empty($_FILES['profile_image']['name'])) {
        $newImg = saveUpload($_FILES['profile_image'], 'profile');
        if ($newImg) {
            if ($profileImage) deleteFile($profileImage);
            $profileImage = $newImg['path'];
        } else {
            flash('error', 'Light image upload failed.');
            header('Location: ' . BASE_URL . '/admin/about.php'); exit;
        }
    }

    if (!empty($_FILES['profile_image_dark']['name'])) {
        $newImgDark = saveUpload($_FILES['profile_image_dark'], 'profile');
        if ($newImgDark) {
            if ($profileImageDark) deleteFile($profileImageDark);
            $profileImageDark = $newImgDark['path'];
        } else {
            flash('error', 'Dark image upload failed.');
            header('Location: ' . BASE_URL . '/admin/about.php'); exit;
        }
    }

    // Handle remove dark image
    if (!empty($_POST['remove_dark_image'])) {
        if ($profileImageDark) deleteFile($profileImageDark);
        $profileImageDark = null;
    }

    if ($about) {
        db()->prepare('UPDATE about SET name=?,title=?,biography=?,profile_image=?,profile_image_dark=? WHERE id=?')
            ->execute([$name, $title, $bio, $profileImage, $profileImageDark, $about['id']]);
    } else {
        db()->prepare('INSERT INTO about (name,title,biography,profile_image,profile_image_dark) VALUES (?,?,?,?,?)')
            ->execute([$name, $title, $bio, $profileImage, $profileImageDark]);
    }

    db()->prepare("UPDATE settings SET setting_value=? WHERE setting_key='profile_image'")->execute([$profileImage]);

    flash('success', 'About page updated.');
    header('Location: ' . BASE_URL . '/admin/about.php'); exit;
}

$about = db()->query('SELECT * FROM about LIMIT 1')->fetch();
include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <h2>About</h2>
    <a href="<?= BASE_URL ?>/about.php" target="_blank" class="btn-admin-secondary"><i class="bi bi-box-arrow-up-right"></i> View Page</a>
</div>

<form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="admin-card">
                <div class="admin-card-header"><span class="admin-card-title">About Content</span></div>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?= e($about['name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Professional Title</label>
                    <input type="text" name="title" class="form-control" value="<?= e($about['title'] ?? '') ?>" placeholder="e.g. Graphic Designer">
                </div>
                <div class="mb-3">
                    <label class="form-label">Biography</label>
                    <textarea name="biography" class="form-control" rows="12"><?= e($about['biography'] ?? '') ?></textarea>
                    <div class="form-text">Use line breaks for paragraphs.</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="admin-card mb-3">
                <div class="admin-card-header"><span class="admin-card-title">Profile Image — Light Mode</span></div>
                <?php if (!empty($about['profile_image'])): ?>
                <img src="<?= e(assetUrl($about['profile_image'])) ?>" id="coverPreview" class="cover-preview mb-2" alt="">
                <div id="coverPlaceholder" class="cover-placeholder mb-2" style="display:none"><i class="bi bi-person"></i></div>
                <?php else: ?>
                <div id="coverPlaceholder" class="cover-placeholder mb-2"><i class="bi bi-person"></i></div>
                <img id="coverPreview" class="cover-preview mb-2" style="display:none" src="" alt="">
                <?php endif; ?>
                <input type="file" name="profile_image" id="coverInput" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                <div class="form-text mt-1">Shown in light mode. JPG, PNG, WEBP — max 10MB</div>
            </div>
            <div class="admin-card">
                <div class="admin-card-header"><span class="admin-card-title">Profile Image — Dark Mode</span></div>
                <?php if (!empty($about['profile_image_dark'])): ?>
                <img src="<?= e(assetUrl($about['profile_image_dark'])) ?>" id="darkPreview" class="cover-preview mb-2" alt="">
                <div id="darkPlaceholder" class="cover-placeholder mb-2" style="display:none"><i class="bi bi-moon"></i></div>
                <div class="mb-2">
                    <label class="form-check-label" style="font-size:0.8rem;color:var(--text-muted)">
                        <input type="checkbox" name="remove_dark_image" value="1" class="form-check-input me-1"> Remove dark image (fall back to light)
                    </label>
                </div>
                <?php else: ?>
                <div id="darkPlaceholder" class="cover-placeholder mb-2"><i class="bi bi-moon"></i></div>
                <img id="darkPreview" class="cover-preview mb-2" style="display:none" src="" alt="">
                <?php endif; ?>
                <input type="file" name="profile_image_dark" id="darkInput" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                <div class="form-text mt-1">Shown in dark mode. If not set, light image is used. JPG, PNG, WEBP — max 10MB</div>
            </div>
        </div>
    </div>
    <div class="mt-2">
        <button type="submit" class="btn-admin-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
