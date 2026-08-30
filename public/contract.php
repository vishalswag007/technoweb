<?php
/**
 * Vishal Web Studio - Public Secure Token Contract Signing & PDF Generator
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/contract_engine.php';

$token = trim($_GET['token'] ?? '');

$stmt = db()->prepare("SELECT c.*, cl.business_name, cl.owner_name, cl.phone as client_phone, cl.email as client_email, cl.address as client_address FROM contracts c JOIN clients cl ON c.client_id = cl.id WHERE c.token = ?");
$stmt->execute([$token]);
$contract = $stmt->fetch();

if (!$contract) {
    die("<h3>Invalid or expired contract link.</h3><p>Please contact Vishal Web Studio support.</p>");
}

$isSigned = ($contract['status'] === 'signed');
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isSigned) {
    $csrfToken   = $_POST['csrf_token'] ?? '';
    $signerName  = trim($_POST['signer_name'] ?? '');
    $signerEmail = trim($_POST['signer_email'] ?? '');
    $sigMethod   = $_POST['signature_method'] ?? 'draw';
    $agreed      = !empty($_POST['agree_terms']);

    $sigData = '';
    if ($sigMethod === 'draw') {
        $sigData = $_POST['canvas_signature_data'] ?? '';
    } elseif ($sigMethod === 'type') {
        $sigData = $_POST['typed_signature_data'] ?? '';
    } elseif ($sigMethod === 'upload') {
        if (!empty($_FILES['upload_signature_file']['name']) && $_FILES['upload_signature_file']['error'] === UPLOAD_ERR_OK) {
            $fileData = file_get_contents($_FILES['upload_signature_file']['tmp_name']);
            $mime = mime_content_type($_FILES['upload_signature_file']['tmp_name']);
            $sigData = 'data:' . $mime . ';base64,' . base64_encode($fileData);
        }
    }

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Session expired. Please try again.';
    } elseif (!$agreed) {
        $error = 'You must check the agreement box confirming that you have read and agree to the contract terms.';
    } elseif (empty($signerName) || empty($signerEmail)) {
        $error = 'Please enter your full legal name and email address.';
    } elseif (empty($sigData)) {
        $error = 'Please provide your digital signature using your preferred method (Draw, Type, or Upload).';
    } else {
        $signedOk = ContractEngine::signContract($contract['id'], [
            'signer_name'      => $signerName,
            'signer_email'     => $signerEmail,
            'signature_method' => $sigMethod,
            'signature_data'   => $sigData
        ]);

        if ($signedOk) {
            set_flash('success', 'Contract successfully signed and legally locked! You can print or download your certificate.');
            header('Location: ' . BASE_URL . '/public/contract.php?token=' . urlencode($token));
            exit;
        } else {
            $error = 'Failed to record digital signature. Please try again.';
        }
    }
}

$businessName = get_setting('business_name', APP_NAME);
$pageTitle = 'Digital Contract Agreement - ' . $contract['contract_number'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e($businessName) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/main.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    
    <style>
        .contract-paper {
            background: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05);
            border-radius: 12px;
            padding: 50px 60px;
            border: 1px solid var(--border-color);
            margin-bottom: 40px;
        }
        .contract-paper h2 { font-size: 1.6rem; color: #0f172a; margin-bottom: 6px; }
        .contract-paper h4 { font-size: 1.15rem; color: #0f172a; margin-top: 28px; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; }
        .contract-paper p, .contract-paper li { font-size: 0.95rem; color: #334155; line-height: 1.7; }
        .contract-paper ul { padding-left: 20px; margin-bottom: 16px; }
        .sig-tab-btn {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            background: #ffffff;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            border-radius: 6px;
            color: var(--text-muted);
            transition: all 0.2s ease;
        }
        .sig-tab-btn.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
        .typed-preview-box {
            font-family: 'Dancing Script', cursive;
            font-size: 2.2rem;
            color: #1e3a8a;
            padding: 24px;
            background: #f8fafc;
            border-radius: 8px;
            text-align: center;
            border: 1px dashed #cbd5e1;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body style="background: #f1f5f9; padding: 40px 0;">

<?= render_flash_messages() ?>

<div class="container" style="max-width: 900px;">
    <!-- Top Action Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="brand-logo-icon"><i class="fas fa-file-contract"></i></div>
            <div>
                <h3 style="margin: 0; font-size: 1.25rem;"><?= e($businessName) ?></h3>
                <span class="text-muted" style="font-size: 0.85rem;">Official Digital Contract Signing Gateway</span>
            </div>
        </div>

        <div style="display: flex; gap: 10px;">
            <?php if ($isSigned): ?>
                <button onclick="window.print()" class="btn btn-primary btn-sm">
                    <i class="fas fa-print"></i> Print / Download PDF
                </button>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-home"></i> Home
            </a>
        </div>
    </div>

    <?php if ($isSigned): ?>
        <div class="card" style="background: #ecfdf5; border-color: #a7f3d0; margin-bottom: 24px;">
            <div class="card-body" style="padding: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #10b981; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: #065f46; font-size: 1.15rem;">Contract Successfully Signed & Locked</h4>
                        <div style="font-size: 0.85rem; color: #047857;">
                            Digitally executed by <strong><?= e($contract['signer_name']) ?></strong> (<?= e($contract['signer_email']) ?>) on <?= format_datetime($contract['signed_at']) ?>
                        </div>
                    </div>
                </div>
                <div class="badge badge-success" style="font-size: 0.85rem; padding: 6px 14px;">
                    <i class="fas fa-lock"></i> Tamper-Proof Locked
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: var(--danger-light); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); padding: 14px 18px; border-radius: var(--radius-md); font-size: 0.92rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- The Contract Paper -->
    <div class="contract-paper">
        <?= $contract['contract_content'] ?>

        <hr style="margin: 40px 0; border: none; border-top: 1px solid #e2e8f0;">

        <!-- Signatures Section -->
        <div class="grid-2" style="margin-top: 30px;">
            <div>
                <p style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">For Developer / Agency:</p>
                <div style="padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; min-height: 120px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div style="font-family: 'Dancing Script', cursive; font-size: 1.8rem; color: var(--primary);">
                        Vishal Yaduvansi
                    </div>
                    <div style="font-size: 0.78rem; color: var(--text-muted); border-top: 1px dashed #cbd5e1; padding-top: 6px;">
                        <strong><?= e($businessName) ?></strong> (Authorized Signatory)
                    </div>
                </div>
            </div>

            <div>
                <p style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">For Client / Business:</p>
                
                <?php if ($isSigned): ?>
                    <div style="padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #10b981; min-height: 120px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <?php if (str_starts_with($contract['signature_data'] ?? '', 'data:image')): ?>
                                <img src="<?= $contract['signature_data'] ?>" alt="Client Digital Signature" style="max-height: 70px; object-fit: contain;">
                            <?php else: ?>
                                <div style="font-family: 'Dancing Script', cursive; font-size: 1.8rem; color: #1e3a8a;">
                                    <?= e($contract['signer_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.78rem; color: #047857; border-top: 1px dashed #a7f3d0; padding-top: 6px;">
                            <strong><?= e($contract['signer_name']) ?></strong> • Signed on <?= format_datetime($contract['signed_at']) ?><br>
                            <span style="font-family: monospace; font-size: 0.7rem; color: #64748b;">Hash: <?= substr($contract['contract_hash'] ?? 'Verified', 0, 24) ?>...</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="padding: 16px; background: #fffbeb; border-radius: 8px; border: 1px dashed #f59e0b; min-height: 120px; display: flex; align-items: center; justify-content: center; text-align: center; color: #d97706; font-size: 0.9rem;">
                        <i class="fas fa-pen-fancy" style="margin-right: 6px;"></i> Awaiting Client Digital Signature Below
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Digital Signing Form Box if Unsigned -->
    <?php if (!$isSigned): ?>
        <div class="card" style="box-shadow: var(--shadow-xl); border-radius: var(--radius-lg); overflow: hidden;">
            <div class="card-header" style="background: #f8fafc;">
                <h3 class="card-title"><i class="fas fa-signature text-primary" style="margin-right: 8px;"></i> Sign This Contract Online</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" id="contractSignForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="signature_method" id="selectedSigMethod" value="draw">
                    <input type="hidden" name="canvas_signature_data" id="canvasSigData">
                    <input type="hidden" name="typed_signature_data" id="typedSigData">

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label" for="signer_name">Full Legal Name *</label>
                            <input type="text" name="signer_name" id="signer_name" class="form-control" placeholder="e.g. Ramesh Sharma" required value="<?= e($contract['owner_name']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="signer_email">Signer Email Address *</label>
                            <input type="email" name="signer_email" id="signer_email" class="form-control" placeholder="e.g. ramesh@gmail.com" required value="<?= e($contract['client_email']) ?>">
                        </div>
                    </div>

                    <!-- Method Selection Tabs -->
                    <div class="form-group">
                        <label class="form-label">Select Signing Method:</label>
                        <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                            <button type="button" class="sig-tab-btn active" data-sig-tab="draw"><i class="fas fa-pencil-alt"></i> Draw Signature</button>
                            <button type="button" class="sig-tab-btn" data-sig-tab="type"><i class="fas fa-keyboard"></i> Type Signature</button>
                            <button type="button" class="sig-tab-btn" data-sig-tab="upload"><i class="fas fa-upload"></i> Upload Signature Image</button>
                        </div>

                        <!-- Tab 1: Draw Canvas -->
                        <div id="sigTabDraw" class="sig-tab-content">
                            <div class="signature-box">
                                <canvas id="sigCanvas" class="signature-canvas"></canvas>
                                <div class="signature-actions">
                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="fas fa-mouse-pointer"></i> Use your finger or mouse to draw</span>
                                    <button type="button" class="btn btn-secondary btn-sm" id="clearCanvasBtn"><i class="fas fa-eraser"></i> Clear Pad</button>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Typed Signature -->
                        <div id="sigTabType" class="sig-tab-content" style="display: none;">
                            <div class="form-group">
                                <input type="text" id="typedInput" class="form-control" placeholder="Type your full name here" value="<?= e($contract['owner_name']) ?>">
                            </div>
                            <div class="typed-preview-box" id="typedPreview">
                                <?= e($contract['owner_name']) ?>
                            </div>
                        </div>

                        <!-- Tab 3: Upload Signature File -->
                        <div id="sigTabUpload" class="sig-tab-content" style="display: none;">
                            <div style="padding: 24px; border: 2px dashed #cbd5e1; border-radius: var(--radius-md); text-align: center; background: #f8fafc;">
                                <input type="file" name="upload_signature_file" id="uploadSigInput" accept="image/png,image/jpeg" class="form-control" style="max-width: 400px; margin: 0 auto 10px;">
                                <span class="form-help">Upload a clear photo or PNG of your handwritten signature.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Agreement Checkbox -->
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 16px; border-radius: var(--radius-md); margin-bottom: 24px;">
                        <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 0.92rem; color: #1e3a8a; cursor: pointer;">
                            <input type="checkbox" name="agree_terms" id="agree_terms" value="1" required style="margin-top: 4px;">
                            <span>I confirm that I have read, understood, and agree to the scope, payment terms, and conditions detailed in this Website Services Agreement.</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="fas fa-check-circle"></i> Complete Digital Signature & Lock Contract
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="<?= ASSETS_URL ?>/js/signature_pad.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sigPad = new SignaturePadEngine('sigCanvas', { strokeColor: '#1e3a8a', lineWidth: 2.5 });
    
    const clearBtn = document.getElementById('clearCanvasBtn');
    if (clearBtn && sigPad) {
        clearBtn.addEventListener('click', () => sigPad.clear());
    }

    // Tab Switching
    const tabBtns = document.querySelectorAll('[data-sig-tab]');
    const tabDraw = document.getElementById('sigTabDraw');
    const tabType = document.getElementById('sigTabType');
    const tabUpload = document.getElementById('sigTabUpload');
    const methodInput = document.getElementById('selectedSigMethod');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const tab = btn.getAttribute('data-sig-tab');
            methodInput.value = tab;

            tabDraw.style.display = tab === 'draw' ? 'block' : 'none';
            tabType.style.display = tab === 'type' ? 'block' : 'none';
            tabUpload.style.display = tab === 'upload' ? 'block' : 'none';

            if (tab === 'draw') sigPad.resizeCanvas();
        });
    });

    // Typed Signature sync
    const typedInput = document.getElementById('typedInput');
    const typedPreview = document.getElementById('typedPreview');
    if (typedInput && typedPreview) {
        typedInput.addEventListener('input', () => {
            typedPreview.textContent = typedInput.value || 'Your Signature Preview';
        });
    }

    // Form Submission capture
    const form = document.getElementById('contractSignForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            const method = methodInput.value;
            if (method === 'draw') {
                if (sigPad.isEmpty()) {
                    e.preventDefault();
                    alert('Please draw your signature in the box before submitting.');
                    return;
                }
                document.getElementById('canvasSigData').value = sigPad.toDataURL();
            } else if (method === 'type') {
                const name = typedInput.value.trim();
                if (!name) {
                    e.preventDefault();
                    alert('Please type your name for your signature.');
                    return;
                }
                document.getElementById('typedSigData').value = generateTypedSignatureSvg(name);
            }
        });
    }
});
</script>

</body>
</html>
