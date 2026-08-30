<?php
/**
 * Vishal Web Studio - Super Admin Global Settings & No-Code Customizer
 * Custom Footers, 3D Design Styles, Payment Integrations & Brand Controls
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Handle Save Settings POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $allowedKeys = [
            'business_name', 'tagline', 'email', 'phone', 'whatsapp', 'address',
            'footer_business_name', 'footer_copyright_text', 'footer_tagline', 'footer_about_text',
            'primary_theme_color', 'accent_theme_color', 'ui_font_family', 'enable_3d_glassmorphism', 'enable_glossy_buttons',
            'currency_symbol', 'currency_code', 'tax_rate', 'tax_name',
            'whatsapp_order_msg', 'whatsapp_contract_msg', 'whatsapp_payment_msg', 'whatsapp_live_msg',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass',
            'razorpay_key', 'razorpay_secret', 'session_timeout'
        ];

        $setStmt = $pdo->prepare("INSERT OR REPLACE INTO global_settings (setting_key, setting_value) VALUES (:key, :val)");
        if (Database::getInstance()->isMySQL()) {
            $setStmt = $pdo->prepare("INSERT INTO global_settings (setting_key, setting_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        }

        foreach ($allowedKeys as $k) {
            $val = isset($_POST[$k]) ? trim($_POST[$k]) : '';
            $setStmt->execute([':key' => $k, ':val' => $val]);
        }

        log_activity(current_user_id(), 'settings_updated', 'global_settings', null, 'Updated global business settings, footers & 3D styling.');
        set_flash('success', 'All business settings, custom footers, and 3D visual styles saved successfully!');
        header('Location: ' . BASE_URL . '/super-admin/settings.php');
        exit;
    }
}

// Fetch all settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM global_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

$pageTitle = 'Global Business & No-Code Design Settings';
$adminNav = 'settings';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<form method="POST" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="save_settings" value="1">

    <div class="grid-2" style="align-items: start; gap: 24px;">
        
        <!-- 1. Business Profile & Branding -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-store text-primary" style="margin-right: 8px;"></i> Studio Branding & Profile</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="business_name">Agency / Business Display Name</label>
                    <input type="text" name="business_name" id="business_name" class="form-control" value="<?= e($settings['business_name'] ?? APP_NAME) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="tagline">Tagline / Mission</label>
                    <input type="text" name="tagline" id="tagline" class="form-control" value="<?= e($settings['tagline'] ?? APP_TAGLINE) ?>">
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="email">Official Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?= e($settings['email'] ?? APP_EMAIL) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Support Phone Number</label>
                        <input type="tel" name="phone" id="phone" class="form-control" value="<?= e($settings['phone'] ?? APP_PHONE) ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="whatsapp">WhatsApp Number (digits only)</label>
                        <input type="text" name="whatsapp" id="whatsapp" class="form-control" value="<?= e($settings['whatsapp'] ?? APP_WHATSAPP) ?>" required>
                        <div class="form-help">Country code included, e.g. 919876543210</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="tax_rate">Default GST / Tax Rate (%)</label>
                        <input type="number" step="0.01" name="tax_rate" id="tax_rate" class="form-control" value="<?= e($settings['tax_rate'] ?? '18.00') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Studio Office Address</label>
                    <textarea name="address" id="address" class="form-control" rows="2"><?= e($settings['address'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- 2. Main Public Website Footer Customizer -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shoe-prints text-success" style="margin-right: 8px;"></i> Main Website Footer Customizer</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="footer_business_name">Footer Brand Name</label>
                    <input type="text" name="footer_business_name" id="footer_business_name" class="form-control" value="<?= e($settings['footer_business_name'] ?? get_setting('business_name', 'Vishal Web Studio')) ?>" placeholder="e.g. Vishal Web Studio IT Solutions">
                    <div class="form-help">Appears in main agency website footer header &amp; copyright.</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="footer_copyright_text">Custom Footer Copyright Notice</label>
                    <input type="text" name="footer_copyright_text" id="footer_copyright_text" class="form-control" value="<?= e($settings['footer_copyright_text'] ?? ('© ' . date('Y') . ' Vishal Web Studio. All rights reserved.')) ?>" placeholder="e.g. © 2026 Vishal Web Studio. All rights reserved.">
                </div>

                <div class="form-group">
                    <label class="form-label" for="footer_tagline">Footer Subtitle / Tagline</label>
                    <input type="text" name="footer_tagline" id="footer_tagline" class="form-control" value="<?= e($settings['footer_tagline'] ?? 'Leading Website Development & Management Software Company in India') ?>" placeholder="e.g. Complete Business Software & IT Systems">
                </div>

                <div class="form-group">
                    <label class="form-label" for="footer_about_text">Footer About Summary</label>
                    <textarea name="footer_about_text" id="footer_about_text" class="form-control" rows="2" placeholder="Brief company summary in footer column 1..."><?= e($settings['footer_about_text'] ?? 'Specialized in customized web development, responsive designs, CMS platforms, ERP systems and software solutions for modern Indian businesses.') ?></textarea>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. No-Code 3D Glassmorphic & UI Customizer -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-cube text-info" style="margin-right: 8px;"></i> No-Code 3D Visual & UI Customizer</h3>
        </div>
        <div class="card-body">
            <div class="grid-4">
                <div class="form-group">
                    <label class="form-label" for="primary_theme_color">Primary 3D Brand Color</label>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="color" name="primary_theme_color" id="primary_theme_color" value="<?= e($settings['primary_theme_color'] ?? '#0754b8') ?>" style="width: 45px; height: 42px; padding: 2px; border-radius: 8px; border: 1px solid #cbd5e1; cursor: pointer;">
                        <input type="text" id="primaryColorText" class="form-control" value="<?= e($settings['primary_theme_color'] ?? '#0754b8') ?>" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="accent_theme_color">Accent 3D Gradient Color</label>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="color" name="accent_theme_color" id="accent_theme_color" value="<?= e($settings['accent_theme_color'] ?? '#ef1515') ?>" style="width: 45px; height: 42px; padding: 2px; border-radius: 8px; border: 1px solid #cbd5e1; cursor: pointer;">
                        <input type="text" id="accentColorText" class="form-control" value="<?= e($settings['accent_theme_color'] ?? '#ef1515') ?>" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ui_font_family">Global Font Family</label>
                    <select name="ui_font_family" id="ui_font_family" class="form-select">
                        <?php $f = $settings['ui_font_family'] ?? 'Inter'; ?>
                        <option value="Inter" <?= $f === 'Inter' ? 'selected' : '' ?>>Inter (Clean &amp; Modern)</option>
                        <option value="Poppins" <?= $f === 'Poppins' ? 'selected' : '' ?>>Poppins (Bold &amp; Rounded)</option>
                        <option value="Montserrat" <?= $f === 'Montserrat' ? 'selected' : '' ?>>Montserrat (Geometric Tech)</option>
                        <option value="Outfit" <?= $f === 'Outfit' ? 'selected' : '' ?>>Outfit (Contemporary 3D)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="enable_3d_glassmorphism">3D Glass &amp; Glossy Aesthetics</label>
                    <select name="enable_3d_glassmorphism" id="enable_3d_glassmorphism" class="form-select">
                        <?php $g = $settings['enable_3d_glassmorphism'] ?? '1'; ?>
                        <option value="1" <?= $g === '1' ? 'selected' : '' ?>>Enabled (3D Glass, Glossy &amp; Elevated)</option>
                        <option value="0" <?= $g === '0' ? 'selected' : '' ?>>Flat Modern Style</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. WhatsApp Quick Message Templates -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fab fa-whatsapp text-success" style="margin-right: 8px;"></i> Automated WhatsApp Templates</h3>
        </div>
        <div class="card-body">
            <div class="grid-3">
                <div class="form-group">
                    <label class="form-label" for="whatsapp_order_msg">Order Confirmation</label>
                    <textarea name="whatsapp_order_msg" id="whatsapp_order_msg" class="form-control" rows="3"><?= e($settings['whatsapp_order_msg'] ?? "Hello {client_name}, thank you for your order {order_number} for {business_name}!") ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="whatsapp_contract_msg">Contract for Signature</label>
                    <textarea name="whatsapp_contract_msg" id="whatsapp_contract_msg" class="form-control" rows="3"><?= e($settings['whatsapp_contract_msg'] ?? "Hello {client_name}, your website contract is ready for digital signature: {contract_url}") ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="whatsapp_live_msg">Website Live Alert</label>
                    <textarea name="whatsapp_live_msg" id="whatsapp_live_msg" class="form-control" rows="3"><?= e($settings['whatsapp_live_msg'] ?? "Congratulations {client_name}! Your website {website_url} is now LIVE!") ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Integrations & Security -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-lock text-warning" style="margin-right: 8px;"></i> Payment Gateway & Security Settings</h3>
        </div>
        <div class="card-body">
            <div class="grid-3">
                <div class="form-group">
                    <label class="form-label" for="razorpay_key">Razorpay Key ID</label>
                    <input type="text" name="razorpay_key" id="razorpay_key" class="form-control" value="<?= e($settings['razorpay_key'] ?? '') ?>" placeholder="rzp_test_xxxx">
                </div>
                <div class="form-group">
                    <label class="form-label" for="razorpay_secret">Razorpay Key Secret</label>
                    <input type="password" name="razorpay_secret" id="razorpay_secret" class="form-control" value="<?= e($settings['razorpay_secret'] ?? '') ?>" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label class="form-label" for="session_timeout">Session Inactivity Timeout (Seconds)</label>
                    <input type="number" name="session_timeout" id="session_timeout" class="form-control" value="<?= e($settings['session_timeout'] ?? '7200') ?>">
                </div>
            </div>
        </div>
        <div class="card-footer" style="padding: 18px 24px; background: #f8fafc; border-top: 1px solid var(--border-color); text-align: right;">
            <button type="submit" class="btn btn-primary btn-lg" style="box-shadow: 0 4px 14px rgba(7, 84, 184, 0.3);">
                <i class="fas fa-save"></i> Save All Global &amp; No-Code Settings
            </button>
        </div>
    </div>
</form>

<script>
const pInput = document.getElementById('primary_theme_color');
const pText = document.getElementById('primaryColorText');
if (pInput && pText) {
    pInput.addEventListener('input', () => { pText.value = pInput.value; });
}

const aInput = document.getElementById('accent_theme_color');
const aText = document.getElementById('accentColorText');
if (aInput && aText) {
    aInput.addEventListener('input', () => { aText.value = aInput.value; });
}
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
