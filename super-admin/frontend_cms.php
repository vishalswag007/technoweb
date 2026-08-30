<?php
/**
 * Vishal Web Studio - Super Admin Frontend Website Visual CMS & Slider Manager
 * Complete sliding images upload system, content redesign, and media controls for the owner.
 * With clear image dimensions, aspect ratio, file size count, and placement location guidelines.
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Handle Add New Slide POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slide'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrfToken)) {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $eyebrow = trim($_POST['eyebrow'] ?? 'HYPERINFONET IT SOLUTIONS');
        $btn1Text = trim($_POST['btn1_text'] ?? 'Request Free Demo');
        $btn1Link = trim($_POST['btn1_link'] ?? '#contact');
        $btn2Text = trim($_POST['btn2_text'] ?? 'Explore 25+ Modules');
        $btn2Link = trim($_POST['btn2_link'] ?? '#solutions');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        $imagePath = '';
        if (!empty($_FILES['slide_image']['name'])) {
            $upRes = upload_file($_FILES['slide_image'], 'sliders', ['jpg', 'jpeg', 'png', 'webp', 'svg', 'avif']);
            if ($upRes['success']) {
                $imagePath = $upRes['path'];
            }
        }

        $ins = $pdo->prepare("INSERT INTO frontend_slides (title, subtitle, eyebrow, image_path, btn1_text, btn1_link, btn2_text, btn2_link, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$title, $subtitle, $eyebrow, $imagePath, $btn1Text, $btn1Link, $btn2Text, $btn2Link, $sortOrder, $status]);

        log_activity(current_user_id(), 'slide_created', 'frontend_slides', (int)$pdo->lastInsertId(), "Added new homepage slider slide: '{$title}'");
        set_flash('success', "New sliding banner '{$title}' added successfully!");
        header('Location: ' . BASE_URL . '/super-admin/frontend_cms.php');
        exit;
    }
}

// Handle Edit Slide POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_slide'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrfToken)) {
        $slideId = (int)$_POST['slide_id'];
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $eyebrow = trim($_POST['eyebrow'] ?? 'HYPERINFONET IT SOLUTIONS');
        $btn1Text = trim($_POST['btn1_text'] ?? 'Request Free Demo');
        $btn1Link = trim($_POST['btn1_link'] ?? '#contact');
        $btn2Text = trim($_POST['btn2_text'] ?? 'Explore 25+ Modules');
        $btn2Link = trim($_POST['btn2_link'] ?? '#solutions');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        $imagePath = $_POST['current_image'] ?? '';
        if (!empty($_FILES['slide_image']['name'])) {
            $upRes = upload_file($_FILES['slide_image'], 'sliders', ['jpg', 'jpeg', 'png', 'webp', 'svg', 'avif']);
            if ($upRes['success']) {
                $imagePath = $upRes['path'];
            }
        }

        $upd = $pdo->prepare("UPDATE frontend_slides SET title = ?, subtitle = ?, eyebrow = ?, image_path = ?, btn1_text = ?, btn1_link = ?, btn2_text = ?, btn2_link = ?, sort_order = ?, status = ? WHERE id = ?");
        $upd->execute([$title, $subtitle, $eyebrow, $imagePath, $btn1Text, $btn1Link, $btn2Text, $btn2Link, $sortOrder, $status, $slideId]);

        log_activity(current_user_id(), 'slide_updated', 'frontend_slides', $slideId, "Updated homepage slider slide: '{$title}'");
        set_flash('success', "Sliding banner '{$title}' updated successfully!");
        header('Location: ' . BASE_URL . '/super-admin/frontend_cms.php');
        exit;
    }
}

// Handle Delete Slide
if (isset($_GET['delete_slide'])) {
    $delId = (int)$_GET['delete_slide'];
    $pdo->prepare("DELETE FROM frontend_slides WHERE id = ?")->execute([$delId]);
    log_activity(current_user_id(), 'slide_deleted', 'frontend_slides', $delId, "Deleted homepage slider slide #{$delId}");
    set_flash('success', 'Slide banner deleted from homepage slider.');
    header('Location: ' . BASE_URL . '/super-admin/frontend_cms.php');
    exit;
}

// Handle Toggle Slide Status
if (isset($_GET['toggle_slide'])) {
    $togId = (int)$_GET['toggle_slide'];
    $st = $pdo->query("SELECT status FROM frontend_slides WHERE id = {$togId}")->fetchColumn();
    $newStatus = ($st === 'active') ? 'inactive' : 'active';
    $pdo->prepare("UPDATE frontend_slides SET status = ? WHERE id = ?")->execute([$newStatus, $togId]);
    set_flash('success', "Slide status changed to {$newStatus}.");
    header('Location: ' . BASE_URL . '/super-admin/frontend_cms.php');
    exit;
}

// Handle Save General CMS Sections POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_frontend_cms'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $allowedKeys = [
            'intro_eyebrow', 'intro_heading', 'intro_description',
            'stat1_number', 'stat1_label', 'stat1_icon',
            'stat2_number', 'stat2_label', 'stat2_icon',
            'stat3_number', 'stat3_label', 'stat3_icon',
            'stat4_number', 'stat4_label', 'stat4_icon',
            'sector_eyebrow', 'sector_heading', 'sector_description',
            'sec1_title', 'sec1_desc', 'sec1_icon',
            'sec2_title', 'sec2_desc', 'sec2_icon',
            'sec3_title', 'sec3_desc', 'sec3_icon',
            'sec4_title', 'sec4_desc', 'sec4_icon',
            'sec5_title', 'sec5_desc', 'sec5_icon',
            'sec6_title', 'sec6_desc', 'sec6_icon',
            'sec7_title', 'sec7_desc', 'sec7_icon',
            'sec8_title', 'sec8_desc', 'sec8_icon',
            'cta_eyebrow', 'cta_heading', 'cta_btn_call', 'cta_btn_wa'
        ];

        $setStmt = $pdo->prepare("INSERT OR REPLACE INTO global_settings (setting_key, setting_value) VALUES (:key, :val)");
        if (Database::getInstance()->isMySQL()) {
            $setStmt = $pdo->prepare("INSERT INTO global_settings (setting_key, setting_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        }

        foreach ($allowedKeys as $k) {
            if (isset($_POST[$k])) {
                $setStmt->execute([':key' => $k, ':val' => trim($_POST[$k])]);
            }
        }

        log_activity(current_user_id(), 'cms_updated', 'global_settings', null, 'Updated public frontend website content & sections.');
        set_flash('success', 'Public frontend website sections, stats, and business sectors updated successfully!');
        header('Location: ' . BASE_URL . '/super-admin/frontend_cms.php');
        exit;
    }
}

// Fetch all slides
$slides = $pdo->query("SELECT * FROM frontend_slides ORDER BY sort_order ASC, id ASC")->fetchAll();

// Fetch all global settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM global_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

$pageTitle = 'Frontend Website Visual CMS & Slider Manager';
$adminNav = 'frontend_cms';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <div>
        <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
            Manage the interactive 3D hero slider, upload slide photos, and customize all homepage headlines &amp; sectors.
        </p>
    </div>

    <div style="display: flex; gap: 10px;">
        <a href="<?= BASE_URL ?>/super-admin/frontend_media.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-photo-video"></i> Photo Media Library
        </a>
        <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-primary btn-sm" style="box-shadow: 0 4px 12px rgba(7,84,184,0.25);">
            <i class="fas fa-external-link-alt"></i> View Live Website
        </a>
    </div>
</div>

<!-- =========================================================================
     IMAGE SIZES, ASPECT RATIOS & DISPLAY LOCATIONS SPECIFICATION GUIDE
     ========================================================================= -->
<div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%); border: 1.5px solid #bfdbfe;">
    <div class="card-body" style="padding: 18px 22px;">
        <h4 style="margin: 0 0 12px; font-size: 1.05rem; color: #1e3a8a; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-ruler-combined text-primary"></i> 📐 Image Upload Sizes, Aspect Ratios &amp; Placement Guide
        </h4>
        <div class="grid-4" style="gap: 14px;">
            <div style="background: #ffffff; padding: 12px 14px; border-radius: 10px; border: 1px solid #dbeafe; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="font-size: 0.78rem; font-weight: 800; color: #0284c7; text-transform: uppercase; margin-bottom: 4px;">
                    <i class="fas fa-images"></i> 1. Hero Slider Banner
                </div>
                <div style="font-size: 1.05rem; font-weight: 900; color: #0f172a;">1920 &times; 800 px</div>
                <div style="font-size: 0.8rem; color: #475569; margin: 3px 0;"><strong>Ratio:</strong> 2.4:1 (or 16:9)</div>
                <div style="font-size: 0.75rem; color: #0369a1; background: #e0f2fe; padding: 3px 6px; border-radius: 6px; display: inline-block;">
                    📍 Place: Homepage Hero Top Carousel
                </div>
            </div>

            <div style="background: #ffffff; padding: 12px 14px; border-radius: 10px; border: 1px solid #dbeafe; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="font-size: 0.78rem; font-weight: 800; color: #16a34a; text-transform: uppercase; margin-bottom: 4px;">
                    <i class="fas fa-crown"></i> 2. Header &amp; Footer Logo
                </div>
                <div style="font-size: 1.05rem; font-weight: 900; color: #0f172a;">512 &times; 512 px / 400 &times; 100 px</div>
                <div style="font-size: 0.8rem; color: #475569; margin: 3px 0;"><strong>Ratio:</strong> 1:1 (Square) or 4:1 (Bar)</div>
                <div style="font-size: 0.75rem; color: #15803d; background: #dcfce7; padding: 3px 6px; border-radius: 6px; display: inline-block;">
                    📍 Place: Top Navbar, Footer &amp; Sidebar
                </div>
            </div>

            <div style="background: #ffffff; padding: 12px 14px; border-radius: 10px; border: 1px solid #dbeafe; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="font-size: 0.78rem; font-weight: 800; color: #d97706; text-transform: uppercase; margin-bottom: 4px;">
                    <i class="fas fa-th-large"></i> 3. Sector &amp; Gallery Cards
                </div>
                <div style="font-size: 1.05rem; font-weight: 900; color: #0f172a;">800 &times; 600 px</div>
                <div style="font-size: 0.8rem; color: #475569; margin: 3px 0;"><strong>Ratio:</strong> 4:3 (Standard Card)</div>
                <div style="font-size: 0.75rem; color: #b45309; background: #fef3c7; padding: 3px 6px; border-radius: 6px; display: inline-block;">
                    📍 Place: Sector Showcase &amp; Portfolio Grid
                </div>
            </div>

            <div style="background: #ffffff; padding: 12px 14px; border-radius: 10px; border: 1px solid #dbeafe; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="font-size: 0.78rem; font-weight: 800; color: #7c3aed; text-transform: uppercase; margin-bottom: 4px;">
                    <i class="fas fa-share-alt"></i> 4. Social Share &amp; Favicon
                </div>
                <div style="font-size: 1.05rem; font-weight: 900; color: #0f172a;">1200 &times; 630 px / 64 &times; 64 px</div>
                <div style="font-size: 0.8rem; color: #475569; margin: 3px 0;"><strong>Ratio:</strong> 1.91:1 (OG) / 1:1 (Favicon)</div>
                <div style="font-size: 0.75rem; color: #6d28d9; background: #ede9fe; padding: 3px 6px; border-radius: 6px; display: inline-block;">
                    📍 Place: WhatsApp Link &amp; Browser Tab
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     1. HERO SLIDER CAROUSEL MANAGER (UPLOAD SLIDING IMAGES)
     ========================================================================= -->
<div class="card" style="margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.06);">
    <div class="card-header" style="background: linear-gradient(135deg, #0754b8 0%, #032b69 100%); color: #ffffff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 class="card-title" style="color: #ffffff;"><i class="fas fa-images" style="margin-right: 8px;"></i> 1. Sliding Images &amp; Hero Carousel Manager</h3>
            <span style="font-size: 0.82rem; opacity: 0.88;">Total Active Slides: <?= count(array_filter($slides, fn($s) => $s['status'] === 'active')) ?> | Display: Public Homepage Hero</span>
        </div>
        <button type="button" class="btn btn-light btn-sm rounded-pill" onclick="openModal('addSlideModal')" style="background: #ffffff; color: var(--brand-blue); font-weight: 700; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <i class="fas fa-plus-circle"></i> Upload New Slider Banner
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($slides)): ?>
            <div style="padding: 30px; text-align: center; background: #f8fafc; border-radius: 12px; border: 1.5px dashed #cbd5e1;">
                <div style="font-size: 2.5rem; color: #94a3b8; margin-bottom: 8px;"><i class="fas fa-film"></i></div>
                <h4>No Slider Banners Added</h4>
                <p class="text-muted">Click the "Upload New Slider Banner" button above to add dynamic slides with background photos (1920x800 px, 2.4:1 ratio) and CTA buttons.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Slide Photo</th>
                            <th style="width: 170px;">Measured Size &amp; Ratio</th>
                            <th>Eyebrow &amp; Main Headline</th>
                            <th>Target Display Placement</th>
                            <th>Buttons</th>
                            <th style="width: 70px;">Order</th>
                            <th style="width: 90px;">Status</th>
                            <th style="width: 130px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slides as $sl): ?>
                            <?php $meta = get_image_metadata($sl['image_path']); ?>
                            <tr>
                                <td>
                                    <?php if ($meta['exists']): ?>
                                        <img src="<?= BASE_URL . '/' . e($sl['image_path']) ?>" alt="Slide" style="width: 100px; height: 58px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 2px 5px rgba(0,0,0,0.08);">
                                    <?php else: ?>
                                        <div style="width: 100px; height: 58px; background: linear-gradient(135deg, #0754b8, #032b69); border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff; font-size: 0.72rem; text-align: center; padding: 4px;">
                                            <i class="fas fa-cube" style="font-size: 1.1rem; margin-bottom: 2px;"></i> 3D Gradient
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($meta['exists']): ?>
                                        <div style="font-size: 0.85rem; font-weight: 800; color: #0f172a;"><?= $meta['dimensions'] ?></div>
                                        <span class="badge badge-primary" style="font-size: 10px;"><?= $meta['ratio'] ?></span>
                                        <span class="badge badge-secondary" style="font-size: 10px;"><?= $meta['size_formatted'] ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary" style="font-size: 10.5px;">Vector 3D Mesh</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-secondary" style="font-size: 10px; margin-bottom: 4px; display: inline-block;"><?= e($sl['eyebrow']) ?></span>
                                    <strong style="display: block; font-size: 0.95rem; color: #0f172a;"><?= e($sl['title']) ?></strong>
                                    <p style="font-size: 0.8rem; color: #64748b; margin: 4px 0 0; max-width: 260px; line-height: 1.3;"><?= e(mb_strimwidth($sl['subtitle'], 0, 85, '...')) ?></p>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem; font-weight: 700; color: #0369a1;">
                                        <i class="fas fa-map-marker-alt text-danger"></i> Homepage Hero Top
                                    </div>
                                    <span style="font-size: 0.73rem; color: #64748b;">(Full-width 3D Slider)</span>
                                </td>
                                <td>
                                    <span class="badge badge-primary" style="font-size: 10.5px;"><?= e($sl['btn1_text']) ?></span>
                                    <?php if (!empty($sl['btn2_text'])): ?>
                                        <span class="badge badge-secondary" style="font-size: 10.5px;"><?= e($sl['btn2_text']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: #475569;"><?= (int)$sl['sort_order'] ?></span>
                                </td>
                                <td>
                                    <a href="?toggle_slide=<?= $sl['id'] ?>" class="badge <?= $sl['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>" style="text-decoration: none;" title="Click to toggle status">
                                        <i class="fas <?= $sl['status'] === 'active' ? 'fa-check' : 'fa-times' ?>"></i> <?= ucfirst($sl['status']) ?>
                                    </a>
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" class="btn btn-secondary btn-sm edit-slide-btn" 
                                            data-id="<?= $sl['id'] ?>"
                                            data-title="<?= e($sl['title']) ?>"
                                            data-subtitle="<?= e($sl['subtitle']) ?>"
                                            data-eyebrow="<?= e($sl['eyebrow']) ?>"
                                            data-btn1-text="<?= e($sl['btn1_text']) ?>"
                                            data-btn1-link="<?= e($sl['btn1_link']) ?>"
                                            data-btn2-text="<?= e($sl['btn2_text']) ?>"
                                            data-btn2-link="<?= e($sl['btn2_link']) ?>"
                                            data-order="<?= $sl['sort_order'] ?>"
                                            data-status="<?= $sl['status'] ?>"
                                            data-image="<?= e($sl['image_path']) ?>"
                                            title="Edit Slide">
                                        <i class="fas fa-edit text-primary"></i>
                                    </button>
                                    <a href="?delete_slide=<?= $sl['id'] ?>" class="btn btn-secondary btn-sm" title="Delete Slide" data-confirm="Remove this slide from homepage carousel?">
                                        <i class="fas fa-trash text-danger"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- =========================================================================
     2. HOMEPAGE GENERAL CONTENT & SECTIONS
     ========================================================================= -->
<form method="POST" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="save_frontend_cms" value="1">

    <!-- 2. SEO Intro Section -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-heading text-primary" style="margin-right: 8px;"></i> 2. SEO Home Intro Section</h3>
        </div>
        <div class="card-body">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="intro_eyebrow">Intro Eyebrow Tag</label>
                    <input type="text" name="intro_eyebrow" id="intro_eyebrow" class="form-control" value="<?= e($settings['intro_eyebrow'] ?? 'HYPERINFONET IT SOLUTIONS PVT. LTD.') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="intro_heading">Intro Main Heading (H2)</label>
                    <input type="text" name="intro_heading" id="intro_heading" class="form-control" value="<?= e($settings['intro_heading'] ?? 'Website Development & Management Software Company Serving All India') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="intro_description">Detailed Introduction Paragraph</label>
                <textarea name="intro_description" id="intro_description" class="form-control" rows="3"><?= e($settings['intro_description'] ?? 'Professional websites, institute management software, school and college ERP, NGO portals, billing solutions, e-commerce and custom PHP/MySQL web software for organizations across India.') ?></textarea>
            </div>
        </div>
    </div>

    <!-- 3. Quick Stats Counter Bar -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-line text-success" style="margin-right: 8px;"></i> 3. Quick Stats Counter Bar (4 Key Metrics)</h3>
        </div>
        <div class="card-body">
            <div class="grid-4">
                <div style="background: #f8fafc; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <h5 style="margin-bottom: 10px; font-size: 0.9rem; color: #1e293b;"><i class="bi bi-people-fill text-primary"></i> Stat Card 1</h5>
                    <div class="form-group">
                        <label class="form-label">Number</label>
                        <input type="text" name="stat1_number" class="form-control" value="<?= e($settings['stat1_number'] ?? '1500+') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Label</label>
                        <input type="text" name="stat1_label" class="form-control" value="<?= e($settings['stat1_label'] ?? 'Happy Clients') ?>">
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <h5 style="margin-bottom: 10px; font-size: 0.9rem; color: #1e293b;"><i class="bi bi-window-stack text-primary"></i> Stat Card 2</h5>
                    <div class="form-group">
                        <label class="form-label">Number</label>
                        <input type="text" name="stat2_number" class="form-control" value="<?= e($settings['stat2_number'] ?? '2000+') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Label</label>
                        <input type="text" name="stat2_label" class="form-control" value="<?= e($settings['stat2_label'] ?? 'Web Projects') ?>">
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <h5 style="margin-bottom: 10px; font-size: 0.9rem; color: #1e293b;"><i class="bi bi-grid-1x2-fill text-primary"></i> Stat Card 3</h5>
                    <div class="form-group">
                        <label class="form-label">Number</label>
                        <input type="text" name="stat3_number" class="form-control" value="<?= e($settings['stat3_number'] ?? '25+') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Label</label>
                        <input type="text" name="stat3_label" class="form-control" value="<?= e($settings['stat3_label'] ?? 'Software Modules') ?>">
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <h5 style="margin-bottom: 10px; font-size: 0.9rem; color: #1e293b;"><i class="bi bi-headset text-primary"></i> Stat Card 4</h5>
                    <div class="form-group">
                        <label class="form-label">Number</label>
                        <input type="text" name="stat4_number" class="form-control" value="<?= e($settings['stat4_number'] ?? '24×7') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Label</label>
                        <input type="text" name="stat4_label" class="form-control" value="<?= e($settings['stat4_label'] ?? 'Support Ready') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Sector Section (8 Business Categories) -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-th-large text-warning" style="margin-right: 8px;"></i> 4. Sector Section (8 Business Types)</h3>
        </div>
        <div class="card-body">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="sector_eyebrow">Section Eyebrow</label>
                    <input type="text" name="sector_eyebrow" id="sector_eyebrow" class="form-control" value="<?= e($settings['sector_eyebrow'] ?? 'WE BUILD FOR EVERY SECTOR') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sector_heading">Section Main Heading</label>
                    <input type="text" name="sector_heading" id="sector_heading" class="form-control" value="<?= e($settings['sector_heading'] ?? 'Websites & Management Software for Every Business Type') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="sector_description">Section Subtext</label>
                <input type="text" name="sector_description" id="sector_description" class="form-control" value="<?= e($settings['sector_description'] ?? 'From education and NGOs to healthcare, billing, e-commerce and custom business portals.') ?>">
            </div>

            <h4 style="font-size: 1rem; color: #1e293b; margin: 20px 0 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">8 Sector Cards Content:</h4>
            <div class="grid-4">
                <div style="background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <strong style="display:block; margin-bottom:8px; font-size:0.88rem;">1. School</strong>
                    <input type="text" name="sec1_title" class="form-control" style="margin-bottom:6px;" value="<?= e($settings['sec1_title'] ?? 'School Website') ?>">
                    <textarea name="sec1_desc" class="form-control" rows="2" style="font-size:0.8rem;"><?= e($settings['sec1_desc'] ?? 'Admission, academics, notices, gallery, results and school information.') ?></textarea>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <strong style="display:block; margin-bottom:8px; font-size:0.88rem;">2. College</strong>
                    <input type="text" name="sec2_title" class="form-control" style="margin-bottom:6px;" value="<?= e($settings['sec2_title'] ?? 'College Website') ?>">
                    <textarea name="sec2_desc" class="form-control" rows="2" style="font-size:0.8rem;"><?= e($settings['sec2_desc'] ?? 'Departments, courses, admission, faculty, notices and student services.') ?></textarea>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <strong style="display:block; margin-bottom:8px; font-size:0.88rem;">3. Computer Institute</strong>
                    <input type="text" name="sec3_title" class="form-control" style="margin-bottom:6px;" value="<?= e($settings['sec3_title'] ?? 'Computer Institute') ?>">
                    <textarea name="sec3_desc" class="form-control" rows="2" style="font-size:0.8rem;"><?= e($settings['sec3_desc'] ?? 'Courses, admission, student login, exam, marksheet and certificate.') ?></textarea>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <strong style="display:block; margin-bottom:8px; font-size:0.88rem;">4. Fire &amp; Safety</strong>
                    <input type="text" name="sec4_title" class="form-control" style="margin-bottom:6px;" value="<?= e($settings['sec4_title'] ?? 'Fire & Safety Institute') ?>">
                    <textarea name="sec4_desc" class="form-control" rows="2" style="font-size:0.8rem;"><?= e($settings['sec4_desc'] ?? 'Training courses, admission, certification and institute ERP.') ?></textarea>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <strong style="display:block; margin-bottom:8px; font-size:0.88rem;">5. Paramedical</strong>
                    <input type="text" name="sec5_title" class="form-control" style="margin-bottom:6px;" value="<?= e($settings['sec5_title'] ?? 'Paramedical Institute') ?>">
                    <textarea name="sec5_desc" class="form-control" rows="2" style="font-size:0.8rem;"><?= e($settings['sec5_desc'] ?? 'Courses, students, examinations, marksheets and certificates.') ?></textarea>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <strong style="display:block; margin-bottom:8px; font-size:0.88rem;">6. Coaching Institute</strong>
                    <input type="text" name="sec6_title" class="form-control" style="margin-bottom:6px;" value="<?= e($settings['sec6_title'] ?? 'Coaching Institute') ?>">
                    <textarea name="sec6_desc" class="form-control" rows="2" style="font-size:0.8rem;"><?= e($settings['sec6_desc'] ?? 'Batches, test series, student portal, fee alerts and enquiry system.') ?></textarea>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <strong style="display:block; margin-bottom:8px; font-size:0.88rem;">7. NGO / Trust</strong>
                    <input type="text" name="sec7_title" class="form-control" style="margin-bottom:6px;" value="<?= e($settings['sec7_title'] ?? 'NGO & Social Trust') ?>">
                    <textarea name="sec7_desc" class="form-control" rows="2" style="font-size:0.8rem;"><?= e($settings['sec7_desc'] ?? 'Projects, donation receipts, 80G tax exemptions, and volunteer management.') ?></textarea>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <strong style="display:block; margin-bottom:8px; font-size:0.88rem;">8. Hospital &amp; Clinic</strong>
                    <input type="text" name="sec8_title" class="form-control" style="margin-bottom:6px;" value="<?= e($settings['sec8_title'] ?? 'Hospital & Clinic Software') ?>">
                    <textarea name="sec8_desc" class="form-control" rows="2" style="font-size:0.8rem;"><?= e($settings['sec8_desc'] ?? 'OPD doctor appointments, patient history, prescriptions and billing.') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Prefooter CTA Section -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-bullhorn text-danger" style="margin-right: 8px;"></i> 5. Prefooter Call-To-Action Banner</h3>
        </div>
        <div class="card-body">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label" for="cta_eyebrow">CTA Eyebrow Tag</label>
                    <input type="text" name="cta_eyebrow" id="cta_eyebrow" class="form-control" value="<?= e($settings['cta_eyebrow'] ?? "LET'S BUILD YOUR DIGITAL SYSTEM") ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="cta_heading">CTA Big Heading</label>
                    <input type="text" name="cta_heading" id="cta_heading" class="form-control" value="<?= e($settings['cta_heading'] ?? 'Website + Management Software, exactly as your business needs.') ?>">
                </div>
            </div>
        </div>

        <div class="card-footer" style="padding: 18px 24px; background: #f8fafc; border-top: 1px solid var(--border-color); text-align: right;">
            <button type="submit" class="btn btn-primary btn-lg" style="box-shadow: 0 4px 14px rgba(7, 84, 184, 0.3);">
                <i class="fas fa-save"></i> Save &amp; Publish Website Changes Live
            </button>
        </div>
    </div>
</form>

<!-- Modal: Add New Slide -->
<div class="modal-backdrop" id="addSlideModal">
    <div class="modal-dialog" style="max-width: 680px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-plus-circle text-primary" style="margin-right: 8px;"></i> Add New Slide to Homepage Carousel</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="add_slide" value="1">

            <div class="modal-body">
                <!-- Dimension & Placement Specification Notice -->
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;">
                    <div style="font-weight: 800; color: #166534; font-size: 0.88rem; margin-bottom: 4px;">
                        <i class="fas fa-info-circle"></i> Slide Image Specifications &amp; Target Placement:
                    </div>
                    <div style="font-size: 0.82rem; color: #15803d; line-height: 1.5;">
                        &bull; <strong>Recommended Dimensions:</strong> 1920 &times; 800 px (or 1920 &times; 720 px)<br>
                        &bull; <strong>Aspect Ratio:</strong> 2.4:1 / 21:9 Ultra-Wide (or 16:9 Widescreen)<br>
                        &bull; <strong>Target Display Location:</strong> Top Hero Slider on Public Website Homepage (<a href="<?= BASE_URL ?>/index.php" target="_blank" style="color: #15803d; text-decoration: underline;">index.php#home</a>)<br>
                        &bull; <strong>Max File Size:</strong> 5 MB (Formats: JPG, PNG, WEBP, AVIF)
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_slide_file">Upload Slide Background Photo</label>
                    <input type="file" name="slide_image" id="add_slide_file" class="form-control" accept="image/*">
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="add_eyebrow">Eyebrow Badge *</label>
                        <input type="text" name="eyebrow" id="add_eyebrow" class="form-control" value="HYPERINFONET IT SOLUTIONS PVT. LTD." required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_sort">Display Sort Order</label>
                        <input type="number" name="sort_order" id="add_sort" class="form-control" value="10">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_title">Slide Main Headline (H1) *</label>
                    <input type="text" name="title" id="add_title" class="form-control" placeholder="e.g. Modern Web ERP for Institutes" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_subtitle">Slide Subtitle / Description *</label>
                    <textarea name="subtitle" id="add_subtitle" class="form-control" rows="2" placeholder="Brief compelling summary for visitors..." required></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="add_btn1_text">Primary Button Text</label>
                        <input type="text" name="btn1_text" id="add_btn1_text" class="form-control" value="Request Free Demo">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_btn1_link">Primary Button Link</label>
                        <input type="text" name="btn1_link" id="add_btn1_link" class="form-control" value="#contact">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="add_btn2_text">Secondary Button Text</label>
                        <input type="text" name="btn2_text" id="add_btn2_text" class="form-control" value="Explore 25+ Modules">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_btn2_link">Secondary Button Link</label>
                        <input type="text" name="btn2_link" id="add_btn2_link" class="form-control" value="#solutions">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_status">Status</label>
                    <select name="status" id="add_status" class="form-select">
                        <option value="active">Active (Visible on Homepage)</option>
                        <option value="inactive">Draft / Inactive</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-cloud-upload-alt"></i> Upload &amp; Add Slide</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Existing Slide -->
<div class="modal-backdrop" id="editSlideModal">
    <div class="modal-dialog" style="max-width: 680px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-edit text-primary" style="margin-right: 8px;"></i> Edit Slider Banner</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="edit_slide" value="1">
            <input type="hidden" name="slide_id" id="edit_slide_id" value="">
            <input type="hidden" name="current_image" id="edit_current_image" value="">

            <div class="modal-body">
                <!-- Dimension & Placement Specification Notice -->
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;">
                    <div style="font-weight: 800; color: #166534; font-size: 0.88rem; margin-bottom: 4px;">
                        <i class="fas fa-info-circle"></i> Slide Image Specifications &amp; Target Placement:
                    </div>
                    <div style="font-size: 0.82rem; color: #15803d; line-height: 1.5;">
                        &bull; <strong>Recommended Dimensions:</strong> 1920 &times; 800 px | <strong>Ratio:</strong> 2.4:1 / 21:9 Ultra-Wide<br>
                        &bull; <strong>Target Display Location:</strong> Homepage Hero Top Slider (<a href="<?= BASE_URL ?>/index.php" target="_blank" style="color: #15803d; text-decoration: underline;">index.php#home</a>)
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_slide_file">Replace Slide Background Photo</label>
                    <input type="file" name="slide_image" id="edit_slide_file" class="form-control" accept="image/*">
                    <div class="form-help">Leave empty to keep current background photo or 3D mesh.</div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="edit_eyebrow">Eyebrow Badge *</label>
                        <input type="text" name="eyebrow" id="edit_eyebrow" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_sort">Display Sort Order</label>
                        <input type="number" name="sort_order" id="edit_sort" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_title">Slide Main Headline (H1) *</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_subtitle">Slide Subtitle / Description *</label>
                    <textarea name="subtitle" id="edit_subtitle" class="form-control" rows="2" required></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="edit_btn1_text">Primary Button Text</label>
                        <input type="text" name="btn1_text" id="edit_btn1_text" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_btn1_link">Primary Button Link</label>
                        <input type="text" name="btn1_link" id="edit_btn1_link" class="form-control">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="edit_btn2_text">Secondary Button Text</label>
                        <input type="text" name="btn2_text" id="edit_btn2_text" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_btn2_link">Secondary Button Link</label>
                        <input type="text" name="btn2_link" id="edit_btn2_link" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_status">Status</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="active">Active (Visible on Homepage)</option>
                        <option value="inactive">Draft / Inactive</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Slide Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.edit-slide-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_slide_id').value = this.dataset.id;
        document.getElementById('edit_title').value = this.dataset.title;
        document.getElementById('edit_subtitle').value = this.dataset.subtitle;
        document.getElementById('edit_eyebrow').value = this.dataset.eyebrow;
        document.getElementById('edit_btn1_text').value = this.dataset.btn1Text;
        document.getElementById('edit_btn1_link').value = this.dataset.btn1Link;
        document.getElementById('edit_btn2_text').value = this.dataset.btn2Text;
        document.getElementById('edit_btn2_link').value = this.dataset.btn2Link;
        document.getElementById('edit_sort').value = this.dataset.order;
        document.getElementById('edit_status').value = this.dataset.status;
        document.getElementById('edit_current_image').value = this.dataset.image;

        openModal('editSlideModal');
    });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
