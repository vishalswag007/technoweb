<?php
/**
 * Vishal Web Studio - Website Order & Requirements Intake Portal
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$selectedTemplateId = (int)($_GET['template'] ?? 0);

// Fetch all active templates for dropdown selection
$templates = [];
try {
    $templates = db()->query("SELECT id, name, category, price FROM templates WHERE status = 'active' ORDER BY sort_order ASC")->fetchAll();
} catch (Exception $e) {}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $businessName = trim($_POST['business_name'] ?? '');
        $ownerName    = trim($_POST['owner_name'] ?? '');
        $email        = trim(strtolower($_POST['email'] ?? ''));
        $phone        = trim($_POST['phone'] ?? '');
        $whatsapp     = trim($_POST['whatsapp'] ?? $phone);
        $category     = trim($_POST['business_category'] ?? 'General');
        $address      = trim($_POST['business_address'] ?? '');

        $templateId   = (int)($_POST['template_id'] ?? 0);
        $websiteName  = trim($_POST['website_name'] ?? $businessName);
        $requiredPages = json_encode($_POST['required_pages'] ?? ['Home', 'About Us', 'Services', 'Contact']);
        $requiredFeatures = json_encode($_POST['required_features'] ?? ['WhatsApp Integration', 'Mobile Responsive', 'Lead Form']);
        $colorPref    = trim($_POST['color_preference'] ?? 'Modern Blue');
        $notes        = trim($_POST['additional_requirements'] ?? '');

        if (empty($businessName) || empty($ownerName) || empty($email) || empty($phone)) {
            $error = 'Please fill in all mandatory business contact fields.';
        } else {
            try {
                $pdo = db();

                // Fetch template price
                $tPrice = 14999.00;
                if ($templateId > 0) {
                    $tStmt = $pdo->prepare("SELECT price FROM templates WHERE id = ?");
                    $tStmt->execute([$templateId]);
                    $tRow = $tStmt->fetch();
                    if ($tRow) $tPrice = (float)$tRow['price'];
                }

                // Handle Logo File Upload if provided
                $logoFileName = null;
                if (!empty($_FILES['logo_file']['name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
                        $logoFileName = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (!is_dir(UPLOADS_PATH)) mkdir(UPLOADS_PATH, 0755, true);
                        move_uploaded_file($_FILES['logo_file']['tmp_name'], UPLOADS_PATH . DIRECTORY_SEPARATOR . $logoFileName);
                    }
                }

                // Handle Assets Zip / Doc if provided
                $assetsFileName = null;
                if (!empty($_FILES['assets_file']['name']) && $_FILES['assets_file']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['assets_file']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['zip', 'pdf', 'docx', 'jpg', 'png'])) {
                        $assetsFileName = 'assets_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (!is_dir(UPLOADS_PATH)) mkdir(UPLOADS_PATH, 0755, true);
                        move_uploaded_file($_FILES['assets_file']['tmp_name'], UPLOADS_PATH . DIRECTORY_SEPARATOR . $assetsFileName);
                    }
                }

                // 1. Find or Create Client Account
                $chkUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $chkUser->execute([$email]);
                $userRow = $chkUser->fetch();

                if ($userRow) {
                    $userId = $userRow['id'];
                } else {
                    $tempPass = bin2hex(random_bytes(4)); // e.g. client123
                    $passHash = password_hash($tempPass, PASSWORD_DEFAULT);
                    $insUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, phone, status) VALUES (?, ?, ?, 'client', ?, 'active')");
                    $insUser->execute([$ownerName, $email, $passHash, $phone]);
                    $userId = $pdo->lastInsertId();
                }

                $chkClient = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
                $chkClient->execute([$email]);
                $clientRow = $chkClient->fetch();

                if ($clientRow) {
                    $clientId = $clientRow['id'];
                } else {
                    $insClient = $pdo->prepare("INSERT INTO clients (user_id, business_name, owner_name, phone, whatsapp, email, address, business_category, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                    $insClient->execute([$userId, $businessName, $ownerName, $phone, $whatsapp, $email, $address, $category]);
                    $clientId = $pdo->lastInsertId();
                }

                // 2. Generate Unique Order ID (e.g. VW-2026-00001)
                $orderNumber = generate_order_number();

                // 3. Insert Order
                $insOrder = $pdo->prepare("INSERT INTO orders (
                    order_number, client_id, template_id, business_name, owner_name, email, phone, whatsapp, 
                    business_category, business_address, required_pages, required_features, color_preference, 
                    additional_requirements, logo_file, assets_file, amount, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')");

                $insOrder->execute([
                    $orderNumber,
                    $clientId,
                    $templateId ?: null,
                    $businessName,
                    $ownerName,
                    $email,
                    $phone,
                    $whatsapp,
                    $category,
                    $address,
                    $requiredPages,
                    $requiredFeatures,
                    $colorPref,
                    $notes,
                    $logoFileName,
                    $assetsFileName,
                    $tPrice
                ]);

                $orderId = $pdo->lastInsertId();

                log_activity(
                    $userId,
                    'order_submitted',
                    'orders',
                    $orderId,
                    "New website order {$orderNumber} received from {$ownerName} ({$businessName})"
                );

                set_flash('success', "Order {$orderNumber} submitted successfully! Our team will review your requirements.");
                header('Location: ' . BASE_URL . '/public/track-order.php?order=' . urlencode($orderNumber));
                exit;

            } catch (Exception $ex) {
                $error = 'Database error: ' . $ex->getMessage();
            }
        }
    }
}

$pageTitle = 'Order Your Custom Website';
$currentNav = 'templates';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="section" style="padding-top: 50px; background: radial-gradient(circle at top right, #eff6ff 0%, #f8fafc 100%);">
    <div class="container" style="max-width: 860px;">
        <div class="section-header" style="margin-bottom: 30px;">
            <div class="section-subtitle">Online Requirements Intake</div>
            <h1 class="section-title">Start Your Website Project</h1>
            <p class="lead">Fill out your business requirements below. We will set up your dedicated website, generate your contract, and get your project underway.</p>
        </div>

        <?php if ($error): ?>
            <div style="background: var(--danger-light); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); padding: 14px 18px; border-radius: var(--radius-md); font-size: 0.92rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="card" style="box-shadow: var(--shadow-xl); border-radius: var(--radius-lg);">
            <?= csrf_field() ?>

            <div class="card-header" style="background: #f8fafc; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">1</div>
                    <h3 class="card-title">Business Information</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="business_name">Business / Company Name *</label>
                        <input type="text" name="business_name" id="business_name" class="form-control" placeholder="e.g. Royal Sweets & Restaurant" required value="<?= e($_POST['business_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="owner_name">Owner / Contact Person *</label>
                        <input type="text" name="owner_name" id="owner_name" class="form-control" placeholder="e.g. Ramesh Sharma" required value="<?= e($_POST['owner_name'] ?? '') ?>">
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address *</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="ramesh@gmail.com" required value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number *</label>
                        <input type="tel" name="phone" id="phone" class="form-control" placeholder="+91 98765 43210" required value="<?= e($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="whatsapp">WhatsApp Number</label>
                        <input type="tel" name="whatsapp" id="whatsapp" class="form-control" placeholder="+91 98765 43210" value="<?= e($_POST['whatsapp'] ?? '') ?>">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="business_category">Business Category</label>
                        <select name="business_category" id="business_category" class="form-select">
                            <option value="Restaurant & Food">Restaurant & Food</option>
                            <option value="Salon & Beauty Spa">Salon & Beauty Spa</option>
                            <option value="Coaching & Institute">Coaching & Institute</option>
                            <option value="Real Estate & Broker">Real Estate & Broker</option>
                            <option value="Medical & Clinic">Medical & Clinic</option>
                            <option value="Corporate & Agency">Corporate & Agency</option>
                            <option value="E-Commerce & Retail">E-Commerce & Retail</option>
                            <option value="Personal Portfolio">Personal Portfolio</option>
                            <option value="Other">Other Custom Field</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="business_address">Business Address / City</label>
                        <input type="text" name="business_address" id="business_address" class="form-control" placeholder="Shop 12, Sector 18, Noida" value="<?= e($_POST['business_address'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="card-header" style="background: #f8fafc; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">2</div>
                    <h3 class="card-title">Website Specifications & Design Preferences</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="template_id">Selected Template / Layout Style</label>
                        <select name="template_id" id="template_id" class="form-select">
                            <option value="0">-- Custom Website (No Template) --</option>
                            <?php foreach ($templates as $tmpl): ?>
                                <option value="<?= $tmpl['id'] ?>" <?= ($selectedTemplateId === (int)$tmpl['id']) ? 'selected' : '' ?>>
                                    <?= e($tmpl['name']) ?> (<?= e($tmpl['category']) ?> - <?= format_currency($tmpl['price']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="color_preference">Preferred Theme Accent Color</label>
                        <input type="text" name="color_preference" id="color_preference" class="form-control" placeholder="e.g. Royal Blue (#2563eb) or Warm Gold" value="<?= e($_POST['color_preference'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Required Pages for Your Website:</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; padding: 12px; background: #f8fafc; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <label style="font-size: 0.88rem; display: flex; align-items: center; gap: 6px;">
                            <input type="checkbox" name="required_pages[]" value="Home" checked> Home Page
                        </label>
                        <label style="font-size: 0.88rem; display: flex; align-items: center; gap: 6px;">
                            <input type="checkbox" name="required_pages[]" value="About Us" checked> About Us
                        </label>
                        <label style="font-size: 0.88rem; display: flex; align-items: center; gap: 6px;">
                            <input type="checkbox" name="required_pages[]" value="Services & Menu" checked> Services / Menu
                        </label>
                        <label style="font-size: 0.88rem; display: flex; align-items: center; gap: 6px;">
                            <input type="checkbox" name="required_pages[]" value="Photo Gallery" checked> Photo Gallery
                        </label>
                        <label style="font-size: 0.88rem; display: flex; align-items: center; gap: 6px;">
                            <input type="checkbox" name="required_pages[]" value="Testimonials" checked> Testimonials
                        </label>
                        <label style="font-size: 0.88rem; display: flex; align-items: center; gap: 6px;">
                            <input type="checkbox" name="required_pages[]" value="Contact & Map" checked> Contact & Map
                        </label>
                        <label style="font-size: 0.88rem; display: flex; align-items: center; gap: 6px;">
                            <input type="checkbox" name="required_pages[]" value="Blog / News"> Blog / News
                        </label>
                        <label style="font-size: 0.88rem; display: flex; align-items: center; gap: 6px;">
                            <input type="checkbox" name="required_pages[]" value="FAQs"> FAQs Section
                        </label>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="logo_file">Upload Brand Logo (Optional)</label>
                        <input type="file" name="logo_file" id="logo_file" class="form-control" accept="image/*">
                        <div class="form-help">Accepted formats: PNG, JPG, WEBP, SVG</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="assets_file">Upload Business Images / Docs (Optional)</label>
                        <input type="file" name="assets_file" id="assets_file" class="form-control" accept=".zip,.pdf,.docx,.jpg,.png">
                        <div class="form-help">Menu card, price list, or photo ZIP</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="additional_requirements">Additional Requirements & Special Instructions</label>
                    <textarea name="additional_requirements" id="additional_requirements" class="form-control" rows="4" placeholder="Tell us any specific features you need, custom domain preference, or reference websites you like..."><?= e($_POST['additional_requirements'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="card-footer" style="padding: 24px; background: #f8fafc; border-top: 1px solid var(--border-color); text-align: center;">
                <button type="submit" class="btn btn-primary btn-lg" style="min-width: 280px;">
                    <i class="fas fa-paper-plane"></i> Submit Website Request
                </button>
                <p class="form-help" style="margin-top: 12px;">
                    <i class="fas fa-lock text-success"></i> Your information is secure. Submitting this form generates your unique tracking ID.
                </p>
            </div>
        </form>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
