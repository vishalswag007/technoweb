<?php
/**
 * Vishal Web Studio - Dynamic Multi-Tenant Client Website & Demo Renderer
 * Renders either a live Client Website (?site=slug) or Template Demo (?demo=slug)
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pdo = db();

$siteSlug = $_GET['site'] ?? '';
$demoSlug = $_GET['demo'] ?? '';
$isPreview = isset($_GET['preview']);

$website = null;
$client = null;
$template = null;

if (!empty($siteSlug)) {
    // Render Client Website
    $sStmt = $pdo->prepare("SELECT w.*, c.business_name, c.owner_name, c.phone, c.whatsapp, c.email, c.address, c.city, c.business_category FROM websites w JOIN clients c ON w.client_id = c.id WHERE w.slug = ?");
    $sStmt->execute([$siteSlug]);
    $website = $sStmt->fetch();

    if (!$website) {
        die("<h3>Website not found.</h3><p>Please check the web address or contact the business owner.</p>");
    }

    $client = [
        'business_name' => $website['business_name'],
        'owner_name'    => $website['owner_name'],
        'phone'         => $website['phone'],
        'whatsapp'      => $website['whatsapp'],
        'email'         => $website['email'],
        'address'       => $website['address'],
        'city'          => $website['city'],
        'category'      => $website['business_category'],
    ];

    // Increment live views if not preview
    if (!$isPreview) {
        $pdo->prepare("UPDATE websites SET views_count = views_count + 1 WHERE id = ?")->execute([$website['id']]);
    }

    $websiteId = $website['id'];

} elseif (!empty($demoSlug)) {
    // Slug Aliases Map for 100% Reliability
    $slugAliases = [
        'apex-coaching-institute' => 'coaching-institute',
        'careplus-health-clinic'  => 'medical-clinic',
        'careplus-clinic'         => 'medical-clinic',
        'hospital-software'       => 'medical-clinic',
        'school-erp'              => 'coaching-institute',
        'institute-erp'           => 'coaching-institute',
        'restaurant-management'   => 'restaurant-delight',
        'beauty-salon'            => 'salon-elegance',
        'digital-agency'          => 'modern-agency',
        'online-store'            => 'ecommerce-store'
    ];
    if (isset($slugAliases[$demoSlug])) {
        $demoSlug = $slugAliases[$demoSlug];
    }

    // Render Template Demo
    $tStmt = $pdo->prepare("SELECT * FROM templates WHERE slug = ?");
    $tStmt->execute([$demoSlug]);
    $template = $tStmt->fetch();

    if (!$template) {
        // Fallback to first available active template
        $template = $pdo->query("SELECT * FROM templates ORDER BY id ASC LIMIT 1")->fetch();
    }

    $website = [
        'id'               => 0,
        'name'             => $template['name'],
        'tagline'          => $template['tagline'],
        'theme_color'      => $template['default_theme_color'] ?? '#2563eb',
        'meta_title'       => $template['name'] . ' - Live Website Demo',
        'meta_description' => $template['description'],
        'status'           => 'demo',
    ];

    $client = [
        'business_name' => $template['name'],
        'owner_name'    => 'Business Manager',
        'phone'         => '+91 98765 43210',
        'whatsapp'      => '919876543210',
        'email'         => 'contact@' . $template['slug'] . '.demo',
        'address'       => '102 Main Street, City Center',
        'city'          => 'New Delhi, India',
        'category'      => $template['category'],
    ];

    $websiteId = 0;

} else {
    // Default fallback: load the first live website or demo
    $fallbackSite = $pdo->query("SELECT slug FROM websites WHERE status = 'live' LIMIT 1")->fetch();
    if ($fallbackSite) {
        header('Location: ' . BASE_URL . '/site/index.php?site=' . urlencode($fallbackSite['slug']));
        exit;
    } else {
        header('Location: ' . BASE_URL . '/site/index.php?demo=restaurant-delight');
        exit;
    }
}

// Fetch Sections, Services, Gallery, Testimonials, FAQs
$sections = [];
$services = [];
$gallery = [];
$testimonials = [];
$faqs = [];

if ($websiteId > 0) {
    // Fetch from database for real client website
    $secRows = $pdo->query("SELECT * FROM website_sections WHERE website_id = {$websiteId} AND is_active = 1 ORDER BY sort_order ASC")->fetchAll();
    foreach ($secRows as $sr) {
        $sr['data'] = json_decode($sr['content_json'] ?? '{}', true);
        $sections[$sr['section_key']] = $sr;
    }

    $services = $pdo->query("SELECT * FROM services WHERE website_id = {$websiteId} AND status = 'active' ORDER BY sort_order ASC")->fetchAll();
    $gallery = $pdo->query("SELECT * FROM gallery WHERE website_id = {$websiteId} ORDER BY sort_order ASC")->fetchAll();
    $testimonials = $pdo->query("SELECT * FROM testimonials WHERE website_id = {$websiteId} ORDER BY sort_order ASC")->fetchAll();
    $faqs = $pdo->query("SELECT * FROM faqs WHERE website_id = {$websiteId} ORDER BY sort_order ASC")->fetchAll();
} else {
    // Demo Mock Data
    $sections['hero'] = [
        'title' => $template['tagline'] ?: "Welcome to {$template['name']}",
        'subtitle' => $template['description'] ?: 'Delivering world-class quality and exceptional service.',
        'data' => [
            'badge' => "Official {$template['category']} Portal",
            'primary_btn_text' => 'View Services',
            'primary_btn_link' => '#services',
            'secondary_btn_text' => 'Get in Touch',
            'secondary_btn_link' => '#contact',
            'stat1_num' => '25+', 'stat1_label' => 'Years Experience',
            'stat2_num' => '100%', 'stat2_label' => 'Quality Verified',
            'stat3_num' => '10k+', 'stat3_label' => 'Happy Customers'
        ]
    ];
    $sections['about'] = [
        'title' => "About Our Story & Craft",
        'subtitle' => "Committed to authenticity, passion, and delivering unforgettable experiences for our community.",
        'data' => [
            'highlight_text' => 'Certified Quality & Dedicated Craftsmanship',
            'paragraph_1' => "Founded with a passion for excellence, we have grown into one of the region's most beloved destinations.",
            'paragraph_2' => "We pride ourselves on using the finest ingredients, employing skilled specialists, and providing courteous service."
        ]
    ];
    $sections['contact'] = [
        'title' => "Visit Us or Get in Touch",
        'subtitle' => "Have questions or need assistance? Call our desk or send a direct WhatsApp message.",
        'data' => [
            'phone' => '+91 98765 43210',
            'whatsapp' => '919876543210',
            'email' => 'hello@' . $template['slug'] . '.demo',
            'address' => 'Shop 14-16, Main Market, City Center',
            'hours' => 'Mon - Sun: 09:00 AM - 10:00 PM'
        ]
    ];

    $services = [
        ['title' => 'Signature Specialty Package', 'description' => 'Our flagship offering prepared with premium ingredients and unmatched attention to detail.', 'price' => 399.00, 'price_label' => 'Standard Item', 'icon' => 'fas fa-star'],
        ['title' => 'Executive Consultation & Service', 'description' => 'Personalized experience tailored to your exact preferences and specifications.', 'price' => 799.00, 'price_label' => 'Complete Plan', 'icon' => 'fas fa-heart'],
        ['title' => 'Family & Group Gathering Feast', 'description' => 'Comprehensive setup designed for parties, group reservations, and corporate celebrations.', 'price' => 1499.00, 'price_label' => 'Starting At', 'icon' => 'fas fa-users'],
    ];

    $faqs = [
        ['question' => 'How can I place an order or book an appointment?', 'answer' => 'You can submit the contact inquiry form below or click the floating WhatsApp button to chat instantly.'],
        ['question' => 'What are your operational timings?', 'answer' => 'We are open 7 days a week from 09:00 AM to 10:00 PM IST.'],
        ['question' => 'Do you provide delivery or onsite services?', 'answer' => 'Yes, we provide swift local service and delivery across our city.']
    ];

    $testimonials = [
        ['client_name' => 'Ananya Roy', 'client_title' => 'Local Verified Customer', 'content' => 'Outstanding quality and wonderful hospitality! Highly recommend to everyone in the area.', 'rating' => 5],
        ['client_name' => 'Kunal Kashyap', 'client_title' => 'Frequent Patron', 'content' => 'The staff is attentive, prices are fair, and the service is super fast.', 'rating' => 5]
    ];
}

$themeColor = $website['theme_color'] ?? '#2563eb';
$secondaryColor = $websiteId > 0 ? get_website_setting($websiteId, 'secondary_color', '#1e40af') : '#1e40af';
$siteFontFamily = $websiteId > 0 ? get_website_setting($websiteId, 'font_family', 'Inter') : 'Inter';
$enable3DGlass = $websiteId > 0 ? get_website_setting($websiteId, 'enable_3d_glass', '1') : '1';

$footerBrandName = $websiteId > 0 ? get_website_setting($websiteId, 'footer_brand_name', $website['name']) : $website['name'];
$footerTagline = $websiteId > 0 ? get_website_setting($websiteId, 'footer_tagline', 'Serving with quality, excellence and satisfaction.') : 'Serving with quality, excellence and satisfaction.';
$footerCopyright = $websiteId > 0 ? get_website_setting($websiteId, 'footer_copyright', '© ' . date('Y') . ' ' . $footerBrandName . '. All rights reserved.') : ('© ' . date('Y') . ' ' . $footerBrandName . '. All rights reserved.');
$footerAbout = $websiteId > 0 ? get_website_setting($websiteId, 'footer_about', 'Experience exceptional service, online inquiries, takeaway orders, and direct WhatsApp booking.') : 'Experience exceptional service, online inquiries, and direct WhatsApp booking.';

$hero = $sections['hero'] ?? ['title' => "Welcome to {$website['name']}", 'subtitle' => '', 'data' => []];
$about = $sections['about'] ?? ['title' => "About {$website['name']}", 'subtitle' => '', 'data' => []];
$contact = $sections['contact'] ?? ['title' => "Contact Us", 'subtitle' => '', 'data' => []];

$whatsAppNumber = $contact['data']['whatsapp'] ?? ($client['whatsapp'] ?? '919876543210');
$contactPhone = $contact['data']['phone'] ?? ($client['phone'] ?? '+91 98765 43210');
$contactEmail = $contact['data']['email'] ?? ($client['email'] ?? 'info@website.com');
$contactAddress = $contact['data']['address'] ?? ($client['address'] ?? 'City Center');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($website['meta_title'] ?: ($website['name'] . ' | ' . ($client['category'] ?? 'Business'))) ?></title>
    <meta name="description" content="<?= e($website['meta_description'] ?: ($website['name'] . ' - Professional ' . ($client['category'] ?? '') . ' services.')) ?>">
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Montserrat:wght@400;600;700;800;900&family=Outfit:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,400&family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/main.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/site.css">
    
    <style>
        :root {
            --site-theme: <?= e($themeColor) ?>;
            --site-theme-dark: <?= e($secondaryColor) ?>;
            --site-theme-light: <?= e($themeColor) ?>18;
            --primary: <?= e($themeColor) ?>;
            font-family: '<?= e($siteFontFamily) ?>', sans-serif;
        }
        body.tenant-site {
            font-family: '<?= e($siteFontFamily) ?>', sans-serif;
        }
    </style>
</head>
<body class="tenant-site">

<?= render_flash_messages() ?>

<!-- Tenant Header -->
<header class="tenant-header">
    <div class="container">
        <nav class="tenant-nav">
            <a href="#hero" class="tenant-brand">
                <div class="tenant-brand-icon"><i class="fas fa-store"></i></div>
                <span><?= e($website['name']) ?></span>
            </a>

            <ul class="tenant-nav-links">
                <li><a href="#hero" class="tenant-nav-link">Home</a></li>
                <li><a href="#about" class="tenant-nav-link">About</a></li>
                <?php if (!empty($services)): ?>
                    <li><a href="#services" class="tenant-nav-link">Services / Menu</a></li>
                <?php endif; ?>
                <?php if (!empty($gallery)): ?>
                    <li><a href="#gallery" class="tenant-nav-link">Gallery</a></li>
                <?php endif; ?>
                <?php if (!empty($faqs)): ?>
                    <li><a href="#faqs" class="tenant-nav-link">FAQs</a></li>
                <?php endif; ?>
                <li><a href="#contact" class="tenant-nav-link">Contact</a></li>
            </ul>

            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <a href="<?= build_whatsapp_link($whatsAppNumber, "Hello {$website['name']}, I want to inquire about your services.") ?>" target="_blank" class="btn btn-whatsapp btn-sm">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="#contact" class="btn btn-primary btn-sm">
                    <i class="fas fa-calendar-check"></i> Book Now
                </a>
                <a href="<?= BASE_URL ?>/client/login.php?site=<?= urlencode($siteSlug) ?>" class="btn btn-secondary btn-sm" title="Client Website Admin Login" style="background: #ffffff; border: 1.5px solid #cbd5e1; color: #1e293b; font-weight: 700;">
                    <i class="fas fa-lock text-primary"></i> Admin Login
                </a>
            </div>
        </nav>
    </div>
</header>

<!-- Tenant Hero -->
<section id="hero" class="tenant-hero">
    <div class="container">
        <div class="tenant-hero-grid">
            <div>
                <span class="tenant-hero-badge">
                    <i class="fas fa-star" style="color: #f59e0b; margin-right: 4px;"></i> <?= e($hero['data']['badge'] ?? "Welcome to {$website['name']}") ?>
                </span>
                <h1 class="tenant-hero-title">
                    <?= e($hero['title']) ?>
                </h1>
                <p class="tenant-hero-desc">
                    <?= e($hero['subtitle'] ?: ($website['tagline'] ?? '')) ?>
                </p>

                <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                    <a href="<?= e($hero['data']['primary_btn_link'] ?? '#services') ?>" class="btn btn-primary btn-lg">
                        <?= e($hero['data']['primary_btn_text'] ?? 'Explore Services') ?> <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="<?= e($hero['data']['secondary_btn_link'] ?? '#contact') ?>" class="btn btn-secondary btn-lg">
                        <?= e($hero['data']['secondary_btn_text'] ?? 'Get in Touch') ?>
                    </a>
                </div>

                <div class="tenant-hero-stats">
                    <div class="tenant-stat-item">
                        <strong><?= e($hero['data']['stat1_num'] ?? '20+') ?></strong>
                        <span><?= e($hero['data']['stat1_label'] ?? 'Years Heritage') ?></span>
                    </div>
                    <div class="tenant-stat-item">
                        <strong><?= e($hero['data']['stat2_num'] ?? '100%') ?></strong>
                        <span><?= e($hero['data']['stat2_label'] ?? 'Quality Verified') ?></span>
                    </div>
                    <div class="tenant-stat-item">
                        <strong><?= e($hero['data']['stat3_num'] ?? '10k+') ?></strong>
                        <span><?= e($hero['data']['stat3_label'] ?? 'Satisfied Clients') ?></span>
                    </div>
                </div>
            </div>

            <div>
                <div class="mockup-window" style="box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                    <div class="mockup-bar">
                        <div class="mockup-dots">
                            <span class="mockup-dot red"></span>
                            <span class="mockup-dot yellow"></span>
                            <span class="mockup-dot green"></span>
                        </div>
                        <div class="mockup-address">https://<?= e($client['business_name']) ?></div>
                    </div>
                    <?php if (!empty($hero['data']['image_path']) && file_exists(ROOT_PATH . '/' . $hero['data']['image_path'])): ?>
                        <div style="height: 180px; overflow: hidden;">
                            <img src="<?= BASE_URL . '/' . e($hero['data']['image_path']) ?>" alt="<?= e($website['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <div class="mockup-body" style="padding: 26px; text-align: center;">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--site-theme-light); color: var(--site-theme); margin: 0 auto 14px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h3 style="font-size: 1.35rem; margin-bottom: 6px;"><?= e($website['name']) ?></h3>
                        <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 18px;">
                            <?= e($client['category'] ?? 'Business') ?> • <?= e($client['city'] ?? 'India') ?>
                        </p>
                        <a href="tel:<?= e($contactPhone) ?>" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">
                            <i class="fas fa-phone-alt"></i> Call Now: <?= e($contactPhone) ?>
                        </a>
                        <a href="<?= build_whatsapp_link($whatsAppNumber, "Hello {$website['name']}, I want to place an order.") ?>" target="_blank" class="btn btn-whatsapp" style="width: 100%;">
                            <i class="fab fa-whatsapp"></i> Quick Order via WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="section section-bg">
    <div class="container">
        <div class="grid-2" style="align-items: center; gap: 48px;">
            <div>
                <span class="badge badge-primary" style="margin-bottom: 12px;"><?= e($about['data']['highlight_text'] ?? 'About Our Brand') ?></span>
                <h2 style="font-size: 2.25rem; margin-bottom: 16px; color: #0f172a;"><?= e($about['title']) ?></h2>
                <p class="lead" style="margin-bottom: 20px;"><?= e($about['subtitle']) ?></p>
                <p style="color: #334155; margin-bottom: 14px; font-size: 0.95rem; line-height: 1.7;">
                    <?= e($about['data']['paragraph_1'] ?? '') ?>
                </p>
                <p style="color: #334155; margin-bottom: 24px; font-size: 0.95rem; line-height: 1.7;">
                    <?= e($about['data']['paragraph_2'] ?? '') ?>
                </p>

                <div style="display: flex; gap: 14px;">
                    <a href="#services" class="btn btn-primary btn-sm">Our Specialties</a>
                    <a href="#contact" class="btn btn-secondary btn-sm">Contact Desk</a>
                </div>
            </div>

            <?php if (!empty($about['data']['image_path']) && file_exists(ROOT_PATH . '/' . $about['data']['image_path'])): ?>
                <div style="border-radius: 20px; overflow: hidden; box-shadow: 0 20px 45px rgba(0,0,0,0.12); position: relative; height: 380px;">
                    <img src="<?= BASE_URL . '/' . e($about['data']['image_path']) ?>" alt="<?= e($website['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(15,23,42,0.9) 0%, transparent 100%); padding: 24px; color: #ffffff;">
                        <div style="font-weight: 800; font-size: 1.1rem;"><?= e($client['owner_name']) ?></div>
                        <div style="font-size: 0.82rem; color: #cbd5e1;">Founder & Management, <?= e($website['name']) ?></div>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: linear-gradient(135deg, var(--site-theme) 0%, #0f172a 100%); border-radius: 20px; padding: 40px; color: #ffffff; box-shadow: 0 20px 45px rgba(0,0,0,0.12);">
                    <i class="fas fa-quote-left" style="font-size: 2.5rem; opacity: 0.5; margin-bottom: 16px;"></i>
                    <h3 style="color: #ffffff; font-size: 1.4rem; margin-bottom: 14px;">Dedicated to Quality & Taste</h3>
                    <p style="color: #cbd5e1; font-size: 0.95rem; line-height: 1.7; margin-bottom: 24px;">
                        "We believe every customer deserves authentic flavor, clean presentation, and personalized warmth. Thank you for making us a part of your journey."
                    </p>
                    <div style="font-weight: 700; font-size: 1rem;"><?= e($client['owner_name']) ?></div>
                    <div style="font-size: 0.8rem; color: #94a3b8;">Founder & Management, <?= e($website['name']) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Services / Menu Catalog -->
<?php if (!empty($services)): ?>
<section id="services" class="section">
    <div class="container">
        <div class="section-header">
            <div class="section-subtitle">Our Menu & Offerings</div>
            <h2 class="section-title">Explore Our Specialties</h2>
            <p class="lead">Handcrafted recipes and professional solutions tailored for your utmost satisfaction.</p>
        </div>

        <div class="grid-3">
            <?php foreach ($services as $svc): ?>
                <div class="service-menu-card">
                    <?php if (!empty($svc['icon']) && str_starts_with($svc['icon'], 'uploads/')): ?>
                        <div style="height: 160px; margin: -24px -24px 16px -24px; overflow: hidden; background: #e2e8f0;">
                            <img src="<?= BASE_URL . '/' . e($svc['icon']) ?>" alt="<?= e($svc['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <div class="service-card-header">
                        <div class="service-card-title"><?= e($svc['title']) ?></div>
                        <div class="service-price-pill"><?= format_currency($svc['price']) ?></div>
                    </div>
                    <p style="font-size: 0.88rem; color: var(--site-text-muted); margin-bottom: 16px; flex-grow: 1;">
                        <?= e($svc['description']) ?>
                    </p>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--site-border);">
                        <span style="font-size: 0.8rem; color: var(--site-text-muted);"><i class="fas fa-tag"></i> <?= e($svc['price_label'] ?: 'Special') ?></span>
                        <a href="<?= build_whatsapp_link($whatsAppNumber, "Hello {$website['name']}, I want to order/book: {$svc['title']}") ?>" target="_blank" class="btn btn-whatsapp btn-sm" style="padding: 4px 10px; font-size: 0.8rem;">
                            <i class="fab fa-whatsapp"></i> Order
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Photo Gallery -->
<?php if (!empty($gallery)): ?>
<section id="gallery" class="section section-bg">
    <div class="container">
        <div class="section-header">
            <div class="section-subtitle">Visual Moments</div>
            <h2 class="section-title">Photo Gallery</h2>
            <p class="lead">A glimpse into our ambiance, creations, and customer smiles.</p>
        </div>

        <div class="grid-3">
            <?php foreach ($gallery as $g): ?>
                <div class="card" style="overflow: hidden; border-radius: 12px;">
                    <div style="height: 220px; background: #e2e8f0; overflow: hidden;">
                        <?php if (file_exists(ROOT_PATH . DIRECTORY_SEPARATOR . $g['image_path'])): ?>
                            <img src="<?= BASE_URL . '/' . e($g['image_path']) ?>" alt="<?= e($g['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: var(--site-theme); color: #ffffff;">
                                <i class="fas fa-camera" style="font-size: 2.5rem;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body" style="padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;">
                        <strong style="font-size: 0.92rem; color: #0f172a;"><?= e($g['title']) ?></strong>
                        <span class="badge badge-secondary"><?= e($g['category']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Testimonials -->
<?php if (!empty($testimonials)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <div class="section-subtitle">Verified Reviews</div>
            <h2 class="section-title">What Our Customers Say</h2>
        </div>

        <div class="grid-3">
            <?php foreach ($testimonials as $tst): ?>
                <div class="testimonial-card">
                    <div class="stars-row">
                        <?php for ($i=0; $i<$tst['rating']; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                    </div>
                    <p class="testimonial-quote">"<?= e($tst['content']) ?>"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar" style="background: var(--site-theme-light); color: var(--site-theme);">
                            <?= strtoupper(substr($tst['client_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem;"><?= e($tst['client_name']) ?></div>
                            <div style="font-size: 0.78rem; color: var(--site-text-muted);"><?= e($tst['client_title']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FAQs -->
<?php if (!empty($faqs)): ?>
<section id="faqs" class="section section-bg">
    <div class="container" style="max-width: 840px;">
        <div class="section-header">
            <div class="section-subtitle">Got Questions?</div>
            <h2 class="section-title">Frequently Asked Questions</h2>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px;">
            <?php foreach ($faqs as $fq): ?>
                <div class="card" style="padding: 20px; box-shadow: var(--shadow-sm);">
                    <h4 style="font-size: 1.05rem; color: #0f172a; margin-bottom: 6px;">
                        <i class="fas fa-question-circle text-primary" style="margin-right: 6px;"></i> <?= e($fq['question']) ?>
                    </h4>
                    <p style="color: var(--site-text-muted); font-size: 0.92rem; margin: 0; line-height: 1.6;">
                        <?= e($fq['answer']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Contact & Inquiry Form -->
<section id="contact" class="section">
    <div class="container">
        <div class="section-header">
            <div class="section-subtitle">Reach Out</div>
            <h2 class="section-title"><?= e($contact['title']) ?></h2>
            <p class="lead"><?= e($contact['subtitle']) ?></p>
        </div>

        <div class="grid-2" style="gap: 40px; align-items: start;">
            <!-- 3D Inquiry Form -->
            <div class="card" style="border-radius: 24px; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.1), inset 0 1px 0 #ffffff; border: 1px solid #e2e8f0; overflow: hidden; background: #ffffff;">
                <div style="padding: 36px 32px;">
                    <h3 style="font-size: 1.35rem; font-weight: 900; color: #0f172a; margin-bottom: 6px;">Send an Online Inquiry / Booking</h3>
                    <p style="color: var(--site-text-muted); font-size: 0.9rem; margin-bottom: 22px;">Fill in your requirements and our team will get back to you promptly.</p>

                    <form method="POST" action="<?= BASE_URL ?>/site/inquiry.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="website_id" value="<?= $websiteId ?>">
                        <input type="hidden" name="return_url" value="<?= BASE_URL ?>/site/index.php?<?= !empty($siteSlug) ? ('site=' . urlencode($siteSlug)) : ('demo=' . urlencode($demoSlug)) ?>#contact">

                        <div class="grid-2" style="gap: 16px; margin-bottom: 4px;">
                            <div class="form-group">
                                <label class="form-label" for="inq_name">Your Name *</label>
                                <input type="text" name="name" id="inq_name" class="form-control" required placeholder="Your Name">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="inq_phone">Phone / WhatsApp *</label>
                                <input type="tel" name="phone" id="inq_phone" class="form-control" required placeholder="+91 98765 43210">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="inq_email">Email Address</label>
                            <input type="email" name="email" id="inq_email" class="form-control" placeholder="name@example.com">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="inq_message">Message / Booking Details *</label>
                            <textarea name="message" id="inq_message" class="form-control" rows="4" required placeholder="Let us know your requirements or questions..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg rounded-pill" style="width: 100%; padding: 14px; font-size: 1rem; margin-top: 10px;">
                            <i class="fas fa-paper-plane"></i> Send Booking Request
                        </button>
                    </form>
                </div>
            </div>

            <!-- Direct Contact Info Box -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div class="card" style="padding: 24px; border-radius: 16px;">
                    <div style="display: flex; gap: 16px; align-items: flex-start;">
                        <div style="width: 44px; height: 44px; border-radius: 8px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 1.1rem; margin-bottom: 4px;">WhatsApp Direct Order Desk</h4>
                            <p class="text-muted" style="font-size: 0.88rem; margin-bottom: 10px;">Chat directly with our representative for takeaway, table bookings, and home delivery.</p>
                            <a href="<?= build_whatsapp_link($whatsAppNumber, "Hello {$website['name']}, I want to place an order.") ?>" target="_blank" class="btn btn-whatsapp btn-sm">
                                <i class="fab fa-whatsapp"></i> Open WhatsApp Chat
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 24px; border-radius: 16px;">
                    <div style="display: flex; gap: 16px; align-items: flex-start;">
                        <div style="width: 44px; height: 44px; border-radius: 8px; background: var(--site-theme-light); color: var(--site-theme); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 1.1rem; margin-bottom: 4px;">Location & Hours</h4>
                            <p style="font-size: 0.9rem; color: #334155; margin-bottom: 6px;"><?= e($contactAddress) ?></p>
                            <p style="font-size: 0.85rem; color: var(--site-text-muted); margin-bottom: 4px;"><i class="fas fa-clock"></i> <?= e($contact['data']['hours'] ?? '09:00 AM - 10:00 PM') ?></p>
                            <p style="font-size: 0.85rem; color: var(--site-text-muted);"><i class="fas fa-phone"></i> Call: <?= e($contactPhone) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Floating WhatsApp Button -->
<a href="<?= build_whatsapp_link($whatsAppNumber, "Hello {$website['name']}, I have an inquiry about your services.") ?>" target="_blank" class="floating-whatsapp-btn" title="Chat on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>

<!-- 3D Glassmorphic Client Website Footer -->
<footer style="background: linear-gradient(180deg, #0f172a 0%, #080d1a 100%); color: #94a3b8; padding: 60px 0 30px; border-top: 2px solid #1e293b; font-size: 0.9rem;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 36px; margin-bottom: 40px;">
            <!-- Col 1: Brand & Tagline -->
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                    <div style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, var(--site-theme), var(--site-theme-dark)); display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 1.25rem; box-shadow: 0 4px 14px rgba(0,0,0,0.3);">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h3 style="color: #ffffff; font-weight: 800; font-size: 1.3rem; margin: 0;"><?= e($footerBrandName) ?></h3>
                </div>
                <p style="color: #cbd5e1; font-weight: 600; font-size: 0.95rem; margin-bottom: 8px;"><?= e($footerTagline) ?></p>
                <p style="color: #64748b; font-size: 0.85rem; line-height: 1.6;"><?= e($footerAbout) ?></p>
            </div>

            <!-- Col 2: Quick Links & Booking -->
            <div>
                <h4 style="color: #ffffff; font-size: 1.05rem; font-weight: 700; margin-bottom: 16px;">Quick Actions</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                    <li><a href="#services" style="color: #94a3b8; text-decoration: none; transition: color 0.2s;"><i class="fas fa-chevron-right text-primary" style="font-size: 11px; margin-right: 8px;"></i> Services &amp; Packages</a></li>
                    <li><a href="#about" style="color: #94a3b8; text-decoration: none; transition: color 0.2s;"><i class="fas fa-chevron-right text-primary" style="font-size: 11px; margin-right: 8px;"></i> About Our Craft</a></li>
                    <li><a href="#contact" style="color: #94a3b8; text-decoration: none; transition: color 0.2s;"><i class="fas fa-chevron-right text-primary" style="font-size: 11px; margin-right: 8px;"></i> Contact &amp; Location</a></li>
                    <li><a href="<?= build_whatsapp_link($whatsAppNumber, "Hello, I want to book a table or service.") ?>" target="_blank" style="color: #22c55e; font-weight: 700; text-decoration: none;"><i class="fab fa-whatsapp" style="margin-right: 8px;"></i> WhatsApp Instant Order</a></li>
                </ul>
            </div>

            <!-- Col 3: Portal Access & Admin -->
            <div>
                <h4 style="color: #ffffff; font-size: 1.05rem; font-weight: 700; margin-bottom: 16px;">Management &amp; Support</h4>
                <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 14px;">Authorized business owners can sign in to manage site content, inquiries, and orders in real-time.</p>
                <a href="<?= BASE_URL ?>/client/login.php?site=<?= urlencode($siteSlug) ?>" class="btn btn-sm" style="background: rgba(255,255,255,0.1); border: 1.5px solid rgba(255,255,255,0.2); color: #ffffff; padding: 9px 18px; border-radius: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-lock text-primary"></i> Client Admin Console
                </a>
            </div>
        </div>

        <div style="padding-top: 24px; border-top: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; font-size: 0.82rem;">
            <span><?= e($footerCopyright) ?></span>
            <span>Powered by <a href="<?= BASE_URL ?>/index.php" target="_blank" style="color: #38bdf8; font-weight: 700;">Vishal Web Studio Platform</a></span>
        </div>
    </div>
</footer>

<script src="<?= ASSETS_URL ?>/js/toast.js"></script>
</body>
</html>
