<?php
require_once __DIR__ . '/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . '/admin/projects.php'); exit; }

$project = db()->prepare('SELECT * FROM projects WHERE id = ?');
$project->execute([$id]);
$project = $project->fetch();
if (!$project) { flash('error', 'Project not found.'); header('Location: ' . BASE_URL . '/admin/projects.php'); exit; }

$pageTitle = 'Edit: ' . $project['title'];
$categories = getCategories();
$clients    = db()->query('SELECT id, name FROM clients WHERE status="active" ORDER BY name ASC')->fetchAll();
$errors = [];

// ── Handle project update ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
    verifyCsrf();

    $title     = trim($_POST['title'] ?? '');
    $slug      = makeSlug(trim($_POST['slug'] ?? '') ?: $title);
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

    // Slug uniqueness (excluding self)
    $exists = db()->prepare('SELECT id FROM projects WHERE slug = ? AND id != ?');
    $exists->execute([$slug, $id]);
    if ($exists->fetch()) $errors[] = 'Slug already in use.';

    // New cover image
    $coverPath = $project['cover_image'];
    if (!empty($_FILES['cover_image']['name'])) {
        $result = saveUpload($_FILES['cover_image'], 'covers');
        if ($result) {
            if ($coverPath) deleteFile($coverPath);
            $coverPath = $result['path'];
        } else {
            $errors[] = 'Cover image upload failed.';
        }
    }

    if (empty($errors)) {
        db()->prepare('UPDATE projects SET category_id=?,client_id=?,title=?,slug=?,short_description=?,description=?,year=?,cover_image=?,featured=?,favorite=?,status=?,display_order=?,location=?,price=?,bedrooms=?,bathrooms=?,area=? WHERE id=?')
            ->execute([$catId, $clientId, $title, $slug, $shortDesc, $desc, $year, $coverPath, $featured, $favorite, $status, $order, $location, $price, $bedrooms, $bathrooms, $area, $id]);
        flash('success', 'Project saved.');
        header('Location: ' . BASE_URL . '/admin/project-edit.php?id=' . $id);
        exit;
    }
    // Re-merge for display
    $project = array_merge($project, $_POST, ['cover_image' => $coverPath]);
}

// ── Handle media upload ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_media'])) {
    verifyCsrf();
    $uploaded = 0;
    $maxOrder = db()->prepare('SELECT COALESCE(MAX(display_order),0) FROM media WHERE project_id = ?');
    $maxOrder->execute([$id]);
    $order = (int)$maxOrder->fetchColumn();

    $files = $_FILES['media_files'];
    $count = is_array($files['name']) ? count($files['name']) : 0;

    for ($i = 0; $i < $count; $i++) {
        $file = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];
        if ($file['error'] !== UPLOAD_ERR_OK) continue;

        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $type = in_array($ext, ALLOWED_VIDEO_EXTS) ? 'video' : 'image';
        $result = saveUpload($file, 'projects');
        if (!$result) continue;

        $order++;
        db()->prepare('INSERT INTO media (project_id, type, file_path, alt_text, display_order, file_size) VALUES (?,?,?,?,?,?)')
            ->execute([$id, $type, $result['path'], $project['title'], $order, $result['size']]);
        $uploaded++;
    }

    flash('success', "$uploaded file(s) uploaded.");
    header('Location: ' . BASE_URL . '/admin/project-edit.php?id=' . $id);
    exit;
}

// ── Handle media delete ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_media'])) {
    verifyCsrf();
    $mid = (int)$_POST['media_id'];
    $row = db()->prepare('SELECT file_path, thumbnail_path FROM media WHERE id = ? AND project_id = ?');
    $row->execute([$mid, $id]);
    $m = $row->fetch();
    if ($m) {
        if ($m['file_path'])      deleteFile($m['file_path']);
        if ($m['thumbnail_path']) deleteFile($m['thumbnail_path']);
        db()->prepare('DELETE FROM media WHERE id = ?')->execute([$mid]);
        flash('success', 'Media deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/project-edit.php?id=' . $id);
    exit;
}

// ── Handle cover from media ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_cover'])) {
    verifyCsrf();
    $mid = (int)$_POST['media_id'];
    $row = db()->prepare('SELECT file_path FROM media WHERE id = ? AND project_id = ? AND type = "image"');
    $row->execute([$mid, $id]);
    $m = $row->fetch();
    if ($m) {
        db()->prepare('UPDATE projects SET cover_image = ? WHERE id = ?')->execute([$m['file_path'], $id]);
        flash('success', 'Cover image updated.');
    }
    header('Location: ' . BASE_URL . '/admin/project-edit.php?id=' . $id);
    exit;
}

