<?php
/**
 * Vishal Web Studio - Super Admin Domain Registry & Expiry Tracker
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Handle Add / Edit Domain POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_domain'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $clientId = (int)($_POST['client_id'] ?? 0);
        $domainName = trim($_POST['domain_name'] ?? '');
        $registrar = trim($_POST['registrar'] ?? 'GoDaddy India');
        $regDate = !empty($_POST['registration_date']) ? $_POST['registration_date'] : date('Y-m-d');
        $expDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : date('Y-m-d', strtotime('+1 year'));
        $renewalCost = (float)($_POST['renewal_cost'] ?? 999.00);
        $status = $_POST['status'] ?? 'active';

        if (empty($domainName) || $clientId <= 0) {
            set_flash('danger', 'Please specify a domain name and select a client.');
        } else {
            $ins = $pdo->prepare("INSERT INTO domains (client_id, domain_name, registrar, registration_date, expiry_date, renewal_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$clientId, $domainName, $registrar, $regDate, $expDate, $renewalCost, $status]);
            log_activity(current_user_id(), 'domain_recorded', 'domains', (int)$pdo->lastInsertId(), "Recorded domain '{$domainName}' for client #{$clientId}");
            set_flash('success', "Domain '{$domainName}' added to monitoring registry.");
            header('Location: ' . BASE_URL . '/super-admin/domains.php');
            exit;
        }
    }
}

// Fetch Domains
$domains = $pdo->query("SELECT d.*, cl.business_name, cl.owner_name, cl.phone as client_phone FROM domains d JOIN clients cl ON d.client_id = cl.id ORDER BY d.expiry_date ASC")->fetchAll();
$clientsList = $pdo->query("SELECT id, business_name, owner_name FROM clients WHERE status = 'active' ORDER BY business_name ASC")->fetchAll();

$pageTitle = 'Domain Name Management & Expiry Alerts';
$adminNav = 'domains';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
        Total Monitored Domains: <strong><?= count($domains) ?></strong> domains.
    </p>

    <button type="button" class="btn btn-primary btn-sm" onclick="openDomainModal()">
        <i class="fas fa-plus"></i> Add Domain Record
    </button>
</div>

<!-- Domains Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Domain Name</th>
                    <th>Client Business</th>
                    <th>Registrar</th>
                    <th>Registration Date</th>
                    <th>Expiry Date</th>
                    <th>Renewal Cost</th>
                    <th>Status</th>
                    <th style="text-align: right;">Renewal Alert</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($domains)): ?>
                    <tr><td colspan="8" class="text-center text-muted" style="padding: 30px;">No domains recorded.</td></tr>
                <?php else: ?>
                    <?php foreach ($domains as $dom): ?>
                        <?php
                        $daysToExpiry = (int)((strtotime($dom['expiry_date']) - time()) / 86400);
                        ?>
                        <tr>
                            <td>
                                <strong class="text-primary" style="font-size: 0.95rem;"><?= e($dom['domain_name']) ?></strong><br>
                                <a href="http://<?= e($dom['domain_name']) ?>" target="_blank" style="font-size: 0.78rem; color: var(--text-muted);">
                                    <i class="fas fa-external-link-alt"></i> Test DNS Reachability
                                </a>
                            </td>
                            <td>
                                <strong><?= e($dom['business_name']) ?></strong><br>
                                <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($dom['owner_name']) ?></span>
                            </td>
                            <td><?= e($dom['registrar']) ?></td>
                            <td><?= format_date($dom['registration_date']) ?></td>
                            <td>
                                <strong><?= format_date($dom['expiry_date']) ?></strong><br>
                                <span style="font-size: 0.78rem; color: <?= $daysToExpiry < 30 ? 'var(--danger)' : 'var(--success)' ?>;">
                                    <?= $daysToExpiry > 0 ? "Expires in {$daysToExpiry} days" : "Expired" ?>
                                </span>
                            </td>
                            <td><strong><?= format_currency($dom['renewal_cost']) ?> / yr</strong></td>
                            <td><?= render_status_badge($dom['status']) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= build_whatsapp_link($dom['client_phone'], "Hello {$dom['owner_name']}, friendly reminder that your domain {$dom['domain_name']} is scheduled for renewal on " . format_date($dom['expiry_date']) . ". Renewal fee: ₹{$dom['renewal_cost']}.") ?>" target="_blank" class="btn btn-whatsapp btn-sm" title="Send Renewal Reminder via WhatsApp">
                                    <i class="fab fa-whatsapp"></i> Alert
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Domain Modal -->
<div class="modal-backdrop" id="domainModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-link text-primary" style="margin-right: 8px;"></i> Add Domain Record</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="save_domain" value="1">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="dom_client_id">Assign to Client *</label>
                    <select name="client_id" id="dom_client_id" class="form-select" required>
                        <option value="">-- Choose Client --</option>
                        <?php foreach ($clientsList as $cl): ?>
                            <option value="<?= $cl['id'] ?>"><?= e($cl['business_name']) ?> (<?= e($cl['owner_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="dom_name">Domain Name *</label>
                        <input type="text" name="domain_name" id="dom_name" class="form-control" placeholder="e.g. www.clientbusiness.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="dom_registrar">Registrar</label>
                        <input type="text" name="registrar" id="dom_registrar" class="form-control" value="GoDaddy India">
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label" for="dom_reg_date">Registration Date</label>
                        <input type="date" name="registration_date" id="dom_reg_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="dom_exp_date">Expiry Date</label>
                        <input type="date" name="expiry_date" id="dom_exp_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="dom_cost">Renewal Cost (INR)</label>
                        <input type="number" step="0.01" name="renewal_cost" id="dom_cost" class="form-control" value="999.00">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Domain Record</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDomainModal() {
    openModal('domainModal');
}
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
