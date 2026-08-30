<?php
/**
 * Vishal Web Studio - Super Admin Client Management CRM
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Handle Impersonation Request
if (isset($_GET['impersonate'])) {
    $targetUserId = (int)$_GET['impersonate'];
    if (impersonate_client($targetUserId)) {
        set_flash('success', 'Logged in as client successfully.');
        header('Location: ' . BASE_URL . '/client/index.php');
        exit;
    } else {
        set_flash('danger', 'Failed to impersonate client.');
    }
}

// Handle Status Toggle
if (isset($_GET['toggle_status'])) {
    $cid = (int)$_GET['toggle_status'];
    $stmt = $pdo->prepare("SELECT id, status, business_name FROM clients WHERE id = ?");
    $stmt->execute([$cid]);
    $cl = $stmt->fetch();
    if ($cl) {
        $newStatus = ($cl['status'] === 'active') ? 'inactive' : 'active';
        $pdo->prepare("UPDATE clients SET status = ? WHERE id = ?")->execute([$newStatus, $cid]);
        log_activity(current_user_id(), 'client_status_toggle', 'clients', $cid, "Toggled client '{$cl['business_name']}' status to {$newStatus}");
        set_flash('success', "Client status updated to {$newStatus}.");
    }
    header('Location: ' . BASE_URL . '/super-admin/clients.php');
    exit;
}

// Handle Add / Edit Client POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_client'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $clientId = (int)($_POST['client_id'] ?? 0);
        $businessName = trim($_POST['business_name'] ?? '');
        $ownerName = trim($_POST['owner_name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? $phone);
        $category = trim($_POST['business_category'] ?? 'General');
        $address = trim($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($businessName) || empty($ownerName) || empty($email) || empty($phone)) {
            set_flash('danger', 'Please provide all mandatory fields.');
        } else {
            if ($clientId > 0) {
                // Edit existing client
                $updCl = $pdo->prepare("UPDATE clients SET business_name = ?, owner_name = ?, phone = ?, whatsapp = ?, email = ?, address = ?, business_category = ? WHERE id = ?");
                $updCl->execute([$businessName, $ownerName, $phone, $whatsapp, $email, $address, $category, $clientId]);

                // Update linked user email / password if changed
                $clRecord = $pdo->query("SELECT user_id FROM clients WHERE id = {$clientId}")->fetch();
                if ($clRecord && $clRecord['user_id']) {
                    if (!empty($password)) {
                        $pHash = password_hash($password, PASSWORD_DEFAULT);
                        $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password_hash = ? WHERE id = ?")->execute([$ownerName, $email, $phone, $pHash, $clRecord['user_id']]);
                    } else {
                        $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?")->execute([$ownerName, $email, $phone, $clRecord['user_id']]);
                    }
                }
                log_activity(current_user_id(), 'client_updated', 'clients', $clientId, "Updated client record for '{$businessName}'");
                set_flash('success', "Client '{$businessName}' updated successfully.");
            } else {
                // Add new client
                $passToUse = !empty($password) ? $password : 'client123';
                $pHash = password_hash($passToUse, PASSWORD_DEFAULT);

                $insUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, phone, status) VALUES (?, ?, ?, 'client', ?, 'active')");
                $insUser->execute([$ownerName, $email, $pHash, $phone]);
                $userId = $pdo->lastInsertId();

                $insCl = $pdo->prepare("INSERT INTO clients (user_id, business_name, owner_name, phone, whatsapp, email, address, business_category, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $insCl->execute([$userId, $businessName, $ownerName, $phone, $whatsapp, $email, $address, $category]);
                $newClientId = $pdo->lastInsertId();

                log_activity(current_user_id(), 'client_created', 'clients', $newClientId, "Created new client '{$businessName}' with user ID {$userId}");
                set_flash('success', "New client '{$businessName}' created successfully with login: {$email}");
            }
            header('Location: ' . BASE_URL . '/super-admin/clients.php');
            exit;
        }
    }
}

// Fetch Clients list with website info
$search = trim($_GET['q'] ?? '');
$clientsSql = "SELECT c.*, u.last_login, (SELECT COUNT(*) FROM websites WHERE client_id = c.id) as websites_count, (SELECT domain FROM websites WHERE client_id = c.id LIMIT 1) as main_domain, (SELECT slug FROM websites WHERE client_id = c.id LIMIT 1) as site_slug FROM clients c LEFT JOIN users u ON c.user_id = u.id";

if (!empty($search)) {
    $clientsSql .= " WHERE c.business_name LIKE :s OR c.owner_name LIKE :s OR c.email LIKE :s OR c.phone LIKE :s";
    $cStmt = $pdo->prepare($clientsSql . " ORDER BY c.id DESC");
    $cStmt->execute([':s' => "%{$search}%"]);
} else {
    $cStmt = $pdo->query($clientsSql . " ORDER BY c.id DESC");
}
$clients = $cStmt->fetchAll();

$pageTitle = 'Client CRM Management';
$adminNav = 'clients';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <!-- Search -->
    <form method="GET" action="" style="display: flex; gap: 8px;">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search clients by name, business, email..." value="<?= e($search) ?>" style="width: 280px;">
        <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i></button>
        <?php if (!empty($search)): ?>
            <a href="<?= BASE_URL ?>/super-admin/clients.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>

    <button type="button" class="btn btn-primary btn-sm" onclick="openClientModal()">
        <i class="fas fa-user-plus"></i> Add New Client
    </button>
</div>

<!-- Clients Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Business & Owner</th>
                    <th>Contact Info</th>
                    <th>Category</th>
                    <th>Websites</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                    <tr><td colspan="7" class="text-center text-muted" style="padding: 30px;">No clients found.</td></tr>
                <?php else: ?>
                    <?php foreach ($clients as $c): ?>
                        <tr>
                            <td>
                                <strong style="font-size: 0.95rem; color: var(--dark);"><?= e($c['business_name']) ?></strong><br>
                                <span style="font-size: 0.82rem; color: var(--text-muted);"><i class="fas fa-user"></i> <?= e($c['owner_name']) ?></span>
                            </td>
                            <td>
                                <div style="font-size: 0.85rem;"><i class="fas fa-envelope text-primary" style="width: 16px;"></i> <?= e($c['email']) ?></div>
                                <div style="font-size: 0.85rem;"><i class="fas fa-phone text-success" style="width: 16px;"></i> <?= e($c['phone']) ?></div>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?= e($c['business_category'] ?? 'General') ?></span>
                            </td>
                            <td>
                                <?php if ($c['websites_count'] > 0): ?>
                                    <a href="<?= BASE_URL ?>/site/index.php?site=<?= urlencode($c['site_slug']) ?>" target="_blank" class="btn btn-outline-primary btn-sm" style="padding: 3px 8px; font-size: 0.78rem;">
                                        <i class="fas fa-globe"></i> View (<?= $c['websites_count'] ?>)
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.8rem;">No website yet</span>
                                <?php endif; ?>
                            </td>
                            <td><?= render_status_badge($c['status']) ?></td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                <?= format_date($c['created_at']) ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <?php if ($c['user_id']): ?>
                                        <a href="<?= BASE_URL ?>/super-admin/clients.php?impersonate=<?= $c['user_id'] ?>" class="btn btn-secondary btn-sm" title="Login as Client" style="color: #7c3aed;">
                                            <i class="fas fa-user-secret"></i>
                                        </a>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-secondary btn-sm" title="Edit Client" onclick='editClient(<?= json_encode($c) ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <a href="<?= BASE_URL ?>/super-admin/clients.php?toggle_status=<?= $c['id'] ?>" class="btn btn-secondary btn-sm" title="Toggle Active/Inactive">
                                        <i class="fas fa-power-off <?= $c['status'] === 'active' ? 'text-success' : 'text-danger' ?>"></i>
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

<!-- Add / Edit Client Modal -->
<div class="modal-backdrop" id="clientModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="clientModalTitle">Add New Client</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="save_client" value="1">
            <input type="hidden" name="client_id" id="modal_client_id" value="0">

            <div class="modal-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="modal_business_name">Business Name *</label>
                        <input type="text" name="business_name" id="modal_business_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="modal_owner_name">Owner / Contact Name *</label>
                        <input type="text" name="owner_name" id="modal_owner_name" class="form-control" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="modal_email">Email Address *</label>
                        <input type="email" name="email" id="modal_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="modal_phone">Phone Number *</label>
                        <input type="tel" name="phone" id="modal_phone" class="form-control" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="modal_whatsapp">WhatsApp Number</label>
                        <input type="tel" name="whatsapp" id="modal_whatsapp" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="modal_category">Business Category</label>
                        <select name="business_category" id="modal_category" class="form-select">
                            <option value="Restaurant">Restaurant</option>
                            <option value="Salon">Salon & Spa</option>
                            <option value="Coaching">Coaching & Education</option>
                            <option value="Real Estate">Real Estate</option>
                            <option value="Medical">Medical & Clinic</option>
                            <option value="Agency">Agency & Business</option>
                            <option value="E-Commerce">E-Commerce</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="modal_address">Business Address</label>
                    <input type="text" name="address" id="modal_address" class="form-control" placeholder="Shop/Office address, City">
                </div>

                <div class="form-group">
                    <label class="form-label" for="modal_password">Client Login Password</label>
                    <input type="password" name="password" id="modal_password" class="form-control" placeholder="Leave empty to keep current password (Default: client123)">
                    <div class="form-help">Client uses this password to access their zero-code admin panel.</div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Client</button>
            </div>
        </form>
    </div>
</div>

<script>
function openClientModal() {
    document.getElementById('clientModalTitle').textContent = 'Add New Client';
    document.getElementById('modal_client_id').value = '0';
    document.getElementById('modal_business_name').value = '';
    document.getElementById('modal_owner_name').value = '';
    document.getElementById('modal_email').value = '';
    document.getElementById('modal_phone').value = '';
    document.getElementById('modal_whatsapp').value = '';
    document.getElementById('modal_address').value = '';
    document.getElementById('modal_password').value = '';
    openModal('clientModal');
}

function editClient(client) {
    document.getElementById('clientModalTitle').textContent = 'Edit Client: ' + client.business_name;
    document.getElementById('modal_client_id').value = client.id;
    document.getElementById('modal_business_name').value = client.business_name;
    document.getElementById('modal_owner_name').value = client.owner_name;
    document.getElementById('modal_email').value = client.email;
    document.getElementById('modal_phone').value = client.phone;
    document.getElementById('modal_whatsapp').value = client.whatsapp || client.phone;
    document.getElementById('modal_category').value = client.business_category || 'Other';
    document.getElementById('modal_address').value = client.address || '';
    document.getElementById('modal_password').value = '';
    openModal('clientModal');
}

<?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
document.addEventListener('DOMContentLoaded', openClientModal);
<?php endif; ?>
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
