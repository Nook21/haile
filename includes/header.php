<?php
require_once __DIR__ . '/functions.php';
$siteTitle  = getSetting('site_title', 'Haile Estate');
$siteLogo   = getSetting('site_logo');
$favicon    = getSetting('favicon');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<script>document.documentElement.setAttribute('data-theme',localStorage.getItem('theme')||'light');var _t=localStorage.getItem('theme')||'light';document.documentElement.style.background=_t==='light'?'#f8f6f1':'#0d0d0a';document.body&&(document.body.style.background=_t==='light'?'#f8f6f1':'#0d0d0a');</script>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?= e(getSetting('site_description')) ?>">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= e($siteTitle) ?></title>
<?php if ($favicon): ?>
<link rel="icon" href="<?= e(assetUrl($favicon)) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400;1,500&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/public.css">
<?php $heroBg = getSetting('hero_bg_image',''); if ($heroBg): ?>
<link rel="preload" as="image" href="<?= e(assetUrl($heroBg)) ?>" fetchpriority="high">
<?php endif; ?>
</head>
<body style="margin:0;padding:0">

<header class="site-nav" id="siteNav">
    <div class="nav-inner">
        <a href="<?= BASE_URL ?>/" class="nav-logo">
            <?php if ($siteLogo): ?>
            <img src="<?= e(assetUrl($siteLogo)) ?>" alt="<?= e($siteTitle) ?>" class="nav-logo-img">
            <?php else: ?>
            <span class="logo-wordmark"><?= e($siteTitle) ?></span>
            <?php endif; ?>
            <span class="logo-sub">Real Estate Advisor</span>
        </a>
        <nav class="nav-links" id="navLinks">
            <a href="<?= BASE_URL ?>/#properties">Properties</a>
            <a href="<?= BASE_URL ?>/#about">About</a>
            <a href="<?= BASE_URL ?>/#services">Services</a>
            <a href="<?= BASE_URL ?>/#insights">Insights</a>
            <a href="<?= BASE_URL ?>/#contact">Contact</a>
        </nav>
        <div class="nav-right">
            <a href="<?= BASE_URL ?>/#contact" class="nav-book-btn">Book a Consultation</a>
            <button class="theme-toggle theme-toggle--desktop" id="themeToggleDesktop" aria-label="Toggle theme">
                <i class="bi bi-sun toggle-icon-light"></i>
                <i class="bi bi-moon toggle-icon-dark"></i>
            </button>
        </div>
        <div class="nav-mobile-controls">
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                <i class="bi bi-sun toggle-icon-light"></i>
                <i class="bi bi-moon toggle-icon-dark"></i>
            </button>
            <button class="nav-burger" id="navBurger" aria-label="Menu" aria-expanded="false">
                <span></span><span></span>
            </button>
        </div>
    </div>
</header>

<div class="nav-mobile-menu" id="mobileMenu" aria-hidden="true">
    <div class="mobile-menu-inner">
        <a href="<?= BASE_URL ?>/#properties">Properties</a>
        <a href="<?= BASE_URL ?>/#about">About</a>
        <a href="<?= BASE_URL ?>/#services">Services</a>
        <a href="<?= BASE_URL ?>/#insights">Insights</a>
        <a href="<?= BASE_URL ?>/#contact">Contact</a>
        <a href="<?= BASE_URL ?>/#contact" class="mobile-book-btn">Book a Consultation</a>
    </div>
</div>
