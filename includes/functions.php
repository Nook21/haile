<?php
require_once __DIR__ . '/database.php';

// ── Output ────────────────────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Settings ──────────────────────────────────────────────
function getSetting(string $key, string $default = ''): string {
    static $cache = [];
    if (!isset($cache[$key])) {
        $st = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $st->execute([$key]);
        $row = $st->fetch();
        $cache[$key] = $row ? (string)$row['setting_value'] : $default;
    }
    return $cache[$key];
}

function getAllSettings(): array {
    $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $out = [];
    foreach ($rows as $r) $out[$r['setting_key']] = $r['setting_value'];
    return $out;
}

// ── Slug ──────────────────────────────────────────────────
function makeSlug(string $text): string {
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ── CSRF ──────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

// ── Projects ──────────────────────────────────────────────
function getPublishedProjects(?int $categoryId = null): array {
    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug, cl.name AS client_name,
            (SELECT file_path FROM media WHERE project_id = p.id AND type = "video" ORDER BY display_order ASC LIMIT 1) AS video_preview
            FROM projects p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN clients cl ON p.client_id = cl.id
            WHERE p.status = "published"';
    if ($categoryId) {
        $st = db()->prepare($sql . ' AND p.category_id = ? ORDER BY p.display_order ASC, p.created_at DESC');
        $st->execute([$categoryId]);
    } else {
        $st = db()->query($sql . ' ORDER BY p.display_order ASC, p.created_at DESC');
    }
    return $st->fetchAll();
}

function getFeaturedProjects(int $limit = 6): array {
    $st = db()->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM projects p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = "published" AND p.featured = 1
        ORDER BY p.display_order ASC LIMIT ?');
    $st->execute([$limit]);
    return $st->fetchAll();
}

function getProjectBySlug(string $slug): ?array {
    $st = db()->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug, cl.name AS client_name
        FROM projects p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN clients cl ON p.client_id = cl.id
        WHERE p.slug = ? AND p.status = "published"');
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}

function getProjectMedia(int $projectId): array {
    $st = db()->prepare('SELECT * FROM media WHERE project_id = ? ORDER BY display_order ASC, id ASC');
    $st->execute([$projectId]);
    return $st->fetchAll();
}

function getAdjacentProjects(int $projectId, int $order): array {
    $prev = db()->prepare('SELECT id, title, slug, cover_image FROM projects WHERE status="published" AND display_order < ? ORDER BY display_order DESC LIMIT 1');
    $prev->execute([$order]);
    $next = db()->prepare('SELECT id, title, slug, cover_image FROM projects WHERE status="published" AND display_order > ? ORDER BY display_order ASC LIMIT 1');
    $next->execute([$order]);
    return ['prev' => $prev->fetch() ?: null, 'next' => $next->fetch() ?: null];
}

// ── Categories ────────────────────────────────────────────
function getCategories(): array {
    return db()->query('SELECT c.*, COUNT(p.id) AS project_count FROM categories c LEFT JOIN projects p ON p.category_id = c.id AND p.status = "published" GROUP BY c.id ORDER BY c.display_order ASC, c.name ASC')->fetchAll();
}

// ── Clients ───────────────────────────────────────────────
function getActiveClients(): array {
    return db()->query('SELECT * FROM clients WHERE status = "active" ORDER BY display_order ASC, name ASC')->fetchAll();
}

function getActiveSponsors(): array {
    return db()->query('SELECT * FROM sponsors WHERE status = "active" ORDER BY display_order ASC, name ASC')->fetchAll();
}

function getFavoriteProjects(): array {
    return db()->query('SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM projects p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = "published" AND p.favorite = 1
        ORDER BY p.display_order ASC, p.created_at DESC')->fetchAll();
}

// ── Insights ─────────────────────────────────────────────
function getPublishedInsights(int $limit = 3): array {
    $st = db()->prepare('SELECT * FROM insights WHERE status = "published" ORDER BY display_order ASC, created_at DESC LIMIT ?');
    $st->execute([$limit]);
    return $st->fetchAll();
}

function getInsightBySlug(string $slug): ?array {
    $st = db()->prepare('SELECT * FROM insights WHERE slug = ? AND status = "published"');
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}

function getActiveTestimonials(): array {
    return db()->query('SELECT * FROM testimonials WHERE status = "active" ORDER BY display_order ASC')->fetchAll();
}

// ── Services ──────────────────────────────────────────────
function getActiveServices(): array {
    return db()->query('SELECT * FROM services WHERE status = "active" ORDER BY display_order ASC')->fetchAll();
}

// ── About ─────────────────────────────────────────────────
function getAbout(): ?array {
    return db()->query('SELECT * FROM about LIMIT 1')->fetch() ?: null;
}

// ── File Upload ───────────────────────────────────────────
function validateUpload(array $file, string $type = 'image'): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload error code: ' . $file['error']];
    }
    $maxSize = $type === 'video' ? MAX_VIDEO_SIZE : MAX_IMAGE_SIZE;
    if ($file['size'] > $maxSize) {
        return ['ok' => false, 'error' => 'File too large.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts  = $type === 'video' ? ALLOWED_VIDEO_EXTS  : ALLOWED_IMAGE_EXTS;
    $allowedMimes = $type === 'video' ? ALLOWED_VIDEO_MIMES : ALLOWED_IMAGE_MIMES;
    if (!in_array($ext, $allowedExts, true)) {
        return ['ok' => false, 'error' => 'File type not allowed.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMimes, true)) {
        return ['ok' => false, 'error' => 'Invalid file MIME type.'];
    }
    if ($type === 'image' && !getimagesize($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'File is not a valid image.'];
    }
    // For videos, accept any ext-matched file (MIME varies widely by container)
    if ($type === 'video') {
        return ['ok' => true, 'ext' => $ext, 'mime' => $mime];
    }
    return ['ok' => true, 'ext' => $ext, 'mime' => $mime];
}

function compressImage(string $path, int $quality = 82): void {
    $size = @filesize($path);
    // Skip compression for files under 100KB — not worth the memory
    if (!$size || $size < 102400) return;

    $info = @getimagesize($path);
    if (!$info) return;

    // Skip very large images that would exhaust memory (>20MP)
    [$w, $h] = $info;
    if ($w * $h > 20000000) return;

    $mime = $info['mime'];

    // Only process JPEG and PNG — skip webp/avif (already efficient)
    if ($mime === 'image/jpeg') {
        $src = @imagecreatefromjpeg($path);
        if (!$src) return;
        imageinterlace($src, 1);
        imagejpeg($src, $path, $quality);
        imagedestroy($src);
    } elseif ($mime === 'image/png') {
        $src = @imagecreatefrompng($path);
        if (!$src) return;
        imagesavealpha($src, true);
        imagepng($src, $path, 7);
        imagedestroy($src);
    }
}

function saveUpload(array $file, string $subdir): ?array {
    $type = in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ALLOWED_VIDEO_EXTS) ? 'video' : 'image';
    $validation = validateUpload($file, $type);
    if (!$validation['ok']) return null;

    $dir = UPLOAD_PATH . $subdir . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // Keep original extension for images (compressImage decides final format)
    $origExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $ext = $type === 'image' ? $origExt : $validation['ext'];
    $filename = uniqid('', true) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;

    if ($type === 'image') compressImage($dest);

    return ['path' => 'uploads/' . $subdir . '/' . $filename, 'size' => filesize($dest)];
}

function deleteFile(string $relativePath): void {
    $abs = __DIR__ . '/../' . ltrim($relativePath, '/');
    if (file_exists($abs)) unlink($abs);
}

// ── Pagination ────────────────────────────────────────────
function paginate(int $total, int $perPage, int $current): array {
    $pages = (int)ceil($total / $perPage);
    return [
        'total'    => $total,
        'per_page' => $perPage,
        'current'  => $current,
        'pages'    => $pages,
        'offset'   => ($current - 1) * $perPage,
    ];
}

// ── Flash messages ────────────────────────────────────────
function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function renderFlash(): string {
    $f = getFlash();
    if (!$f) return '';
    $cls = $f['type'] === 'success' ? 'alert-success' : ($f['type'] === 'error' ? 'alert-danger' : 'alert-info');
    return '<div class="alert ' . $cls . ' alert-dismissible fade show" role="alert">'
        . e($f['msg'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

// ── Image URL helper ──────────────────────────────────────
function assetUrl(string $path): string {
    if (!$path) return BASE_URL . '/assets/images/placeholder.svg';
    return BASE_URL . '/' . ltrim($path, '/');
}
