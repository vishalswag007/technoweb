<?php
/**
 * Vishal Web Studio - Super Admin Blog CMS & SEO Articles
 * Clean 3D Layout with Fixed Modals & Zero Data Leaks
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Handle Delete Post
if (isset($_GET['delete'])) {
    $postId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$postId]);
    log_activity(current_user_id(), 'blog_deleted', 'blog_posts', $postId, "Deleted blog post #{$postId}");
    set_flash('success', "Blog post deleted successfully.");
    header('Location: ' . BASE_URL . '/super-admin/blog.php');
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $postId = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT id, status FROM blog_posts WHERE id = ?");
    $stmt->execute([$postId]);
    $p = $stmt->fetch();
    if ($p) {
        $newStatus = ($p['status'] === 'published') ? 'draft' : 'published';
        $pdo->prepare("UPDATE blog_posts SET status = ? WHERE id = ?")->execute([$newStatus, $postId]);
        set_flash('success', "Post status updated to {$newStatus}.");
    }
    header('Location: ' . BASE_URL . '/super-admin/blog.php');
    exit;
}

// Handle Add / Edit Post POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_blog'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $postId = (int)($_POST['post_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Web Design');
        $summary = trim($_POST['summary'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $tags = trim($_POST['tags'] ?? '');
        $metaTitle = trim($_POST['meta_title'] ?? $title);
        $metaDesc = trim($_POST['meta_description'] ?? $summary);
        $status = $_POST['status'] ?? 'published';

        if (empty($title) || empty($content)) {
            set_flash('danger', 'Please provide a title and article body.');
        } else {
            $slug = slugify($title);
            if ($postId > 0) {
                $upd = $pdo->prepare("UPDATE blog_posts SET title = ?, category = ?, summary = ?, content = ?, tags = ?, meta_title = ?, meta_description = ?, status = ? WHERE id = ?");
                $upd->execute([$title, $category, $summary, $content, $tags, $metaTitle, $metaDesc, $status, $postId]);
                log_activity(current_user_id(), 'blog_updated', 'blog_posts', $postId, "Updated blog post '{$title}'");
                set_flash('success', "Article '{$title}' updated successfully.");
            } else {
                $ins = $pdo->prepare("INSERT INTO blog_posts (author_id, title, slug, summary, content, category, tags, meta_title, meta_description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([current_user_id(), $title, $slug, $summary, $content, $category, $tags, $metaTitle, $metaDesc, $status]);
                $newPostId = $pdo->lastInsertId();
                log_activity(current_user_id(), 'blog_created', 'blog_posts', $newPostId, "Created new blog post '{$title}'");
                set_flash('success', "New article '{$title}' published.");
            }
            header('Location: ' . BASE_URL . '/super-admin/blog.php');
            exit;
        }
    }
}

// Fetch Posts
$posts = $pdo->query("SELECT b.*, u.name as author_name FROM blog_posts b LEFT JOIN users u ON b.author_id = u.id ORDER BY b.id DESC")->fetchAll();

$pageTitle = 'Blog CMS & SEO Articles';
$adminNav = 'blog';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
        Total Articles: <strong><?= count($posts) ?></strong> published guides and blueprints.
    </p>

    <button type="button" class="btn btn-primary btn-sm" onclick="openBlogModal()">
        <i class="fas fa-plus"></i> Write New Article
    </button>
</div>

<!-- Blog Table (Clean & Protected against JSON leaking) -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 40%;">Article Title &amp; Slug</th>
                    <th>Category</th>
                    <th>Views</th>
                    <th>Status</th>
                    <th>Published Date</th>
                    <th style="text-align: right; width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                    <tr><td colspan="6" class="text-center text-muted" style="padding: 30px;">No blog posts created yet. Click Write New Article above.</td></tr>
                <?php else: ?>
                    <?php foreach ($posts as $p): ?>
                        <tr>
                            <td>
                                <strong style="font-size: 0.95rem; color: var(--dark); display: block; margin-bottom: 2px;"><?= e($p['title']) ?></strong>
                                <span style="font-size: 0.78rem; color: var(--text-muted); font-family: monospace;">/public/blog-post.php?slug=<?= e($p['slug']) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?= e($p['category']) ?></span>
                            </td>
                            <td>
                                <span style="font-weight: 700; color: var(--primary);"><i class="fas fa-eye"></i> <?= number_format($p['views']) ?></span>
                            </td>
                            <td><?= render_status_badge($p['status']) ?></td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                <?= format_date($p['published_at']) ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= BASE_URL ?>/public/blog-post.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="View Article">
                                        <i class="fas fa-eye text-primary"></i>
                                    </a>

                                    <button type="button" class="btn btn-secondary btn-sm btn-edit-blog" data-blog-id="<?= $p['id'] ?>" title="Edit Article">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <a href="<?= BASE_URL ?>/super-admin/blog.php?toggle=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="Toggle Publish/Draft">
                                        <i class="fas fa-toggle-on <?= $p['status'] === 'published' ? 'text-success' : 'text-muted' ?>"></i>
                                    </a>

                                    <a href="<?= BASE_URL ?>/super-admin/blog.php?delete=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="Delete Article" data-confirm="Are you sure you want to delete this blog post?">
                                        <i class="fas fa-trash text-danger"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Write / Edit Blog Modal -->
<div class="modal-backdrop" id="blogModal">
    <div class="modal-dialog" style="max-width: 850px;">
        <div class="modal-header">
            <h3 class="modal-title" id="blogModalTitle">Write New Article</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="save_blog" value="1">
            <input type="hidden" name="post_id" id="modal_blog_id" value="0">

            <div class="modal-body" style="padding: 24px;">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="blog_title">Article Title *</label>
                        <input type="text" name="title" id="blog_title" class="form-control" required placeholder="e.g. 5 Growth Hacks for Modern Local Businesses">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="blog_category">Category</label>
                        <select name="category" id="blog_category" class="form-select">
                            <option value="Web Design">Web Design</option>
                            <option value="Business Growth">Business Growth</option>
                            <option value="Local SEO">Local SEO</option>
                            <option value="Technology">Technology</option>
                            <option value="Marketing">Marketing</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="blog_summary">Short Summary / Excerpt</label>
                    <textarea name="summary" id="blog_summary" class="form-control" rows="2" placeholder="Brief 1-2 sentence preview for search engines and listing cards..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="blog_content">Article Content (HTML / Markdown supported) *</label>
                    <textarea name="content" id="blog_content" class="form-control" rows="8" required placeholder="Write your full guide, formatting with <h3>, <p>, <ul>, etc..."></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="blog_tags">Tags (Comma Separated)</label>
                        <input type="text" name="tags" id="blog_tags" class="form-control" placeholder="e.g. website, local-business, growth">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="blog_status">Publication Status</label>
                        <select name="status" id="blog_status" class="form-select">
                            <option value="published">Published (Live immediately)</option>
                            <option value="draft">Draft (Private / Work in progress)</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="blog_m_title">SEO Meta Title</label>
                        <input type="text" name="meta_title" id="blog_m_title" class="form-control" placeholder="Defaults to post title if blank">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="blog_m_desc">SEO Meta Description</label>
                        <input type="text" name="meta_description" id="blog_m_desc" class="form-control" placeholder="Defaults to summary if blank">
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="padding: 16px 24px; background: #fafcff; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Publish / Save Article</button>
            </div>
        </form>
    </div>
</div>

<!-- Blog Data JSON Store for Safe Non-Leaking Modal Editing -->
<script>
const blogData = <?= json_encode($posts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function openBlogModal() {
    document.getElementById('blogModalTitle').innerText = 'Write New Article';
    document.getElementById('modal_blog_id').value = '0';
    document.getElementById('blog_title').value = '';
    document.getElementById('blog_category').value = 'Web Design';
    document.getElementById('blog_summary').value = '';
    document.getElementById('blog_content').value = '';
    document.getElementById('blog_tags').value = '';
    document.getElementById('blog_m_title').value = '';
    document.getElementById('blog_m_desc').value = '';
    document.getElementById('blog_status').value = 'published';
    document.getElementById('blogModal').classList.add('show');
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-edit-blog').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = parseInt(btn.getAttribute('data-blog-id'), 10);
            const p = blogData.find(item => parseInt(item.id, 10) === id);
            if (p) {
                document.getElementById('blogModalTitle').innerText = 'Edit Article: ' + p.title;
                document.getElementById('modal_blog_id').value = p.id;
                document.getElementById('blog_title').value = p.title || '';
                document.getElementById('blog_category').value = p.category || 'Web Design';
                document.getElementById('blog_summary').value = p.summary || '';
                document.getElementById('blog_content').value = p.content || '';
                document.getElementById('blog_tags').value = p.tags || '';
                document.getElementById('blog_m_title').value = p.meta_title || '';
                document.getElementById('blog_m_desc').value = p.meta_description || '';
                document.getElementById('blog_status').value = p.status || 'published';
                document.getElementById('blogModal').classList.add('show');
            }
        });
    });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
