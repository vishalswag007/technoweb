<?php
/**
 * Vishal Web Studio - 15-Stage Visual Order Pipeline Tracker
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$orderNumber = trim($_GET['order'] ?? '');
$order = null;
$contract = null;
$invoice = null;

if (!empty($orderNumber)) {
    $stmt = db()->prepare("SELECT o.*, t.name as template_name, t.category as template_category, c.business_name as client_biz FROM orders o LEFT JOIN templates t ON o.template_id = t.id LEFT JOIN clients c ON o.client_id = c.id WHERE o.order_number = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();

    if ($order) {
        // Check linked contract
        $cStmt = db()->prepare("SELECT id, contract_number, token, status FROM contracts WHERE order_id = ? ORDER BY id DESC LIMIT 1");
        $cStmt->execute([$order['id']]);
        $contract = $cStmt->fetch();

        // Check linked invoice
        $iStmt = db()->prepare("SELECT id, invoice_number, status, total, paid_amount FROM invoices WHERE order_id = ? ORDER BY id DESC LIMIT 1");
        $iStmt->execute([$order['id']]);
        $invoice = $iStmt->fetch();
    }
}

$allStatuses = [
    'new' => '1. New Order',
    'contacted' => '2. Contacted',
    'requirements_pending' => '3. Requirements',
    'contract_pending' => '4. Contract Pending',
    'contract_signed' => '5. Contract Signed',
    'payment_pending' => '6. Payment Pending',
    'payment_received' => '7. Payment Done',
    'development' => '8. Development',
    'client_review' => '9. Client Review',
    'changes_requested' => '10. Revisions',
    'final_approval' => '11. Approved',
    'published' => '12. Published Live',
    'maintenance' => '13. Maintenance',
    'completed' => '14. Completed',
    'cancelled' => 'Cancelled'
];

$pageTitle = 'Track Website Order Progress';
$currentNav = 'home';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="section" style="padding-top: 50px;">
    <div class="container" style="max-width: 960px;">
        <div class="section-header">
            <div class="section-subtitle">Real-Time Project Pipeline</div>
            <h1 class="section-title">Track Website Order Status</h1>
            <p class="lead">Check the real-time stage of your website development, digital contract, and publication.</p>
        </div>

        <!-- Search Bar -->
        <div class="card" style="margin-bottom: 30px; box-shadow: var(--shadow-md);">
            <div class="card-body" style="padding: 20px;">
                <form method="GET" action="" style="display: flex; gap: 12px; align-items: center;">
                    <div style="flex-grow: 1;">
                        <input type="text" name="order" class="form-control" placeholder="Enter Order Number (e.g. VW-2026-00001)" value="<?= e($orderNumber) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="white-space: nowrap;">
                        <i class="fas fa-search"></i> Track Status
                    </button>
                </form>
            </div>
        </div>

        <?php if ($order): ?>
            <?php
            $currentStatus = strtolower($order['status']);
            $statusKeys = array_keys($allStatuses);
            $currentIndex = array_search($currentStatus, $statusKeys);
            if ($currentIndex === false) $currentIndex = 0;
            ?>

            <div class="card" style="box-shadow: var(--shadow-xl); border-radius: var(--radius-lg); overflow: hidden;">
                <div class="card-header" style="background: #f8fafc; padding: 24px;">
                    <div>
                        <div style="font-size: 0.8rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted);">Order Tracking</div>
                        <h2 style="font-size: 1.6rem; color: var(--dark); margin: 4px 0;"><?= e($order['business_name']) ?></h2>
                        <div style="font-size: 0.88rem; color: var(--text-muted);">
                            Order ID: <strong class="text-primary"><?= e($order['order_number']) ?></strong> • Placed on <?= format_date($order['created_at']) ?>
                        </div>
                    </div>
                    <div>
                        <?= render_status_badge($order['status']) ?>
                    </div>
                </div>

                <div class="card-body" style="padding: 30px 24px;">
                    <!-- Visual Progress Bar / Stepper -->
                    <div style="margin-bottom: 36px;">
                        <h4 style="margin-bottom: 16px; font-size: 1.1rem;"><i class="fas fa-tasks text-primary" style="margin-right: 8px;"></i> Project Stage Progression</h4>
                        
                        <div class="order-stepper">
                            <?php 
                            $stepCount = 0;
                            foreach ($allStatuses as $key => $label): 
                                if ($key === 'cancelled') continue;
                                $stepCount++;
                                $isCompleted = ($currentIndex > array_search($key, $statusKeys));
                                $isActive = ($currentStatus === $key);
                            ?>
                                <div class="step-item <?= $isCompleted ? 'completed' : ($isActive ? 'active' : '') ?>">
                                    <div class="step-circle">
                                        <?php if ($isCompleted): ?>
                                            <i class="fas fa-check" style="font-size: 0.8rem;"></i>
                                        <?php else: ?>
                                            <?= $stepCount ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="step-label"><?= e(explode('. ', $label)[1] ?? $label) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Action Hub based on Status -->
                    <div style="background: #f8fafc; border-radius: var(--radius-md); padding: 24px; border: 1px solid var(--border-color); margin-bottom: 30px;">
                        <h4 style="margin-bottom: 12px; font-size: 1.1rem;"><i class="fas fa-bolt text-warning" style="margin-right: 8px;"></i> Next Steps for this Order</h4>
                        
                        <?php if ($contract && $contract['status'] === 'draft' || $contract && $contract['status'] === 'sent'): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                                <div>
                                    <strong>Digital Contract Ready for Signature!</strong>
                                    <p class="text-muted" style="font-size: 0.88rem; margin: 4px 0 0;">Please review your contract terms and apply your digital signature.</p>
                                </div>
                                <a href="<?= BASE_URL ?>/public/contract.php?token=<?= urlencode($contract['token']) ?>" class="btn btn-primary">
                                    <i class="fas fa-file-signature"></i> Sign Contract Now
                                </a>
                            </div>
                        <?php elseif ($contract && $contract['status'] === 'signed'): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                                <div>
                                    <strong class="text-success"><i class="fas fa-check-circle"></i> Contract Digitally Signed & Locked</strong>
                                    <p class="text-muted" style="font-size: 0.88rem; margin: 4px 0 0;">You can download your signed agreement certificate anytime.</p>
                                </div>
                                <a href="<?= BASE_URL ?>/public/contract.php?token=<?= urlencode($contract['token']) ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-download"></i> View Signed Contract
                                </a>
                            </div>
                        <?php else: ?>
                            <p style="font-size: 0.92rem; color: var(--text-main); margin-bottom: 0;">
                                Our engineering team is currently reviewing your project requirements. You will receive a WhatsApp notification with your secure contract signing link shortly.
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Details Summary Grid -->
                    <div class="grid-2">
                        <div>
                            <h4 style="font-size: 1rem; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">Business Information</h4>
                            <p style="font-size: 0.9rem; margin-bottom: 6px;"><strong>Owner:</strong> <?= e($order['owner_name']) ?></p>
                            <p style="font-size: 0.9rem; margin-bottom: 6px;"><strong>Email:</strong> <?= e($order['email']) ?></p>
                            <p style="font-size: 0.9rem; margin-bottom: 6px;"><strong>Phone / WhatsApp:</strong> <?= e($order['phone']) ?></p>
                            <p style="font-size: 0.9rem; margin-bottom: 6px;"><strong>Category:</strong> <?= e($order['business_category']) ?></p>
                        </div>
                        <div>
                            <h4 style="font-size: 1rem; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">Website Specifications</h4>
                            <p style="font-size: 0.9rem; margin-bottom: 6px;"><strong>Selected Template:</strong> <?= e($order['template_name'] ?? 'Custom Web Solution') ?></p>
                            <p style="font-size: 0.9rem; margin-bottom: 6px;"><strong>Color Preference:</strong> <?= e($order['color_preference']) ?></p>
                            <p style="font-size: 0.9rem; margin-bottom: 6px;"><strong>Project Amount:</strong> <strong class="text-primary"><?= format_currency($order['amount']) ?></strong></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif (!empty($orderNumber)): ?>
            <div class="card" style="padding: 40px; text-align: center;">
                <div style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 12px;"><i class="fas fa-question-circle"></i></div>
                <h3>Order Not Found</h3>
                <p class="text-muted">No matching website project found for reference: <strong><?= e($orderNumber) ?></strong>. Please verify your order number or contact our WhatsApp desk.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
