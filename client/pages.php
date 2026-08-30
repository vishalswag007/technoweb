<?php
/**
 * Vishal Web Studio - Client Custom Pages Manager
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_client();

$client = get_current_client_record();
$website = get_current_client_website();
$pdo = db();

if (!$website) die("<h3>No website found.</h3>");

$websiteId = $website['id'];

// Handle Add / Edit Page POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrfToken)) {
        $pId = (int)($_POST['page_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $metaTitle = trim($_POST['meta_title'] ?? $title);
        $metaDesc = trim($_POST['meta_description'] ?? '');

        if (!empty($title)) {
            $slug = slugify($title);
            if ($pId > 0) {
                $upd = $pdo->prepare("UPDATE website_pages SET title = ?, slug = ?, content = ?, meta_title = ?, meta_description = ? WHERE id = ? AND website_id = ?");
                $upd->execute([$title, $slug, $content, $metaTitle, $metaDesc, $pId, $websiteId]);
                set_flash('success', "Page '{$title}' updated.");
            } else {
                $ins = $pdo->prepare("INSERT INTO website_pages (website_id, title, slug, content, meta_title, meta_description, status) VALUES (?, ?, ?, ?, ?, ?, 'published')");
                $ins->execute([$websiteId, $title, $slug, $content, $metaTitle, $metaDesc]);
                set_flash('success', "New page '{$title}' created.");
            }
        }
        header('Location: ' . BASE_URL . '/client/pages.php');
        exit;
    }
}

// Handle Delete Page
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM website_pages WHERE id = ? AND website_id = ?")->execute([$delId, $websiteId]);
    set_flash('success', 'Page removed.');
    header('Location: ' . BASE_URL . '/client/pages.php');
    exit;
}

$pages = $pdo->query("SELECT * FROM website_pages WHERE website_id = {$websiteId} ORDER BY sort_order ASC, id ASC")->fetchAll();

$pageTitle = 'Custom Pages Manager';
$clientNav = 'pages';
require_once dirname(__DIR__) . '/includes/client_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
        Total Pages: <strong><?= count($pages) ?></strong> pages on your website.
    </p>

    <button type="button" class="btn btn-primary btn-sm" onclick="openPageModal()">
        <i class="fas fa-plus"></i> Add New Custom Page
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Page Title</th>
                    <th>URL Slug</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pages)): ?>
                    <tr><td colspan="5" class="text-center text-muted" style="padding: 30px;">No custom pages found.</td></tr>
                <?php else: ?>
                    <?php foreach ($pages as $p): ?>
                        <tr>
                            <td>
                                <strong style="font-size: 0.95rem; color: var(--dark);"><?= e($p['title']) ?></strong>
                            </td>
                            <td>
                                <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;">/<?= e($p['slug']) ?></code>
                            </td>
                            <td><?= render_status_badge($p['status']) ?></td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                <?= format_date($p['updated_at'] ?? $p['created_at']) ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick='editPage(<?= json_encode($p) ?>)'>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="<?= BASE_URL ?>/client/pages.php?delete=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" data-confirm="Delete this page?">
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

<!-- Page Modal -->
<div class="modal-backdrop" id="pageModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="pageModalTitle">Add Custom Page</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="save_page" value="1">
            <input type="hidden" name="page_id" id="modal_page_id" value="0">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="page_title">Page Title *</label>
                    <input type="text" name="title" id="page_title" class="form-control" required placeholder="e.g. Terms & Privacy or Catering Menu">
                </div>

                <div class="form-group">
                    <label class="form-label" for="page_content">Page Body Content (HTML Allowed)</label>
                    <textarea name="content" id="page_content" class="form-control" rows="6" placeholder="<p>Enter your page content...</p>"></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="page_meta_title">SEO Title</label>
                        <input type="text" name="meta_title" id="page_meta_title" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="page_meta_desc">SEO Description</label>
                        <input type="text" name="meta_description" id="page_meta_desc" class="form-control">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Page</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPageModal() {
    document.getElementById('pageModalTitle').textContent = 'Add Custom Page';
    document.getElementById('modal_page_id').value = '0';
    document.getElementById('page_title').value = '';
    document.getElementById('page_content').value = '<p>Your custom content here...</p>';
    document.getElementById('page_meta_title').value = '';
    document.getElementById('page_meta_desc').value = '';
    openModal('pageModal');
}

function editPage(p) {
    document.getElementById('pageModalTitle').textContent = 'Edit Page: ' + p.title;
    document.getElementById('modal_page_id').value = p.id;
    document.getElementById('page_title').value = p.title;
    document.getElementById('page_content').value = p.content || '';
    document.getElementById('page_meta_title').value = p.meta_title || '';
    document.getElementById('page_meta_desc').value = p.meta_description || '';
    openModal('pageModal');
}
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
