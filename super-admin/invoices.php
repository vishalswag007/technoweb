<?php
/**
 * Vishal Web Studio - Super Admin Invoicing Manager
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Handle Create Invoice POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $clientId = (int)($_POST['client_id'] ?? 0);
        $orderId = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        $taxRate = (float)($_POST['tax_rate'] ?? 18.00);
        $discount = (float)($_POST['discount'] ?? 0);
        $taxAmount = ($subtotal - $discount) * ($taxRate / 100);
        $total = ($subtotal - $discount) + $taxAmount;
        $paidAmount = (float)($_POST['paid_amount'] ?? 0);
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d', strtotime('+7 days'));
        $itemDesc = trim($_POST['item_desc'] ?? 'Website Development Package');

        $status = 'pending';
        if ($paidAmount >= $total) {
            $status = 'paid';
        } elseif ($paidAmount > 0) {
            $status = 'partial';
        }

        $invNumber = generate_invoice_number();
        $itemsJson = json_encode([['desc' => $itemDesc, 'amount' => $subtotal]]);

        $ins = $pdo->prepare("INSERT INTO invoices (invoice_number, client_id, order_id, subtotal, tax_rate, tax_amount, discount, total, paid_amount, status, due_date, items_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$invNumber, $clientId, $orderId, $subtotal, $taxRate, $taxAmount, $discount, $total, $paidAmount, $status, $dueDate, $itemsJson]);
        $newInvId = $pdo->lastInsertId();

        // If payment recorded, log into payments table as well
        if ($paidAmount > 0) {
            $pdo->prepare("INSERT INTO payments (client_id, order_id, invoice_id, amount, payment_method, transaction_id, status) VALUES (?, ?, ?, ?, 'upi', 'INIT-SETTLEMENT', 'completed')")->execute([$clientId, $orderId, $newInvId, $paidAmount]);
        }

        log_activity(current_user_id(), 'invoice_created', 'invoices', $newInvId, "Created invoice {$invNumber} for amount ₹{$total}");
        set_flash('success', "Invoice {$invNumber} created successfully!");
        header('Location: ' . BASE_URL . '/super-admin/invoices.php');
        exit;
    }
}

// Fetch Invoices
$invoices = $pdo->query("SELECT i.*, cl.business_name, cl.owner_name, cl.phone as client_phone FROM invoices i JOIN clients cl ON i.client_id = cl.id ORDER BY i.id DESC")->fetchAll();
$clientsList = $pdo->query("SELECT id, business_name, owner_name FROM clients WHERE status = 'active' ORDER BY business_name ASC")->fetchAll();

$pageTitle = 'Invoices & Billing';
$adminNav = 'invoices';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
        Total Invoices: <strong><?= count($invoices) ?></strong> generated.
    </p>

    <button type="button" class="btn btn-primary btn-sm" onclick="openInvoiceModal()">
        <i class="fas fa-plus"></i> Generate New Invoice
    </button>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Client Business</th>
                    <th>Subtotal</th>
                    <th>Tax (GST)</th>
                    <th>Total Value</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="9" class="text-center text-muted" style="padding: 30px;">No invoices found.</td></tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td>
                                <strong class="text-primary"><?= e($inv['invoice_number']) ?></strong>
                            </td>
                            <td>
                                <strong><?= e($inv['business_name']) ?></strong><br>
                                <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($inv['owner_name']) ?></span>
                            </td>
                            <td><?= format_currency($inv['subtotal']) ?></td>
                            <td><?= format_currency($inv['tax_amount']) ?></td>
                            <td><strong><?= format_currency($inv['total']) ?></strong></td>
                            <td style="color: var(--success); font-weight: 600;"><?= format_currency($inv['paid_amount']) ?></td>
                            <td><?= render_status_badge($inv['status']) ?></td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);"><?= format_date($inv['due_date']) ?></td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= BASE_URL ?>/public/invoice.php?inv=<?= urlencode($inv['invoice_number']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="View & Print Invoice">
                                        <i class="fas fa-print text-primary"></i>
                                    </a>

                                    <a href="<?= build_whatsapp_link($inv['client_phone'], "Hello {$inv['owner_name']}, here is your official tax invoice #{$inv['invoice_number']} for ₹{$inv['total']} from Vishal Web Studio: " . BASE_URL . "/public/invoice.php?inv=" . urlencode($inv['invoice_number'])) ?>" target="_blank" class="btn btn-whatsapp btn-sm" title="Share via WhatsApp">
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

<!-- Create Invoice Modal -->
<div class="modal-backdrop" id="invoiceModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-file-invoice-dollar text-primary" style="margin-right: 8px;"></i> Generate New Invoice</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="create_invoice" value="1">

            <div class="modal-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="inv_client_id">Select Client *</label>
                        <select name="client_id" id="inv_client_id" class="form-select" required>
                            <option value="">-- Choose Client --</option>
                            <?php foreach ($clientsList as $cl): ?>
                                <option value="<?= $cl['id'] ?>"><?= e($cl['business_name']) ?> (<?= e($cl['owner_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="inv_due_date">Due Date</label>
                        <input type="date" name="due_date" id="inv_due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inv_item_desc">Line Item Description</label>
                    <input type="text" name="item_desc" id="inv_item_desc" class="form-control" value="Professional Business Website Design, Customization & Cloud Hosting" required>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label" for="inv_subtotal">Subtotal (INR) *</label>
                        <input type="number" step="0.01" name="subtotal" id="inv_subtotal" class="form-control" value="12711.02" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="inv_tax_rate">GST Tax Rate (%)</label>
                        <input type="number" step="0.01" name="tax_rate" id="inv_tax_rate" class="form-control" value="18.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="inv_discount">Discount (INR)</label>
                        <input type="number" step="0.01" name="discount" id="inv_discount" class="form-control" value="0.00">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inv_paid_amount">Initial Amount Paid (INR)</label>
                    <input type="number" step="0.01" name="paid_amount" id="inv_paid_amount" class="form-control" value="0.00">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Generate Tax Invoice</button>
            </div>
        </form>
    </div>
</div>

<script>
function openInvoiceModal() {
    openModal('invoiceModal');
}

<?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
document.addEventListener('DOMContentLoaded', openInvoiceModal);
<?php endif; ?>
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
