<?php
/**
 * Vishal Web Studio - Client Invoices & Payment Receipts (Tenant-Isolated)
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_client();

$client = get_current_client_record();
$pdo = db();

if (!$client) {
    header('Location: ' . BASE_URL . '/client/index.php');
    exit;
}

$clientId = (int)$client['id'];

// Strict Tenant Isolation: Query invoices belonging only to this client
$invStmt = $pdo->prepare("SELECT * FROM invoices WHERE client_id = ? ORDER BY id DESC");
$invStmt->execute([$clientId]);
$invoices = $invStmt->fetchAll();

$payStmt = $pdo->prepare("SELECT p.*, i.invoice_number FROM payments p LEFT JOIN invoices i ON p.invoice_id = i.id WHERE p.client_id = ? ORDER BY p.id DESC");
$payStmt->execute([$clientId]);
$payments = $payStmt->fetchAll();

$pageTitle = 'Invoices & Billing History';
$clientNav = 'invoices';
require_once dirname(__DIR__) . '/includes/client_header.php';
?>

<div class="card" style="margin-bottom: 30px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar text-primary" style="margin-right: 8px;"></i> Invoices</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Subtotal</th>
                    <th>Tax (GST)</th>
                    <th>Total Amount</th>
                    <th>Paid Amount</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="8" class="text-center text-muted" style="padding: 30px;">No invoices found.</td></tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td>
                                <strong class="text-primary"><?= e($inv['invoice_number']) ?></strong>
                            </td>
                            <td><?= format_currency($inv['subtotal']) ?></td>
                            <td><?= format_currency($inv['tax_amount']) ?></td>
                            <td><strong><?= format_currency($inv['total']) ?></strong></td>
                            <td style="color: var(--success); font-weight: 600;"><?= format_currency($inv['paid_amount']) ?></td>
                            <td><?= render_status_badge($inv['status']) ?></td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);"><?= format_date($inv['due_date']) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= BASE_URL ?>/public/invoice.php?inv=<?= urlencode($inv['invoice_number']) ?>" target="_blank" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-print"></i> View / PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-receipt text-success" style="margin-right: 8px;"></i> Payment Receipts</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Txn ID</th>
                    <th>Invoice</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="6" class="text-center text-muted" style="padding: 30px;">No payment settlements logged yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td>
                                <strong class="text-primary"><?= e($pay['transaction_id']) ?></strong>
                            </td>
                            <td><?= e($pay['invoice_number'] ?? 'Direct Advance') ?></td>
                            <td><strong class="text-success"><?= format_currency($pay['amount']) ?></strong></td>
                            <td><span class="badge badge-secondary"><?= strtoupper($pay['payment_method']) ?></span></td>
                            <td><?= render_status_badge($pay['status']) ?></td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);"><?= format_datetime($pay['paid_at'] ?? $pay['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
