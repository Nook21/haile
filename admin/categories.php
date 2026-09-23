<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Categories';

// Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    verifyCsrf();
    $name  = trim($_POST['name'] ?? '');
    $slug  = makeSlug(trim($_POST['slug'] ?? '') ?: $name);
    $desc  = trim($_POST['description'] ?? '');
    $order = (int)($_POST['display_order'] ?? 0);
    $img   = '';
    if (!empty($_FILES['image']['name'])) {
        $r = saveUpload($_FILES['image'], 'covers');
        if ($r) $img = $r['path'];
    }
    if ($name && $slug) {
        try {
            db()->prepare('INSERT INTO categories (name,slug,description,image,display_order) VALUES (?,?,?,?,?)')->execute([$name,$slug,$desc,$img,$order]);
            flash('success', 'Category added.');
        } catch (PDOException $e) { flash('error', 'Slug already exists.'); }
    }
    header('Location: ' . BASE_URL . '/admin/categories.php'); exit;
}

// Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    verifyCsrf();
    $cid   = (int)$_POST['cat_id'];
    $name  = trim($_POST['name'] ?? '');
    $slug  = makeSlug(trim($_POST['slug'] ?? '') ?: $name);
    $desc  = trim($_POST['description'] ?? '');
    $order = (int)($_POST['display_order'] ?? 0);

    // Get existing image
    $existing = db()->prepare('SELECT image FROM categories WHERE id=?');
    $existing->execute([$cid]);
    $img = $existing->fetchColumn() ?: '';

    if (!empty($_FILES['image']['name'])) {
        $r = saveUpload($_FILES['image'], 'covers');
        if ($r) {
            if ($img) deleteFile($img);
            $img = $r['path'];
        }
    }
    if (!empty($_POST['remove_image'])) {
        if ($img) deleteFile($img);
        $img = '';
    }

    if ($name && $slug) {
        try {
            db()->prepare('UPDATE categories SET name=?,slug=?,description=?,image=?,display_order=? WHERE id=?')->execute([$name,$slug,$desc,$img,$order,$cid]);
            flash('success', 'Category updated.');
        } catch (PDOException $e) { flash('error', 'Slug already exists.'); }
    }
    header('Location: ' . BASE_URL . '/admin/categories.php'); exit;
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    verifyCsrf();
    $cid = (int)$_POST['cat_id'];
    $row = db()->prepare('SELECT image FROM categories WHERE id=?');
    $row->execute([$cid]);
    $img = $row->fetchColumn();
    if ($img) deleteFile($img);
    db()->prepare('UPDATE projects SET category_id = NULL WHERE category_id = ?')->execute([$cid]);
    db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$cid]);
    flash('success', 'Category deleted.');
    header('Location: ' . BASE_URL . '/admin/categories.php'); exit;
}

$categories = db()->query('SELECT c.*, COUNT(p.id) AS project_count FROM categories c LEFT JOIN projects p ON p.category_id = c.id GROUP BY c.id ORDER BY c.display_order ASC, c.name ASC')->fetchAll();
$editing = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare('SELECT * FROM categories WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $editing = $st->fetch() ?: null;
}
include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <h2>Categories</h2>
    <a href="?new=1" class="btn-admin-primary"><i class="bi bi-plus-lg"></i> Add Category</a>
</div>

