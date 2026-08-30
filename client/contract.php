<?php
/**
 * Vishal Web Studio - Client Contract & Signature Viewer
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_client();

$client = get_current_client_record();
$pdo = db();

$stmt = $pdo->prepare("SELECT * FROM contracts WHERE client_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$client['id']]);
$contract = $stmt->fetch();

$pageTitle = 'Signed Agreement & Digital Certificate';
$clientNav = 'contract';
require_once dirname(__DIR__) . '/includes/client_header.php';
?>

<div style="max-width: 860px; margin: 0 auto;">
    <?php if ($contract): ?>
        <div class="card" style="box-shadow: var(--shadow-md); border-radius: var(--radius-lg); margin-bottom: 24px;">
            <div class="card-header" style="background: #f8fafc; padding: 20px;">
                <div>
                    <h3 class="card-title"><?= e($contract['title']) ?></h3>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Contract Ref: <strong><?= e($contract['contract_number']) ?></strong></span>
                </div>
                <div>
                    <?= render_status_badge($contract['status']) ?>
                </div>
            </div>

            <div class="card-body" style="padding: 30px;">
                <div style="background: #f8fafc; border-radius: var(--radius-md); padding: 20px; border: 1px solid var(--border-color); margin-bottom: 24px;">
                    <div class="grid-2">
                        <div>
                            <p style="margin-bottom: 6px;"><strong>Agreed Package:</strong> <?= e($contract['package_name']) ?></p>
                            <p style="margin-bottom: 6px;"><strong>Project Compensation:</strong> <span class="text-primary font-bold"><?= format_currency($contract['price']) ?></span></p>
                            <p style="margin-bottom: 6px;"><strong>Timeline:</strong> <?= e($contract['timeline']) ?></p>
                        </div>
                        <div>
                            <?php if ($contract['status'] === 'signed'): ?>
                                <p style="margin-bottom: 6px;"><strong>Signed By:</strong> <?= e($contract['signer_name']) ?></p>
                                <p style="margin-bottom: 6px;"><strong>Signer Email:</strong> <?= e($contract['signer_email']) ?></p>
                                <p style="margin-bottom: 6px;"><strong>Execution Date:</strong> <?= format_datetime($contract['signed_at']) ?></p>
                            <?php else: ?>
                                <p class="text-warning"><strong>Status:</strong> Awaiting your signature</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px;">
                    <a href="<?= BASE_URL ?>/public/contract.php?token=<?= urlencode($contract['token']) ?>" target="_blank" class="btn btn-primary">
                        <i class="fas fa-file-signature"></i> View Full Contract & Sign Online
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card" style="padding: 40px; text-align: center;">
            <div style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 12px;"><i class="fas fa-file-contract"></i></div>
            <h3>No Contract Assigned Yet</h3>
            <p class="text-muted">Once your order requirements are reviewed by Vishal Web Studio, your digital contract link will appear here.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
