<?php
/**
 * Vishal Web Studio - Super Admin Payment Tracking Ledger
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Handle Record Payment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $clientId = (int)($_POST['client_id'] ?? 0);
        $invoiceId = !empty($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : null;
        $amount = (float)($_POST['amount'] ?? 0);
        $method = $_POST['payment_method'] ?? 'upi';
        $txnId = trim($_POST['transaction_id'] ?? ('TXN-' . time()));
        $notes = trim($_POST['notes'] ?? '');

        if ($clientId <= 0 || $amount <= 0) {
            set_flash('danger', 'Please select a client and provide a valid payment amount.');
        } else {
            $ins = $pdo->prepare("INSERT INTO payments (client_id, invoice_id, amount, payment_method, transaction_id, status, notes) VALUES (?, ?, ?, ?, ?, 'completed', ?)");
            $ins->execute([$clientId, $invoiceId, $amount, $method, $txnId, $notes]);
            $payId = $pdo->lastInsertId();

            // If invoice linked, update invoice paid amount & status
            if ($invoiceId) {
                $invStmt = $pdo->prepare("SELECT total, paid_amount FROM invoices WHERE id = ?");
                $invStmt->execute([$invoiceId]);
                $inv = $invStmt->fetch();
                if ($inv) {
                    $newPaid = $inv['paid_amount'] + $amount;
                    $newStatus = ($newPaid >= $inv['total']) ? 'paid' : 'partial';
                    $pdo->prepare("UPDATE invoices SET paid_amount = ?, status = ? WHERE id = ?")->execute([$newPaid, $newStatus, $invoiceId]);
                }
            }

            log_activity(current_user_id(), 'payment_recorded', 'payments', $payId, "Recorded payment of ₹{$amount} via {$method} for client #{$clientId}");
            set_flash('success', "Payment of " . format_currency($amount) . " logged successfully!");
            header('Location: ' . BASE_URL . '/super-admin/payments.php');
            exit;
        }
    }
}

// Fetch Payments
$payments = $pdo->query("SELECT p.*, cl.business_name, cl.owner_name, i.invoice_number FROM payments p JOIN clients cl ON p.client_id = cl.id LEFT JOIN invoices i ON p.invoice_id = i.id ORDER BY p.id DESC")->fetchAll();
$clientsList = $pdo->query("SELECT id, business_name, owner_name FROM clients WHERE status = 'active' ORDER BY business_name ASC")->fetchAll();
$invoicesList = $pdo->query("SELECT id, invoice_number, total, paid_amount, client_id FROM invoices WHERE status != 'paid' ORDER BY id DESC")->fetchAll();

$pageTitle = 'Payments Ledger';
$adminNav = 'payments';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
        Total Received: <strong><?= format_currency(array_sum(array_column($payments, 'amount'))) ?></strong> across all transactions.
    </p>

    <button type="button" class="btn btn-primary btn-sm" onclick="openPaymentModal()">
        <i class="fas fa-plus"></i> Record Manual Payment
    </button>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Txn Reference</th>
                    <th>Client Business</th>
                    <th>Invoice Ref</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date & Time</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="8" class="text-center text-muted" style="padding: 30px;">No payments recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td>
                                <strong class="text-primary"><?= e($pay['transaction_id'] ?: ('PAY-' . $pay['id'])) ?></strong>
                            </td>
                            <td>
                                <strong><?= e($pay['business_name']) ?></strong><br>
                                <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($pay['owner_name']) ?></span>
                            </td>
                            <td>
                                <?php if ($pay['invoice_number']): ?>
                                    <a href="<?= BASE_URL ?>/public/invoice.php?inv=<?= urlencode($pay['invoice_number']) ?>" target="_blank" style="font-size: 0.85rem; font-weight: 600;">
                                        <?= e($pay['invoice_number']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.8rem;">Direct Advance</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="text-success" style="font-size: 1rem;"><?= format_currency($pay['amount']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-secondary" style="text-transform: uppercase;"><?= e($pay['payment_method']) ?></span>
                            </td>
                            <td><?= render_status_badge($pay['status']) ?></td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                <?= format_datetime($pay['paid_at'] ?? $pay['created_at']) ?>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                <?= e($pay['notes'] ?: '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="modal-backdrop" id="paymentModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-receipt text-primary" style="margin-right: 8px;"></i> Record Settlement / Payment</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="record_payment" value="1">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="pay_client_id">Client Business *</label>
                    <select name="client_id" id="pay_client_id" class="form-select" required>
                        <option value="">-- Choose Client --</option>
                        <?php foreach ($clientsList as $cl): ?>
                            <option value="<?= $cl['id'] ?>"><?= e($cl['business_name']) ?> (<?= e($cl['owner_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="pay_amount">Payment Amount (INR) *</label>
                        <input type="number" step="0.01" name="amount" id="pay_amount" class="form-control" required placeholder="5000.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pay_method">Payment Method *</label>
                        <select name="payment_method" id="pay_method" class="form-select">
                            <option value="upi">UPI (Google Pay / PhonePe / Paytm)</option>
                            <option value="bank_transfer">IMPS / NEFT Bank Transfer</option>
                            <option value="credit_card">Credit / Debit Card</option>
                            <option value="cash">Cash / In-Person</option>
                            <option value="razorpay">Razorpay Gateway</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="pay_invoice_id">Link to Invoice (Optional)</label>
                        <select name="invoice_id" id="pay_invoice_id" class="form-select">
                            <option value="">-- No specific invoice --</option>
                            <?php foreach ($invoicesList as $inv): ?>
                                <option value="<?= $inv['id'] ?>"><?= e($inv['invoice_number']) ?> (Balance: ₹<?= number_format($inv['total'] - $inv['paid_amount'], 2) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pay_txn">Transaction ID / Ref</label>
                        <input type="text" name="transaction_id" id="pay_txn" class="form-control" placeholder="UPI-REF-XXXXX">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="pay_notes">Notes</label>
                    <input type="text" name="notes" id="pay_notes" class="form-control" placeholder="e.g. 50% Project advance received via GPay">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Payment Record</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal() {
    openModal('paymentModal');
}
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
