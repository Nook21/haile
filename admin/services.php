<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Services';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    verifyCsrf();
    $title  = trim($_POST['title'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $icon   = trim($_POST['icon'] ?? '');
    $order  = (int)($_POST['display_order'] ?? 0);
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    if ($title) {
        db()->prepare('INSERT INTO services (title, description, icon, display_order, status) VALUES (?,?,?,?,?)')->execute([$title, $desc, $icon, $order, $status]);
        flash('success', 'Service added.');
    }
    header('Location: ' . BASE_URL . '/admin/services.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_service'])) {
    verifyCsrf();
    $sid    = (int)$_POST['service_id'];
    $title  = trim($_POST['title'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $icon   = trim($_POST['icon'] ?? '');
    $order  = (int)($_POST['display_order'] ?? 0);
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    if ($title) {
        db()->prepare('UPDATE services SET title=?,description=?,icon=?,display_order=?,status=? WHERE id=?')->execute([$title, $desc, $icon, $order, $status, $sid]);
        flash('success', 'Service updated.');
    }
    header('Location: ' . BASE_URL . '/admin/services.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    verifyCsrf();
    db()->prepare('DELETE FROM services WHERE id = ?')->execute([(int)$_POST['service_id']]);
    flash('success', 'Service deleted.');
    header('Location: ' . BASE_URL . '/admin/services.php'); exit;
}

$services = db()->query('SELECT * FROM services ORDER BY display_order ASC, title ASC')->fetchAll();
include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <h2>Services</h2>
    <button class="btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add Service</button>
</div>

<div class="admin-card">
    <!-- Table: desktop -->
    <div class="d-none d-md-block">
    <table class="admin-table">
        <thead><tr><th>Icon</th><th>Title</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="sortableList" data-endpoint="reorder-services.php">
        <?php foreach ($services as $s): ?>
        <tr data-id="<?= $s['id'] ?>">
            <td><i class="bi <?= e($s['icon'] ?? 'bi-circle') ?>" style="font-size:1.2rem;color:var(--text-muted)"></i></td>
            <td>
                <i class="bi bi-grip-vertical order-handle me-2"></i>
                <span style="font-weight:500"><?= e($s['title']) ?></span>
                <?php if ($s['description']): ?>
                <div style="font-size:0.78rem;color:var(--text-muted)"><?= e(mb_substr($s['description'], 0, 80)) ?>…</div>
                <?php endif; ?>
            </td>
            <td style="color:var(--text-muted);font-size:0.82rem"><?= $s['display_order'] ?></td>
            <td><span class="badge-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
            <td>
                <button class="btn-icon" onclick="openEditModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                    <button type="submit" name="delete_service" class="btn-icon danger"
                        data-confirm="Delete service '<?= e(addslashes($s['title'])) ?>'?"
                        title="Delete"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Cards: mobile -->
    <div class="d-md-none">
        <?php foreach ($services as $s): ?>
        <div class="proj-mobile-card">
            <div class="proj-mobile-thumb proj-mobile-thumb--empty">
                <i class="bi <?= e($s['icon'] ?? 'bi-circle') ?>" style="font-size:1.2rem;color:var(--text-muted)"></i>
            </div>
            <div class="proj-mobile-info">
                <div class="proj-mobile-title"><?= e($s['title']) ?></div>
                <?php if ($s['description']): ?>
                <div class="proj-mobile-meta"><?= e(mb_substr($s['description'], 0, 60)) ?>…</div>
                <?php endif; ?>
                <span class="badge-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span>
            </div>
            <div class="d-flex flex-column gap-1 align-items-end">
                <button class="btn-icon" onclick="openEditModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                    <button type="submit" name="delete_service" class="btn-icon danger"
                        data-confirm="Delete service '<?= e(addslashes($s['title'])) ?>'?"
                        title="Delete"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($services)): ?>
        <p style="text-align:center;color:var(--text-muted);padding:2rem 0">No services yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content" style="background:var(--surface);border:1px solid var(--border)">
        <div class="modal-header" style="border-color:var(--border)">
            <h5 class="modal-title" style="font-size:0.95rem">Add Service</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1) opacity(0.5)"></button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                <div class="mb-3"><label class="form-label">Bootstrap Icon Class</label><input type="text" name="icon" class="form-control" placeholder="e.g. bi-palette"><div class="form-text">See <a href="https://icons.getbootstrap.com" target="_blank" style="color:#aaa">icons.getbootstrap.com</a></div></div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">Display Order</label><input type="number" name="display_order" class="form-control" value="0" min="0"></div>
                    <div class="col-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                </div>
            </div>
            <div class="modal-footer" style="border-color:var(--border)">
                <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_service" class="btn-admin-primary">Add</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content" style="background:var(--surface);border:1px solid var(--border)">
        <div class="modal-header" style="border-color:var(--border)">
            <h5 class="modal-title" style="font-size:0.95rem">Edit Service</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1) opacity(0.5)"></button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="service_id" id="editId">
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Title *</label><input type="text" name="title" id="editTitle" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="editDesc" class="form-control" rows="3"></textarea></div>
                <div class="mb-3"><label class="form-label">Bootstrap Icon Class</label><input type="text" name="icon" id="editIcon" class="form-control"></div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">Display Order</label><input type="number" name="display_order" id="editOrder" class="form-control" min="0"></div>
                    <div class="col-6"><label class="form-label">Status</label><select name="status" id="editStatus" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                </div>
            </div>
            <div class="modal-footer" style="border-color:var(--border)">
                <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_service" class="btn-admin-primary">Save</button>
            </div>
        </form>
    </div></div>
</div>

<script>
function openEditModal(s) {
    document.getElementById('editId').value     = s.id;
    document.getElementById('editTitle').value  = s.title;
    document.getElementById('editDesc').value   = s.description || '';
    document.getElementById('editIcon').value   = s.icon || '';
    document.getElementById('editOrder').value  = s.display_order;
    document.getElementById('editStatus').value = s.status;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
