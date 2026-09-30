<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Media Library';

// Brand video upload/remove (slots 1–4)
$_brandVideoKeys = ['brand_video','brand_video_2','brand_video_3','brand_video_4'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_brand_video'])) {
    verifyCsrf();
    $slot = in_array($_POST['slot'] ?? '', $_brandVideoKeys) ? $_POST['slot'] : 'brand_video';
    if (!empty($_FILES['brand_video']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['brand_video']['name'], PATHINFO_EXTENSION));
        $allowed = ['mp4','webm','mov'];
        if (in_array($ext, $allowed) && $_FILES['brand_video']['error'] === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/../uploads/brand/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $old = getSetting($slot);
            if ($old) { $abs = __DIR__ . '/../' . ltrim($old,'/'); if (file_exists($abs)) unlink($abs); }
            $filename = 'brand_' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['brand_video']['tmp_name'], $dir . $filename);
            $path = 'uploads/brand/' . $filename;
            $st = db()->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?');
            $st->execute([$slot, $path, $path]);
            flash('success', 'Video updated.');
        } else {
            flash('error', 'Invalid file. Use MP4, WebM or MOV.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/media'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_brand_video'])) {
    verifyCsrf();
    $slot = in_array($_POST['slot'] ?? '', $_brandVideoKeys) ? $_POST['slot'] : 'brand_video';
    $old = getSetting($slot);
    if ($old) { $abs = __DIR__ . '/../' . ltrim($old,'/'); if (file_exists($abs)) unlink($abs); }
    db()->prepare('UPDATE settings SET setting_value="" WHERE setting_key=?')->execute([$slot]);
    flash('success', 'Video removed.');
    header('Location: ' . BASE_URL . '/admin/media'); exit;
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_media'])) {
    verifyCsrf();
    $mid = (int)$_POST['media_id'];
    $row = db()->prepare('SELECT file_path, thumbnail_path FROM media WHERE id = ?');
    $row->execute([$mid]);
    $m = $row->fetch();
    if ($m) {
        if ($m['file_path'])      deleteFile($m['file_path']);
        if ($m['thumbnail_path']) deleteFile($m['thumbnail_path']);
        db()->prepare('DELETE FROM media WHERE id = ?')->execute([$mid]);
        flash('success', 'File deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/media.php'); exit;
}

$perPage = 40;
$page    = max(1, (int)($_GET['page'] ?? 1));
$type    = $_GET['type'] ?? '';
$project = (int)($_GET['project'] ?? 0);

$where  = '1=1';
$params = [];
if ($type === 'image' || $type === 'video') { $where .= ' AND m.type = ?'; $params[] = $type; }
if ($project) { $where .= ' AND m.project_id = ?'; $params[] = $project; }

$total = db()->prepare("SELECT COUNT(*) FROM media m WHERE $where");
$total->execute($params);
$totalCount = (int)$total->fetchColumn();
$pg = paginate($totalCount, $perPage, $page);

$st = db()->prepare("SELECT m.*, p.title AS project_title, p.slug AS project_slug
    FROM media m
    LEFT JOIN projects p ON m.project_id = p.id
    WHERE $where
    ORDER BY m.created_at DESC
    LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$st->execute($params);
$files = $st->fetchAll();

$projects = db()->query('SELECT id, title FROM projects ORDER BY title ASC')->fetchAll();
include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<!-- Brand Video Cards (4 slots) -->
<?php
$_brandLabels = ['Video 1','Video 2','Video 3','Video 4'];
$_brandSlots  = ['brand_video','brand_video_2','brand_video_3','brand_video_4'];
?>
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <span class="admin-card-title"><i class="bi bi-camera-video me-2"></i>Homepage Brand Videos (4 slots)</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.25rem;margin-top:0.75rem">
    <?php foreach ($_brandSlots as $i => $slot):
        $bv = getSetting($slot); ?>
    <div style="border:1px solid var(--border);border-radius:8px;padding:1.25rem;display:flex;flex-direction:column;gap:0.75rem">

        <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.1em">
            <?= $_brandLabels[$i] ?>
            <?php if ($bv): ?><span style="color:#22c55e;margin-left:0.5rem">&#10003; Active</span><?php endif; ?>
        </div>

        <?php if ($bv): ?>
        <div style="display:flex;align-items:center;gap:0.75rem">
            <video src="<?= e(assetUrl($bv)) ?>" style="height:60px;width:80px;object-fit:cover;border-radius:4px;background:#000;flex-shrink:0" muted playsinline></video>
            <span style="font-size:0.75rem;color:var(--text-muted);word-break:break-all"><?= e(basename($bv)) ?></span>
        </div>
        <?php else: ?>
        <div style="height:60px;border-radius:4px;background:var(--surface2);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:0.78rem">
            No video uploaded
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:0.5rem">
            <?= csrfField() ?>
            <input type="hidden" name="slot" value="<?= $slot ?>">
            <input type="file" name="brand_video" class="form-control" accept=".mp4,.webm,.mov" style="font-size:0.8rem">
            <button type="submit" name="save_brand_video" class="btn-admin-primary" style="width:100%;justify-content:center">
                <i class="bi bi-upload"></i> <?= $bv ? 'Replace Video' : 'Upload Video' ?>
            </button>
        </form>

        <?php if ($bv): ?>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="slot" value="<?= $slot ?>">
            <button type="submit" name="remove_brand_video" class="btn-admin-secondary" style="width:100%;justify-content:center"
                data-confirm="Remove this video?">
                <i class="bi bi-trash"></i> Remove
            </button>
        </form>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>
    </div>
    <p style="font-size:0.75rem;color:var(--text-muted);margin-top:1rem">Accepted: MP4, WebM, MOV. Videos with no file uploaded are hidden on the homepage.</p>
</div>

<div class="page-header"><h2>Media Library</h2></div>

<!-- Filters -->
<div class="admin-card mb-3" style="padding:1rem 1.25rem">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <select name="type" class="form-select">
                <option value="">All Types</option>
                <option value="image" <?= $type === 'image' ? 'selected' : '' ?>>Images</option>
                <option value="video" <?= $type === 'video' ? 'selected' : '' ?>>Videos</option>
            </select>
        </div>
        <div class="col-md-5">
            <select name="project" class="form-select">
                <option value="">All Projects</option>
                <?php foreach ($projects as $pr): ?>
                <option value="<?= $pr['id'] ?>" <?= $project == $pr['id'] ? 'selected' : '' ?>><?= e($pr['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn-admin-secondary flex-fill">Filter</button>
            <?php if ($type || $project): ?>
            <a href="<?= BASE_URL ?>/admin/media.php" class="btn-admin-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-card">
    <div style="margin-bottom:0.75rem;font-size:0.82rem;color:var(--text-muted)"><?= $pg['total'] ?> file(s)</div>

    <!-- Table: desktop -->
    <div class="d-none d-md-block">
    <table class="admin-table">
        <thead><tr><th>Preview</th><th>File</th><th>Type</th><th>Size</th><th>Project</th><th>Uploaded</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($files as $f): ?>
        <tr>
            <td>
                <?php if ($f['type'] === 'image'): ?>
                <img src="<?= e(assetUrl($f['file_path'])) ?>" class="table-thumb" alt="" loading="lazy">
                <?php else: ?>
                <div class="table-thumb-placeholder"><i class="bi bi-camera-video"></i></div>
                <?php endif; ?>
            </td>
            <td style="font-size:0.82rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= e(basename($f['file_path'])) ?>
            </td>
            <td><span class="badge-<?= $f['type'] === 'image' ? 'published' : 'featured' ?>"><?= ucfirst($f['type']) ?></span></td>
            <td style="color:var(--text-muted);font-size:0.82rem">
                <?= $f['file_size'] ? round($f['file_size'] / 1024) . ' KB' : '—' ?>
            </td>
            <td style="font-size:0.82rem">
                <?php if ($f['project_title']): ?>
                <a href="<?= BASE_URL ?>/admin/project-edit.php?id=<?= $f['project_id'] ?>" style="color:var(--text-muted)"><?= e($f['project_title']) ?></a>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td style="color:var(--text-muted);font-size:0.78rem"><?= date('M j, Y', strtotime($f['created_at'])) ?></td>
            <td>
                <a href="<?= e(assetUrl($f['file_path'])) ?>" target="_blank" class="btn-icon" title="View"><i class="bi bi-eye"></i></a>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="media_id" value="<?= $f['id'] ?>">
                    <button type="submit" name="delete_media" class="btn-icon danger"
                        data-confirm="Delete this file permanently?" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($files)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem">No media files found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <!-- Cards: mobile -->
    <div class="d-md-none">
        <?php if (empty($files)): ?>
        <p style="text-align:center;color:var(--text-muted);padding:2rem 0">No media files found.</p>
        <?php endif; ?>
        <?php foreach ($files as $f): ?>
        <div class="proj-mobile-card">
            <?php if ($f['type'] === 'image'): ?>
            <img src="<?= e(assetUrl($f['file_path'])) ?>" class="proj-mobile-thumb" alt="" loading="lazy">
            <?php else: ?>
            <div class="proj-mobile-thumb proj-mobile-thumb--empty"><i class="bi bi-camera-video"></i></div>
            <?php endif; ?>
            <div class="proj-mobile-info">
                <div class="proj-mobile-title"><?= e(basename($f['file_path'])) ?></div>
                <div class="proj-mobile-meta">
                    <span class="badge-<?= $f['type'] === 'image' ? 'published' : 'featured' ?>"><?= ucfirst($f['type']) ?></span>
                    <?php if ($f['file_size']): ?> · <?= round($f['file_size'] / 1024) ?> KB<?php endif; ?>
                </div>
                <div class="proj-mobile-meta">
                    <?php if ($f['project_title']): ?>
                    <a href="<?= BASE_URL ?>/admin/project-edit.php?id=<?= $f['project_id'] ?>" style="color:var(--text-muted)"><?= e($f['project_title']) ?></a>
                    <?php else: ?>—<?php endif; ?>
                    · <?= date('M j, Y', strtotime($f['created_at'])) ?>
                </div>
            </div>
            <div class="d-flex flex-column gap-1 align-items-end">
                <a href="<?= e(assetUrl($f['file_path'])) ?>" target="_blank" class="btn-icon" title="View"><i class="bi bi-eye"></i></a>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="media_id" value="<?= $f['id'] ?>">
                    <button type="submit" name="delete_media" class="btn-icon danger"
                        data-confirm="Delete this file permanently?" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pg['pages'] > 1): ?>
    <div style="padding:1rem 0 0;display:flex;gap:0.5rem;justify-content:center">
        <?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
        <a href="?page=<?= $i ?>&type=<?= urlencode($type) ?>&project=<?= $project ?>"
           style="padding:0.35rem 0.75rem;border-radius:6px;font-size:0.82rem;text-decoration:none;
                  background:<?= $i === $pg['current'] ? '#fff' : 'var(--surface2)' ?>;
                  color:<?= $i === $pg['current'] ? '#000' : 'var(--text-muted)' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
