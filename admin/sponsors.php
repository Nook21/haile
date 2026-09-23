<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Sponsors';

// Add — image only, name auto-generated
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sponsor'])) {
    verifyCsrf();
    if (!empty($_FILES['logo']['name'])) {
        $result = saveUpload($_FILES['logo'], 'sponsors');
        if ($result) {
            $name = pathinfo($_FILES['logo']['name'], PATHINFO_FILENAME);
            $order = (int) db()->query('SELECT COALESCE(MAX(display_order),0)+1 FROM sponsors')->fetchColumn();
            db()->prepare('INSERT INTO sponsors (name, logo, display_order, status) VALUES (?,?,?,?)')->execute([$name, $result['path'], $order, 'active']);
            flash('success', 'Sponsor image added.');
        } else {
            flash('error', 'Upload failed. Use JPG, PNG, WEBP or SVG.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/sponsors.php'); exit;
}

// Multi-upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sponsors_bulk'])) {
    verifyCsrf();
    $added = 0;
    $order = (int) db()->query('SELECT COALESCE(MAX(display_order),0)+1 FROM sponsors')->fetchColumn();
    foreach ($_FILES['logos']['name'] as $i => $fname) {
        if (empty($fname)) continue;
        $file = [
            'name'     => $_FILES['logos']['name'][$i],
            'type'     => $_FILES['logos']['type'][$i],
            'tmp_name' => $_FILES['logos']['tmp_name'][$i],
            'error'    => $_FILES['logos']['error'][$i],
            'size'     => $_FILES['logos']['size'][$i],
        ];
        $result = saveUpload($file, 'sponsors');
        if ($result) {
            $name = pathinfo($fname, PATHINFO_FILENAME);
            db()->prepare('INSERT INTO sponsors (name, logo, display_order, status) VALUES (?,?,?,?)')->execute([$name, $result['path'], $order++, 'active']);
            $added++;
        }
    }
    flash($added ? 'success' : 'error', $added ? "$added image(s) added." : 'No valid images uploaded.');
    header('Location: ' . BASE_URL . '/admin/sponsors.php'); exit;
}

// Toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_sponsor'])) {
    verifyCsrf();
    $sid = (int)$_POST['sponsor_id'];
    db()->prepare('UPDATE sponsors SET status = IF(status="active","inactive","active") WHERE id=?')->execute([$sid]);
    header('Location: ' . BASE_URL . '/admin/sponsors.php'); exit;
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_sponsor'])) {
    verifyCsrf();
    $sid = (int)$_POST['sponsor_id'];
    $st = db()->prepare('SELECT logo FROM sponsors WHERE id = ?');
    $st->execute([$sid]);
    $logoPath = $st->fetchColumn();
    if ($logoPath) deleteFile($logoPath);
    db()->prepare('DELETE FROM sponsors WHERE id = ?')->execute([$sid]);
    flash('success', 'Sponsor removed.');
    header('Location: ' . BASE_URL . '/admin/sponsors.php'); exit;
}

$sponsors = db()->query('SELECT * FROM sponsors ORDER BY display_order ASC, id ASC')->fetchAll();
include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    <h2>Sponsors</h2>
    <button class="btn-admin-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="bi bi-upload"></i> Upload Images
    </button>
</div>

<p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:1.5rem;">
    Upload sponsor logos — they appear as a scrolling marquee on the homepage. Hover an image to delete or toggle visibility.
</p>

<?php if ($sponsors): ?>
<div class="sponsors-grid-admin">
    <?php foreach ($sponsors as $sp): ?>
    <div class="sponsor-card-admin <?= $sp['status'] === 'inactive' ? 'inactive' : '' ?>">
        <?php if ($sp['logo']): ?>
        <img src="<?= e(assetUrl($sp['logo'])) ?>" alt="<?= e($sp['name']) ?>">
        <?php else: ?>
        <div class="sponsor-card-placeholder"><i class="bi bi-image"></i></div>
        <?php endif; ?>
        <div class="sponsor-card-overlay">
            <form method="POST" style="display:inline">
                <?= csrfField() ?>
                <input type="hidden" name="sponsor_id" value="<?= $sp['id'] ?>">
                <button type="submit" name="toggle_sponsor" class="sponsor-action-btn"
                    title="<?= $sp['status'] === 'active' ? 'Hide' : 'Show' ?>">
                    <i class="bi bi-<?= $sp['status'] === 'active' ? 'eye-slash' : 'eye' ?>"></i>
                </button>
            </form>
            <form method="POST" style="display:inline">
                <?= csrfField() ?>
                <input type="hidden" name="sponsor_id" value="<?= $sp['id'] ?>">
                <button type="submit" name="delete_sponsor" class="sponsor-action-btn danger"
                    data-confirm="Remove this sponsor image?"
                    title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
        <?php if ($sp['status'] === 'inactive'): ?>
        <div class="sponsor-card-hidden-badge">Hidden</div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div style="text-align:center;padding:5rem 2rem;border:1px dashed var(--border);border-radius:4px;">
    <i class="bi bi-images" style="font-size:2.5rem;color:var(--text-muted);display:block;margin-bottom:1rem"></i>
    <p style="color:var(--text-muted);font-size:0.9rem">No sponsor images yet. Upload some to get started.</p>
