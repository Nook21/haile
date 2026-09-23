<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . BASE_URL . '/#insights'); exit; }

$insight = getInsightBySlug($slug);
if (!$insight) {
    http_response_code(404);
    $pageTitle = '404 – Not Found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="not-found"><p class="section-eyebrow">404</p><h1>Insight Not Found</h1><a href="' . BASE_URL . '/#insights" class="btn-primary">← Back to Insights</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $insight['title'];
include __DIR__ . '/includes/header.php';
?>

<div class="insight-page">

    <!-- Hero -->
    <div class="insight-hero">
        <?php if ($insight['cover_image']): ?>
        <div class="insight-hero-bg">
            <img src="<?= e(assetUrl($insight['cover_image'])) ?>" alt="<?= e($insight['title']) ?>">
            <div class="insight-hero-overlay"></div>
        </div>
        <?php endif; ?>
        <div class="insight-hero-content">
            <nav class="property-breadcrumb">
                <a href="<?= BASE_URL ?>/">Home</a>
                <i class="bi bi-chevron-right"></i>
                <a href="<?= BASE_URL ?>/#insights">Insights</a>
                <?php if ($insight['category']): ?>
                <i class="bi bi-chevron-right"></i>
                <span><?= e($insight['category']) ?></span>
                <?php endif; ?>
            </nav>
            <?php if ($insight['category']): ?>
            <span class="insight-hero-cat"><?= e($insight['category']) ?></span>
            <?php endif; ?>
            <h1 class="insight-hero-title"><?= e($insight['title']) ?></h1>
            <p class="insight-hero-date"><?= date('F Y', strtotime($insight['created_at'])) ?></p>
        </div>
    </div>

    <!-- Body -->
    <div class="insight-body">
        <div class="insight-body-inner">
            <?php if ($insight['excerpt']): ?>
            <p class="insight-excerpt"><?= e($insight['excerpt']) ?></p>
            <?php endif; ?>
            <?php if ($insight['body']): ?>
            <div class="insight-content">
                <?php foreach (array_filter(explode("\n", $insight['body'])) as $para): ?>
                <p><?= e(trim($para)) ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="insight-back">
                <a href="<?= BASE_URL ?>/#insights" class="property-back-link">
                    <i class="bi bi-arrow-left"></i> All Insights
                </a>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
