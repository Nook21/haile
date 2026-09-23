<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Clients';

// Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    verifyCsrf();
    $name   = trim($_POST['name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $test   = trim($_POST['testimonial'] ?? '');
    $ptitle = trim($_POST['person_title'] ?? '');
    $order  = (int)($_POST['display_order'] ?? 0);
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $logo   = null;
    if (!empty($_FILES['logo']['name'])) {
        $result = saveUpload($_FILES['logo'], 'clients');
        if ($result) $logo = $result['path'];
    }
    if ($name) {
        db()->prepare('INSERT INTO clients (name, logo, description, testimonial, person_title, display_order, status) VALUES (?,?,?,?,?,?,?)')->execute([$name, $logo, $desc, $test ?: null, $ptitle ?: null, $order, $status]);
        flash('success', 'Client added.');
    }
    header('Location: ' . BASE_URL . '/admin/clients.php'); exit;
}

// Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_client'])) {
    verifyCsrf();
    $cid    = (int)$_POST['client_id'];
    $name   = trim($_POST['name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $test   = trim($_POST['testimonial'] ?? '');
    $ptitle = trim($_POST['person_title'] ?? '');
    $order  = (int)($_POST['display_order'] ?? 0);
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';

    $existing = db()->prepare('SELECT logo FROM clients WHERE id = ?');
    $existing->execute([$cid]);
    $logo = $existing->fetchColumn();

    if (!empty($_FILES['logo']['name'])) {
        $result = saveUpload($_FILES['logo'], 'clients');
        if ($result) {
            if ($logo) deleteFile($logo);
            $logo = $result['path'];
        }
    }
    if ($name) {
        db()->prepare('UPDATE clients SET name=?,logo=?,description=?,testimonial=?,person_title=?,display_order=?,status=? WHERE id=?')->execute([$name, $logo, $desc, $test ?: null, $ptitle ?: null, $order, $status, $cid]);
        flash('success', 'Client updated.');
    }
    header('Location: ' . BASE_URL . '/admin/clients.php'); exit;
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_client'])) {
    verifyCsrf();
    $cid = (int)$_POST['client_id'];
    $logo = db()->prepare('SELECT logo FROM clients WHERE id = ?');
    $logo->execute([$cid]);
    $logoPath = $logo->fetchColumn();
    if ($logoPath) deleteFile($logoPath);
    db()->prepare('UPDATE projects SET client_id = NULL WHERE client_id = ?')->execute([$cid]);
    db()->prepare('DELETE FROM clients WHERE id = ?')->execute([$cid]);
    flash('success', 'Client deleted.');
    header('Location: ' . BASE_URL . '/admin/clients.php'); exit;
}

$clients = db()->query('SELECT cl.*, COUNT(p.id) AS project_count FROM clients cl LEFT JOIN projects p ON p.client_id = cl.id GROUP BY cl.id ORDER BY cl.display_order ASC, cl.name ASC')->fetchAll();
include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <h2>Clients</h2>
    <button class="btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add Client</button>
</div>

<div class="admin-card">
    <!-- Table: desktop -->
    <div class="d-none d-md-block">
    <table class="admin-table">
        <thead><tr><th>Logo</th><th>Name</th><th>Projects</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($clients as $cl): ?>
        <tr>
            <td>
                <?php if ($cl['logo']): ?>
                <img src="<?= e(assetUrl($cl['logo'])) ?>" class="table-thumb" alt="" style="object-fit:contain;background:#111">
                <?php else: ?>
                <div class="table-thumb-placeholder"><i class="bi bi-building"></i></div>
                <?php endif; ?>
            </td>
            <td style="font-weight:500"><?= e($cl['name']) ?></td>
            <td style="color:var(--text-muted);font-size:0.82rem"><?= $cl['project_count'] ?></td>
            <td style="color:var(--text-muted);font-size:0.82rem"><?= $cl['display_order'] ?></td>
            <td><span class="badge-<?= $cl['status'] ?>"><?= ucfirst($cl['status']) ?></span></td>
            <td>
                <button class="btn-icon" onclick="openEditModal(<?= htmlspecialchars(json_encode($cl), ENT_QUOTES) ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="client_id" value="<?= $cl['id'] ?>">
                    <button type="submit" name="delete_client" class="btn-icon danger"
                        data-confirm="Delete client '<?= e(addslashes($cl['name'])) ?>'?"
                        title="Delete"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($clients)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem">No clients yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <!-- Cards: mobile -->
    <div class="d-md-none">
        <?php if (empty($clients)): ?>
        <p style="text-align:center;color:var(--text-muted);padding:2rem 0">No clients yet.</p>
        <?php endif; ?>
        <?php foreach ($clients as $cl): ?>
        <div class="proj-mobile-card">
            <?php if ($cl['logo']): ?>
            <img src="<?= e(assetUrl($cl['logo'])) ?>" class="proj-mobile-thumb" alt="" style="object-fit:contain;background:#111">
            <?php else: ?>
            <div class="proj-mobile-thumb proj-mobile-thumb--empty"><i class="bi bi-building"></i></div>
            <?php endif; ?>
            <div class="proj-mobile-info">
                <div class="proj-mobile-title"><?= e($cl['name']) ?></div>
                <div class="proj-mobile-meta"><?= $cl['project_count'] ?> project(s) · Order <?= $cl['display_order'] ?></div>
                <span class="badge-<?= $cl['status'] ?>"><?= ucfirst($cl['status']) ?></span>
            </div>
            <div class="d-flex flex-column gap-1 align-items-end">
                <button class="btn-icon" onclick="openEditModal(<?= htmlspecialchars(json_encode($cl), ENT_QUOTES) ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="client_id" value="<?= $cl['id'] ?>">
                    <button type="submit" name="delete_client" class="btn-icon danger"
                        data-confirm="Delete client '<?= e(addslashes($cl['name'])) ?>'?"
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
            <h5 class="modal-title" style="font-size:0.95rem">Add Client</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1) opacity(0.5)"></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Logo</label><input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                <div class="mb-3"><label class="form-label">Testimonial Quote</label><textarea name="testimonial" class="form-control" rows="3" placeholder="Leave blank to hide testimonials section"></textarea></div>
                <div class="mb-3"><label class="form-label">Person Title</label><input type="text" name="person_title" class="form-control" placeholder="e.g. Marketing Director"></div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">Display Order</label><input type="number" name="display_order" class="form-control" value="0" min="0"></div>
                    <div class="col-6"><label class="form-label">Status</label>
                        <select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-color:var(--border)">
                <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_client" class="btn-admin-primary">Add</button>
            </div>
        </form>
    </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content" style="background:var(--surface);border:1px solid var(--border)">
        <div class="modal-header" style="border-color:var(--border)">
            <h5 class="modal-title" style="font-size:0.95rem">Edit Client</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1) opacity(0.5)"></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="client_id" id="editId">
            <div class="modal-body">
                <div class="mb-2" id="editLogoPreview"></div>
                <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" id="editName" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Logo (upload to replace)</label><input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="editDesc" class="form-control" rows="2"></textarea></div>
                <div class="mb-3"><label class="form-label">Testimonial Quote</label><textarea name="testimonial" id="editTestimonial" class="form-control" rows="3" placeholder="Leave blank to hide testimonials section"></textarea></div>
                <div class="mb-3"><label class="form-label">Person Title</label><input type="text" name="person_title" id="editPersonTitle" class="form-control" placeholder="e.g. Marketing Director"></div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">Display Order</label><input type="number" name="display_order" id="editOrder" class="form-control" min="0"></div>
                    <div class="col-6"><label class="form-label">Status</label>
                        <select name="status" id="editStatus" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-color:var(--border)">
                <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_client" class="btn-admin-primary">Save</button>
            </div>
        </form>
    </div></div>
</div>

<script>
function openEditModal(cl) {
    document.getElementById('editId').value     = cl.id;
    document.getElementById('editName').value   = cl.name;
    document.getElementById('editDesc').value         = cl.description || '';
    document.getElementById('editTestimonial').value  = cl.testimonial || '';
    document.getElementById('editPersonTitle').value  = cl.person_title || '';
    document.getElementById('editOrder').value  = cl.display_order;
    document.getElementById('editStatus').value = cl.status;
    const prev = document.getElementById('editLogoPreview');
    prev.innerHTML = cl.logo
        ? '<img src="<?= BASE_URL ?>/' + cl.logo + '" style="height:40px;object-fit:contain;margin-bottom:0.5rem">'
        : '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
