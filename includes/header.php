<?php
/**
 * Vishal Web Studio - Public Header (HyperInfonet 100% Replication Design)
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$pageTitle = $pageTitle ?? 'Website Development & Management Software Company in India | ' . APP_NAME;
$pageDescription = $pageDescription ?? 'Website development, institute ERP, school management software, NGO portals, billing, e-commerce and custom web software services across India.';

$siteEmail = get_setting('email', 'support@vishalwebstudio.com');
$sitePhone = get_setting('phone', '+91 90448 77444');
$siteAddress = get_setting('address', 'Lucknow & Noida, Uttar Pradesh, India');
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#0754b8">
    
    <!-- Bootstrap Icons & Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Main Design System CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/main.css">
</head>
<body>

<?= render_flash_messages() ?>

<!-- 1. Topbar -->
<div class="topbar">
    <div class="container topbar-inner">
        <div class="topbar-contact">
            <span><i class="bi bi-geo-alt-fill"></i> <?= e($siteAddress) ?></span>
            <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $sitePhone)) ?>"><i class="bi bi-telephone-fill"></i> <?= e($sitePhone) ?></a>
            <a href="mailto:<?= e($siteEmail) ?>"><i class="bi bi-envelope-fill"></i> <?= e($siteEmail) ?></a>
        </div>
        <div class="topbar-actions">
            <div class="topbar-socials">
                <a href="https://facebook.com" target="_blank" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                <a href="https://youtube.com" target="_blank" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                <a href="https://linkedin.com" target="_blank" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
            </div>
            <?php if (is_logged_in()): ?>
                <a href="<?= is_super_admin() ? BASE_URL . '/super-admin/index.php' : BASE_URL . '/client/index.php' ?>" class="badge badge-primary" style="font-size: 12px;">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/public/login.php" style="font-size: 12px; font-weight: 700; color: var(--brand-blue);">
                    <i class="bi bi-person-lock"></i> Portal Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 2. Sticky Site Header -->
<header class="site-header">
    <div class="container">
        <div class="navbar-wrapper">
            <a class="site-logo" href="<?= BASE_URL ?>/index.php" aria-label="Vishal Web Studio home">
                <div class="site-logo-badge"><i class="bi bi-display"></i></div>
                <span>HYPER<b>INFO NET</b> <small style="display:block; font-size:10px; color:var(--brand-red); letter-spacing:0.5px; font-weight:700;">IT SOLUTIONS PVT. LTD.</small></span>
            </a>

            <nav>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentUri === '/' || str_contains($currentUri, 'index.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_contains($currentUri, 'about') ? 'active' : '' ?>" href="<?= BASE_URL ?>/public/about.php">About Us</a>
                    </li>
                    <li class="nav-item has-dropdown">
                        <a class="nav-link dropdown-toggle <?= str_contains($currentUri, 'services') ? 'active' : '' ?>" href="<?= BASE_URL ?>/public/services.php" role="button" aria-expanded="false">
                            Services <i class="bi bi-chevron-down" style="font-size: 11px; margin-left: 2px;"></i>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="<?= BASE_URL ?>/public/services.php"><i class="bi bi-grid-fill"></i> All Software &amp; Web Services</a>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/site/index.php?demo=coaching-institute" target="_blank"><i class="bi bi-mortarboard-fill"></i> Institute Management Software</a>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/site/index.php?demo=coaching-institute" target="_blank"><i class="bi bi-building-fill"></i> School Management System</a>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/site/index.php?demo=medical-clinic" target="_blank"><i class="bi bi-hospital-fill"></i> Hospital &amp; Clinic Software</a>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/site/index.php?demo=restaurant-delight" target="_blank"><i class="bi bi-cup-hot-fill"></i> Restaurant Billing &amp; POS</a>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/public/templates.php"><i class="bi bi-window-stack"></i> Website Development Templates</a>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/public/order.php"><i class="bi bi-receipt"></i> Start Project / Order Software</a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/index.php#service-areas">India Service Areas</a>
                    </li>
                    <li class="nav-item has-dropdown">
                        <a class="nav-link dropdown-toggle <?= str_contains($currentUri, 'blog') ? 'active' : '' ?>" href="<?= BASE_URL ?>/public/blog.php" role="button" aria-expanded="false">
                            Resources <i class="bi bi-chevron-down" style="font-size: 11px; margin-left: 2px;"></i>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="<?= BASE_URL ?>/public/blog.php"><i class="bi bi-journal-richtext"></i> SEO Blog &amp; Tech Guides</a>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/public/track-order.php"><i class="bi bi-file-earmark-bar-graph"></i> 15-Stage Live Order Tracker</a>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/public/portfolio.php"><i class="bi bi-laptop"></i> Client Project Portfolio</a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_contains($currentUri, 'portfolio') ? 'active' : '' ?>" href="<?= BASE_URL ?>/public/portfolio.php">Portfolio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/index.php#clients">Clients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_contains($currentUri, 'contact') ? 'active' : '' ?>" href="<?= BASE_URL ?>/public/contact.php">Contact Us</a>
                    </li>
                    <li class="nav-item" style="margin-left: 10px;">
                        <a class="btn btn-brand rounded-pill px-4" href="<?= BASE_URL ?>/public/order.php">
                            <i class="bi bi-send-fill"></i> Get Demo
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>
