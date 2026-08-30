<?php
/**
 * Vishal Web Studio - Super Admin Order Management & 15-Stage Workflow
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/contract_engine.php';
require_once dirname(__DIR__) . '/includes/template_cloner.php';

require_super_admin();

$pdo = db();

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $orderId = (int)$_POST['order_id'];
        $newStatus = $_POST['status'];
        $notes = trim($_POST['notes'] ?? '');

        $pdo->prepare("UPDATE orders SET status = ?, notes = ? WHERE id = ?")->execute([$newStatus, $notes, $orderId]);
        log_activity(current_user_id(), 'order_status_update', 'orders', $orderId, "Updated order #{$orderId} status to {$newStatus}");
        set_flash('success', "Order status updated to " . ucfirst(str_replace('_', ' ', $newStatus)));
        header('Location: ' . BASE_URL . '/super-admin/orders.php');
        exit;
    }
}

// 1-Click Generate Contract from Order
if (isset($_GET['quick_contract'])) {
    $orderId = (int)$_GET['quick_contract'];
    $stmt = $pdo->prepare("SELECT o.*, t.name as template_name, c.id as client_id, c.owner_name, c.business_name, c.email as client_email, c.phone as client_phone FROM orders o LEFT JOIN templates t ON o.template_id = t.id JOIN clients c ON o.client_id = c.id WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $ord = $stmt->fetch();

    if ($ord) {
        $contractNumber = generate_contract_number();
        $token = generate_secure_token();
        $packageName = $ord['template_name'] ?? 'Professional Custom Website';

        $contractData = [
            'contract_number' => $contractNumber,
            'client_name'     => $ord['owner_name'],
            'business_name'   => $ord['business_name'],
            'client_email'    => $ord['client_email'],
            'client_phone'    => $ord['client_phone'],
            'package_name'    => $packageName,
            'price'           => $ord['amount'],
            'payment_terms'   => '50% advance upon contract signing, 50% prior to final live launch.',
            'timeline'        => '7 to 10 business days'
        ];

        $content = ContractEngine::compileContract(ContractEngine::getDefaultContractTemplate(), $contractData);

        $insCnt = $pdo->prepare("INSERT INTO contracts (contract_number, token, order_id, client_id, title, package_name, price, payment_terms, timeline, contract_content, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'sent')");
        $insCnt->execute([
            $contractNumber,
            $token,
            $orderId,
            $ord['client_id'],
            "Website Services Contract - {$ord['business_name']}",
            $packageName,
            $ord['amount'],
            $contractData['payment_terms'],
            $contractData['timeline'],
            $content
        ]);

        $pdo->prepare("UPDATE orders SET status = 'contract_pending' WHERE id = ?")->execute([$orderId]);
        log_activity(current_user_id(), 'contract_generated', 'contracts', (int)$pdo->lastInsertId(), "Generated contract {$contractNumber} for order {$ord['order_number']}");
        set_flash('success', "Contract {$contractNumber} generated successfully for {$ord['business_name']}!");
        header('Location: ' . BASE_URL . '/super-admin/contracts.php');
        exit;
    }
}

// 1-Click Provision Website from Order
if (isset($_GET['quick_website'])) {
    $orderId = (int)$_GET['quick_website'];
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $ord = $stmt->fetch();

    if ($ord && $ord['client_id']) {
        try {
            $tplId = $ord['template_id'] ?: 1;
            $newSiteId = TemplateCloner::createWebsiteFromTemplate($ord['client_id'], $tplId, $ord['business_name']);
            $pdo->prepare("UPDATE orders SET status = 'development' WHERE id = ?")->execute([$orderId]);
            set_flash('success', "Website provisioned and isolated for {$ord['business_name']}! Order moved to Development.");
            header('Location: ' . BASE_URL . '/super-admin/websites.php');
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Error provisioning website: ' . $e->getMessage());
        }
    }
}

// Filter and List Orders
$statusFilter = $_GET['status'] ?? 'all';
$ordersSql = "SELECT o.*, t.name as template_name, c.owner_name as client_owner, (SELECT token FROM contracts WHERE order_id = o.id LIMIT 1) as contract_token, (SELECT id FROM websites WHERE client_id = o.client_id LIMIT 1) as site_id FROM orders o LEFT JOIN templates t ON o.template_id = t.id LEFT JOIN clients c ON o.client_id = c.id";

if ($statusFilter !== 'all') {
    $oStmt = $pdo->prepare($ordersSql . " WHERE o.status = ? ORDER BY o.id DESC");
    $oStmt->execute([$statusFilter]);
} else {
    $oStmt = $pdo->query($ordersSql . " ORDER BY o.id DESC");
}
$orders = $oStmt->fetchAll();

$allStatuses = [
    'new' => 'New Order',
    'contacted' => 'Contacted',
    'requirements_pending' => 'Requirements Pending',
    'contract_pending' => 'Contract Pending',
    'contract_signed' => 'Contract Signed',
    'payment_pending' => 'Payment Pending',
    'payment_received' => 'Payment Received',
    'development' => 'In Development',
    'client_review' => 'Client Review',
    'changes_requested' => 'Changes Requested',
    'final_approval' => 'Final Approval',
    'published' => 'Published Live',
    'maintenance' => 'In Maintenance',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];

$businessWhatsApp = get_setting('whatsapp', APP_WHATSAPP);

$pageTitle = 'Orders & Delivery Pipeline';
$adminNav = 'orders';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <!-- Status Filter Buttons -->
    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>/super-admin/orders.php" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">
            All (<?= count($orders) ?>)
        </a>
        <a href="<?= BASE_URL ?>/super-admin/orders.php?status=new" class="btn btn-sm <?= $statusFilter === 'new' ? 'btn-primary' : 'btn-secondary' ?>">
            New
        </a>
        <a href="<?= BASE_URL ?>/super-admin/orders.php?status=contract_pending" class="btn btn-sm <?= $statusFilter === 'contract_pending' ? 'btn-primary' : 'btn-secondary' ?>">
            Contract Pending
        </a>
        <a href="<?= BASE_URL ?>/super-admin/orders.php?status=development" class="btn btn-sm <?= $statusFilter === 'development' ? 'btn-primary' : 'btn-secondary' ?>">
            Development
        </a>
        <a href="<?= BASE_URL ?>/super-admin/orders.php?status=published" class="btn btn-sm <?= $statusFilter === 'published' ? 'btn-primary' : 'btn-secondary' ?>">
            Published
        </a>
    </div>

    <a href="<?= BASE_URL ?>/public/order.php" target="_blank" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Open Intake Form
    </a>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Order Ref</th>
                    <th>Client & Contact</th>
                    <th>Template & Scope</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="text-center text-muted" style="padding: 30px;">No orders found matching criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td>
                                <strong class="text-primary"><?= e($ord['order_number']) ?></strong>
                            </td>
                            <td>
                                <strong><?= e($ord['business_name']) ?></strong><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">
                                    <?= e($ord['owner_name']) ?> • <?= e($ord['phone']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?= e($ord['template_name'] ?? 'Custom Solution') ?></span><br>
                                <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($ord['business_category']) ?></span>
                            </td>
                            <td>
                                <strong><?= format_currency($ord['amount']) ?></strong>
                            </td>
                            <td>
                                <?= render_status_badge($ord['status']) ?>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                <?= format_date($ord['created_at']) ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <button type="button" class="btn btn-secondary btn-sm" title="View Requirements" onclick='viewOrder(<?= json_encode($ord) ?>)'>
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <!-- Quick Generate Contract -->
                                    <a href="<?= BASE_URL ?>/super-admin/orders.php?quick_contract=<?= $ord['id'] ?>" class="btn btn-secondary btn-sm" title="1-Click Generate Contract" style="color: #d97706;">
                                        <i class="fas fa-file-signature"></i>
                                    </a>

                                    <!-- Quick Create Website -->
                                    <a href="<?= BASE_URL ?>/super-admin/orders.php?quick_website=<?= $ord['id'] ?>" class="btn btn-secondary btn-sm" title="1-Click Create Website from Template" style="color: #10b981;">
                                        <i class="fas fa-globe"></i>
                                    </a>

                                    <!-- WhatsApp Notification -->
                                    <a href="<?= build_whatsapp_link($ord['whatsapp'] ?: $ord['phone'], "Hello {$ord['owner_name']}, regarding your website project {$ord['order_number']} with Vishal Web Studio...") ?>" target="_blank" class="btn btn-whatsapp btn-sm" title="WhatsApp Client">
                                        <i class="fab fa-whatsapp"></i>
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

<!-- View Order Requirements & Update Status Modal -->
<div class="modal-backdrop" id="orderModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="orderModalTitle">Order Requirements</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body" id="orderModalBody">
            <!-- Dynamic Injection via JavaScript -->
        </div>
    </div>
</div>

<script>
function viewOrder(ord) {
    document.getElementById('orderModalTitle').textContent = 'Order: ' + ord.order_number + ' - ' + ord.business_name;
    
    let pages = [];
    try { pages = JSON.parse(ord.required_pages || '[]'); } catch(e) {}

    let features = [];
    try { features = JSON.parse(ord.required_features || '[]'); } catch(e) {}

    let statusOptionsHtml = '';
    const statuses = <?= json_encode($allStatuses) ?>;
    for (const [key, label] of Object.entries(statuses)) {
        const isSel = (ord.status === key) ? 'selected' : '';
        statusOptionsHtml += `<option value="${key}" ${isSel}>${label}</option>`;
    }

    const contractLinkHtml = ord.contract_token ? 
        `<div style="margin-top: 12px; padding: 12px; background: #ecfdf5; border-radius: 6px; border: 1px solid #a7f3d0;">
            <i class="fas fa-link text-success"></i> <strong>Contract Link:</strong> 
            <a href="<?= BASE_URL ?>/public/contract.php?token=${ord.contract_token}" target="_blank" style="word-break: break-all;">
                <?= BASE_URL ?>/public/contract.php?token=${ord.contract_token}
            </a>
        </div>` : 
        `<div style="margin-top: 12px;">
            <a href="<?= BASE_URL ?>/super-admin/orders.php?quick_contract=${ord.id}" class="btn btn-warning btn-sm"><i class="fas fa-file-signature"></i> 1-Click Generate Contract</a>
        </div>`;

    document.getElementById('orderModalBody').innerHTML = `
        <div class="grid-2" style="margin-bottom: 20px;">
            <div>
                <h4 style="font-size: 1rem; margin-bottom: 8px;">Business & Client Details</h4>
                <p style="font-size: 0.88rem; margin-bottom: 4px;"><strong>Owner:</strong> ${ord.owner_name}</p>
                <p style="font-size: 0.88rem; margin-bottom: 4px;"><strong>Email:</strong> ${ord.email}</p>
                <p style="font-size: 0.88rem; margin-bottom: 4px;"><strong>Phone / WhatsApp:</strong> ${ord.phone}</p>
                <p style="font-size: 0.88rem; margin-bottom: 4px;"><strong>Address:</strong> ${ord.business_address || 'N/A'}</p>
                <p style="font-size: 0.88rem; margin-bottom: 4px;"><strong>Category:</strong> ${ord.business_category}</p>
            </div>
            <div>
                <h4 style="font-size: 1rem; margin-bottom: 8px;">Project Scope</h4>
                <p style="font-size: 0.88rem; margin-bottom: 4px;"><strong>Template:</strong> ${ord.template_name || 'Custom Solution'}</p>
                <p style="font-size: 0.88rem; margin-bottom: 4px;"><strong>Color Preference:</strong> ${ord.color_preference || 'Default'}</p>
                <p style="font-size: 0.88rem; margin-bottom: 4px;"><strong>Agreed Price:</strong> ₹${parseFloat(ord.amount).toFixed(2)}</p>
                <p style="font-size: 0.88rem; margin-bottom: 4px;"><strong>Date:</strong> ${ord.created_at}</p>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <h4 style="font-size: 0.95rem; margin-bottom: 6px;">Requested Pages & Modules:</h4>
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                ${pages.map(p => `<span class="badge badge-secondary">${p}</span>`).join('')}
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <h4 style="font-size: 0.95rem; margin-bottom: 6px;">Special Requirements:</h4>
            <div style="background: #f8fafc; padding: 14px; border-radius: 6px; border: 1px solid var(--border-color); font-size: 0.88rem;">
                ${ord.additional_requirements || 'No additional notes provided.'}
            </div>
        </div>

        ${contractLinkHtml}

        <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-color);">

        <!-- Update Status Form -->
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="update_order_status" value="1">
            <input type="hidden" name="order_id" value="${ord.id}">

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Update Pipeline Stage:</label>
                    <select name="status" class="form-select">
                        ${statusOptionsHtml}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Internal Milestone Notes:</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. Discussed with client, contract sent." value="${ord.notes || ''}">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Update Stage</button>
            </div>
        </form>
    `;

    openModal('orderModal');
}
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