$media = getProjectMedia($id);
include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<?php if ($errors): ?>
<div class="alert alert-danger mb-3"><?= implode('<br>', array_map('e', $errors)) ?></div>
<?php endif; ?>

<div class="page-header">
    <h2><?= e($project['title']) ?></h2>
    <div class="d-flex gap-2">
        <?php if ($project['status'] === 'published'): ?>
        <a href="<?= BASE_URL ?>/project.php?slug=<?= e($project['slug']) ?>" target="_blank" class="btn-admin-secondary"><i class="bi bi-box-arrow-up-right"></i> View</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/admin/projects.php" class="btn-admin-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<!-- Project form -->
<form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="admin-card">
                <div class="admin-card-header"><span class="admin-card-title">Project Details</span></div>
                <div class="mb-3">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" id="titleInput" class="form-control" value="<?= e($project['title']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="slugInput" class="form-control" value="<?= e($project['slug']) ?>" data-manual="1">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $project['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Client</label>
                        <select name="client_id" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($clients as $cl): ?>
                            <option value="<?= $cl['id'] ?>" <?= $project['client_id'] == $cl['id'] ? 'selected' : '' ?>><?= e($cl['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Short Description</label>
                    <input type="text" name="short_description" class="form-control" value="<?= e($project['short_description'] ?? '') ?>" maxlength="300">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="7"><?= e($project['description'] ?? '') ?></textarea>
                </div>
                <div class="admin-card-header" style="margin:1.5rem -1.5rem 1rem;padding:0.75rem 1.5rem;border-top:1px solid var(--border)"><span class="admin-card-title" style="font-size:0.82rem">Property Details</span></div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= e($project['location'] ?? '') ?>" placeholder="e.g. Bole, Addis Ababa">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Price</label>
                        <input type="text" name="price" class="form-control" value="<?= e($project['price'] ?? '') ?>" placeholder="e.g. $2,400,000">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Bedrooms</label>
                        <input type="number" name="bedrooms" class="form-control" value="<?= e($project['bedrooms'] ?? '') ?>" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bathrooms</label>
                        <input type="number" name="bathrooms" class="form-control" value="<?= e($project['bathrooms'] ?? '') ?>" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Floor Area</label>
                        <input type="text" name="area" class="form-control" value="<?= e($project['area'] ?? '') ?>" placeholder="e.g. 620 sqm">
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
                        <option value="draft" <?= $project['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $project['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control" value="<?= e($project['year'] ?? '') ?>" min="2000" max="2099">
                </div>
                <div class="mb-3">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="<?= e($project['display_order']) ?>" min="0">
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="featured" id="featured" class="form-check-input" <?= $project['featured'] ? 'checked' : '' ?>>
                    <label for="featured" class="form-check-label">Featured on homepage</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="favorite" id="favorite" class="form-check-input" <?= ($project['favorite'] ?? 0) ? 'checked' : '' ?>>
                    <label for="favorite" class="form-check-label">Favourite / Highlighted</label>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header"><span class="admin-card-title">Cover Image</span></div>
                <?php if ($project['cover_image']): ?>
                <img id="coverPreview" src="<?= e(assetUrl($project['cover_image'])) ?>" class="cover-preview mb-2" alt="" loading="lazy">
                <div id="coverPlaceholder" class="cover-placeholder mb-2" style="display:none"><i class="bi bi-image"></i></div>
                <?php else: ?>
                <div id="coverPlaceholder" class="cover-placeholder mb-2"><i class="bi bi-image"></i></div>
                <img id="coverPreview" class="cover-preview mb-2" style="display:none" src="" alt="">
                <?php endif; ?>
                <input type="file" name="cover_image" id="coverInput" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif">
                <div class="form-text mt-1">Upload new to replace current</div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-2 mb-4">
        <button type="submit" name="save_project" class="btn-admin-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
    </div>
</form>

<!-- ── Media Gallery ──────────────────────────────────────── -->
<div class="admin-card">
    <div class="admin-card-header">
        <span class="admin-card-title">Media Gallery (<?= count($media) ?> files)</span>
        <span style="font-size:0.78rem;color:var(--text-muted)">Drag to reorder</span>
    </div>

    <?php if (!empty($media)): ?>
    <div class="media-grid mb-3" id="mediaGrid" data-project="<?= $id ?>">
        <?php foreach ($media as $m): ?>
        <div class="media-item" data-id="<?= $m['id'] ?>">
            <?php if ($m['type'] === 'image'): ?>
            <img src="<?= e(assetUrl($m['file_path'])) ?>" class="media-thumb" alt="<?= e($m['alt_text'] ?? '') ?>" loading="lazy">
            <?php else: ?>
            <video class="media-thumb" src="<?= e(assetUrl($m['file_path'])) ?>" preload="metadata" muted playsinline
                onmouseenter="this.play()" onmouseleave="this.pause();this.currentTime=0;"></video>
            <?php endif; ?>
            <div class="media-info">
                <span class="drag-handle"><i class="bi bi-grip-vertical"></i></span>
                <?= $m['type'] === 'video' ? '<i class="bi bi-camera-video"></i>' : '' ?>
                #<?= $m['display_order'] ?>
            </div>
            <div class="media-actions">
                <?php if ($m['type'] === 'image'): ?>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="media_id" value="<?= $m['id'] ?>">
                    <button type="submit" name="set_cover" class="media-action-btn" title="Set as cover"><i class="bi bi-image"></i></button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="media_id" value="<?= $m['id'] ?>">
                    <button type="submit" name="delete_media" class="media-action-btn del"
                        data-confirm="Delete this media file?" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="color:var(--text-muted);font-size:0.88rem;margin-bottom:1rem">No media uploaded yet.</p>
    <?php endif; ?>

    <!-- Upload form -->
    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="upload-zone mb-2" id="uploadZone">
            <i class="bi bi-cloud-upload"></i>
            <p>Drop files here or click to upload</p>
            <p style="font-size:0.78rem;margin-top:0.25rem">JPG, PNG, WEBP, AVIF · MP4, MKV, MOV, AVI, WEBM and more — no size limit on videos</p>
            <input type="file" name="media_files[]" id="mediaFilesInput" multiple accept=".jpg,.jpeg,.png,.webp,.avif,.mp4,.webm,.mkv,.mov,.avi,.wmv,.m4v,.ogv,.flv,.3gp" style="display:none">
        </div>
        <div id="uploadPreviewGrid" style="display:none;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:0.6rem;margin-bottom:0.85rem"></div>
        <div id="uploadFileCount" style="font-size:0.82rem;color:var(--text-muted);margin-bottom:0.75rem"></div>
        <button type="submit" name="upload_media" class="btn-admin-primary"><i class="bi bi-upload"></i> Upload Files</button>
    </form>
</div>

<style>
#uploadPreviewGrid .up-thumb {
    position: relative;
    aspect-ratio: 1;
    border-radius: 6px;
    overflow: hidden;
    background: var(--surface2, #1a1a1a);
    border: 1px solid var(--border);
}
#uploadPreviewGrid .up-thumb img,
#uploadPreviewGrid .up-thumb video {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
}
#uploadPreviewGrid .up-thumb-label {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    font-size: 0.62rem;
    padding: 3px 5px;
    background: rgba(0,0,0,0.55);
    color: #ddd;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
#uploadPreviewGrid .up-thumb-video-icon {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    font-size: 1.6rem;
    color: rgba(255,255,255,0.85);
    pointer-events: none;
}
</style>

<script>
const mediaInput     = document.getElementById('mediaFilesInput');
const previewGrid    = document.getElementById('uploadPreviewGrid');
const uploadFileCount = document.getElementById('uploadFileCount');
const uploadZone     = document.getElementById('uploadZone');

function buildPreviews(files) {
    previewGrid.innerHTML = '';
    if (!files.length) {
        previewGrid.style.display = 'none';
        uploadFileCount.textContent = '';
        return;
    }
    previewGrid.style.display = 'grid';
    uploadFileCount.textContent = files.length + ' file' + (files.length > 1 ? 's' : '') + ' selected';

    Array.from(files).forEach(file => {
        const wrap = document.createElement('div');
        wrap.className = 'up-thumb';

        const isVideo = file.type.startsWith('video/');
        const url = URL.createObjectURL(file);

        if (isVideo) {
            const vid = document.createElement('video');
            vid.src = url;
            vid.muted = true;
            vid.preload = 'metadata';
            vid.playsInline = true;
            wrap.appendChild(vid);
            const icon = document.createElement('i');
            icon.className = 'bi bi-play-circle-fill up-thumb-video-icon';
            wrap.appendChild(icon);
        } else {
            const img = document.createElement('img');
            img.src = url;
            wrap.appendChild(img);
        }

        const label = document.createElement('div');
        label.className = 'up-thumb-label';
        label.textContent = file.name;
        wrap.appendChild(label);

        previewGrid.appendChild(wrap);
    });
}

if (mediaInput) {
    mediaInput.addEventListener('change', () => buildPreviews(mediaInput.files));
}
if (uploadZone && mediaInput) {
    uploadZone.addEventListener('click', e => { if (e.target !== mediaInput) mediaInput.click(); });
    uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
    uploadZone.addEventListener('drop', e => {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        mediaInput.files = e.dataTransfer.files;
        buildPreviews(e.dataTransfer.files);
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
