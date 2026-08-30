<?php
/**
 * Vishal Web Studio - Client Business Info & Maps Settings
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_client();

$client = get_current_client_record();
$website = get_current_client_website();
$pdo = db();

if (!$website) die("<h3>No website found.</h3>");

$clientId = $client['id'];
$websiteId = $website['id'];

// Handle Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_biz_info'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $bizName = trim($_POST['business_name'] ?? '');
        $ownerName = trim($_POST['owner_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? $phone);
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');

        if (!empty($bizName)) {
            $pdo->prepare("UPDATE clients SET business_name = ?, owner_name = ?, phone = ?, whatsapp = ?, email = ?, address = ?, city = ? WHERE id = ?")->execute([$bizName, $ownerName, $phone, $whatsapp, $email, $address, $city, $clientId]);
            $pdo->prepare("UPDATE websites SET name = ?, tagline = ? WHERE id = ?")->execute([$bizName, $tagline, $websiteId]);

            // Sync to contact section as well
            $chkSec = $pdo->query("SELECT id, content_json FROM website_sections WHERE website_id = {$websiteId} AND section_key = 'contact'")->fetch();
            if ($chkSec) {
                $cData = json_decode($chkSec['content_json'] ?? '{}', true);
                $cData['phone'] = $phone;
                $cData['whatsapp'] = $whatsapp;
                $cData['email'] = $email;
                $cData['address'] = $address;
                $pdo->prepare("UPDATE website_sections SET content_json = ? WHERE id = ?")->execute([json_encode($cData), $chkSec['id']]);
            }

            log_activity(current_user_id(), 'business_info_updated', 'clients', $clientId, "Updated business contact details for '{$bizName}'");
            set_flash('success', 'Business contact details and maps information saved!');
            header('Location: ' . BASE_URL . '/client/business-info.php');
            exit;
        }
    }
}

$pageTitle = 'Business Contact & Maps Details';
$clientNav = 'business-info';
require_once dirname(__DIR__) . '/includes/client_header.php';
?>

<form method="POST" action="" class="card" style="box-shadow: var(--shadow-sm); border-radius: var(--radius-lg);">
    <?= csrf_field() ?>
    <input type="hidden" name="save_biz_info" value="1">

    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-store text-primary" style="margin-right: 8px;"></i> Official Business Profile</h3>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Changes</button>
    </div>

    <div class="card-body">
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="biz_name">Business / Store Name *</label>
                <input type="text" name="business_name" id="biz_name" class="form-control" value="<?= e($client['business_name']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="owner_name">Owner / Manager Name *</label>
                <input type="text" name="owner_name" id="owner_name" class="form-control" value="<?= e($client['owner_name']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="biz_tagline">Business Tagline / Motto</label>
            <input type="text" name="tagline" id="biz_tagline" class="form-control" value="<?= e($website['tagline'] ?? '') ?>" placeholder="e.g. Authentic North Indian Delicacies Since 1994">
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label class="form-label" for="biz_phone">Customer Phone Number *</label>
                <input type="tel" name="phone" id="biz_phone" class="form-control" value="<?= e($client['phone']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="biz_whatsapp">WhatsApp Order Number</label>
                <input type="tel" name="whatsapp" id="biz_whatsapp" class="form-control" value="<?= e($client['whatsapp'] ?? $client['phone']) ?>" required>
                <div class="form-help">Powers the floating 1-click WhatsApp button on your website.</div>
            </div>
            <div class="form-group">
                <label class="form-label" for="biz_email">Customer Support Email *</label>
                <input type="email" name="email" id="biz_email" class="form-control" value="<?= e($client['email']) ?>" required>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="biz_address">Full Store / Office Physical Address</label>
                <input type="text" name="address" id="biz_address" class="form-control" value="<?= e($client['address'] ?? '') ?>" placeholder="Shop 14, Main Market, Sector 18">
            </div>
            <div class="form-group">
                <label class="form-label" for="biz_city">City & State</label>
                <input type="text" name="city" id="biz_city" class="form-control" value="<?= e($client['city'] ?? 'Noida, UP') ?>" placeholder="e.g. Noida, UP">
            </div>
        </div>
    </div>

    <div class="card-footer" style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid var(--border-color); text-align: right;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Business Information</button>
    </div>
</form>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
