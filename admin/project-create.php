<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Add Property';

$categories = getCategories();
$clients    = db()->query('SELECT id, name FROM clients WHERE status="active" ORDER BY name ASC')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title     = trim($_POST['title'] ?? '');
    $slug      = trim($_POST['slug'] ?? '');
    $catId     = (int)($_POST['category_id'] ?? 0) ?: null;
    $clientId  = (int)($_POST['client_id'] ?? 0) ?: null;
    $year      = (int)($_POST['year'] ?? 0) ?: null;
    $shortDesc = trim($_POST['short_description'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $featured  = isset($_POST['featured']) ? 1 : 0;
    $favorite  = isset($_POST['favorite']) ? 1 : 0;
    $status    = $_POST['status'] === 'published' ? 'published' : 'draft';
    $order     = (int)($_POST['display_order'] ?? 0);
    $location  = trim($_POST['location'] ?? '');
    $price     = trim($_POST['price'] ?? '');
    $bedrooms  = (int)($_POST['bedrooms'] ?? 0) ?: null;
    $bathrooms = (int)($_POST['bathrooms'] ?? 0) ?: null;
    $area      = trim($_POST['area'] ?? '');

    if (!$title) $errors[] = 'Title is required.';
    if (!$slug)  $slug = makeSlug($title);
    else         $slug = makeSlug($slug);

    // Check slug uniqueness
    if ($slug) {
        $exists = db()->prepare('SELECT id FROM projects WHERE slug = ?');
        $exists->execute([$slug]);
        if ($exists->fetch()) $errors[] = 'Slug already exists. Choose a different one.';
    }

    // Cover image
    $coverPath = null;
    if (!empty($_FILES['cover_image']['name'])) {
        $result = saveUpload($_FILES['cover_image'], 'covers');
        if ($result) $coverPath = $result['path'];
        else $errors[] = 'Cover image upload failed. Check file type and size.';
    }

    if (empty($errors)) {
        $st = db()->prepare('INSERT INTO projects
            (category_id, client_id, title, slug, short_description, description, year, cover_image, featured, favorite, status, display_order, location, price, bedrooms, bathrooms, area)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([$catId, $clientId, $title, $slug, $shortDesc, $desc, $year, $coverPath, $featured, $favorite, $status, $order, $location, $price, $bedrooms, $bathrooms, $area]);
        $newId = db()->lastInsertId();
        flash('success', 'Property created. Now upload media files.');
        header('Location: ' . BASE_URL . '/admin/project-edit.php?id=' . $newId);
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<?php if ($errors): ?>
<div class="alert alert-danger mb-3"><?= implode('<br>', array_map('e', $errors)) ?></div>
<?php endif; ?>

<div class="page-header">
    <h2>Add Property</h2>
    <a href="<?= BASE_URL ?>/admin/projects.php" class="btn-admin-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="admin-card">
                <div class="admin-card-header"><span class="admin-card-title">Property Details</span></div>

                <div class="mb-3">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" id="titleInput" class="form-control" value="<?= e($_POST['title'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="slugInput" class="form-control" value="<?= e($_POST['slug'] ?? '') ?>" placeholder="auto-generated from title">
                    <div class="form-text">URL: /project.php?slug=<span id="slugPreview"></span></div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">— Select Category —</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Client</label>
                        <select name="client_id" class="form-select">
                            <option value="">— Select Client —</option>
                            <?php foreach ($clients as $cl): ?>
                            <option value="<?= $cl['id'] ?>" <?= ($_POST['client_id'] ?? '') == $cl['id'] ? 'selected' : '' ?>><?= e($cl['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Short Description</label>
                    <input type="text" name="short_description" class="form-control" value="<?= e($_POST['short_description'] ?? '') ?>" maxlength="300" placeholder="One-line summary shown in property grid">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="6" placeholder="Full property description"><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="admin-card-header" style="margin:1.5rem -1.5rem 1rem;padding:0.75rem 1.5rem;border-top:1px solid var(--border)"><span class="admin-card-title" style="font-size:0.82rem">Property Details</span></div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= e($_POST['location'] ?? '') ?>" placeholder="e.g. Bole, Addis Ababa">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Price</label>
                        <input type="text" name="price" class="form-control" value="<?= e($_POST['price'] ?? '') ?>" placeholder="e.g. $2,400,000">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Bedrooms</label>
                        <input type="number" name="bedrooms" class="form-control" value="<?= e($_POST['bedrooms'] ?? '') ?>" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bathrooms</label>
                        <input type="number" name="bathrooms" class="form-control" value="<?= e($_POST['bathrooms'] ?? '') ?>" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Floor Area</label>
                        <input type="text" name="area" class="form-control" value="<?= e($_POST['area'] ?? '') ?>" placeholder="e.g. 620 sqm">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="admin-card mb-3">
                <div class="admin-card-header"><span class="admin-card-title">Publish</span></div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft" <?= ($_POST['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($_POST['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control" value="<?= e($_POST['year'] ?? date('Y')) ?>" min="2000" max="2099">
                </div>
                <div class="mb-3">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="<?= e($_POST['display_order'] ?? 0) ?>" min="0">
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="featured" id="featured" class="form-check-input" <?= isset($_POST['featured']) ? 'checked' : '' ?>>
                    <label for="featured" class="form-check-label">Featured on homepage</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="favorite" id="favorite" class="form-check-input" <?= isset($_POST['favorite']) ? 'checked' : '' ?>>
                    <label for="favorite" class="form-check-label">Favourite / Highlighted</label>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header"><span class="admin-card-title">Cover Image</span></div>
                <div id="coverPlaceholder" class="cover-placeholder mb-2"><i class="bi bi-image"></i></div>
                <img id="coverPreview" class="cover-preview mb-2" style="display:none" src="" alt="">
                <input type="file" name="cover_image" id="coverInput" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif">
                <div class="form-text mt-1">JPG, PNG, WEBP, AVIF — max 10MB</div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-2">
        <button type="submit" class="btn-admin-primary"><i class="bi bi-check-lg"></i> Add Property</button>
        <a href="<?= BASE_URL ?>/admin/projects.php" class="btn-admin-secondary">Cancel</a>
    </div>
</form>

<script>
const slugInput = document.getElementById('slugInput');
const slugPreview = document.getElementById('slugPreview');
if (slugInput && slugPreview) {
    const update = () => slugPreview.textContent = slugInput.value;
    slugInput.addEventListener('input', update);
    update();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
