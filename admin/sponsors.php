<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Sponsors';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sponsor'])) {
    verifyCsrf();
    $name  = trim($_POST['name'] ?? '') ?: (isset($_FILES['logo']['name']) ? pathinfo($_FILES['logo']['name'], PATHINFO_FILENAME) : 'Sponsor');
    $order = (int)($_POST['display_order'] ?? 0);
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $logo = null;
    if (!empty($_FILES['logo']['name'])) {
        $result = saveUpload($_FILES['logo'], 'sponsors');
        if ($result) $logo = $result['path'];
    }
    if ($logo || $name) {
        db()->prepare('INSERT INTO sponsors (name, logo, display_order, status) VALUES (?,?,?,?)')->execute([$name, $logo, $order, $status]);
        flash('success', 'Sponsor added.');
    }
    header('Location: ' . BASE_URL . '/admin/sponsors'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_sponsor'])) {
    verifyCsrf();
    $sid   = (int)$_POST['sponsor_id'];
    $name  = trim($_POST['name'] ?? '') ?: 'Sponsor';
    $order = (int)($_POST['display_order'] ?? 0);
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $st = db()->prepare('SELECT logo FROM sponsors WHERE id = ?');
    $st->execute([$sid]);
    $logo = $st->fetchColumn();
    if (!empty($_FILES['logo']['name'])) {
        $result = saveUpload($_FILES['logo'], 'sponsors');
        if ($result) { if ($logo) deleteFile($logo); $logo = $result['path']; }
    }
    if ($name) {
        db()->prepare('UPDATE sponsors SET name=?,logo=?,display_order=?,status=? WHERE id=?')->execute([$name, $logo, $order, $status, $sid]);
        flash('success', 'Sponsor updated.');
    }
    header('Location: ' . BASE_URL . '/admin/sponsors'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_sponsor'])) {
    verifyCsrf();
    $sid = (int)$_POST['sponsor_id'];
    $st = db()->prepare('SELECT logo FROM sponsors WHERE id = ?');
    $st->execute([$sid]);
    $logo = $st->fetchColumn();
    if ($logo) deleteFile($logo);
    db()->prepare('DELETE FROM sponsors WHERE id = ?')->execute([$sid]);
    flash('success', 'Sponsor deleted.');
    header('Location: ' . BASE_URL . '/admin/sponsors'); exit;
}

$sponsors = db()->query('SELECT * FROM sponsors ORDER BY display_order ASC, name ASC')->fetchAll();
include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <h2>Sponsors</h2>
    <button class="btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add Sponsor</button>
</div>

<div class="admin-card">
    <div class="d-none d-md-block">
    <table class="admin-table">
        <thead><tr><th>Logo</th><th>Label</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($sponsors as $sp): ?>
        <tr>
            <td>
                <?php if ($sp['logo']): ?>
                <img src="<?= e(assetUrl($sp['logo'])) ?>" class="table-thumb" alt="" style="object-fit:contain;background:#111">
                <?php else: ?>
                <div class="table-thumb-placeholder"><i class="bi bi-award"></i></div>
                <?php endif; ?>
            </td>
            <td style="font-weight:500"><?= e($sp['name']) ?></td>
            <td style="color:var(--text-muted);font-size:0.82rem"><?= $sp['display_order'] ?></td>
            <td><span class="badge-<?= $sp['status'] ?>"><?= ucfirst($sp['status']) ?></span></td>
            <td>
                <button class="btn-icon" onclick="openEdit(<?= htmlspecialchars(json_encode($sp), ENT_QUOTES) ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="sponsor_id" value="<?= $sp['id'] ?>">
                    <button type="submit" name="delete_sponsor" class="btn-icon danger"
                        data-confirm="Delete sponsor '<?= e(addslashes($sp['name'])) ?>'?"
                        title="Delete"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($sponsors)): ?>
        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem">No sponsors yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <div class="d-md-none">
        <?php if (empty($sponsors)): ?>
        <p style="text-align:center;color:var(--text-muted);padding:2rem 0">No sponsors yet.</p>
        <?php endif; ?>
        <?php foreach ($sponsors as $sp): ?>
        <div class="proj-mobile-card">
            <?php if ($sp['logo']): ?>
            <img src="<?= e(assetUrl($sp['logo'])) ?>" class="proj-mobile-thumb" alt="" style="object-fit:contain;background:#111">
            <?php else: ?>
            <div class="proj-mobile-thumb proj-mobile-thumb--empty"><i class="bi bi-award"></i></div>
            <?php endif; ?>
            <div class="proj-mobile-info">
                <div class="proj-mobile-title"><?= e($sp['name']) ?></div>
                <div class="proj-mobile-meta">Order <?= $sp['display_order'] ?></div>
                <span class="badge-<?= $sp['status'] ?>"><?= ucfirst($sp['status']) ?></span>
            </div>
            <div class="d-flex flex-column gap-1 align-items-end">
                <button class="btn-icon" onclick="openEdit(<?= htmlspecialchars(json_encode($sp), ENT_QUOTES) ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="sponsor_id" value="<?= $sp['id'] ?>">
                    <button type="submit" name="delete_sponsor" class="btn-icon danger"
                        data-confirm="Delete sponsor '<?= e(addslashes($sp['name'])) ?>'?"
                        title="Delete"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content" style="background:var(--surface);border:1px solid var(--border)">
        <div class="modal-header" style="border-color:var(--border)">
            <h5 class="modal-title" style="font-size:0.95rem">Add Sponsor</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1) opacity(0.5)"></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Logo</label><input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg"></div>
                <div class="mb-3"><label class="form-label">Internal Label <small class="text-muted">(admin only, not shown on site)</small></label><input type="text" name="name" class="form-control" placeholder="e.g. Nike, Partner A"></div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">Display Order</label><input type="number" name="display_order" class="form-control" value="0" min="0"></div>
                    <div class="col-6"><label class="form-label">Status</label>
                        <select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-color:var(--border)">
                <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_sponsor" class="btn-admin-primary">Add</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content" style="background:var(--surface);border:1px solid var(--border)">
        <div class="modal-header" style="border-color:var(--border)">
            <h5 class="modal-title" style="font-size:0.95rem">Edit Sponsor</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1) opacity(0.5)"></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="sponsor_id" id="editId">
            <div class="modal-body">
                <div class="mb-2" id="editLogoPreview"></div>
                <div class="mb-3"><label class="form-label">Logo (upload to replace)</label><input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg"></div>
                <div class="mb-3"><label class="form-label">Internal Label <small class="text-muted">(admin only, not shown on site)</small></label><input type="text" name="name" id="editName" class="form-control" placeholder="e.g. Nike, Partner A"></div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">Display Order</label><input type="number" name="display_order" id="editOrder" class="form-control" min="0"></div>
                    <div class="col-6"><label class="form-label">Status</label>
                        <select name="status" id="editStatus" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-color:var(--border)">
                <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_sponsor" class="btn-admin-primary">Save</button>
            </div>
        </form>
    </div></div>
</div>

<script>
function openEdit(sp) {
    document.getElementById('editId').value     = sp.id;
    document.getElementById('editName').value   = sp.name;
    document.getElementById('editOrder').value  = sp.display_order;
    document.getElementById('editStatus').value = sp.status;
    const prev = document.getElementById('editLogoPreview');
    prev.innerHTML = sp.logo
        ? '<img src="<?= BASE_URL ?>/' + sp.logo + '" style="height:40px;object-fit:contain;margin-bottom:0.5rem">'
        : '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
