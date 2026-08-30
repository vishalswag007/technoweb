<?php
/**
 * Vishal Web Studio - Super Admin Template Management
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Handle Status Toggle
if (isset($_GET['toggle_status'])) {
    $tid = (int)$_GET['toggle_status'];
    $stmt = $pdo->prepare("SELECT id, status, name FROM templates WHERE id = ?");
    $stmt->execute([$tid]);
    $tpl = $stmt->fetch();
    if ($tpl) {
        $newStatus = ($tpl['status'] === 'active') ? 'inactive' : 'active';
        $pdo->prepare("UPDATE templates SET status = ? WHERE id = ?")->execute([$newStatus, $tid]);
        log_activity(current_user_id(), 'template_status_toggle', 'templates', $tid, "Toggled template '{$tpl['name']}' status to {$newStatus}");
        set_flash('success', "Template status updated to {$newStatus}.");
    }
    header('Location: ' . BASE_URL . '/super-admin/templates.php');
    exit;
}

// Handle Add / Edit Template POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $tplId = (int)($_POST['template_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'Business');
        $tagline = trim($_POST['tagline'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 14999.00);
        $themeColor = trim($_POST['default_theme_color'] ?? '#2563eb');
        $featuresArr = array_filter(array_map('trim', explode("\n", $_POST['features_raw'] ?? '')));
        $featuresJson = json_encode(array_values($featuresArr));
        $pagesArr = array_filter(array_map('trim', explode(',', $_POST['default_pages_raw'] ?? 'Home,About,Services,Contact')));
        $pagesJson = json_encode(array_values($pagesArr));

        if (empty($name)) {
            set_flash('danger', 'Template name is required.');
        } else {
            $slug = slugify($name);
            if ($tplId > 0) {
                // Update
                $upd = $pdo->prepare("UPDATE templates SET name = ?, category = ?, tagline = ?, description = ?, price = ?, default_theme_color = ?, features = ?, default_pages = ? WHERE id = ?");
                $upd->execute([$name, $category, $tagline, $description, $price, $themeColor, $featuresJson, $pagesJson, $tplId]);
                log_activity(current_user_id(), 'template_updated', 'templates', $tplId, "Updated template '{$name}'");
                set_flash('success', "Template '{$name}' updated successfully.");
            } else {
                // Insert
                $ins = $pdo->prepare("INSERT INTO templates (name, slug, category, tagline, description, price, default_theme_color, features, default_pages, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $ins->execute([$name, $slug, $category, $tagline, $description, $price, $themeColor, $featuresJson, $pagesJson]);
                $newTplId = $pdo->lastInsertId();
                log_activity(current_user_id(), 'template_created', 'templates', $newTplId, "Created new template '{$name}'");
                set_flash('success', "New template '{$name}' added to marketplace.");
            }
            header('Location: ' . BASE_URL . '/super-admin/templates.php');
            exit;
        }
    }
}

// Fetch Templates
$templates = $pdo->query("SELECT * FROM templates ORDER BY sort_order ASC, id DESC")->fetchAll();

$pageTitle = 'Website Templates Manager';
$adminNav = 'templates';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
        Total Market Templates: <strong><?= count($templates) ?></strong> active layout engines.
    </p>

    <button type="button" class="btn btn-primary btn-sm" onclick="openTemplateModal()">
        <i class="fas fa-plus"></i> Add New Template
    </button>
</div>

<!-- Templates Grid -->
<div class="grid-3">
    <?php foreach ($templates as $t): ?>
        <?php 
        $features = json_decode($t['features'] ?? '[]', true); 
        $pages = json_decode($t['default_pages'] ?? '[]', true);
        ?>
        <div class="card" style="box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); overflow: hidden; display: flex; flex-direction: column;">
            <div style="height: 140px; background: linear-gradient(135deg, <?= e($t['default_theme_color']) ?> 0%, #0f172a 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #ffffff; padding: 16px; position: relative;">
                <span class="badge <?= $t['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>" style="position: absolute; top: 12px; right: 12px;">
                    <?= ucfirst($t['status']) ?>
                </span>
                <span class="badge badge-secondary" style="position: absolute; top: 12px; left: 12px; background: rgba(0,0,0,0.4); color: #ffffff;">
                    <?= e($t['category']) ?>
                </span>
                <h3 style="color: #ffffff; font-size: 1.15rem; margin-top: 12px; text-align: center;"><?= e($t['name']) ?></h3>
            </div>

            <div class="card-body" style="flex-grow: 1; display: flex; flex-direction: column; padding: 20px;">
                <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 12px; line-height: 1.4;">
                    <?= e($t['tagline']) ?>
                </p>

                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 16px;">
                    <strong>Features:</strong> <?= count($features) ?> items • <strong>Pages:</strong> <?= implode(', ', array_slice($pages, 0, 3)) ?>...
                </div>

                <div style="margin-top: auto; padding-top: 14px; border-top: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                    <strong style="font-size: 1.15rem; color: var(--dark);"><?= format_currency($t['price']) ?></strong>
                    
                    <div style="display: flex; gap: 6px;">
                        <a href="<?= BASE_URL ?>/public/template-detail.php?slug=<?= urlencode($t['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Live Preview">
                            <i class="fas fa-eye"></i>
                        </a>

                        <button type="button" class="btn btn-secondary btn-sm" title="Edit Template" onclick='editTemplate(<?= json_encode($t) ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>

                        <a href="<?= BASE_URL ?>/super-admin/templates.php?toggle_status=<?= $t['id'] ?>" class="btn btn-secondary btn-sm" title="Toggle Status">
                            <i class="fas fa-power-off <?= $t['status'] === 'active' ? 'text-success' : 'text-danger' ?>"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add / Edit Template Modal -->
<div class="modal-backdrop" id="templateModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="templateModalTitle">Add New Template</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="save_template" value="1">
            <input type="hidden" name="template_id" id="modal_tpl_id" value="0">

            <div class="modal-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="modal_tpl_name">Template Name *</label>
                        <input type="text" name="name" id="modal_tpl_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="modal_tpl_category">Category *</label>
                        <select name="category" id="modal_tpl_category" class="form-select" required>
                            <option value="Restaurant">Restaurant</option>
                            <option value="Salon">Salon & Spa</option>
                            <option value="Coaching">Coaching & Academy</option>
                            <option value="Real Estate">Real Estate</option>
                            <option value="Medical">Medical & Clinic</option>
                            <option value="Business">Business & Corporate</option>
                            <option value="Portfolio">Portfolio & Personal</option>
                            <option value="E-Commerce">E-Commerce</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="modal_tpl_price">Base Package Price (INR) *</label>
                        <input type="number" step="0.01" name="price" id="modal_tpl_price" class="form-control" required value="14999.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="modal_tpl_color">Default Accent Hex Color</label>
                        <input type="text" name="default_theme_color" id="modal_tpl_color" class="form-control" value="#2563eb">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="modal_tpl_tagline">Tagline</label>
                    <input type="text" name="tagline" id="modal_tpl_tagline" class="form-control" placeholder="e.g. Exquisite Dining & Culinary Experience">
                </div>

                <div class="form-group">
                    <label class="form-label" for="modal_tpl_description">Full Description</label>
                    <textarea name="description" id="modal_tpl_description" class="form-control" rows="3"></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="modal_tpl_features">Features List (1 per line)</label>
                        <textarea name="features_raw" id="modal_tpl_features" class="form-control" rows="4" placeholder="Interactive Food Menu&#10;Table Reservation Form&#10;WhatsApp Order Button"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="modal_tpl_pages">Default Pages (Comma-separated)</label>
                        <input type="text" name="default_pages_raw" id="modal_tpl_pages" class="form-control" value="Home, About Us, Menu, Gallery, Contact Us">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Template</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTemplateModal() {
    document.getElementById('templateModalTitle').textContent = 'Add New Template';
    document.getElementById('modal_tpl_id').value = '0';
    document.getElementById('modal_tpl_name').value = '';
    document.getElementById('modal_tpl_tagline').value = '';
    document.getElementById('modal_tpl_description').value = '';
    document.getElementById('modal_tpl_price').value = '14999.00';
    document.getElementById('modal_tpl_color').value = '#2563eb';
    document.getElementById('modal_tpl_features').value = '';
    document.getElementById('modal_tpl_pages').value = 'Home, About Us, Services, Contact Us';
    openModal('templateModal');
}

function editTemplate(tpl) {
    document.getElementById('templateModalTitle').textContent = 'Edit Template: ' + tpl.name;
    document.getElementById('modal_tpl_id').value = tpl.id;
    document.getElementById('modal_tpl_name').value = tpl.name;
    document.getElementById('modal_tpl_category').value = tpl.category;
    document.getElementById('modal_tpl_tagline').value = tpl.tagline || '';
    document.getElementById('modal_tpl_description').value = tpl.description || '';
    document.getElementById('modal_tpl_price').value = tpl.price;
    document.getElementById('modal_tpl_color').value = tpl.default_theme_color || '#2563eb';

    let feats = [];
    try { feats = JSON.parse(tpl.features || '[]'); } catch(e) {}
    document.getElementById('modal_tpl_features').value = feats.join("\n");

    let pgs = [];
    try { pgs = JSON.parse(tpl.default_pages || '[]'); } catch(e) {}
    document.getElementById('modal_tpl_pages').value = pgs.join(', ');

    openModal('templateModal');
}
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
