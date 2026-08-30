<?php
/**
 * Vishal Web Studio - Client Website SEO, Theme Colors & No-Code Customizer
 * Custom Footers, 3D Styles, Typography & Brand Controls
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_client();

$client = get_current_client_record();
$website = get_current_client_website();
$pdo = db();

if (!$website) die("<h3>No website found.</h3>");

$websiteId = $website['id'];
$userId = current_user_id();

// Handle Save SEO, Theme & Custom Footer POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_seo_theme'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrfToken)) {
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDesc = trim($_POST['meta_description'] ?? '');
        $themeColor = trim($_POST['theme_color'] ?? '#2563eb');

        $pdo->prepare("UPDATE websites SET meta_title = ?, meta_description = ?, theme_color = ? WHERE id = ?")->execute([$metaTitle, $metaDesc, $themeColor, $websiteId]);

        // Save Custom No-Code Settings
        $customKeys = [
            'secondary_color', 'font_family', 'enable_3d_glass',
            'footer_brand_name', 'footer_tagline', 'footer_copyright', 'footer_about'
        ];

        foreach ($customKeys as $k) {
            $val = trim($_POST[$k] ?? '');
            set_website_setting($websiteId, $k, $val);
        }

        log_activity($userId, 'settings_updated', 'websites', $websiteId, "Client updated website theme, custom footer & SEO settings.");
        set_flash('success', 'Website styling, custom footer, and SEO settings saved successfully!');
        header('Location: ' . BASE_URL . '/client/settings.php');
        exit;
    }
}

// Handle Password Change POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrfToken)) {
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (strlen($newPass) < 6) {
            set_flash('danger', 'Password must be at least 6 characters long.');
        } elseif ($newPass !== $confirmPass) {
            set_flash('danger', 'New passwords do not match.');
        } else {
            $pHash = password_hash($newPass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$pHash, $userId]);
            log_activity($userId, 'password_changed', 'users', $userId, "Client changed account password.");
            set_flash('success', 'Your password has been changed successfully.');
            header('Location: ' . BASE_URL . '/client/settings.php');
            exit;
        }
    }
}

$pageTitle = 'No-Code Design & Footer Settings';
$clientNav = 'settings';
require_once dirname(__DIR__) . '/includes/client_header.php';
?>

<form method="POST" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="save_seo_theme" value="1">

    <div class="grid-2" style="align-items: start; gap: 24px;">
        
        <!-- 1. 3D Visual Styling & Colors -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-palette text-primary" style="margin-right: 8px;"></i> 3D Visual Styling &amp; Colors</h3>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="theme_color">Primary 3D Accent Color</label>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <input type="color" name="theme_color" id="theme_color" value="<?= e($website['theme_color'] ?? '#2563eb') ?>" style="width: 45px; height: 42px; padding: 2px; border-radius: 8px; border: 1px solid var(--border-color); cursor: pointer;">
                            <input type="text" id="colorText" class="form-control" value="<?= e($website['theme_color'] ?? '#2563eb') ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="secondary_color">Secondary 3D Shade</label>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <input type="color" name="secondary_color" id="secondary_color" value="<?= e(get_website_setting($websiteId, 'secondary_color', '#1e40af')) ?>" style="width: 45px; height: 42px; padding: 2px; border-radius: 8px; border: 1px solid var(--border-color); cursor: pointer;">
                            <input type="text" id="secColorText" class="form-control" value="<?= e(get_website_setting($websiteId, 'secondary_color', '#1e40af')) ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="font_family">Website Font Style</label>
                        <select name="font_family" id="font_family" class="form-select">
                            <?php $ff = get_website_setting($websiteId, 'font_family', 'Inter'); ?>
                            <option value="Inter" <?= $ff === 'Inter' ? 'selected' : '' ?>>Inter (Clean &amp; Modern)</option>
                            <option value="Poppins" <?= $ff === 'Poppins' ? 'selected' : '' ?>>Poppins (Bold &amp; Rounded)</option>
                            <option value="Playfair Display" <?= $ff === 'Playfair Display' ? 'selected' : '' ?>>Playfair Display (Luxury &amp; Royal)</option>
                            <option value="Outfit" <?= $ff === 'Outfit' ? 'selected' : '' ?>>Outfit (Contemporary 3D)</option>
                            <option value="Montserrat" <?= $ff === 'Montserrat' ? 'selected' : '' ?>>Montserrat (Geometric Tech)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="enable_3d_glass">3D Glass Cards &amp; Glossy Glow</label>
                        <select name="enable_3d_glass" id="enable_3d_glass" class="form-select">
                            <?php $eg = get_website_setting($websiteId, 'enable_3d_glass', '1'); ?>
                            <option value="1" <?= $eg === '1' ? 'selected' : '' ?>>Enabled (3D Glassmorphism &amp; Shadows)</option>
                            <option value="0" <?= $eg === '0' ? 'selected' : '' ?>>Flat Clean Look</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Client Website Footer Customizer (No-Code) -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shoe-prints text-success" style="margin-right: 8px;"></i> Live Website Footer Customizer</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="footer_brand_name">Footer Business Name</label>
                    <input type="text" name="footer_brand_name" id="footer_brand_name" class="form-control" value="<?= e(get_website_setting($websiteId, 'footer_brand_name', $website['name'])) ?>" placeholder="e.g. Sharma Sweets & Restaurant">
                    <div class="form-help">Custom title displayed in the footer branding section.</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="footer_tagline">Footer Tagline / Slogan</label>
                    <input type="text" name="footer_tagline" id="footer_tagline" class="form-control" value="<?= e(get_website_setting($websiteId, 'footer_tagline', 'Serving authentic delicacies with premium ingredients since 1998.')) ?>" placeholder="e.g. Pure Vegetarian Sweets, Snacks & Dining">
                </div>

                <div class="form-group">
                    <label class="form-label" for="footer_copyright">Custom Copyright Notice</label>
                    <input type="text" name="footer_copyright" id="footer_copyright" class="form-control" value="<?= e(get_website_setting($websiteId, 'footer_copyright', '© ' . date('Y') . ' ' . $website['name'] . '. All rights reserved.')) ?>" placeholder="e.g. © 2026 Sharma Sweets. All rights reserved.">
                </div>

                <div class="form-group">
                    <label class="form-label" for="footer_about">Footer Short Summary</label>
                    <textarea name="footer_about" id="footer_about" class="form-control" rows="2" placeholder="Brief 1-2 sentence description in footer..."><?= e(get_website_setting($websiteId, 'footer_about', 'Experience premium hospitality, takeaway orders, table bookings and home delivery with fast service.')) ?></textarea>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. Google SEO & Browser Tab Title -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-search text-info" style="margin-right: 8px;"></i> Google Search &amp; Browser Tab Title</h3>
        </div>
        <div class="card-body">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="meta_title">Google Search Headline (Meta Title)</label>
                    <input type="text" name="meta_title" id="meta_title" class="form-control" value="<?= e($website['meta_title']) ?>" placeholder="e.g. Best Restaurant & Sweets in Noida | Sharma Sweets">
                </div>

                <div class="form-group">
                    <label class="form-label" for="meta_desc">Google Search Summary (Meta Description)</label>
                    <textarea name="meta_description" id="meta_desc" class="form-control" rows="2" placeholder="Brief summary for Google search results..."><?= e($website['meta_description']) ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer" style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid var(--border-color); text-align: right;">
            <button type="submit" class="btn btn-primary btn-lg" style="box-shadow: 0 4px 14px rgba(7, 84, 184, 0.3);">
                <i class="fas fa-save"></i> Save Website Styling &amp; Custom Footer
            </button>
        </div>
    </div>
</form>

<!-- 4. Security / Change Password Card -->
<form method="POST" action="" class="card" style="margin-top: 24px;">
    <?= csrf_field() ?>
    <input type="hidden" name="change_password" value="1">

    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-key text-warning" style="margin-right: 8px;"></i> Change Account Password</h3>
    </div>
    <div class="card-body">
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="new_pass">New Password *</label>
                <input type="password" name="new_password" id="new_pass" class="form-control" required placeholder="Minimum 6 characters">
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_pass">Confirm New Password *</label>
                <input type="password" name="confirm_password" id="confirm_pass" class="form-control" required placeholder="Repeat new password">
            </div>
        </div>
    </div>
    <div class="card-footer" style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid var(--border-color); text-align: right;">
        <button type="submit" class="btn btn-secondary"><i class="fas fa-lock"></i> Update Password</button>
    </div>
</form>

<script>
const cInput = document.getElementById('theme_color');
const cText = document.getElementById('colorText');
if (cInput && cText) {
    cInput.addEventListener('input', () => { cText.value = cInput.value; });
}

const sInput = document.getElementById('secondary_color');
const sText = document.getElementById('secColorText');
if (sInput && sText) {
    sInput.addEventListener('input', () => { sText.value = sInput.value; });
}
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
