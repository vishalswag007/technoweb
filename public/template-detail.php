<?php
/**
 * Vishal Web Studio - Template Interactive Detail & Responsive Live Demo
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT * FROM templates WHERE slug = ? AND status = 'active'");
$stmt->execute([$slug]);
$template = $stmt->fetch();

if (!$template) {
    set_flash('danger', 'The requested template could not be found.');
    header('Location: ' . BASE_URL . '/public/templates.php');
    exit;
}

$features = json_decode($template['features'] ?? '[]', true);
$pages = json_decode($template['default_pages'] ?? '[]', true);
$demoUrl = BASE_URL . '/site/index.php?demo=' . urlencode($template['slug']);

$pageTitle = $template['name'] . ' - Interactive Demo Preview';
$currentNav = 'templates';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($template['name']) ?> | Live Responsive Demo - Vishal Web Studio</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/main.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/site.css">
</head>
<body style="background: #0f172a; margin: 0; padding: 0; overflow-x: hidden;">

<?= render_flash_messages() ?>

<!-- Responsive Demo Control Bar -->
<div class="demo-viewport-bar">
    <div style="display: flex; align-items: center; gap: 16px;">
        <a href="<?= BASE_URL ?>/public/templates.php" class="btn btn-secondary btn-sm" style="background: #1e293b; color: #ffffff; border-color: #334155;">
            <i class="fas fa-arrow-left"></i> All Templates
        </a>
        <div>
            <strong style="font-size: 1rem; color: #ffffff;"><?= e($template['name']) ?></strong>
            <span class="badge badge-info" style="margin-left: 8px;"><?= e($template['category']) ?></span>
        </div>
    </div>

    <div class="device-switcher-group">
        <button type="button" class="device-btn active" data-device="desktop">
            <i class="fas fa-desktop"></i> Desktop
        </button>
        <button type="button" class="device-btn" data-device="tablet">
            <i class="fas fa-tablet-alt"></i> Tablet
        </button>
        <button type="button" class="device-btn" data-device="mobile">
            <i class="fas fa-mobile-alt"></i> Mobile
        </button>
    </div>

    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 1.15rem; font-weight: 800; color: #38bdf8;">
            <?= format_currency($template['price']) ?>
        </span>
        <a href="<?= $demoUrl ?>" target="_blank" class="btn btn-secondary btn-sm" style="background: #1e293b; color: #ffffff; border-color: #334155;">
            <i class="fas fa-external-link-alt"></i> Open Full
        </a>
        <a href="<?= BASE_URL ?>/public/order.php?template=<?= $template['id'] ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-check"></i> Use This Template
        </a>
    </div>
</div>

<!-- Frame Wrapper -->
<div class="preview-frame-container">
    <iframe src="<?= $demoUrl ?>" class="preview-iframe desktop" title="Live Template Preview"></iframe>
</div>

<!-- Details Modal Trigger info container -->
<div style="background: #ffffff; padding: 60px 0; border-top: 1px solid var(--border-color);">
    <div class="container">
        <div class="grid-2" style="align-items: start;">
            <div>
                <span class="badge badge-primary" style="margin-bottom: 12px;"><?= e($template['category']) ?> Category</span>
                <h2 style="font-size: 2rem; margin-bottom: 12px;"><?= e($template['name']) ?></h2>
                <p class="lead" style="margin-bottom: 20px;"><?= e($template['description']) ?></p>

                <h4 style="margin-bottom: 12px;">Included Pages & Layouts:</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px;">
                    <?php foreach ($pages as $p): ?>
                        <span class="badge badge-secondary" style="font-size: 0.85rem; padding: 6px 12px;">
                            <i class="fas fa-file text-primary" style="margin-right: 4px;"></i> <?= e($p) ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <a href="<?= BASE_URL ?>/public/order.php?template=<?= $template['id'] ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-rocket"></i> Select & Build Website (<?= format_currency($template['price']) ?>)
                </a>
            </div>

            <div class="card" style="box-shadow: var(--shadow-lg);">
                <div class="card-header">
                    <h3 class="card-title">Key Template Features</h3>
                </div>
                <div class="card-body">
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 14px;">
                        <?php foreach ($features as $f): ?>
                            <li style="display: flex; align-items: center; gap: 12px; font-size: 0.95rem;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span><?= e($f) ?></span>
                            </li>
                        <?php endforeach; ?>
                        <li style="display: flex; align-items: center; gap: 12px; font-size: 0.95rem;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0;">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Zero-Code Client Content Management Panel Included</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 12px; font-size: 0.95rem;">
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0;">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Free SSL Certificate & High-Speed NVMe Cloud Hosting</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= ASSETS_URL ?>/js/main.js"></script>
<script src="<?= ASSETS_URL ?>/js/toast.js"></script>
</body>
</html>
