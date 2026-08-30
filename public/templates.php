<?php
/**
 * Vishal Web Studio - Website Templates Marketplace
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$activeCat = $_GET['cat'] ?? 'all';

// Fetch all active templates
$templates = [];
try {
    $sql = "SELECT * FROM templates WHERE status = 'active' ORDER BY sort_order ASC";
    $templates = db()->query($sql)->fetchAll();
} catch (Exception $e) {}

$categories = [
    'all' => 'All Templates',
    'business' => 'Business & Agency',
    'restaurant' => 'Restaurant & Cafe',
    'salon' => 'Salon & Spa',
    'coaching' => 'Coaching & Academy',
    'education' => 'Education & School',
    'medical' => 'Medical & Clinics',
    'real-estate' => 'Real Estate',
    'portfolio' => 'Portfolio & Personal',
    'ecommerce' => 'E-Commerce Store'
];

$pageTitle = 'Ready-Made Website Templates & Live Demos';
$currentNav = 'templates';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="section" style="padding-top: 50px;">
    <div class="container">
        <div class="section-header">
            <div class="section-subtitle">Website Template Marketplace</div>
            <h1 class="section-title">Ready-Made Website Demos</h1>
            <p class="lead">Select your business category, explore live responsive previews, and launch your customized website in 7 days.</p>
        </div>

        <!-- Category Filters -->
        <div style="display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; margin-bottom: 40px;">
            <?php foreach ($categories as $key => $name): ?>
                <button type="button" 
                        class="btn btn-sm <?= strtolower($activeCat) === $key ? 'btn-primary active' : 'btn-secondary' ?>" 
                        data-filter-category="<?= $key ?>">
                    <?= e($name) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Templates Grid -->
        <div class="grid-3">
            <?php foreach ($templates as $t): ?>
                <?php 
                $features = json_decode($t['features'] ?? '[]', true); 
                $catSlug = strtolower(slugify($t['category']));
                ?>
                <div class="template-card" data-template-category="<?= e($catSlug) ?>">
                    <div class="template-preview-box">
                        <div style="background: linear-gradient(135deg, <?= e($t['default_theme_color']) ?> 0%, #0f172a 100%); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #ffffff; padding: 20px; text-align: center;">
                            <i class="fas fa-desktop" style="font-size: 2.2rem; margin-bottom: 10px;"></i>
                            <strong style="font-size: 1.1rem;"><?= e($t['name']) ?></strong>
                        </div>
                        <span class="template-category-tag"><?= e($t['category']) ?></span>
                    </div>

                    <div class="template-card-body">
                        <h3 class="template-title"><?= e($t['name']) ?></h3>
                        <p class="template-tagline"><?= e($t['tagline']) ?></p>

                        <ul class="template-features-list">
                            <?php foreach (array_slice($features, 0, 5) as $feat): ?>
                                <li><i class="fas fa-check"></i> <?= e($feat) ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="template-card-footer">
                            <div class="template-price"><?= format_currency($t['price']) ?></div>
                            <div style="display: flex; gap: 8px;">
                                <a href="<?= BASE_URL ?>/public/template-detail.php?slug=<?= urlencode($t['slug']) ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-eye"></i> View Demo
                                </a>
                                <a href="<?= BASE_URL ?>/public/order.php?template=<?= $t['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-check"></i> Use This
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($templates)): ?>
            <div class="text-center" style="padding: 60px 0;">
                <p class="text-muted">No templates found in this category currently.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