<?php if (isset($_GET['new']) || $editing): ?>
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <span class="admin-card-title"><?= $editing ? 'Edit: ' . e($editing['name']) : 'New Category' ?></span>
        <a href="<?= BASE_URL ?>/admin/categories.php" class="btn-admin-secondary" style="padding:0.3rem 0.75rem;font-size:0.78rem">Cancel</a>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="cat_id" value="<?= $editing ? $editing['id'] : 0 ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name *</label>
                <input type="text" name="name" id="catName" class="form-control" value="<?= e($editing['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" id="catSlug" class="form-control" value="<?= e($editing['slug'] ?? '') ?>" placeholder="auto-generated">
            </div>
            <div class="col-md-3">
                <label class="form-label">Display Order</label>
                <input type="number" name="display_order" class="form-control" value="<?= $editing['display_order'] ?? 0 ?>" min="0">
            </div>
            <div class="col-12">
                <label class="form-label">Description <span class="form-text d-inline">(shown on hover in the categories section)</span></label>
                <input type="text" name="description" class="form-control" value="<?= e($editing['description'] ?? '') ?>" maxlength="300" placeholder="e.g. Curated luxury homes and private residences">
            </div>
            <div class="col-12">
                <label class="form-label">Category Image <span class="form-text d-inline">(background image for the category tile)</span></label>
                <?php if (!empty($editing['image'])): ?>
                <div style="margin-bottom:0.75rem;display:flex;align-items:center;gap:1rem">
                    <img src="<?= e(assetUrl($editing['image'])) ?>" style="height:80px;width:140px;object-fit:cover;border-radius:6px;border:1px solid var(--border)" alt="">
                    <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.82rem;color:var(--text-muted);cursor:pointer">
                        <input type="checkbox" name="remove_image" value="1" class="form-check-input" style="margin:0"> Remove image
                    </label>
                </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                <div class="form-text">Recommended: 800×600px JPG. This image appears as the tile background.</div>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" name="<?= $editing ? 'edit_category' : 'add_category' ?>" class="btn-admin-primary">
                <i class="bi bi-check-lg"></i> <?= $editing ? 'Save Changes' : 'Add Category' ?>
            </button>
        </div>
    </form>
</div>
<script>
(function(){
    const n = document.getElementById('catName');
    const s = document.getElementById('catSlug');
    if (n && s && !s.value) {
        n.addEventListener('input', () => {
            s.value = n.value.toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').replace(/^-+|-+$/g,'');
        });
    }
})();
</script>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header"><span class="admin-card-title">All Categories (<?= count($categories) ?>)</span></div>
    <table class="admin-table">
        <thead><tr><th>Image</th><th>Name</th><th>Description</th><th>Properties</th><th>Order</th><th></th></tr></thead>
        <tbody id="sortableList" data-endpoint="reorder-categories.php">
        <?php foreach ($categories as $cat): ?>
        <tr data-id="<?= $cat['id'] ?>">
            <td>
                <?php if (!empty($cat['image'])): ?>
                <img src="<?= e(assetUrl($cat['image'])) ?>" style="width:64px;height:44px;object-fit:cover;border-radius:4px" alt="">
                <?php else: ?>
                <div style="width:64px;height:44px;background:var(--surface2);border-radius:4px;display:flex;align-items:center;justify-content:center;color:#555"><i class="bi bi-image"></i></div>
                <?php endif; ?>
            </td>
            <td>
                <i class="bi bi-grip-vertical order-handle me-2"></i>
                <strong><?= e($cat['name']) ?></strong>
                <div style="font-size:0.75rem;color:var(--text-muted)"><?= e($cat['slug']) ?></div>
            </td>
            <td style="color:var(--text-muted);font-size:0.82rem;max-width:220px"><?= e($cat['description'] ?? '—') ?></td>
            <td style="color:var(--text-muted);font-size:0.82rem"><?= $cat['project_count'] ?></td>
            <td style="color:var(--text-muted);font-size:0.82rem"><?= $cat['display_order'] ?></td>
            <td class="table-actions">
                <a href="?edit=<?= $cat['id'] ?>" class="btn-admin-secondary" style="padding:0.3rem 0.65rem;font-size:0.78rem"><i class="bi bi-pencil"></i></a>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                    <button type="submit" name="delete_category" class="btn-admin-danger" style="padding:0.3rem 0.65rem;font-size:0.78rem"
                        data-confirm="Delete '<?= e(addslashes($cat['name'])) ?>'? Projects will become uncategorized.">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem">No categories yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
