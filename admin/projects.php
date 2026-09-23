<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Properties';

// Handle favorite toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_favorite'])) {
    verifyCsrf();
    $id  = (int)$_POST['project_id'];
    $cur = db()->prepare('SELECT favorite FROM projects WHERE id = ?');
    $cur->execute([$id]);
    $new = $cur->fetchColumn() ? 0 : 1;
    db()->prepare('UPDATE projects SET favorite = ? WHERE id = ?')->execute([$new, $id]);
    header('Location: ' . BASE_URL . '/admin/projects.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// Handle quick status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    verifyCsrf();
    $id = (int)$_POST['project_id'];
    $st = db()->prepare('SELECT status FROM projects WHERE id = ?');
    $st->execute([$id]);
    $cur = $st->fetchColumn();
    $new = $cur === 'published' ? 'draft' : 'published';
    db()->prepare('UPDATE projects SET status = ? WHERE id = ?')->execute([$new, $id]);
    flash('success', 'Project status updated.');
    header('Location: ' . BASE_URL . '/admin/projects.php');
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    verifyCsrf();
    $id = (int)$_POST['project_id'];
    // Delete associated media files
    $mediaRows = db()->prepare('SELECT file_path, thumbnail_path FROM media WHERE project_id = ?');
    $mediaRows->execute([$id]);
    foreach ($mediaRows->fetchAll() as $m) {
        if ($m['file_path'])      deleteFile($m['file_path']);
        if ($m['thumbnail_path']) deleteFile($m['thumbnail_path']);
    }
    // Delete cover
    $cover = db()->prepare('SELECT cover_image FROM projects WHERE id = ?');
    $cover->execute([$id]);
    $coverPath = $cover->fetchColumn();
    if ($coverPath) deleteFile($coverPath);
    // DB cascade deletes media records
    db()->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
    flash('success', 'Project deleted.');
    header('Location: ' . BASE_URL . '/admin/projects.php');
    exit;
}

// Pagination
$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$search  = trim($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);

$where = '1=1';
$params = [];
if ($search) { $where .= ' AND p.title LIKE ?'; $params[] = "%$search%"; }
if ($catFilter) { $where .= ' AND p.category_id = ?'; $params[] = $catFilter; }

$total = db()->prepare("SELECT COUNT(*) FROM projects p WHERE $where");
$total->execute($params);
$pg = paginate((int)$total->fetchColumn(), $perPage, $page);

