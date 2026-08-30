<?php
/**
 * Vishal Web Studio - Completed Websites Portfolio
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$websites = [];
try {
    $stmt = db()->query("SELECT w.*, c.business_name, c.city, c.owner_name, t.category, t.name as template_name FROM websites w JOIN clients c ON w.client_id = c.id LEFT JOIN templates t ON w.template_id = t.id ORDER BY w.id DESC");
    $websites = $stmt->fetchAll();
} catch (Exception $e) {}

$pageTitle = 'Our Completed Websites & Live Client Portfolio';
$currentNav = 'portfolio';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="section" style="padding-top: 50px;">
    <div class="container">
        <div class="section-header">
            <div class="section-subtitle">Real Work & Live Results</div>
            <h1 class="section-title">Client Websites Showcase</h1>
            <p class="lead">Explore real websites created for our clients, running with independent client admin panels and custom domain setups.</p>
        </div>

        <div class="grid-2">
            <?php foreach ($websites as $w): ?>
                <div class="card" style="box-shadow: var(--shadow-md); border-radius: var(--radius-lg); overflow: hidden; display: flex; flex-direction: column;">
                    <div style="height: 180px; background: linear-gradient(135deg, <?= e($w['theme_color']) ?> 0%, #0f172a 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #ffffff; padding: 20px; position: relative;">
                        <span class="badge badge-success" style="position: absolute; top: 14px; right: 14px;">
                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i> <?= ucfirst($w['status']) ?>
                        </span>
                        <i class="fas fa-globe" style="font-size: 2.2rem; margin-bottom: 8px;"></i>
                        <h3 style="color: #ffffff; font-size: 1.3rem; margin-bottom: 4px;"><?= e($w['name']) ?></h3>
                        <span style="font-size: 0.85rem; color: #cbd5e1;"><?= e($w['domain'] ?? ($w['slug'] . '.vishalwebstudio.com')) ?></span>
                    </div>

                    <div class="card-body" style="flex-grow: 1; display: flex; flex-direction: column;">
                        <p class="text-muted" style="font-size: 0.92rem; margin-bottom: 16px;">
                            <?= e($w['tagline'] ?: $w['meta_description']) ?>
                        </p>

                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px; display: flex; flex-direction: column; gap: 4px;">
                            <div><i class="fas fa-user-tie text-primary" style="width: 18px;"></i> Client: <strong><?= e($w['business_name']) ?></strong> (<?= e($w['owner_name']) ?>)</div>
                            <div><i class="fas fa-map-marker-alt text-primary" style="width: 18px;"></i> Location: <?= e($w['city'] ?? 'India') ?></div>
                            <div><i class="fas fa-layer-group text-primary" style="width: 18px;"></i> Base Layout: <?= e($w['template_name'] ?? 'Custom Build') ?></div>
                        </div>

                        <div style="margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="fas fa-eye"></i> <?= number_format($w['views_count']) ?> Views</span>
                            <a href="<?= BASE_URL ?>/site/index.php?site=<?= urlencode($w['slug']) ?>" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fas fa-external-link-alt"></i> Visit Live Website
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
