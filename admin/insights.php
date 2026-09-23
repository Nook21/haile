<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Insights';

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_insight'])) {
    verifyCsrf();
    $id = (int)$_POST['insight_id'];
    $row = db()->prepare('SELECT cover_image FROM insights WHERE id = ?');
    $row->execute([$id]);
    $ins = $row->fetch();
    if ($ins && $ins['cover_image']) deleteFile($ins['cover_image']);
    db()->prepare('DELETE FROM insights WHERE id = ?')->execute([$id]);
    flash('success', 'Insight deleted.');
    header('Location: ' . BASE_URL . '/admin/insights.php'); exit;
}

// Save (create or update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_insight'])) {
    verifyCsrf();
    $id       = (int)($_POST['insight_id'] ?? 0);
    $title    = trim($_POST['title'] ?? '');
    $slug     = makeSlug(trim($_POST['slug'] ?? '') ?: $title);
    $excerpt  = trim($_POST['excerpt'] ?? '');
    $body     = trim($_POST['body'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status   = $_POST['status'] === 'published' ? 'published' : 'draft';
    $order    = (int)($_POST['display_order'] ?? 0);

    $coverPath = $id ? (db()->prepare('SELECT cover_image FROM insights WHERE id=?')->execute([$id]) ? db()->prepare('SELECT cover_image FROM insights WHERE id=?') : null) : null;
    if ($id) {
        $st = db()->prepare('SELECT cover_image FROM insights WHERE id=?');
        $st->execute([$id]);
        $coverPath = $st->fetchColumn() ?: '';
    } else {
        $coverPath = '';
    }

    if (!empty($_FILES['cover_image']['name'])) {
        $result = saveUpload($_FILES['cover_image'], 'covers');
        if ($result) {
            if ($coverPath) deleteFile($coverPath);
            $coverPath = $result['path'];
        }
    }

    if ($id) {
        db()->prepare('UPDATE insights SET title=?,slug=?,excerpt=?,body=?,category=?,cover_image=?,status=?,display_order=? WHERE id=?')
            ->execute([$title, $slug, $excerpt, $body, $category, $coverPath, $status, $order, $id]);
        flash('success', 'Insight updated.');
    } else {
        db()->prepare('INSERT INTO insights (title,slug,excerpt,body,category,cover_image,status,display_order) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$title, $slug, $excerpt, $body, $category, $coverPath, $status, $order]);
        flash('success', 'Insight created.');
    }
    header('Location: ' . BASE_URL . '/admin/insights.php'); exit;
}

$insights = db()->query('SELECT * FROM insights ORDER BY display_order ASC, created_at DESC')->fetchAll();
$editing  = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare('SELECT * FROM insights WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $editing = $st->fetch() ?: null;
}

include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <h2>Insights</h2>
    <a href="?new=1" class="btn-admin-primary"><i class="bi bi-plus-lg"></i> New Insight</a>
</div>

<?php if (isset($_GET['new']) || $editing): ?>
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <span class="admin-card-title"><?= $editing ? 'Edit Insight' : 'New Insight' ?></span>
        <a href="<?= BASE_URL ?>/admin/insights.php" class="btn-admin-secondary" style="padding:0.3rem 0.75rem;font-size:0.78rem">Cancel</a>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="insight_id" value="<?= $editing ? $editing['id'] : 0 ?>">
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="mb-3">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" id="insightTitle" class="form-control" value="<?= e($editing['title'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="insightSlug" class="form-control" value="<?= e($editing['slug'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Excerpt</label>
                    <textarea name="excerpt" class="form-control" rows="3" maxlength="400"><?= e($editing['excerpt'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Body</label>
                    <textarea name="body" class="form-control" rows="10"><?= e($editing['body'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft" <?= ($editing['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($editing['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" value="<?= e($editing['category'] ?? '') ?>" placeholder="e.g. Market Insights">
                </div>
                <div class="mb-3">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="<?= e($editing['display_order'] ?? 0) ?>" min="0">
                </div>
                <div class="mb-3">
                    <label class="form-label">Cover Image</label>
                    <?php if (!empty($editing['cover_image'])): ?>
                    <img src="<?= e(assetUrl($editing['cover_image'])) ?>" class="cover-preview mb-2" alt="">
                    <?php endif; ?>
                    <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                </div>
            </div>
        </div>
        <button type="submit" name="save_insight" class="btn-admin-primary"><i class="bi bi-check-lg"></i> Save Insight</button>
    </form>
</div>
<script>
(function(){
    const t = document.getElementById('insightTitle');
    const s = document.getElementById('insightSlug');
    if (t && s && !s.value) {
        t.addEventListener('input', () => {
            s.value = t.value.toLowerCase().trim().replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').replace(/^-|-$/g,'');
        });
    }
})();
</script>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header"><span class="admin-card-title">All Insights (<?= count($insights) ?>)</span></div>
    <?php if ($insights): ?>
    <table class="admin-table">
        <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Order</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($insights as $ins): ?>
        <tr>
            <td><?= e($ins['title']) ?></td>
            <td><?= e($ins['category'] ?? '—') ?></td>
            <td><span class="status-badge status-<?= $ins['status'] ?>"><?= $ins['status'] ?></span></td>
            <td><?= $ins['display_order'] ?></td>
            <td class="table-actions">
                <a href="?edit=<?= $ins['id'] ?>" class="btn-admin-secondary" style="padding:0.3rem 0.65rem;font-size:0.78rem"><i class="bi bi-pencil"></i></a>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="insight_id" value="<?= $ins['id'] ?>">
                    <button type="submit" name="delete_insight" class="btn-admin-danger" style="padding:0.3rem 0.65rem;font-size:0.78rem" data-confirm="Delete this insight?"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:var(--text-muted);font-size:0.88rem;padding:1rem 0">No insights yet. Click "New Insight" to create one.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
