<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Testimonials';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_testimonial'])) {
    verifyCsrf();
    db()->prepare('DELETE FROM testimonials WHERE id = ?')->execute([(int)$_POST['testimonial_id']]);
    flash('success', 'Testimonial deleted.');
    header('Location: ' . BASE_URL . '/admin/testimonials.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_testimonial'])) {
    verifyCsrf();
    $id     = (int)($_POST['testimonial_id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');
    $title  = trim($_POST['person_title'] ?? '');
    $body   = trim($_POST['body'] ?? '');
    $rating = min(5, max(1, (int)($_POST['rating'] ?? 5)));
    $order  = (int)($_POST['display_order'] ?? 0);
    $status = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';

    if ($id) {
        db()->prepare('UPDATE testimonials SET name=?,person_title=?,body=?,rating=?,display_order=?,status=? WHERE id=?')
            ->execute([$name,$title,$body,$rating,$order,$status,$id]);
        flash('success', 'Testimonial updated.');
    } else {
        db()->prepare('INSERT INTO testimonials (name,person_title,body,rating,display_order,status) VALUES (?,?,?,?,?,?)')
            ->execute([$name,$title,$body,$rating,$order,$status]);
        flash('success', 'Testimonial created.');
    }
    header('Location: ' . BASE_URL . '/admin/testimonials.php'); exit;
}

$testimonials = db()->query('SELECT * FROM testimonials ORDER BY display_order ASC, id ASC')->fetchAll();
$editing = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare('SELECT * FROM testimonials WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $editing = $st->fetch() ?: null;
}

include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <h2>Testimonials</h2>
    <a href="?new=1" class="btn-admin-primary"><i class="bi bi-plus-lg"></i> New Testimonial</a>
</div>

<?php if (isset($_GET['new']) || $editing): ?>
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <span class="admin-card-title"><?= $editing ? 'Edit Testimonial' : 'New Testimonial' ?></span>
        <a href="<?= BASE_URL ?>/admin/testimonials.php" class="btn-admin-secondary" style="padding:0.3rem 0.75rem;font-size:0.78rem">Cancel</a>
    </div>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="testimonial_id" value="<?= $editing ? $editing['id'] : 0 ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Client Name *</label>
                <input type="text" name="name" class="form-control" value="<?= e($editing['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Title / Role</label>
                <input type="text" name="person_title" class="form-control" value="<?= e($editing['person_title'] ?? '') ?>" placeholder="e.g. Residential Client">
            </div>
            <div class="col-12">
                <label class="form-label">Testimonial *</label>
                <textarea name="body" class="form-control" rows="4" required><?= e($editing['body'] ?? '') ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Rating (1–5)</label>
                <input type="number" name="rating" class="form-control" value="<?= $editing['rating'] ?? 5 ?>" min="1" max="5">
            </div>
            <div class="col-md-4">
                <label class="form-label">Display Order</label>
                <input type="number" name="display_order" class="form-control" value="<?= $editing['display_order'] ?? 0 ?>" min="0">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= ($editing['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($editing['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" name="save_testimonial" class="btn-admin-primary"><i class="bi bi-check-lg"></i> Save</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header"><span class="admin-card-title">All Testimonials (<?= count($testimonials) ?>)</span></div>
    <?php if ($testimonials): ?>
    <table class="admin-table">
        <thead><tr><th>Name</th><th>Role</th><th>Rating</th><th>Status</th><th>Order</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($testimonials as $t): ?>
        <tr>
            <td><?= e($t['name']) ?></td>
            <td style="color:var(--text-muted);font-size:0.82rem"><?= e($t['person_title'] ?? '—') ?></td>
            <td style="color:#e0a030"><?= str_repeat('★', (int)$t['rating']) ?></td>
            <td><span class="badge-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
            <td><?= $t['display_order'] ?></td>
            <td class="table-actions">
                <a href="?edit=<?= $t['id'] ?>" class="btn-admin-secondary" style="padding:0.3rem 0.65rem;font-size:0.78rem"><i class="bi bi-pencil"></i></a>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="testimonial_id" value="<?= $t['id'] ?>">
                    <button type="submit" name="delete_testimonial" class="btn-admin-danger" style="padding:0.3rem 0.65rem;font-size:0.78rem" data-confirm="Delete this testimonial?"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:var(--text-muted);font-size:0.88rem;padding:1rem 0">No testimonials yet.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
