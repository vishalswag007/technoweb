<?php
/**
 * Vishal Web Studio - Client Admin Header & Topbar Layout
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_client();

$pageTitle = $pageTitle ?? 'Client Admin';
$user = current_user();
$client = get_current_client_record();
$website = get_current_client_website();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e($client['business_name'] ?? 'Client Portal') ?></title>
    
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
    <?php require_once __DIR__ . '/client_sidebar.php'; ?>

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
                        <i class="fas fa-user-shield"></i>
                        <span>Admin Impersonation Mode</span>
                        <a href="<?= BASE_URL ?>/public/revert.php" class="btn btn-danger btn-sm" style="padding: 2px 8px; font-size: 0.75rem;">Return to Super Admin</a>
                    </div>
                <?php endif; ?>

                <?php if ($website): ?>
                    <a href="<?= BASE_URL ?>/site/index.php?site=<?= urlencode($website['slug']) ?>&preview=1" target="_blank" class="btn btn-secondary btn-sm">
                        <i class="fas fa-eye"></i> Preview Website
                    </a>
                    <a href="<?= BASE_URL ?>/client/publish.php" class="btn btn-success btn-sm" data-confirm="Publish all saved content changes to your live public website?">
                        <i class="fas fa-paper-plane"></i> Publish Changes
                    </a>
                <?php endif; ?>

                <div class="user-profile-menu">
                    <div class="user-avatar-badge">
                        <?= strtoupper(substr($user['name'] ?? 'C', 0, 1)) ?>
                    </div>
                    <div class="user-meta-info">
                        <span class="user-meta-name"><?= e($user['name'] ?? 'Client') ?></span>
                        <span class="user-meta-role"><?= e($client['business_name'] ?? 'Business Owner') ?></span>
                    </div>
                    <a href="<?= BASE_URL ?>/public/logout.php" class="user-logout-btn" title="Log Out">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