</div>
<?php endif; ?>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content" style="background:var(--surface);border:1px solid var(--border)">
        <div class="modal-header" style="border-color:var(--border)">
            <h5 class="modal-title" style="font-size:0.95rem">Upload Sponsor Images</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1) opacity(0.5)"></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="modal-body">
                <div class="sponsor-upload-zone" id="uploadZone">
                    <i class="bi bi-cloud-upload" style="font-size:2rem;color:var(--text-muted)"></i>
                    <p style="margin:0.5rem 0 0.25rem;font-size:0.9rem">Drop images here or click to browse</p>
                    <p style="font-size:0.75rem;color:var(--text-muted)">JPG, PNG, WEBP, SVG — multiple files supported</p>
                    <input type="file" name="logos[]" id="logoInput" multiple accept=".jpg,.jpeg,.png,.webp,.svg" style="position:absolute;inset:0;opacity:0;cursor:pointer">
                </div>
                <div id="previewGrid" style="display:none;margin-top:1rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:0.5rem"></div>
            </div>
            <div class="modal-footer" style="border-color:var(--border)">
                <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_sponsors_bulk" class="btn-admin-primary">Upload</button>
            </div>
        </form>
    </div></div>
</div>

<style>
.sponsors-grid-admin {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 1rem;
}
.sponsor-card-admin {
    position: relative;
    aspect-ratio: 3/2;
    background: var(--surface-2, #1e1e1e);
    border: 1px solid var(--border);
    border-radius: 6px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color 0.2s;
}
.sponsor-card-admin:hover { border-color: var(--primary, #6c63ff); }
.sponsor-card-admin.inactive { opacity: 0.4; }
.sponsor-card-admin img {
    max-width: 80%;
    max-height: 70%;
    object-fit: contain;
    display: block;
    opacity: 0.9;
    transition: opacity 0.2s;
}
.sponsor-card-admin:hover img { opacity: 0.3; }
.sponsor-card-placeholder {
    font-size: 2rem;
    color: var(--text-muted);
}
.sponsor-card-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.2s;
    background: rgba(0,0,0,0.4);
}
.sponsor-card-admin:hover .sponsor-card-overlay { opacity: 1; }
@media (hover: none) {
    .sponsor-card-admin img { opacity: 0.7; }
    .sponsor-card-overlay {
        opacity: 1;
        background: rgba(0,0,0,0.25);
        align-items: flex-end;
        justify-content: flex-end;
        padding: 0.35rem;
        gap: 0.3rem;
    }
    .sponsor-action-btn { width: 30px; height: 30px; font-size: 0.8rem; }
}
.sponsor-action-btn {
    width: 36px; height: 36px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: #fff;
    font-size: 0.9rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: background 0.2s, border-color 0.2s;
}
.sponsor-action-btn:hover { background: rgba(255,255,255,0.25); border-color: rgba(255,255,255,0.6); }
.sponsor-action-btn.danger:hover { background: rgba(220,53,69,0.7); border-color: #dc3545; }
.sponsor-card-hidden-badge {
    position: absolute;
    top: 6px; right: 6px;
    font-size: 0.6rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    background: rgba(0,0,0,0.6);
    color: #aaa;
    padding: 2px 6px;
    border-radius: 3px;
}
.sponsor-upload-zone {
    position: relative;
    border: 2px dashed var(--border);
    border-radius: 6px;
    padding: 2.5rem 1rem;
    text-align: center;
    transition: border-color 0.2s, background 0.2s;
    cursor: pointer;
}
.sponsor-upload-zone:hover,
.sponsor-upload-zone.drag-over {
    border-color: var(--primary, #6c63ff);
    background: rgba(108,99,255,0.05);
}
</style>

<script>
const zone = document.getElementById('uploadZone');
const input = document.getElementById('logoInput');
const grid  = document.getElementById('previewGrid');

zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    input.files = e.dataTransfer.files;
    showPreviews(e.dataTransfer.files);
});
input.addEventListener('change', () => showPreviews(input.files));

function showPreviews(files) {
    grid.innerHTML = '';
    grid.style.display = files.length ? 'grid' : 'none';
    Array.from(files).forEach(f => {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(f);
        img.style.cssText = 'width:100%;aspect-ratio:3/2;object-fit:contain;background:#111;border-radius:4px;border:1px solid var(--border)';
        grid.appendChild(img);
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
