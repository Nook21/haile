<?php
// $pageTitle must be set before including this file
$pageTitle = $pageTitle ?? 'Admin';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> — Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body>

<!-- Sidebar -->
<nav class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <span class="brand-name">HE</span>
        <span class="brand-label">Haile Estate CMS</span>
    </div>
    <ul class="sidebar-nav">
        <li class="nav-section">Overview</li>
        <li><a href="<?= BASE_URL ?>/admin/dashboard" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a></li>

        <li class="nav-section">Content</li>
        <li><a href="<?= BASE_URL ?>/admin/projects" class="<?= in_array($currentPage, ['projects','project-create','project-edit']) ? 'active' : '' ?>">
            <i class="bi bi-building"></i> Properties
        </a></li>
        <li><a href="<?= BASE_URL ?>/admin/categories" class="<?= $currentPage === 'categories' ? 'active' : '' ?>">
            <i class="bi bi-tag"></i> Categories
        </a></li>
        <li><a href="<?= BASE_URL ?>/admin/clients" class="<?= $currentPage === 'clients' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Clients
        </a></li>
        <li><a href="<?= BASE_URL ?>/admin/insights" class="<?= $currentPage === 'insights' ? 'active' : '' ?>">
            <i class="bi bi-newspaper"></i> Insights
        </a></li>
        <li><a href="<?= BASE_URL ?>/admin/testimonials" class="<?= $currentPage === 'testimonials' ? 'active' : '' ?>">
            <i class="bi bi-chat-quote"></i> Testimonials
        </a></li>
        <li><a href="<?= BASE_URL ?>/admin/sponsors" class="<?= $currentPage === 'sponsors' ? 'active' : '' ?>">
            <i class="bi bi-award"></i> Sponsors
        </a></li>
        <li><a href="<?= BASE_URL ?>/admin/media" class="<?= $currentPage === 'media' ? 'active' : '' ?>">
            <i class="bi bi-images"></i> Media Library
        </a></li>

        <li class="nav-section">Site</li>
        <li><a href="<?= BASE_URL ?>/admin/services" class="<?= $currentPage === 'services' ? 'active' : '' ?>">
            <i class="bi bi-briefcase"></i> Services
        </a></li>
        <li><a href="<?= BASE_URL ?>/admin/about" class="<?= $currentPage === 'about' ? 'active' : '' ?>">
            <i class="bi bi-person"></i> About
        </a></li>
        <li><a href="<?= BASE_URL ?>/admin/homepage" class="<?= $currentPage === 'homepage' ? 'active' : '' ?>">
            <i class="bi bi-house"></i> Homepage
        </a></li>
        <li><a href="<?= BASE_URL ?>/admin/settings" class="<?= $currentPage === 'settings' ? 'active' : '' ?>">
            <i class="bi bi-gear"></i> Settings
        </a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>" target="_blank" class="sidebar-link"><i class="bi bi-box-arrow-up-right"></i> View Site</a>
        <a href="<?= BASE_URL ?>/admin/logout" class="sidebar-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</nav>

<!-- Main wrapper -->
<div class="admin-main" id="adminMain">
    <!-- Top bar -->
    <header class="admin-topbar">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
        <h1 class="topbar-title"><?= e($pageTitle) ?></h1>
        <div class="topbar-user">
            <i class="bi bi-person-circle me-1"></i>
            <?= e($_SESSION['admin_name'] ?? 'Admin') ?>
        </div>
    </header>
    <!-- Page content starts here -->
    <div class="admin-content">
