<?php
/**
 * Vishal Web Studio - Zero-Code Client Content Management CMS
 * 3D Tactile Design with Frontend Media Upload
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_client();

$client = get_current_client_record();
$website = get_current_client_website();
$pdo = db();

if (!$website) {
    die("<h3>No website provisioned for this account yet.</h3>");
}

$websiteId = $website['id'];

// Handle Section Save POST (Hero, About, Contact)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_section'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $sectionKey = $_POST['section_key'];
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $contentData = $_POST['content'] ?? [];

        // Check if a previous content JSON exists to retain existing image if not replaced
        $chkSec = $pdo->prepare("SELECT id, content_json FROM website_sections WHERE website_id = ? AND section_key = ?");
        $chkSec->execute([$websiteId, $sectionKey]);
        $existing = $chkSec->fetch();
        $existingData = $existing ? json_decode($existing['content_json'] ?? '{}', true) : [];

        if (is_array($existingData)) {
            $contentData = array_merge($existingData, $contentData);
        }

        // Handle File Uploads (Hero image, About image)
        if (!empty($_FILES['section_image']['name'])) {
            $uploadRes = upload_file($_FILES['section_image'], 'media');
            if ($uploadRes['success']) {
                $contentData['image_path'] = $uploadRes['path'];
                // Also record into gallery table for media library
                $insGal = $pdo->prepare("INSERT INTO gallery (website_id, title, image_path, category) VALUES (?, ?, ?, ?)");
                $insGal->execute([$websiteId, ucfirst($sectionKey) . ' Image', $uploadRes['path'], 'sections']);
            }
        }

        $contentJson = json_encode($contentData);

        if ($existing) {
            $upd = $pdo->prepare("UPDATE website_sections SET title = ?, subtitle = ?, content_json = ? WHERE id = ?");
            $upd->execute([$title, $subtitle, $contentJson, $existing['id']]);
        } else {
            $ins = $pdo->prepare("INSERT INTO website_sections (website_id, section_key, title, subtitle, content_json, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1, 1)");
            $ins->execute([$websiteId, $sectionKey, $title, $subtitle, $contentJson]);
        }

        log_activity(current_user_id(), 'section_updated', 'website_sections', $websiteId, "Client updated '{$sectionKey}' section");
        set_flash('success', ucfirst($sectionKey) . " section content and media saved successfully!");
        header('Location: ' . BASE_URL . '/client/content.php#' . $sectionKey . 'Tab');
        exit;
    }
}

// Handle Add / Edit Service POST with Image Upload
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_service'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrfToken)) {
        $svcId = (int)($_POST['service_id'] ?? 0);
        $sTitle = trim($_POST['service_title'] ?? '');
        $sDesc = trim($_POST['service_desc'] ?? '');
        $sPrice = (float)($_POST['service_price'] ?? 0);
        $sIcon = trim($_POST['service_icon'] ?? 'fas fa-star');

        if ($svcId > 0 && empty($_FILES['service_image']['name'])) {
            $oldSvc = $pdo->prepare("SELECT icon FROM services WHERE id = ? AND website_id = ?");
            $oldSvc->execute([$svcId, $websiteId]);
            $oldRow = $oldSvc->fetch();
            if ($oldRow && !empty($oldRow['icon'])) {
                $sIcon = $oldRow['icon'];
            }
        }

        // Handle Service Image Upload
        if (!empty($_FILES['service_image']['name'])) {
            $uploadRes = upload_file($_FILES['service_image'], 'media');
            if ($uploadRes['success']) {
                $sIcon = $uploadRes['path'];
            }
        }

        if (!empty($sTitle)) {
            if ($svcId > 0) {
                $updSvc = $pdo->prepare("UPDATE services SET title = ?, description = ?, price = ?, price_label = ?, icon = ? WHERE id = ? AND website_id = ?");
                $updSvc->execute([$sTitle, $sDesc, $sPrice, $sLabel, $sIcon, $svcId, $websiteId]);
                set_flash('success', "Service / Item '{$sTitle}' updated.");
            } else {
                $insSvc = $pdo->prepare("INSERT INTO services (website_id, title, description, price, price_label, icon, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                $insSvc->execute([$websiteId, $sTitle, $sDesc, $sPrice, $sLabel, $sIcon]);
                set_flash('success', "New service '{$sTitle}' added with photo!");
            }
        }
        header('Location: ' . BASE_URL . '/client/content.php#servicesTab');
        exit;
    }
}

// Handle Delete Service
if (isset($_GET['delete_service'])) {
    $delId = (int)$_GET['delete_service'];
    $pdo->prepare("DELETE FROM services WHERE id = ? AND website_id = ?")->execute([$delId, $websiteId]);
    set_flash('success', 'Service item removed.');
    header('Location: ' . BASE_URL . '/client/content.php#servicesTab');
    exit;
}

// Handle Add FAQ POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_faq'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrfToken)) {
        $q = trim($_POST['faq_question'] ?? '');
        $a = trim($_POST['faq_answer'] ?? '');
        if (!empty($q) && !empty($a)) {
            $pdo->prepare("INSERT INTO faqs (website_id, question, answer) VALUES (?, ?, ?)")->execute([$websiteId, $q, $a]);
            set_flash('success', 'New FAQ added.');
        }
        header('Location: ' . BASE_URL . '/client/content.php#faqsTab');
        exit;
    }
}

// Handle Delete FAQ
if (isset($_GET['delete_faq'])) {
    $delFaq = (int)$_GET['delete_faq'];
    $pdo->prepare("DELETE FROM faqs WHERE id = ? AND website_id = ?")->execute([$delFaq, $websiteId]);
    set_flash('success', 'FAQ deleted.');
    header('Location: ' . BASE_URL . '/client/content.php#faqsTab');
    exit;
}

// Load current sections from DB
$sections = [];
$secStmt = $pdo->prepare("SELECT * FROM website_sections WHERE website_id = ?");
$secStmt->execute([$websiteId]);
foreach ($secStmt->fetchAll() as $sec) {
    $sec['data'] = json_decode($sec['content_json'] ?? '{}', true);
    $sections[$sec['section_key']] = $sec;
}

// Fetch Services, FAQs, Testimonials
$services = $pdo->query("SELECT * FROM services WHERE website_id = {$websiteId} ORDER BY sort_order ASC, id DESC")->fetchAll();
$faqs = $pdo->query("SELECT * FROM faqs WHERE website_id = {$websiteId} ORDER BY sort_order ASC, id DESC")->fetchAll();
$testimonials = $pdo->query("SELECT * FROM testimonials WHERE website_id = {$websiteId} ORDER BY id DESC")->fetchAll();

$pageTitle = 'Zero-Code Website Builder & Content Editor';
$clientNav = 'content';
require_once dirname(__DIR__) . '/includes/client_header.php';
?>

<!-- 3D Tab Navigation Header -->
<div style="display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
    <button type="button" class="content-tab-btn active" data-tab-target="heroSection">
        <i class="fas fa-home"></i> Hero Banner
    </button>
    <button type="button" class="content-tab-btn" data-tab-target="aboutSection">
        <i class="fas fa-info-circle"></i> About Us Story
    </button>
    <button type="button" class="content-tab-btn" data-tab-target="servicesSection">
        <i class="fas fa-utensils"></i> Services / Menu (<?= count($services) ?>)
    </button>
    <button type="button" class="content-tab-btn" data-tab-target="faqsSection">
        <i class="fas fa-question-circle"></i> FAQs (<?= count($faqs) ?>)
    </button>
    <button type="button" class="content-tab-btn" data-tab-target="contactSection">
        <i class="fas fa-phone-alt"></i> Contact &amp; Hours
    </button>
</div>

<!-- TAB 1: HERO SECTION (With 3D Layout & Media Upload) -->
<div id="heroSection" class="content-tab-pane">
    <?php $hero = $sections['hero'] ?? ['title' => "Welcome to {$website['name']}", 'subtitle' => 'Professional services designed for you.', 'data' => []]; ?>
    <form method="POST" action="" enctype="multipart/form-data" class="card">
        <?= csrf_field() ?>
        <input type="hidden" name="save_section" value="1">
        <input type="hidden" name="section_key" value="hero">

        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-heading text-primary"></i> Homepage Hero Banner &amp; Visuals</h3>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Hero Changes</button>
        </div>
        <div class="card-body">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="hero_title">Hero Main Headline *</label>
                    <input type="text" name="title" id="hero_title" class="form-control" value="<?= e($hero['title']) ?>" required>
                    <div class="form-help">e.g. Taste the Authentic Flavors of India</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="hero_badge">Hero Top Badge Text</label>
                    <input type="text" name="content[badge]" id="hero_badge" class="form-control" value="<?= e($hero['data']['badge'] ?? "Welcome to {$website['name']}") ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="hero_subtitle">Hero Subtitle / Value Statement</label>
                <textarea name="subtitle" id="hero_subtitle" class="form-control" rows="2"><?= e($hero['subtitle']) ?></textarea>
            </div>

            <!-- Front Media Upload: Hero Image / Logo -->
            <div class="grid-2" style="background: #f8fafc; padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 20px;">
                <div>
                    <label class="form-label"><i class="fas fa-image text-primary"></i> Upload Hero Visual / Brand Photo</label>
                    <input type="file" name="section_image" accept="image/*" class="form-control">
                    <div class="form-help">Supported: JPG, PNG, WEBP (Max 5MB)</div>
                </div>
                <div>
                    <label class="form-label">Current Visual Preview</label>
                    <?php if (!empty($hero['data']['image_path']) && file_exists(ROOT_PATH . '/' . $hero['data']['image_path'])): ?>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <img src="<?= BASE_URL . '/' . e($hero['data']['image_path']) ?>" style="height: 52px; border-radius: 10px; border: 1px solid #cbd5e1; object-fit: cover;">
                            <span style="font-size: 0.8rem; color: var(--success); font-weight: 700;"><i class="fas fa-check-circle"></i> Custom image active</span>
                        </div>
                    <?php else: ?>
                        <div style="padding: 12px; background: #ffffff; border-radius: 10px; border: 1px dashed #cbd5e1; font-size: 0.82rem; color: #94a3b8;">
                            Default template badge active. Upload an image to replace.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="hero_btn1">Primary Button Text</label>
                    <input type="text" name="content[primary_btn_text]" id="hero_btn1" class="form-control" value="<?= e($hero['data']['primary_btn_text'] ?? 'Explore Services') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="hero_btn2">Secondary Button Text</label>
                    <input type="text" name="content[secondary_btn_text]" id="hero_btn2" class="form-control" value="<?= e($hero['data']['secondary_btn_text'] ?? 'Book Appointment / Table') ?>">
                </div>
            </div>

            <div class="grid-3" style="background: #f8fafc; padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-top: 10px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Highlight Stat 1</label>
                    <input type="text" name="content[stat1_num]" class="form-control" value="<?= e($hero['data']['stat1_num'] ?? '30+') ?>" placeholder="e.g. 30+">
                    <input type="text" name="content[stat1_label]" class="form-control" style="margin-top: 6px;" value="<?= e($hero['data']['stat1_label'] ?? 'Years Heritage') ?>" placeholder="Label">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Highlight Stat 2</label>
                    <input type="text" name="content[stat2_num]" class="form-control" value="<?= e($hero['data']['stat2_num'] ?? '150+') ?>" placeholder="e.g. 150+">
                    <input type="text" name="content[stat2_label]" class="form-control" style="margin-top: 6px;" value="<?= e($hero['data']['stat2_label'] ?? 'Dishes / Services') ?>" placeholder="Label">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Highlight Stat 3</label>
                    <input type="text" name="content[stat3_num]" class="form-control" value="<?= e($hero['data']['stat3_num'] ?? '50k+') ?>" placeholder="e.g. 50k+">
                    <input type="text" name="content[stat3_label]" class="form-control" style="margin-top: 6px;" value="<?= e($hero['data']['stat3_label'] ?? 'Happy Clients') ?>" placeholder="Label">
                </div>
            </div>
        </div>
        <div class="card-footer" style="text-align: right;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Hero Changes</button>
        </div>
    </form>
</div>

<!-- TAB 2: ABOUT SECTION (With Media Upload) -->
<div id="aboutSection" class="content-tab-pane" style="display: none;">
    <?php $about = $sections['about'] ?? ['title' => "About {$website['name']}", 'subtitle' => 'Our commitment to excellence.', 'data' => []]; ?>
    <form method="POST" action="" enctype="multipart/form-data" class="card">
        <?= csrf_field() ?>
        <input type="hidden" name="save_section" value="1">
        <input type="hidden" name="section_key" value="about">

        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-info-circle text-primary"></i> About Us Story Section</h3>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save About Changes</button>
        </div>
        <div class="card-body">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="about_title">Section Title *</label>
                    <input type="text" name="title" id="about_title" class="form-control" value="<?= e($about['title']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="about_highlight">Highlight Tagline</label>
                    <input type="text" name="content[highlight_text]" id="about_highlight" class="form-control" value="<?= e($about['data']['highlight_text'] ?? '100% Pure & Quality Guaranteed') ?>">
                </div>
            </div>

            <!-- Front Media Upload: About Story Photo -->
            <div class="grid-2" style="background: #f8fafc; padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 20px;">
                <div>
                    <label class="form-label"><i class="fas fa-camera text-primary"></i> Upload Story / Founder Photo</label>
                    <input type="file" name="section_image" accept="image/*" class="form-control">
                    <div class="form-help">Upload photo of your store, team, or craft</div>
                </div>
                <div>
                    <label class="form-label">Current Story Photo</label>
                    <?php if (!empty($about['data']['image_path']) && file_exists(ROOT_PATH . '/' . $about['data']['image_path'])): ?>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <img src="<?= BASE_URL . '/' . e($about['data']['image_path']) ?>" style="height: 52px; border-radius: 10px; border: 1px solid #cbd5e1; object-fit: cover;">
                            <span style="font-size: 0.8rem; color: var(--success); font-weight: 700;"><i class="fas fa-check-circle"></i> Story photo active</span>
                        </div>
                    <?php else: ?>
                        <div style="padding: 12px; background: #ffffff; border-radius: 10px; border: 1px dashed #cbd5e1; font-size: 0.82rem; color: #94a3b8;">
                            Default quote card active. Upload an image to display.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="about_subtitle">Subtitle / Mission Statement</label>
                <input type="text" name="subtitle" id="about_subtitle" class="form-control" value="<?= e($about['subtitle']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="about_p1">Story Paragraph 1</label>
                <textarea name="content[paragraph_1]" id="about_p1" class="form-control" rows="3"><?= e($about['data']['paragraph_1'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="about_p2">Story Paragraph 2</label>
                <textarea name="content[paragraph_2]" id="about_p2" class="form-control" rows="3"><?= e($about['data']['paragraph_2'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="card-footer" style="text-align: right;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save About Changes</button>
        </div>
    </form>
</div>

<!-- TAB 3: SERVICES & MENU ITEMS -->
<div id="servicesSection" class="content-tab-pane" style="display: none;">
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-utensils text-primary"></i> Services &amp; Menu Catalog</h3>
            <button type="button" class="btn btn-primary btn-sm" onclick="openServiceModal()">
                <i class="fas fa-plus"></i> Add New Service / Item
            </button>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Photo / Icon</th>
                        <th>Service / Dish Title</th>
                        <th>Description</th>
                        <th>Price (INR)</th>
                        <th>Price Label</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr><td colspan="6" class="text-center text-muted" style="padding: 30px;">No services added yet. Click Add New Service.</td></tr>
                    <?php else: ?>
                        <?php foreach ($services as $svc): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($svc['icon']) && str_starts_with($svc['icon'], 'uploads/')): ?>
                                        <img src="<?= BASE_URL . '/' . e($svc['icon']) ?>" style="width: 42px; height: 42px; border-radius: 10px; object-fit: cover; border: 1px solid #cbd5e1;">
                                    <?php else: ?>
                                        <div style="width: 42px; height: 42px; border-radius: 10px; background: var(--primary-light); color: var(--primary); display: grid; place-items: center; font-size: 1.1rem;">
                                            <i class="<?= e($svc['icon'] ?: 'fas fa-star') ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color: var(--dark); font-size: 0.95rem;"><?= e($svc['title']) ?></strong>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--text-muted);">
                                    <?= e($svc['description']) ?>
                                </td>
                                <td>
                                    <strong class="text-primary"><?= format_currency($svc['price']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?= e($svc['price_label'] ?: 'Standard') ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick='editService(<?= json_encode($svc) ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="<?= BASE_URL ?>/client/content.php?delete_service=<?= $svc['id'] ?>" class="btn btn-secondary btn-sm" data-confirm="Remove this service?">
                                            <i class="fas fa-trash text-danger"></i>
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
</div>

<!-- TAB 4: FAQS -->
<div id="faqsSection" class="content-tab-pane" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-question-circle text-primary"></i> Frequently Asked Questions</h3>
            <button type="button" class="btn btn-primary btn-sm" onclick="openFaqModal()">
                <i class="fas fa-plus"></i> Add New FAQ
            </button>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php if (empty($faqs)): ?>
                    <p class="text-muted text-center" style="padding: 20px;">No FAQs added yet.</p>
                <?php else: ?>
                    <?php foreach ($faqs as $fq): ?>
                        <div style="background: #f8fafc; border-radius: var(--radius-md); padding: 16px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
                            <div>
                                <strong style="font-size: 0.95rem; color: var(--dark); display: block; margin-bottom: 4px;">
                                    Q: <?= e($fq['question']) ?>
                                </strong>
                                <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">
                                    <?= e($fq['answer']) ?>
                                </p>
                            </div>
                            <a href="<?= BASE_URL ?>/client/content.php?delete_faq=<?= $fq['id'] ?>" class="btn btn-secondary btn-sm" data-confirm="Delete FAQ?">
                                <i class="fas fa-trash text-danger"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- TAB 5: CONTACT SECTION -->
<div id="contactSection" class="content-tab-pane" style="display: none;">
    <?php $contact = $sections['contact'] ?? ['title' => "Connect with {$website['name']}", 'subtitle' => 'We are here to assist you.', 'data' => []]; ?>
    <form method="POST" action="" class="card">
        <?= csrf_field() ?>
        <input type="hidden" name="save_section" value="1">
        <input type="hidden" name="section_key" value="contact">

        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-phone-alt text-primary"></i> Contact Details &amp; Business Hours</h3>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Contact Info</button>
        </div>
        <div class="card-body">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="cnt_phone">Customer Care Phone Number</label>
                    <input type="text" name="content[phone]" id="cnt_phone" class="form-control" value="<?= e($contact['data']['phone'] ?? $client['phone']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="cnt_whatsapp">Direct WhatsApp Number (Digits only)</label>
                    <input type="text" name="content[whatsapp]" id="cnt_whatsapp" class="form-control" value="<?= e($contact['data']['whatsapp'] ?? $client['whatsapp']) ?>">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="cnt_email">Customer Inquiry Email</label>
                    <input type="email" name="content[email]" id="cnt_email" class="form-control" value="<?= e($contact['data']['email'] ?? $client['email']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="cnt_hours">Business Working Hours</label>
                    <input type="text" name="content[hours]" id="cnt_hours" class="form-control" value="<?= e($contact['data']['hours'] ?? 'Mon - Sun: 09:00 AM - 10:00 PM') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="cnt_address">Shop / Office Physical Address</label>
                <textarea name="content[address]" id="cnt_address" class="form-control" rows="2"><?= e($contact['data']['address'] ?? $client['address']) ?></textarea>
            </div>
        </div>
        <div class="card-footer" style="text-align: right;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Contact Info</button>
        </div>
    </form>
</div>

<!-- Service Modal (With Media Upload) -->
<div class="modal-backdrop" id="serviceModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="serviceModalTitle">Add New Service</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="save_service" value="1">
            <input type="hidden" name="service_id" id="modal_svc_id" value="0">

            <div class="modal-body" style="padding: 24px;">
                <div class="form-group">
                    <label class="form-label" for="svc_title">Service / Dish Title *</label>
                    <input type="text" name="service_title" id="svc_title" class="form-control" required placeholder="e.g. Special Shahi Paneer">
                </div>

                <div class="form-group">
                    <label class="form-label" for="svc_desc">Description</label>
                    <textarea name="service_desc" id="svc_desc" class="form-control" rows="2" placeholder="Brief description of this menu or service item..."></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="svc_price">Price (INR) *</label>
                        <input type="number" step="0.01" name="service_price" id="svc_price" class="form-control" required value="299.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="svc_label">Price Label</label>
                        <input type="text" name="service_label" id="svc_label" class="form-control" value="Per Portion">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fas fa-camera text-primary"></i> Upload Item Photo (Optional)</label>
                    <input type="file" name="service_image" accept="image/*" class="form-control">
                </div>
            </div>

            <div class="modal-footer" style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Service Item</button>
            </div>
        </form>
    </div>
</div>

<!-- FAQ Modal -->
<div class="modal-backdrop" id="faqModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Add Frequently Asked Question</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="save_faq" value="1">

            <div class="modal-body" style="padding: 24px;">
                <div class="form-group">
                    <label class="form-label" for="faq_q">Question *</label>
                    <input type="text" name="faq_question" id="faq_q" class="form-control" required placeholder="e.g. Do you accept party bookings?">
                </div>
                <div class="form-group">
                    <label class="form-label" for="faq_a">Answer *</label>
                    <textarea name="faq_answer" id="faq_a" class="form-control" rows="3" required placeholder="Yes, we cater for all types of parties and gatherings..."></textarea>
                </div>
            </div>

            <div class="modal-footer" style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save FAQ</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Tab Switcher
    const tabButtons = document.querySelectorAll('.content-tab-btn');
    const tabPanes = document.querySelectorAll('.content-tab-pane');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tabButtons.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.style.display = 'none');

            btn.classList.add('active');
            const targetId = btn.getAttribute('data-tab-target');
            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.style.display = 'block';
            }
        });
    });

    // Hash tab support
    if (window.location.hash) {
        const hash = window.location.hash.replace('#', '').replace('Tab', '');
        const targetBtn = document.querySelector(`[data-tab-target="${hash}Section"]`);
        if (targetBtn) {
            targetBtn.click();
        }
    }
});

function openServiceModal() {
    document.getElementById('serviceModalTitle').innerText = 'Add New Service / Item';
    document.getElementById('modal_svc_id').value = '0';
    document.getElementById('svc_title').value = '';
    document.getElementById('svc_desc').value = '';
    document.getElementById('svc_price').value = '299.00';
    document.getElementById('svc_label').value = 'Per Item';
    document.getElementById('serviceModal').classList.add('show');
}

function editService(svc) {
    document.getElementById('serviceModalTitle').innerText = 'Edit Service / Item';
    document.getElementById('modal_svc_id').value = svc.id;
    document.getElementById('svc_title').value = svc.title;
    document.getElementById('svc_desc').value = svc.description;
    document.getElementById('svc_price').value = svc.price;
    document.getElementById('svc_label').value = svc.price_label;
    document.getElementById('serviceModal').classList.add('show');
}

function openFaqModal() {
    document.getElementById('faq_q').value = '';
    document.getElementById('faq_a').value = '';
    document.getElementById('faqModal').classList.add('show');
}
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
