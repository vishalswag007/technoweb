<?php
/**
 * Vishal Web Studio - Public Blog List
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$posts = [];
try {
    $stmt = db()->query("SELECT b.*, u.name as author_name FROM blog_posts b LEFT JOIN users u ON b.author_id = u.id WHERE b.status = 'published' ORDER BY b.published_at DESC");
    $posts = $stmt->fetchAll();
} catch (Exception $e) {}

$pageTitle = 'Web Design & Business Growth Blog';
$currentNav = 'blog';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="section" style="padding-top: 50px;">
    <div class="container">
        <div class="section-header">
            <div class="section-subtitle">Insights & Guides</div>
            <h1 class="section-title">Web Design & Business Growth</h1>
            <p class="lead">Actionable blueprints to help Indian business owners expand their brand, capture leads, and master digital presence.</p>
        </div>

        <div class="grid-3">
            <?php foreach ($posts as $p): ?>
                <div class="card" style="box-shadow: var(--shadow-md); border-radius: var(--radius-lg); overflow: hidden; display: flex; flex-direction: column;">
                    <div style="height: 180px; background: linear-gradient(135deg, var(--primary) 0%, #0f172a 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; padding: 24px; text-align: center;">
                        <i class="fas fa-newspaper" style="font-size: 2.5rem; opacity: 0.8;"></i>
                    </div>
                    <div class="card-body" style="flex-grow: 1; display: flex; flex-direction: column;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span class="badge badge-primary"><?= e($p['category']) ?></span>
                            <span style="font-size: 0.78rem; color: var(--text-muted);"><i class="fas fa-calendar-alt"></i> <?= format_date($p['published_at']) ?></span>
                        </div>

                        <h3 style="font-size: 1.25rem; margin-bottom: 10px; line-height: 1.35;">
                            <a href="<?= BASE_URL ?>/public/blog-post.php?slug=<?= urlencode($p['slug']) ?>" style="color: var(--dark);">
                                <?= e($p['title']) ?>
                            </a>
                        </h3>

                        <p class="text-muted" style="font-size: 0.9rem; flex-grow: 1; margin-bottom: 16px;">
                            <?= e($p['summary']) ?>
                        </p>

                        <div style="margin-top: auto; padding-top: 14px; border-top: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 0.82rem; color: var(--text-muted);"><i class="fas fa-user-circle"></i> <?= e($p['author_name'] ?? 'Vishal Yaduvansi') ?></span>
                            <a href="<?= BASE_URL ?>/public/blog-post.php?slug=<?= urlencode($p['slug']) ?>" style="font-weight: 600; font-size: 0.88rem;">
                                Read Article <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
