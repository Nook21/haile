<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Dashboard';

// Real stats from DB — 3 queries instead of 9
$db = db();

$projRow = $db->query(
    'SELECT
        COUNT(*) AS total,
        SUM(status="published") AS published,
        SUM(status="draft") AS draft,
        SUM(featured=1 AND status="published") AS featured
     FROM projects'
)->fetch();

$mediaRow = $db->query(
    'SELECT
        COUNT(*) AS total,
        SUM(type="image") AS images,
        SUM(type="video") AS videos
     FROM media'
)->fetch();

$stats = [
    'total_projects'     => $projRow['total'],
    'published_projects' => $projRow['published'],
    'draft_projects'     => $projRow['draft'],
    'featured_projects'  => $projRow['featured'],
    'categories'         => $db->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'clients'            => $db->query('SELECT COUNT(*) FROM clients WHERE status="active"')->fetchColumn(),
    'insights'           => $db->query('SELECT COUNT(*) FROM insights WHERE status="published"')->fetchColumn(),
    'testimonials'       => $db->query('SELECT COUNT(*) FROM testimonials WHERE status="active"')->fetchColumn(),
    'total_media'        => $mediaRow['total'],
    'images'             => $mediaRow['images'],
    'videos'             => $mediaRow['videos'],
];

// Recent projects
$recentProjects = $db->query(
    'SELECT p.*, c.name AS category_name FROM projects p
     LEFT JOIN categories c ON p.category_id = c.id
     ORDER BY p.created_at DESC LIMIT 8'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<?= renderFlash() ?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-building"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total_projects'] ?></div>
                <div class="stat-label">Properties</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-eye"></i></div>
            <div>
                <div class="stat-value"><?= $stats['published_projects'] ?></div>
                <div class="stat-label">Listed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
            <div>
                <div class="stat-value"><?= $stats['draft_projects'] ?></div>
                <div class="stat-label">Drafts</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-value"><?= $stats['clients'] ?></div>
                <div class="stat-label">Clients</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-newspaper"></i></div>
            <div>
                <div class="stat-value"><?= $stats['insights'] ?></div>
                <div class="stat-label">Insights</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-images"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total_media'] ?></div>
                <div class="stat-label">Media Files</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent projects -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="admin-card-title">Recent Properties</span>
                <a href="<?= BASE_URL ?>/admin/projects.php" class="btn-admin-secondary" style="padding:0.35rem 0.85rem;font-size:0.8rem;">View All</a>
            </div>

            <!-- Table: desktop -->
            <div class="d-none d-md-block">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentProjects as $p): ?>
                <tr>
                    <td>
                        <?php if ($p['cover_image']): ?>
                        <img src="<?= e(assetUrl($p['cover_image'])) ?>" class="table-thumb" alt="" loading="lazy">
                        <?php else: ?>
                        <div class="table-thumb-placeholder"><i class="bi bi-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-weight:500"><?= e($p['title']) ?></span>
                        <?php if ($p['featured']): ?><span class="badge-featured ms-1">Featured</span><?php endif; ?>
                    </td>
                    <td><span style="color:var(--text-muted);font-size:0.82rem"><?= e($p['category_name'] ?? '—') ?></span></td>
                    <td><span style="color:var(--text-muted);font-size:0.82rem"><?= e($p['year'] ?? '—') ?></span></td>
                    <td><span class="badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                    <td>
                        <a href="<?= BASE_URL ?>/admin/project-edit.php?id=<?= $p['id'] ?>" class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentProjects)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem">No projects yet. <a href="<?= BASE_URL ?>/admin/project-create.php">Create one</a></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>

            <!-- Cards: mobile -->
            <div class="d-md-none">
                <?php if (empty($recentProjects)): ?>
                <p style="text-align:center;color:var(--text-muted);padding:2rem 0">No projects yet. <a href="<?= BASE_URL ?>/admin/project-create.php">Create one</a></p>
                <?php endif; ?>
                <?php foreach ($recentProjects as $p): ?>
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
                        <span class="badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/project-edit.php?id=<?= $p['id'] ?>" class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <!-- Quick actions + media summary -->
    <div class="col-lg-4">
        <div class="admin-card mb-3">
            <div class="admin-card-header">
                <span class="admin-card-title">Quick Actions</span>
            </div>
            <div class="d-flex flex-column gap-2 quick-actions-grid">
                <a href="<?= BASE_URL ?>/admin/project-create.php" class="btn-admin-primary"><i class="bi bi-plus-lg"></i> Add Property</a>
                <a href="<?= BASE_URL ?>/admin/insights.php" class="btn-admin-secondary"><i class="bi bi-newspaper"></i> Manage Insights</a>
                <a href="<?= BASE_URL ?>/admin/testimonials.php" class="btn-admin-secondary"><i class="bi bi-chat-quote"></i> Testimonials</a>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="btn-admin-secondary"><i class="bi bi-tag"></i> Manage Categories</a>
                <a href="<?= BASE_URL ?>/admin/sponsors" class="btn-admin-secondary"><i class="bi bi-award"></i> Sponsors</a>
                <a href="<?= BASE_URL ?>/admin/clients.php" class="btn-admin-secondary"><i class="bi bi-people"></i> Manage Clients</a>
                <a href="<?= BASE_URL ?>/admin/media.php" class="btn-admin-secondary"><i class="bi bi-images"></i> Media Library</a>
                <a href="<?= BASE_URL ?>/admin/settings.php" class="btn-admin-secondary"><i class="bi bi-gear"></i> Site Settings</a>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <span class="admin-card-title">Media Summary</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span style="color:var(--text-muted);font-size:0.85rem">Images</span>
                <span style="font-weight:600"><?= $stats['images'] ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span style="color:var(--text-muted);font-size:0.85rem">Videos</span>
                <span style="font-weight:600"><?= $stats['videos'] ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span style="color:var(--text-muted);font-size:0.85rem">Featured Properties</span>
                <span style="font-weight:600"><?= $stats['featured_projects'] ?></span>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
