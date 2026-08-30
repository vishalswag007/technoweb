<?php
/**
 * Vishal Web Studio - Super Admin Website Management & Template Cloner
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/template_cloner.php';

require_super_admin();

$pdo = db();

// Handle Status Change
if (isset($_GET['set_status']) && isset($_GET['id'])) {
    $siteId = (int)$_GET['id'];
    $status = $_GET['set_status'];
    $allowed = ['draft', 'development', 'review', 'live', 'suspended', 'archived'];
    if (in_array($status, $allowed)) {
        $pdo->prepare("UPDATE websites SET status = ? WHERE id = ?")->execute([$status, $siteId]);
        log_activity(current_user_id(), 'website_status_change', 'websites', $siteId, "Changed website #{$siteId} status to {$status}");
        set_flash('success', "Website status updated to {$status}.");
    }
    header('Location: ' . BASE_URL . '/super-admin/websites.php');
    exit;
}

// Handle Delete Website
if (isset($_GET['delete'])) {
    $siteId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM websites WHERE id = ?")->execute([$siteId]);
    log_activity(current_user_id(), 'website_deleted', 'websites', $siteId, "Deleted website #{$siteId}");
    set_flash('success', "Website and associated pages deleted.");
    header('Location: ' . BASE_URL . '/super-admin/websites.php');
    exit;
}

// Handle Create Website from Template POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clone_website'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $clientId = (int)($_POST['client_id'] ?? 0);
        $templateId = (int)($_POST['template_id'] ?? 0);
        $websiteName = trim($_POST['website_name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');

        if ($clientId <= 0 || $templateId <= 0 || empty($websiteName)) {
            set_flash('danger', 'Please select a client, template, and provide a website name.');
        } else {
            try {
                $newSiteId = TemplateCloner::createWebsiteFromTemplate($clientId, $templateId, $websiteName, $domain);
                set_flash('success', "Website '{$websiteName}' created and isolated successfully from template!");
                header('Location: ' . BASE_URL . '/super-admin/websites.php');
                exit;
            } catch (Exception $e) {
                set_flash('danger', 'Error creating website: ' . $e->getMessage());
            }
        }
    }
}

// Fetch Websites list
$websites = $pdo->query("SELECT w.*, c.business_name, c.owner_name, c.user_id, t.name as template_name, t.category as template_cat, (SELECT status FROM hosting WHERE website_id = w.id LIMIT 1) as hosting_status FROM websites w JOIN clients c ON w.client_id = c.id LEFT JOIN templates t ON w.template_id = t.id ORDER BY w.id DESC")->fetchAll();

// Fetch Clients & Templates for Cloning Modal
$clientsList = $pdo->query("SELECT id, business_name, owner_name FROM clients WHERE status = 'active' ORDER BY business_name ASC")->fetchAll();
$templatesList = $pdo->query("SELECT id, name, category, price FROM templates WHERE status = 'active' ORDER BY sort_order ASC")->fetchAll();

$pageTitle = 'Websites Management';
$adminNav = 'websites';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
        Total Websites: <strong><?= count($websites) ?></strong> across all active clients.
    </p>

    <button type="button" class="btn btn-primary btn-sm" onclick="openCloneModal()">
        <i class="fas fa-magic"></i> Create Website From Template
    </button>
</div>

<!-- Websites Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Website & Domain</th>
                    <th>Client Business</th>
                    <th>Template Base</th>
                    <th>SSL & Hosting</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($websites)): ?>
                    <tr><td colspan="7" class="text-center text-muted" style="padding: 30px;">No client websites created yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($websites as $w): ?>
                        <tr>
                            <td>
                                <strong style="font-size: 0.95rem; color: var(--dark);"><?= e($w['name']) ?></strong><br>
                                <a href="<?= BASE_URL ?>/site/index.php?site=<?= urlencode($w['slug']) ?>" target="_blank" style="font-size: 0.8rem; color: var(--primary);">
                                    <i class="fas fa-external-link-alt"></i> <?= e($w['domain'] ?: ($w['slug'] . '.vishalwebstudio.com')) ?>
                                </a>
                            </td>
                            <td>
                                <strong style="font-size: 0.88rem;"><?= e($w['business_name']) ?></strong><br>
                                <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($w['owner_name']) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?= e($w['template_name'] ?? 'Custom Layout') ?></span>
                            </td>
                            <td>
                                <div style="font-size: 0.82rem; display: flex; align-items: center; gap: 6px;">
                                    <span class="text-success"><i class="fas fa-lock"></i> SSL Active</span>
                                    <span>•</span>
                                    <span class="text-muted"><?= ucfirst($w['hosting_status'] ?? 'Active') ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown" style="display: inline-block;">
                                    <?= render_status_badge($w['status']) ?>
                                </div>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                <?= format_date($w['created_at']) ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= BASE_URL ?>/site/index.php?site=<?= urlencode($w['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="View Public Website">
                                        <i class="fas fa-eye text-primary"></i>
                                    </a>

                                    <?php if ($w['user_id']): ?>
                                        <a href="<?= BASE_URL ?>/super-admin/clients.php?impersonate=<?= $w['user_id'] ?>" class="btn btn-secondary btn-sm" title="Login as Client to Edit Content" style="color: #7c3aed;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($w['status'] !== 'live'): ?>
                                        <a href="<?= BASE_URL ?>/super-admin/websites.php?set_status=live&id=<?= $w['id'] ?>" class="btn btn-success btn-sm" title="Publish Live">
                                            <i class="fas fa-paper-plane"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>/super-admin/websites.php?set_status=suspended&id=<?= $w['id'] ?>" class="btn btn-secondary btn-sm" title="Suspend Website">
                                            <i class="fas fa-pause text-warning"></i>
                                        </a>
                                    <?php endif; ?>

                                    <a href="<?= BASE_URL ?>/super-admin/websites.php?delete=<?= $w['id'] ?>" class="btn btn-secondary btn-sm" title="Delete Website" data-confirm="Are you sure you want to permanently delete this website? All pages and content will be removed.">
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

<!-- Create Website from Template Modal -->
<div class="modal-backdrop" id="cloneModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-magic text-primary" style="margin-right: 8px;"></i> Create Website From Template</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="clone_website" value="1">

            <div class="modal-body">
                <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 20px;">
                    This will deep-clone the selected template structure, default pages, services, menus, and FAQs into a completely isolated client record.
                </p>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="client_id">Assign to Client *</label>
                        <select name="client_id" id="client_id" class="form-select" required>
                            <option value="">-- Choose Client --</option>
                            <?php foreach ($clientsList as $cl): ?>
                                <option value="<?= $cl['id'] ?>"><?= e($cl['business_name']) ?> (<?= e($cl['owner_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="template_id">Base Template Layout *</label>
                        <select name="template_id" id="template_id" class="form-select" required>
                            <option value="">-- Choose Template --</option>
                            <?php foreach ($templatesList as $tpl): ?>
                                <option value="<?= $tpl['id'] ?>"><?= e($tpl['name']) ?> (<?= e($tpl['category']) ?> - <?= format_currency($tpl['price']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="website_name">Website Title / Business Name *</label>
                        <input type="text" name="website_name" id="website_name" class="form-control" placeholder="e.g. Apex Career Academy" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="domain">Custom Domain / Subdomain</label>
                        <input type="text" name="domain" id="domain" class="form-control" placeholder="e.g. www.apexacademy.com or leave empty">
                        <div class="form-help">Leave empty to use automatic subdomain slug.</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-bolt"></i> Clone & Provision Website</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCloneModal() {
    openModal('cloneModal');
}

<?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
document.addEventListener('DOMContentLoaded', openCloneModal);
<?php endif; ?>
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
