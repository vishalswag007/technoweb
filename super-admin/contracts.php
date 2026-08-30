<?php
/**
 * Vishal Web Studio - Super Admin Contract Management & Template Builder
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/contract_engine.php';

require_super_admin();

$pdo = db();

// Handle Create Contract POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_contract'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $clientId = (int)($_POST['client_id'] ?? 0);
        $title = trim($_POST['title'] ?? 'Website Development Agreement');
        $packageName = trim($_POST['package_name'] ?? 'Professional Package');
        $price = (float)($_POST['price'] ?? 14999.00);
        $paymentTerms = trim($_POST['payment_terms'] ?? '50% advance upon contract signing, 50% prior to final live launch.');
        $timeline = trim($_POST['timeline'] ?? '7 to 10 business days');
        $customContent = trim($_POST['contract_content'] ?? '');

        if ($clientId <= 0) {
            set_flash('danger', 'Please select a client.');
        } else {
            $clStmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
            $clStmt->execute([$clientId]);
            $client = $clStmt->fetch();

            $contractNumber = generate_contract_number();
            $token = generate_secure_token();

            if (empty($customContent)) {
                $templateRaw = ContractEngine::getDefaultContractTemplate();
                $contractData = [
                    'contract_number' => $contractNumber,
                    'client_name'     => $client['owner_name'],
                    'business_name'   => $client['business_name'],
                    'client_email'    => $client['email'],
                    'client_phone'    => $client['phone'],
                    'package_name'    => $packageName,
                    'price'           => $price,
                    'payment_terms'   => $paymentTerms,
                    'timeline'        => $timeline
                ];
                $compiledContent = ContractEngine::compileContract($templateRaw, $contractData);
            } else {
                $compiledContent = $customContent;
            }

            $ins = $pdo->prepare("INSERT INTO contracts (contract_number, token, client_id, title, package_name, price, payment_terms, timeline, contract_content, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'sent')");
            $ins->execute([$contractNumber, $token, $clientId, $title, $packageName, $price, $paymentTerms, $timeline, $compiledContent]);
            $newContractId = $pdo->lastInsertId();

            log_activity(current_user_id(), 'contract_created', 'contracts', $newContractId, "Created contract {$contractNumber} for client '{$client['business_name']}'");
            set_flash('success', "Contract {$contractNumber} generated successfully!");
            header('Location: ' . BASE_URL . '/super-admin/contracts.php');
            exit;
        }
    }
}

// Fetch Contracts
$contracts = $pdo->query("SELECT c.*, cl.business_name, cl.owner_name, cl.phone as client_phone FROM contracts c JOIN clients cl ON c.client_id = cl.id ORDER BY c.id DESC")->fetchAll();
$clientsList = $pdo->query("SELECT id, business_name, owner_name FROM clients WHERE status = 'active' ORDER BY business_name ASC")->fetchAll();

$pageTitle = 'Contract Generator & Signatures';
$adminNav = 'contracts';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
        Total Contracts: <strong><?= count($contracts) ?></strong> digital agreements generated.
    </p>

    <button type="button" class="btn btn-primary btn-sm" onclick="openContractModal()">
        <i class="fas fa-plus"></i> Generate New Contract
    </button>
</div>

<!-- Contracts Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Contract Ref & Title</th>
                    <th>Client Business</th>
                    <th>Package & Value</th>
                    <th>Status</th>
                    <th>Execution Audit</th>
                    <th>Created</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contracts)): ?>
                    <tr><td colspan="7" class="text-center text-muted" style="padding: 30px;">No contracts generated yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($contracts as $cnt): ?>
                        <tr>
                            <td>
                                <strong class="text-primary"><?= e($cnt['contract_number']) ?></strong><br>
                                <span style="font-size: 0.85rem; color: var(--dark);"><?= e($cnt['title']) ?></span>
                            </td>
                            <td>
                                <strong><?= e($cnt['business_name']) ?></strong><br>
                                <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($cnt['owner_name']) ?></span>
                            </td>
                            <td>
                                <strong><?= format_currency($cnt['price']) ?></strong><br>
                                <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($cnt['package_name']) ?></span>
                            </td>
                            <td>
                                <?= render_status_badge($cnt['status']) ?>
                            </td>
                            <td>
                                <?php if ($cnt['status'] === 'signed'): ?>
                                    <div style="font-size: 0.8rem; color: var(--success);">
                                        <i class="fas fa-check-circle"></i> Signed by: <strong><?= e($cnt['signer_name']) ?></strong><br>
                                        <span style="font-size: 0.72rem; color: var(--text-muted);"><?= format_datetime($cnt['signed_at']) ?> • IP: <?= e($cnt['signer_ip']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--warning);"><i class="fas fa-clock"></i> Awaiting Signature</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                <?= format_date($cnt['created_at']) ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= BASE_URL ?>/public/contract.php?token=<?= urlencode($cnt['token']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="View / Sign Contract">
                                        <i class="fas fa-eye text-primary"></i>
                                    </a>

                                    <button type="button" class="btn btn-secondary btn-sm" title="Copy Secure Signing Link" onclick="copyLink('<?= BASE_URL ?>/public/contract.php?token=<?= urlencode($cnt['token']) ?>')">
                                        <i class="fas fa-link text-info"></i>
                                    </button>

                                    <a href="<?= build_whatsapp_link($cnt['client_phone'], "Hello {$cnt['owner_name']}, your official website contract {$cnt['contract_number']} is ready for digital signature: " . BASE_URL . "/public/contract.php?token=" . urlencode($cnt['token'])) ?>" target="_blank" class="btn btn-whatsapp btn-sm" title="Send WhatsApp Signing Link">
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

<!-- Create Contract Modal -->
<div class="modal-backdrop" id="contractModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-file-signature text-primary" style="margin-right: 8px;"></i> Generate New Contract</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="create_contract" value="1">

            <div class="modal-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="cnt_client_id">Select Client *</label>
                        <select name="client_id" id="cnt_client_id" class="form-select" required>
                            <option value="">-- Choose Client --</option>
                            <?php foreach ($clientsList as $cl): ?>
                                <option value="<?= $cl['id'] ?>"><?= e($cl['business_name']) ?> (<?= e($cl['owner_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cnt_title">Agreement Title</label>
                        <input type="text" name="title" id="cnt_title" class="form-control" value="Website Design, Development & Hosting Agreement" required>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label" for="cnt_package">Package / Plan</label>
                        <input type="text" name="package_name" id="cnt_package" class="form-control" value="Restaurant Delight Package">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cnt_price">Total Compensation (INR) *</label>
                        <input type="number" step="0.01" name="price" id="cnt_price" class="form-control" value="14999.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cnt_timeline">Timeline</label>
                        <input type="text" name="timeline" id="cnt_timeline" class="form-control" value="7 to 10 business days">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="cnt_payment_terms">Payment Terms</label>
                    <input type="text" name="payment_terms" id="cnt_payment_terms" class="form-control" value="50% advance upon contract signing, 50% prior to final live launch.">
                </div>

                <div class="form-group">
                    <label class="form-label" for="cnt_content">Custom Terms Override (Leave empty to use standard template)</label>
                    <textarea name="contract_content" id="cnt_content" class="form-control" rows="4" placeholder="Leave empty to automatically compile standard terms with merge tags..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-bolt"></i> Generate Contract Link</button>
            </div>
        </form>
    </div>
</div>

<script>
function openContractModal() {
    openModal('contractModal');
}

function copyLink(url) {
    navigator.clipboard.writeText(url).then(() => {
        showToast('Secure signing link copied to clipboard!', 'success');
    });
}

<?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
document.addEventListener('DOMContentLoaded', openContractModal);
<?php endif; ?>
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