$st = db()->prepare("SELECT p.*, c.name AS category_name, cl.name AS client_name
    FROM projects p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN clients cl ON p.client_id = cl.id
    WHERE $where
    ORDER BY p.display_order ASC, p.created_at DESC
    LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$st->execute($params);
$projects = $st->fetchAll();

$categories = getCategories();
include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<div class="page-header">
    
    <h2>Properties</h2>
    <a href="<?= BASE_URL ?>/admin/project-create.php" class="btn-admin-primary"><i class="bi bi-plus-lg"></i> Add Property</a>
</div>

<!-- Filters -->
<div class="admin-card mb-3" style="padding:1rem 1.25rem">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-5">
            <input type="text" name="q" class="form-control" placeholder="Search projects…" value="<?= e($search) ?>">
        </div>
        <div class="col-12 col-md-4">
            <select name="cat" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn-admin-secondary flex-fill">Filter</button>
            <?php if ($search || $catFilter): ?>
            <a href="<?= BASE_URL ?>/admin/projects.php" class="btn-admin-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-card">
    <!-- Table: desktop -->
    <div class="d-none d-md-block">
    <table class="admin-table">
        <thead>
            <tr>
                <th></th>
                <th>Title</th>
                <th>Category</th>
                <th>Location</th>
                <th>Price</th>
                <th>Year</th>
                <th>Status</th>
                <th>Order</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($projects as $p): ?>
        <tr>
            <td>
                <?php if ($p['cover_image']): ?>
                <img src="<?= e(assetUrl($p['cover_image'])) ?>" class="table-thumb" alt="" loading="lazy">
                <?php else: ?>
                <div class="table-thumb-placeholder"><i class="bi bi-image"></i></div>
                <?php endif; ?>
            </td>
            <td>
                <div style="font-weight:500"><?= e($p['title']) ?></div>
                <?php if ($p['featured']): ?><span class="badge-featured">Featured</span><?php endif; ?>
                <div style="font-size:0.75rem;color:var(--text-muted)"><?= e($p['slug']) ?></div>
            </td>
            <td style="color:var(--text-muted);font-size:0.85rem"><?= e($p['category_name'] ?? '—') ?></td>
            <td style="color:var(--text-muted);font-size:0.85rem"><?= e($p['location'] ?? '—') ?></td>
            <td style="color:var(--text-muted);font-size:0.85rem"><?= e($p['price'] ?? '—') ?></td>
            <td style="color:var(--text-muted);font-size:0.85rem"><?= e($p['year'] ?? '—') ?></td>
            <td>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                    <button type="submit" name="toggle_status" class="badge-<?= $p['status'] ?>" style="border:none;cursor:pointer;background:none;padding:0">
                        <?= ucfirst($p['status']) ?>
                    </button>
                </form>
            </td>
            <td style="color:var(--text-muted);font-size:0.85rem"><?= $p['display_order'] ?></td>
            <td>
                <div class="d-flex gap-1">
                    <a href="<?= BASE_URL ?>/admin/project-edit.php?id=<?= $p['id'] ?>" class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form method="POST" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                        <button type="submit" name="toggle_favorite"
                            class="btn-icon <?= $p['favorite'] ? 'active' : '' ?>"
                            title="<?= $p['favorite'] ? 'Remove from favourites' : 'Mark as favourite' ?>"
                            style="color:<?= $p['favorite'] ? '#f5c800' : 'var(--text-muted)' ?>">
                            <i class="bi bi-star<?= $p['favorite'] ? '-fill' : '' ?>"></i>
                        </button>
                    </form>
                    <?php if ($p['status'] === 'published'): ?>
                    <a href="<?= BASE_URL ?>/project.php?slug=<?= e($p['slug']) ?>" target="_blank" class="btn-icon" title="View"><i class="bi bi-box-arrow-up-right"></i></a>
                    <?php endif; ?>
                    <form method="POST" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                        <button type="submit" name="delete_project" class="btn-icon danger"
                            data-confirm="Delete '<?= e(addslashes($p['title'])) ?>'? This will also delete all associated media files. This cannot be undone."
                            title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($projects)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2.5rem">
            No projects found. <a href="<?= BASE_URL ?>/admin/project-create.php">Create your first project</a>
        </td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <!-- Cards: mobile -->
    <div class="d-md-none">
        <?php if (empty($projects)): ?>
        <p style="text-align:center;color:var(--text-muted);padding:2rem 0">No projects found. <a href="<?= BASE_URL ?>/admin/project-create.php">Create your first project</a></p>
        <?php endif; ?>
        <?php foreach ($projects as $p): ?>
        <div class="proj-mobile-card">
            <?php if ($p['cover_image']): ?>
            <img src="<?= e(assetUrl($p['cover_image'])) ?>" class="proj-mobile-thumb" alt="" loading="lazy">
            <?php else: ?>
            <div class="proj-mobile-thumb proj-mobile-thumb--empty"><i class="bi bi-image"></i></div>
            <?php endif; ?>
            <div class="proj-mobile-info">
                <div class="proj-mobile-title">
                    <?= e($p['title']) ?>
                    <?php if ($p['featured']): ?><span class="badge-featured ms-1">Featured</span><?php endif; ?>
                </div>
                <div class="proj-mobile-meta">
                    <span><?= e($p['category_name'] ?? '—') ?></span>
                    <?php if ($p['year']): ?><span>· <?= e($p['year']) ?></span><?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <form method="POST" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                        <button type="submit" name="toggle_status" class="badge-<?= $p['status'] ?>" style="border:none;cursor:pointer;background:none;padding:0">
                            <?= ucfirst($p['status']) ?>
                        </button>
                    </form>
                </div>
            </div>
            <div class="d-flex flex-column gap-1 align-items-end">
                <a href="<?= BASE_URL ?>/admin/project-edit.php?id=<?= $p['id'] ?>" class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                    <button type="submit" name="toggle_favorite" class="btn-icon"
                        style="color:<?= $p['favorite'] ? '#f5c800' : 'var(--text-muted)' ?>">
                        <i class="bi bi-star<?= $p['favorite'] ? '-fill' : '' ?>"></i>
                    </button>
                </form>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                    <button type="submit" name="delete_project" class="btn-icon danger"
                        data-confirm="Delete '<?= e(addslashes($p['title'])) ?>'? This cannot be undone."
                        title="Delete"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pg['pages'] > 1): ?>
    <div style="padding:1rem 0 0;display:flex;gap:0.5rem;justify-content:center;flex-wrap:wrap">
        <?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
        <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&cat=<?= $catFilter ?>"
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
