<?php
/**
 * Vishal Web Studio - Single Blog Post Reader
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT b.*, u.name as author_name FROM blog_posts b LEFT JOIN users u ON b.author_id = u.id WHERE b.slug = ? AND b.status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    set_flash('danger', 'The requested blog article was not found.');
    header('Location: ' . BASE_URL . '/public/blog.php');
    exit;
}

// Increment view counter
db()->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?")->execute([$post['id']]);

$pageTitle = $post['meta_title'] ?: $post['title'];
$currentNav = 'blog';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="section" style="padding-top: 50px;">
    <div class="container" style="max-width: 840px;">
        <div style="margin-bottom: 24px;">
            <a href="<?= BASE_URL ?>/public/blog.php" class="btn btn-secondary btn-sm" style="margin-bottom: 16px;">
                <i class="fas fa-arrow-left"></i> Back to Blog
            </a>
            <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px;">
                <span class="badge badge-primary"><?= e($post['category']) ?></span>
                <span style="font-size: 0.85rem; color: var(--text-muted);"><i class="fas fa-calendar-alt"></i> <?= format_date($post['published_at']) ?></span>
                <span style="font-size: 0.85rem; color: var(--text-muted); margin-left: auto;"><i class="fas fa-eye"></i> <?= number_format($post['views'] + 1) ?> views</span>
            </div>
            <h1 style="font-size: 2.5rem; line-height: 1.25; margin-bottom: 16px;"><?= e($post['title']) ?></h1>
            <p class="lead" style="margin-bottom: 24px; font-weight: 500;"><?= e($post['summary']) ?></p>
            <div style="display: flex; align-items: center; gap: 12px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color);">
                <div class="author-avatar" style="width: 40px; height: 40px; font-size: 1rem;">
                    <?= strtoupper(substr($post['author_name'] ?? 'V', 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 0.95rem;"><?= e($post['author_name'] ?? 'Vishal Yaduvansi') ?></div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">Lead Web Strategist & Founder</div>
                </div>
            </div>
        </div>

        <div class="card" style="box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); padding: 40px; line-height: 1.8; font-size: 1.05rem;">
            <?= $post['content'] ?>
        </div>

        <?php if (!empty($post['tags'])): ?>
            <div style="margin-top: 24px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-weight: 600; font-size: 0.88rem; color: var(--text-muted);"><i class="fas fa-tags"></i> Tags:</span>
                <?php foreach (explode(',', $post['tags']) as $tag): ?>
                    <span class="badge badge-secondary"><?= e(trim($tag)) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- CTA Box -->
        <div style="margin-top: 50px; background: linear-gradient(135deg, var(--dark) 0%, #1e293b 100%); border-radius: var(--radius-lg); padding: 36px; color: #ffffff; text-align: center;">
            <h3 style="color: #ffffff; margin-bottom: 10px;">Want a High-Converting Website for Your Business?</h3>
            <p style="color: #94a3b8; font-size: 0.95rem; margin-bottom: 20px;">Get a ready-made template or custom build tailored to your exact industry.</p>
            <a href="<?= BASE_URL ?>/public/order.php" class="btn btn-primary">
                <i class="fas fa-rocket"></i> Get Started with Your Website
            </a>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
