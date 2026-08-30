<?php
/**
 * Vishal Web Studio - Super Admin Header & Topbar Layout
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pageTitle = $pageTitle ?? 'Super Admin Dashboard';
$user = current_user();
$businessName = get_setting('business_name', APP_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Super Admin - <?= e($businessName) ?></title>
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Core & Dashboard CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/main.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
</head>
<body>

<?= render_flash_messages() ?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/admin_sidebar.php'; ?>

    <div class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title"><?= e($pageTitle) ?></h1>
            </div>

            <div class="topbar-right">
                <?php if (is_impersonating()): ?>
                    <div class="impersonation-banner">
                        <i class="fas fa-user-secret"></i>
                        <span>Impersonating Client Session</span>
                        <a href="<?= BASE_URL ?>/public/revert.php" class="btn btn-danger btn-sm" style="padding: 2px 8px; font-size: 0.75rem;">Revert to Admin</a>
                    </div>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-secondary btn-sm" title="Visit Public Agency Site">
                    <i class="fas fa-globe text-primary"></i> Live Platform
                </a>

                <div class="user-profile-menu">
                    <div class="user-avatar-badge admin">
                        <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
                    </div>
                    <div class="user-meta-info">
                        <span class="user-meta-name"><?= e($user['name'] ?? 'Admin') ?></span>
                        <span class="user-meta-role">Super Admin</span>
                    </div>
                    <a href="<?= BASE_URL ?>/public/logout.php" class="user-logout-btn" title="Log Out">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
