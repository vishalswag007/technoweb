<?php
/**
 * Vishal Web Studio - Professional Invoice & Printable Receipt
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$invoiceNumber = trim($_GET['inv'] ?? '');
$invoiceId = (int)($_GET['id'] ?? 0);

$stmt = null;
if (!empty($invoiceNumber)) {
    $stmt = db()->prepare("SELECT i.*, c.business_name, c.owner_name, c.phone as client_phone, c.email as client_email, c.address as client_address, o.order_number FROM invoices i JOIN clients c ON i.client_id = c.id LEFT JOIN orders o ON i.order_id = o.id WHERE i.invoice_number = ?");
    $stmt->execute([$invoiceNumber]);
} elseif ($invoiceId > 0) {
    $stmt = db()->prepare("SELECT i.*, c.business_name, c.owner_name, c.phone as client_phone, c.email as client_email, c.address as client_address, o.order_number FROM invoices i JOIN clients c ON i.client_id = c.id LEFT JOIN orders o ON i.order_id = o.id WHERE i.id = ?");
    $stmt->execute([$invoiceId]);
}

$invoice = $stmt ? $stmt->fetch() : null;

if (!$invoice) {
    die("<h3>Invoice record not found.</h3><p>Please check your invoice reference.</p>");
}

// Check authorization if user is logged in as a client (strict tenant check)
if (is_logged_in() && !is_super_admin()) {
    $client = get_current_client_record();
    if (!$client || $client['id'] != $invoice['client_id']) {
        http_response_code(403);
        die("<h3>Access Denied</h3><p>You are not authorized to view this invoice.</p>");
    }
}

$items = json_decode($invoice['items_json'] ?? '[]', true);
if (empty($items)) {
    $items = [
        ['desc' => 'Professional Business Website Design, Customization & Hosting Setup', 'amount' => $invoice['subtotal']]
    ];
}

$businessName = get_setting('business_name', APP_NAME);
$businessPhone = get_setting('phone', APP_PHONE);
$businessEmail = get_setting('email', APP_EMAIL);
$businessAddress = get_setting('address', '102, Cyber Tower, Sector 62, Noida, NCR, India');
$businessWhatsApp = get_setting('whatsapp', APP_WHATSAPP);

$balanceDue = max(0, $invoice['total'] - $invoice['paid_amount']);
$pageTitle = 'Tax Invoice ' . $invoice['invoice_number'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e($businessName) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/main.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    
    <style>
        .invoice-paper {
            background: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05);
            border-radius: 12px;
            padding: 50px;
            border: 1px solid var(--border-color);
            margin: 30px auto;
            max-width: 850px;
        }
        .invoice-table th { background: #f1f5f9; padding: 10px 14px; font-size: 0.82rem; }
        .invoice-table td { padding: 12px 14px; font-size: 0.9rem; }
    </style>
</head>
<body style="background: #f1f5f9; padding: 20px 0;">

<div class="container" style="max-width: 850px;">
    <!-- Top Action Bar -->
    <div class="no-print" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Home
        </a>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="fas fa-print"></i> Print / Download PDF
            </button>
            <a href="<?= build_whatsapp_link($businessWhatsApp, "Hello {$businessName}, regarding invoice {$invoice['invoice_number']} for {$invoice['business_name']}") ?>" target="_blank" class="btn btn-whatsapp btn-sm">
                <i class="fab fa-whatsapp"></i> Share on WhatsApp
            </a>
        </div>
    </div>

    <!-- Invoice Sheet -->
    <div class="invoice-paper">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 36px; flex-wrap: wrap; gap: 20px;">
            <div>
                <div class="brand-logo" style="margin-bottom: 8px;">
                    <div class="brand-logo-icon"><i class="fas fa-layer-group"></i></div>
                    <span><?= e($businessName) ?></span>
                </div>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                    <?= e($businessAddress) ?><br>
                    Email: <?= e($businessEmail) ?> • Phone: <?= e($businessPhone) ?><br>
                    GSTIN / Tax ID: 07AAAAA0000A1Z5
                </p>
            </div>

            <div style="text-align: right;">
                <h2 style="font-size: 1.8rem; color: var(--primary); margin-bottom: 4px;">TAX INVOICE</h2>
                <div style="font-size: 0.95rem; font-weight: 700;"># <?= e($invoice['invoice_number']) ?></div>
                <div style="margin-top: 8px;">
                    <?= render_status_badge($invoice['status']) ?>
                </div>
            </div>
        </div>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin-bottom: 24px;">

        <!-- Bill To & Meta -->
        <div class="grid-2" style="margin-bottom: 30px;">
            <div>
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">Billed To:</div>
                <h4 style="font-size: 1.1rem; margin-bottom: 4px;"><?= e($invoice['business_name']) ?></h4>
                <p style="font-size: 0.88rem; color: #334155; margin: 0; line-height: 1.5;">
                    Attn: <?= e($invoice['owner_name']) ?><br>
                    Email: <?= e($invoice['client_email']) ?><br>
                    Phone: <?= e($invoice['client_phone']) ?><br>
                    <?= e($invoice['client_address']) ?>
                </p>
            </div>

            <div style="text-align: right;">
                <p style="font-size: 0.88rem; margin-bottom: 6px;"><strong>Invoice Date:</strong> <?= format_date($invoice['created_at']) ?></p>
                <p style="font-size: 0.88rem; margin-bottom: 6px;"><strong>Due Date:</strong> <?= format_date($invoice['due_date']) ?></p>
                <?php if (!empty($invoice['order_number'])): ?>
                    <p style="font-size: 0.88rem; margin-bottom: 6px;"><strong>Project Ref:</strong> <?= e($invoice['order_number']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Line Items Table -->
        <table class="table invoice-table" style="margin-bottom: 24px;">
            <thead>
                <tr>
                    <th style="width: 55%;">Item & Description</th>
                    <th style="width: 15%; text-align: center;">Qty</th>
                    <th style="width: 30%; text-align: right;">Amount (INR)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--dark);"><?= e($item['desc']) ?></strong>
                        </td>
                        <td style="text-align: center;">1</td>
                        <td style="text-align: right; font-weight: 600;"><?= format_currency($item['amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals Calculation -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 30px;">
            <div style="width: 320px;">
                <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.9rem;">
                    <span style="color: var(--text-muted);">Subtotal:</span>
                    <strong><?= format_currency($invoice['subtotal']) ?></strong>
                </div>
                <?php if ($invoice['discount'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.9rem; color: var(--danger);">
                        <span>Discount:</span>
                        <strong>- <?= format_currency($invoice['discount']) ?></strong>
                    </div>
                <?php endif; ?>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.9rem;">
                    <span style="color: var(--text-muted);">GST (<?= (float)$invoice['tax_rate'] ?>%):</span>
                    <strong><?= format_currency($invoice['tax_amount']) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-top: 2px solid var(--border-color); border-bottom: 2px solid var(--border-color); font-size: 1.15rem; color: var(--dark);">
                    <strong>Total Amount:</strong>
                    <strong class="text-primary"><?= format_currency($invoice['total']) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.9rem; color: var(--success);">
                    <span>Amount Paid:</span>
                    <strong><?= format_currency($invoice['paid_amount']) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 1rem; font-weight: 700; color: <?= $balanceDue > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                    <span>Balance Due:</span>
                    <span><?= format_currency($balanceDue) ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Instructions & Bank Details -->
        <div style="background: #f8fafc; border-radius: var(--radius-md); padding: 20px; border: 1px solid var(--border-color);">
            <h4 style="font-size: 0.95rem; margin-bottom: 8px;"><i class="fas fa-university text-primary"></i> Payment Modes & Bank Details</h4>
            <div class="grid-2" style="font-size: 0.85rem; color: #334155;">
                <div>
                    <strong>UPI ID / QR:</strong> <?= e($businessWhatsApp) ?>@upi<br>
                    <strong>Google Pay / PhonePe:</strong> <?= e($businessPhone) ?>
                </div>
                <div>
                    <strong>Bank Name:</strong> HDFC Bank Ltd.<br>
                    <strong>A/C Name:</strong> <?= e($businessName) ?><br>
                    <strong>IFSC Code:</strong> HDFC0001234
                </div>
            </div>
        </div>

        <div style="margin-top: 36px; text-align: center; font-size: 0.82rem; color: var(--text-muted);">
            Thank you for choosing <?= e($businessName) ?>. This is a computer-generated tax invoice.
        </div>
    </div>
</div>

</body>
</html>
