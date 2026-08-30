<?php
/**
 * Vishal Web Studio - Super Admin Hosting Server & Infrastructure Manager
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Handle Add Hosting POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_hosting'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $clientId = (int)($_POST['client_id'] ?? 0);
        $provider = trim($_POST['provider'] ?? 'High-Speed NVMe Cloud');
        $planName = trim($_POST['plan_name'] ?? 'Startup Web Cloud');
        $serverIp = trim($_POST['server_ip'] ?? '185.199.108.153');
        $diskSpace = trim($_POST['disk_space'] ?? '10 GB NVMe');
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
        $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : date('Y-m-d', strtotime('+1 year'));
        $renewalCost = (float)($_POST['renewal_cost'] ?? 3499.00);

        if ($clientId <= 0) {
            set_flash('danger', 'Please select a client.');
        } else {
            $ins = $pdo->prepare("INSERT INTO hosting (client_id, provider, plan_name, server_ip, disk_space, start_date, expiry_date, renewal_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $ins->execute([$clientId, $provider, $planName, $serverIp, $diskSpace, $startDate, $expiryDate, $renewalCost]);
            log_activity(current_user_id(), 'hosting_recorded', 'hosting', (int)$pdo->lastInsertId(), "Recorded hosting allocation for client #{$clientId}");
            set_flash('success', "Hosting plan registered successfully.");
            header('Location: ' . BASE_URL . '/super-admin/hosting.php');
            exit;
        }
    }
}

// Fetch Hosting allocations
$hostings = $pdo->query("SELECT h.*, cl.business_name, cl.owner_name, (SELECT domain FROM websites WHERE client_id = h.client_id LIMIT 1) as website_domain FROM hosting h JOIN clients cl ON h.client_id = cl.id ORDER BY h.id DESC")->fetchAll();
$clientsList = $pdo->query("SELECT id, business_name, owner_name FROM clients WHERE status = 'active' ORDER BY business_name ASC")->fetchAll();

$pageTitle = 'Cloud Hosting & Server Allocations';
$adminNav = 'hosting';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
        Total Active Server Tenants: <strong><?= count($hostings) ?></strong> cloud allocations.
    </p>

    <button type="button" class="btn btn-primary btn-sm" onclick="openHostingModal()">
        <i class="fas fa-plus"></i> Allocate Cloud Hosting
    </button>
</div>

<!-- Hosting Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tenant Business</th>
                    <th>Hosting Provider</th>
                    <th>Plan & Disk Space</th>
                    <th>Server IP</th>
                    <th>Expiry Date</th>
                    <th>Annual Renewal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hostings)): ?>
                    <tr><td colspan="7" class="text-center text-muted" style="padding: 30px;">No hosting allocations recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($hostings as $h): ?>
                        <tr>
                            <td>
                                <strong><?= e($h['business_name']) ?></strong><br>
                                <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($h['website_domain'] ?? 'Active Site') ?></span>
                            </td>
                            <td><?= e($h['provider']) ?></td>
                            <td>
                                <strong><?= e($h['plan_name']) ?></strong><br>
                                <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($h['disk_space']) ?></span>
                            </td>
                            <td>
                                <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;"><?= e($h['server_ip']) ?></code>
                            </td>
                            <td>
                                <strong><?= format_date($h['expiry_date']) ?></strong>
                            </td>
                            <td>
                                <strong><?= format_currency($h['renewal_cost']) ?> / yr</strong>
                            </td>
                            <td><?= render_status_badge($h['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Allocate Hosting Modal -->
<div class="modal-backdrop" id="hostingModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-server text-primary" style="margin-right: 8px;"></i> Allocate Cloud Server Plan</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="save_hosting" value="1">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="host_client_id">Select Client *</label>
                    <select name="client_id" id="host_client_id" class="form-select" required>
                        <option value="">-- Choose Client --</option>
                        <?php foreach ($clientsList as $cl): ?>
                            <option value="<?= $cl['id'] ?>"><?= e($cl['business_name']) ?> (<?= e($cl['owner_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="host_provider">Cloud Provider</label>
                        <input type="text" name="provider" id="host_provider" class="form-control" value="High-Speed NVMe Cloud">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="host_plan">Plan Name</label>
                        <input type="text" name="plan_name" id="host_plan" class="form-control" value="Startup Web Cloud">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="host_ip">Dedicated Server IP</label>
                        <input type="text" name="server_ip" id="host_ip" class="form-control" value="185.199.108.153">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="host_disk">Disk Space</label>
                        <input type="text" name="disk_space" id="host_disk" class="form-control" value="10 GB NVMe Storage">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="host_expiry">Renewal Date</label>
                        <input type="date" name="expiry_date" id="host_expiry" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="host_cost">Annual Renewal Cost (INR)</label>
                        <input type="number" step="0.01" name="renewal_cost" id="host_cost" class="form-control" value="3499.00">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Hosting Allocation</button>
            </div>
        </form>
    </div>
</div>

<script>
function openHostingModal() {
    openModal('hostingModal');
}
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
