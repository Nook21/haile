<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . BASE_URL . '/'); exit; }

$project = getProjectBySlug($slug);
if (!$project) {
    http_response_code(404);
    $pageTitle = '404 – Not Found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="not-found"><p class="section-eyebrow">404</p><h1>Property Not Found</h1><a href="' . BASE_URL . '/" class="btn-primary">← Back to Properties</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$media    = getProjectMedia($project['id']);
$adjacent = getAdjacentProjects($project['id'], $project['display_order']);
$pageTitle = $project['title'];

$imageMedia = array_values(array_filter($media, fn($m) => $m['type'] === 'image'));
$videoMedia = array_values(array_filter($media, fn($m) => $m['type'] === 'video'));

include __DIR__ . '/includes/header.php';
?>

<div class="property-page">

<!-- HERO -->
<div class="property-hero">
    <?php if ($project['cover_image']): ?>
    <div class="property-hero-bg">
        <img src="<?= e(assetUrl($project['cover_image'])) ?>" alt="<?= e($project['title']) ?>">
        <div class="property-hero-overlay"></div>
    </div>
    <?php else: ?>
    <div class="property-hero-bg property-hero-bg--empty"></div>
    <?php endif; ?>
    <div class="property-hero-content">
        <nav class="property-breadcrumb">
            <a href="<?= BASE_URL ?>/">Home</a>
            <i class="bi bi-chevron-right"></i>
            <a href="<?= BASE_URL ?>/#properties">Properties</a>
            <?php if ($project['category_name']): ?>
            <i class="bi bi-chevron-right"></i>
            <span><?= e($project['category_name']) ?></span>
            <?php endif; ?>
        </nav>
        <h1 class="property-hero-title"><?= e($project['title']) ?></h1>
        <?php if (!empty($project['location'])): ?>
        <p class="property-hero-location"><i class="bi bi-geo-alt"></i> <?= e($project['location']) ?></p>
        <?php endif; ?>
        <div class="property-hero-tags">
            <?php if ($project['category_name']): ?>
            <span class="property-tag"><?= e($project['category_name']) ?></span>
            <?php endif; ?>
            <?php if ($project['year']): ?>
            <span class="property-tag"><?= e($project['year']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($project['price'])): ?>
    <div class="property-price-badge">
        <span class="property-price-label">Price</span>
        <span class="property-price-value"><?= e($project['price']) ?></span>
    </div>
    <?php endif; ?>
</div>

<!-- SPECS BAR -->
<?php if (!empty($project['bedrooms']) || !empty($project['bathrooms']) || !empty($project['area'])): ?>
<div class="property-specs-bar">
    <div class="property-specs-inner">
        <?php if (!empty($project['bedrooms'])): ?>
        <div class="property-spec">
            <i class="bi bi-door-open"></i>
            <span class="property-spec-val"><?= e($project['bedrooms']) ?></span>
            <span class="property-spec-label">Bedrooms</span>
        </div>
        <?php endif; ?>
        <?php if (!empty($project['bathrooms'])): ?>
        <div class="property-spec-div"></div>
        <div class="property-spec">
            <i class="bi bi-droplet"></i>
            <span class="property-spec-val"><?= e($project['bathrooms']) ?></span>
            <span class="property-spec-label">Bathrooms</span>
        </div>
        <?php endif; ?>
        <?php if (!empty($project['area'])): ?>
        <div class="property-spec-div"></div>
        <div class="property-spec">
            <i class="bi bi-aspect-ratio"></i>
            <span class="property-spec-val"><?= e($project['area']) ?></span>
            <span class="property-spec-label">Floor Area</span>
        </div>
        <?php endif; ?>
        <?php if ($project['year']): ?>
        <div class="property-spec-div"></div>
        <div class="property-spec">
            <i class="bi bi-calendar3"></i>
            <span class="property-spec-val"><?= e($project['year']) ?></span>
            <span class="property-spec-label">Year</span>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- BODY -->
<div class="property-body">
    <div class="property-body-inner">

        <!-- Top: Description + Sidebar side by side -->
        <div class="property-body-main">

            <!-- Description -->
            <?php if ($project['description'] || $project['short_description']): ?>
            <div class="property-desc-wrap">
                <p class="section-eyebrow">About This Property</p>
                <div class="property-desc">
                    <?php
                    $desc = $project['description'] ?: $project['short_description'];
                    foreach (array_filter(explode("\n", $desc)) as $para):
                        $para = trim($para);
                        if ($para) echo '<p>' . e($para) . '</p>';
                    endforeach;
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Sidebar -->
            <div class="property-sidebar">
                <div class="property-sidebar-card">
                    <p class="property-sidebar-title">Property Details</p>
                    <?php if ($project['client_name']): ?>
                    <div class="property-sidebar-row">
                        <span class="property-sidebar-label">Developer</span>
                        <span class="property-sidebar-value"><?= e($project['client_name']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($project['location'])): ?>
                    <div class="property-sidebar-row">
                        <span class="property-sidebar-label">Location</span>
                        <span class="property-sidebar-value"><?= e($project['location']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($project['price'])): ?>
                    <div class="property-sidebar-row">
                        <span class="property-sidebar-label">Price</span>
                        <span class="property-sidebar-value property-sidebar-price"><?= e($project['price']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($project['category_name']): ?>
                    <div class="property-sidebar-row">
                        <span class="property-sidebar-label">Type</span>
                        <span class="property-sidebar-value"><?= e($project['category_name']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($project['year']): ?>
                    <div class="property-sidebar-row">
                        <span class="property-sidebar-label">Year</span>
                        <span class="property-sidebar-value"><?= e($project['year']) ?></span>
                    </div>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/#contact" class="btn-primary btn-primary--full" style="margin-top:1.5rem;">
                        Enquire About This Property <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/#properties" class="property-back-link">
                        <i class="bi bi-arrow-left"></i> All Properties
                    </a>
                </div>
            </div>

        </div><!-- /.property-body-main -->

        <!-- Gallery — full width below -->
        <?php if ($imageMedia || $videoMedia): ?>
        <div class="property-gallery">
            <p class="section-eyebrow">Gallery</p>
            <div class="gallery-masonry" id="galleryGrid">
                <?php foreach ($imageMedia as $i => $img): ?>
                <div class="gallery-item fade-up" style="transition-delay:<?= min($i * 0.06, 0.3) ?>s">
                    <div class="gallery-frame">
                        <img src="<?= e(assetUrl($img['file_path'])) ?>" alt="<?= e($img['alt_text'] ?: $project['title']) ?>" <?= $i === 0 ? 'loading="eager"' : 'loading="lazy"' ?>>
                        <div class="gallery-overlay">
                            <button class="gallery-zoom" data-src="<?= e(assetUrl($img['file_path'])) ?>" data-alt="<?= e($img['alt_text'] ?: $project['title']) ?>" data-type="image" aria-label="View full size">
                                <i class="bi bi-arrows-fullscreen"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php foreach ($videoMedia as $i => $vid): ?>
                <div class="gallery-item gallery-item--video fade-up" style="transition-delay:<?= min(($i + count($imageMedia)) * 0.06, 0.3) ?>s">
                    <div class="gallery-frame gallery-frame--video">
                        <video src="<?= e(assetUrl($vid['file_path'])) ?>" preload="metadata" muted playsinline loop></video>
                        <div class="gallery-video-poster"><i class="bi bi-play-circle-fill"></i></div>
                        <div class="gallery-overlay">
                            <button class="gallery-zoom gallery-zoom--video" data-src="<?= e(assetUrl($vid['file_path'])) ?>" data-type="video" aria-label="Play video">
                                <i class="bi bi-play-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.property-body-inner -->
</div><!-- /.property-body -->

<!-- Lightbox -->
<div id="propLightbox">
    <button id="propLbClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    <button id="propLbPrev" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>
    <button id="propLbNext" aria-label="Next"><i class="bi bi-chevron-right"></i></button>
    <img id="propLbImg" src="" alt="">
    <video id="propLbVideo" controls playsinline style="display:none;max-width:92vw;max-height:88vh;border-radius:8px;"></video>
</div>
<script>
window.PROP_MEDIA = [
    <?php foreach ($imageMedia as $img): ?>
    { src: "<?= e(assetUrl($img['file_path'])) ?>", alt: "<?= e(addslashes($img['alt_text'] ?: $project['title'])) ?>", type: 'image' },
    <?php endforeach; ?>
    <?php foreach ($videoMedia as $vid): ?>
    { src: "<?= e(assetUrl($vid['file_path'])) ?>", alt: "", type: 'video' },
    <?php endforeach; ?>
];
</script>

<!-- ADJACENT NAV -->
<nav class="property-nav">
    <?php if ($adjacent['prev']): ?>
    <a href="<?= BASE_URL ?>/property?slug=<?= e($adjacent['prev']['slug']) ?>" class="property-nav-item">
        <span class="property-nav-dir"><i class="bi bi-arrow-left"></i> Previous</span>
        <span class="property-nav-title"><?= e($adjacent['prev']['title']) ?></span>
        <?php if ($adjacent['prev']['cover_image']): ?>
        <img src="<?= e(assetUrl($adjacent['prev']['cover_image'])) ?>" alt="" class="property-nav-thumb" loading="lazy">
        <?php endif; ?>
    </a>
    <?php else: ?><div></div><?php endif; ?>
    <?php if ($adjacent['next']): ?>
    <a href="<?= BASE_URL ?>/property?slug=<?= e($adjacent['next']['slug']) ?>" class="property-nav-item property-nav-item--next">
        <span class="property-nav-dir">Next <i class="bi bi-arrow-right"></i></span>
        <span class="property-nav-title"><?= e($adjacent['next']['title']) ?></span>
        <?php if ($adjacent['next']['cover_image']): ?>
        <img src="<?= e(assetUrl($adjacent['next']['cover_image'])) ?>" alt="" class="property-nav-thumb" loading="lazy">
        <?php endif; ?>
    </a>
    <?php else: ?><div></div><?php endif; ?>
</nav>

</div><!-- /.property-page -->

<?php include __DIR__ . '/includes/footer.php'; ?>
