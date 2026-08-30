<?php
/**
 * Vishal Web Studio - Main Homepage (HyperInfonet 100% Replication Design)
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = db();

// Fetch Dynamic CMS Settings
$heroEyebrow = get_setting('hero_eyebrow', 'HYPERINFONET IT SOLUTIONS PVT. LTD.');
$heroTitle = get_setting('hero_title', 'Website Development & Management Software for All Organizations');
$heroSubtitle = get_setting('hero_subtitle', 'Automate your School, College, Computer Institute, Hospital, NGO, or Business with dynamic websites, admissions, fees, ID cards, marksheets, and digital student portals.');
$heroBtn1Text = get_setting('hero_btn1_text', 'Request Free Demo');
$heroBtn1Link = get_setting('hero_btn1_link', '#contact');
$heroBtn2Text = get_setting('hero_btn2_text', 'Explore 25+ Modules');
$heroBtn2Link = get_setting('hero_btn2_link', '#solutions');
$heroBanner = get_setting('hero_banner_image', '');

$introEyebrow = get_setting('intro_eyebrow', 'HYPERINFONET IT SOLUTIONS PVT. LTD.');
$introHeading = get_setting('intro_heading', 'Website Development & Management Software Company Serving All India');
$introDesc = get_setting('intro_description', 'Professional websites, institute management software, school and college ERP, NGO portals, billing solutions, e-commerce and custom PHP/MySQL web software for organizations across India.');

$stat1Num = get_setting('stat1_number', '1500+');
$stat1Label = get_setting('stat1_label', 'Happy Clients');
$stat2Num = get_setting('stat2_number', '2000+');
$stat2Label = get_setting('stat2_label', 'Web Projects');
$stat3Num = get_setting('stat3_number', '25+');
$stat3Label = get_setting('stat3_label', 'Software Modules');
$stat4Num = get_setting('stat4_number', '24×7');
$stat4Label = get_setting('stat4_label', 'Support Ready');

$sectorEyebrow = get_setting('sector_eyebrow', 'WE BUILD FOR EVERY SECTOR');
$sectorHeading = get_setting('sector_heading', 'Websites & Management Software for Every Business Type');
$sectorDesc = get_setting('sector_description', 'From education and NGOs to healthcare, billing, e-commerce and custom business portals.');

$sec1Title = get_setting('sec1_title', 'School Website');
$sec1Desc = get_setting('sec1_desc', 'Admission, academics, notices, gallery, results and school information.');
$sec2Title = get_setting('sec2_title', 'College Website');
$sec2Desc = get_setting('sec2_desc', 'Departments, courses, admission, faculty, notices and student services.');
$sec3Title = get_setting('sec3_title', 'Computer Institute');
$sec3Desc = get_setting('sec3_desc', 'Courses, admission, student login, exam, marksheet and certificate.');
$sec4Title = get_setting('sec4_title', 'Fire & Safety Institute');
$sec4Desc = get_setting('sec4_desc', 'Training courses, admission, certification and institute ERP.');
$sec5Title = get_setting('sec5_title', 'Paramedical Institute');
$sec5Desc = get_setting('sec5_desc', 'Courses, students, examinations, marksheets and certificates.');
$sec6Title = get_setting('sec6_title', 'Coaching Institute');
$sec6Desc = get_setting('sec6_desc', 'Batches, test series, student portal, fee alerts and enquiry system.');
$sec7Title = get_setting('sec7_title', 'NGO & Social Trust');
$sec7Desc = get_setting('sec7_desc', 'Projects, donation receipts, 80G tax exemptions, and volunteer management.');
$sec8Title = get_setting('sec8_title', 'Hospital & Clinic Software');
$sec8Desc = get_setting('sec8_desc', 'OPD doctor appointments, patient history, prescriptions and billing.');

// Fetch Slides from Database
$slides = [];
try {
    $slides = $pdo->query("SELECT * FROM frontend_slides WHERE status = 'active' ORDER BY sort_order ASC, id ASC")->fetchAll();
} catch (Exception $e) {}

if (empty($slides)) {
    $slides = [[
        'eyebrow' => $heroEyebrow,
        'title' => $heroTitle,
        'subtitle' => $heroSubtitle,
        'btn1_text' => $heroBtn1Text,
        'btn1_link' => $heroBtn1Link,
        'btn2_text' => $heroBtn2Text,
        'btn2_link' => $heroBtn2Link,
        'image_path' => $heroBanner
    ]];
}

// Fetch Featured Templates & Demos
$templates = $pdo->query("SELECT * FROM templates ORDER BY sort_order ASC LIMIT 6")->fetchAll();

$pageTitle = 'Website Development & Management Software Company in India | HyperInfonet';
$pageDescription = 'Website development, institute ERP, school management software, NGO portals, billing, e-commerce and custom web software services across India by HyperInfonet.';
require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. HERO 3D INTERACTIVE SLIDER CAROUSEL -->
<section id="home" class="hero-slider-wrap">
    <div class="hero-slider-container" id="heroCarousel">
        <?php foreach ($slides as $idx => $slide): ?>
            <?php
            $bgStyle = '';
            if (!empty($slide['image_path']) && file_exists(ROOT_PATH . DIRECTORY_SEPARATOR . $slide['image_path'])) {
                $bgStyle = 'style="background-image: url(' . BASE_URL . '/' . e($slide['image_path']) . ');"';
            }
            ?>
            <div class="hero-slide <?= $idx === 0 ? 'active' : '' ?>" <?= $bgStyle ?> data-slide-index="<?= $idx ?>">
                <div class="hero-slide-overlay"></div>
                <div class="container" style="position: relative; z-index: 3;">
                    <div class="hero-slide-inner">
                        <span class="eyebrow light" style="margin-bottom: 12px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="bi bi-patch-check-fill text-warning"></i> <?= e($slide['eyebrow'] ?: 'HYPERINFONET IT SOLUTIONS PVT. LTD.') ?>
                        </span>
                        <h1><?= e($slide['title']) ?></h1>
                        <p><?= e($slide['subtitle']) ?></p>
                        <div style="display: flex; flex-wrap: wrap; gap: 14px;">
                            <?php if (!empty($slide['btn1_text'])): ?>
                                <a class="btn btn-brand rounded-pill px-4 btn-lg" href="<?= e($slide['btn1_link'] ?: '#contact') ?>">
                                    <i class="bi bi-play-circle-fill"></i> <?= e($slide['btn1_text']) ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($slide['btn2_text'])): ?>
                                <a class="btn btn-outline-brand rounded-pill px-4 btn-lg" href="<?= e($slide['btn2_link'] ?: '#solutions') ?>" style="background: transparent; color: #ffffff; border-color: rgba(255,255,255,0.6);">
                                    <i class="bi bi-grid-fill"></i> <?= e($slide['btn2_text']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (count($slides) > 1): ?>
            <!-- 3D Navigation Controls -->
            <button class="hero-slider-prev" id="heroPrevBtn" aria-label="Previous Slide">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button class="hero-slider-next" id="heroNextBtn" aria-label="Next Slide">
                <i class="bi bi-chevron-right"></i>
            </button>

            <!-- Slide Dots -->
            <div class="hero-slider-dots" id="heroDots">
                <?php for ($i = 0; $i < count($slides); $i++): ?>
                    <div class="hero-slider-dot <?= $i === 0 ? 'active' : '' ?>" data-slide-target="<?= $i ?>"></div>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 2. SEO HOME INTRO -->
<section class="seo-home-intro">
    <div class="container text-center">
        <span class="eyebrow"><?= e($introEyebrow) ?></span>
        <h1 style="font-size: 38px; font-weight: 900; margin: 10px 0 14px; letter-spacing: -1.2px;">
            <?= e($introHeading) ?>
        </h1>
        <p style="color: var(--muted); max-width: 820px; margin: 0 auto; font-size: 16px;">
            <?= e($introDesc) ?>
        </p>
    </div>
</section>

<!-- 3. QUICK STATS GRADIENT BAR -->
<section class="quick-stats">
    <div class="container">
        <div class="stats-grid">
            <div>
                <i class="bi bi-people-fill"></i>
                <strong><?= e($stat1Num) ?></strong>
                <span><?= e($stat1Label) ?></span>
            </div>
            <div>
                <i class="bi bi-window-stack"></i>
                <strong><?= e($stat2Num) ?></strong>
                <span><?= e($stat2Label) ?></span>
            </div>
            <div>
                <i class="bi bi-grid-1x2-fill"></i>
                <strong><?= e($stat3Num) ?></strong>
                <span><?= e($stat3Label) ?></span>
            </div>
            <div>
                <i class="bi bi-headset"></i>
                <strong><?= e($stat4Num) ?></strong>
                <span><?= e($stat4Label) ?></span>
            </div>
        </div>
    </div>
</section>

<!-- 4. SECTOR SECTION: WE BUILD FOR EVERY SECTOR -->
<section class="section-sm sector-section soft-bg">
    <div class="container">
        <div class="section-head compact">
            <span class="eyebrow"><?= e($sectorEyebrow) ?></span>
            <h2><?= e($sectorHeading) ?></h2>
            <p><?= e($sectorDesc) ?></p>
        </div>

        <div class="type-grid">
            <div class="type-card color-card color-1">
                <i class="bi bi-building main-icon"></i>
                <h3><?= e($sec1Title) ?></h3>
                <p><?= e($sec1Desc) ?></p>
            </div>
            <div class="type-card color-card color-2">
                <i class="bi bi-bank main-icon"></i>
                <h3><?= e($sec2Title) ?></h3>
                <p><?= e($sec2Desc) ?></p>
            </div>
            <div class="type-card color-card color-3">
                <i class="bi bi-pc-display main-icon"></i>
                <h3><?= e($sec3Title) ?></h3>
                <p><?= e($sec3Desc) ?></p>
            </div>
            <div class="type-card color-card color-4">
                <i class="bi bi-fire main-icon"></i>
                <h3><?= e($sec4Title) ?></h3>
                <p><?= e($sec4Desc) ?></p>
            </div>
            <div class="type-card color-card color-5">
                <i class="bi bi-heart-pulse main-icon"></i>
                <h3><?= e($sec5Title) ?></h3>
                <p><?= e($sec5Desc) ?></p>
            </div>
            <div class="type-card color-card color-6">
                <i class="bi bi-journal-check main-icon"></i>
                <h3><?= e($sec6Title) ?></h3>
                <p><?= e($sec6Desc) ?></p>
            </div>
            <div class="type-card color-card color-1">
                <i class="bi bi-heart main-icon"></i>
                <h3><?= e($sec7Title) ?></h3>
                <p><?= e($sec7Desc) ?></p>
            </div>
            <div class="type-card color-card color-2">
                <i class="bi bi-hospital main-icon"></i>
                <h3><?= e($sec8Title) ?></h3>
                <p><?= e($sec8Desc) ?></p>
            </div>
            <div class="type-card color-card color-4">
                <i class="bi bi-briefcase main-icon"></i>
                <h3>Business / Corporate</h3>
                <p>Professional company profile, services, portfolio and leads.</p>
            </div>
            <div class="type-card color-card color-5">
                <i class="bi bi-bag-check main-icon"></i>
                <h3>E-commerce Store</h3>
                <p>Products, cart, checkout, payment and order management.</p>
            </div>
            <div class="type-card color-card color-6">
                <i class="bi bi-receipt main-icon"></i>
                <h3>Billing / Inventory</h3>
                <p>Billing, inventory, customers, payments and reports.</p>
            </div>
            <div class="type-card color-card color-1">
                <i class="bi bi-houses main-icon"></i>
                <h3>Real Estate Website</h3>
                <p>Property listings, projects, agents, leads and enquiry forms.</p>
            </div>
            <div class="type-card color-card color-2">
                <i class="bi bi-building-fill-check main-icon"></i>
                <h3>Hotel / Restaurant</h3>
                <p>Rooms, menu, booking enquiries, gallery and contact.</p>
            </div>
            <div class="type-card color-card color-3">
                <i class="bi bi-newspaper main-icon"></i>
                <h3>News / E-paper</h3>
                <p>Categories, articles, editions, media and admin publishing.</p>
            </div>
            <div class="type-card color-card color-4">
                <i class="bi bi-calendar-event main-icon"></i>
                <h3>Event / Booking Portal</h3>
                <p>Events, booking, payment, ticket and QR verification.</p>
            </div>
            <div class="type-card color-card color-5">
                <i class="bi bi-person-vcard main-icon"></i>
                <h3>Membership Portal</h3>
                <p>Registration, member ID, card, receipt and verification.</p>
            </div>
            <div class="type-card color-card color-6">
                <i class="bi bi-shop main-icon"></i>
                <h3>Retailer / Distributor Portal</h3>
                <p>Network users, wallet, transactions, KYC and reports.</p>
            </div>
            <div class="type-card color-card color-1">
                <i class="bi bi-person-workspace main-icon"></i>
                <h3>Job / Recruitment Portal</h3>
                <p>Jobs, applications, employer and candidate workflows.</p>
            </div>
            <div class="type-card color-card color-2">
                <i class="bi bi-airplane main-icon"></i>
                <h3>Travel / Tour Website</h3>
                <p>Packages, destinations, booking enquiry and gallery.</p>
            </div>
            <div class="type-card color-card color-3">
                <i class="bi bi-briefcase-fill main-icon"></i>
                <h3>Law / Professional Firm</h3>
                <p>Professional profile, services, team, articles and leads.</p>
            </div>
            <div class="type-card color-card color-4">
                <i class="bi bi-buildings-fill main-icon"></i>
                <h3>Construction Company</h3>
                <p>Projects, services, gallery, company profile and enquiry.</p>
            </div>
            <div class="type-card color-card color-5">
                <i class="bi bi-stars main-icon"></i>
                <h3>Beauty / Salon / Academy</h3>
                <p>Services, courses, bookings, gallery and offers.</p>
            </div>
            <div class="type-card color-card color-6">
                <i class="bi bi-code-square main-icon"></i>
                <h3>Custom Web Portal</h3>
                <p>Any custom workflow, dashboard, database and reporting portal.</p>
            </div>
        </div>
    </div>
</section>

<!-- 5. CORE SOLUTIONS SECTION (Matches Screenshot with 100% Accuracy) -->
<section class="section core-solutions-section" id="solutions">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow"><i class="bi bi-stars"></i> OUR CORE SOLUTIONS</span>
            <h2>One Technology Partner for <span>Website, Software &amp; Automation</span></h2>
            <p>Complete digital solutions for schools, colleges, institutes, NGOs, businesses, healthcare, billing, e-commerce and custom workflows.</p>
        </div>

        <!-- Core Visual Card -->
        <div class="core-visual-grid">
            <div class="core-image-card">
                <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); height: 320px; display: flex; align-items: center; justify-content: center; color: #ffffff;">
                    <i class="bi bi-speedometer2" style="font-size: 64px; opacity: 0.7;"></i>
                </div>
                <div class="core-image-badge">
                    <i class="bi bi-patch-check-fill"></i>
                    <span>
                        <b>Complete Solution</b>
                        <small>Website + Admin + Management Software</small>
                    </span>
                </div>
            </div>

            <div class="core-copy-card">
                <span class="eyebrow" style="font-size: 11px;">BUILT AROUND YOUR WORKFLOW</span>
                <h3>Everything your organization needs in one connected platform.</h3>
                <p>We combine a professional public website with secure management panels, automation, reports and communication tools.</p>
                
                <div class="core-points">
                    <span><i class="bi bi-check2-circle"></i> Responsive Dynamic Website</span>
                    <span><i class="bi bi-check2-circle"></i> Admin &amp; Branch Controls</span>
                    <span><i class="bi bi-check2-circle"></i> Secure Database Management</span>
                    <span><i class="bi bi-check2-circle"></i> Reports &amp; Print Documents</span>
                    <span><i class="bi bi-check2-circle"></i> Payment &amp; WhatsApp Ready</span>
                    <span><i class="bi bi-check2-circle"></i> Custom Modules &amp; Integrations</span>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px;">
                    <a class="btn btn-brand rounded-pill px-4" href="#contact">Request Free Demo</a>
                    <a class="btn btn-outline-brand rounded-pill px-4" href="<?= BASE_URL ?>/public/services.php">Explore Services</a>
                </div>
            </div>
        </div>

        <!-- 12-Card Colorful Service Grid (Exact match to User's Uploaded Screenshot) -->
        <div class="service-color-grid">
            <!-- Row 1 -->
            <a class="service-card color-card color-1" href="<?= BASE_URL ?>/site/index.php?demo=apex-coaching-institute" target="_blank">
                <div class="service-icon"><i class="bi bi-mortarboard-fill"></i></div>
                <h3>Institute Website &amp; Management Software</h3>
                <p>Complete dynamic website and ERP for Computer, Fire &amp; Safety, Paramedical, Skill, Coaching and Training Institutes.</p>
                <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
            </a>

            <a class="service-card color-card color-2" href="<?= BASE_URL ?>/site/index.php?demo=apex-coaching-institute" target="_blank">
                <div class="service-icon"><i class="bi bi-building-fill"></i></div>
                <h3>School Management System</h3>
                <p>School ERP for admission, fees, students, attendance, examination, result and communication.</p>
                <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
            </a>

            <a class="service-card color-card color-3" href="<?= BASE_URL ?>/site/index.php?demo=apex-coaching-institute" target="_blank">
                <div class="service-icon"><i class="bi bi-bank2"></i></div>
                <h3>College Management System</h3>
                <p>Digital college ERP for students, departments, courses, fees, examination and certificates.</p>
                <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
            </a>

            <!-- Row 2 -->
            <a class="service-card color-card color-4" href="<?= BASE_URL ?>/public/order.php?category=NGO">
                <div class="service-icon"><i class="bi bi-heart-fill"></i></div>
                <h3>NGO / Trust / Society Website &amp; Software</h3>
                <p>Professional NGO website with member, donation, project, event, receipt and certificate management.</p>
                <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
            </a>

            <a class="service-card color-card color-5" href="<?= BASE_URL ?>/public/order.php?category=Billing">
                <div class="service-icon"><i class="bi bi-receipt-cutoff"></i></div>
                <h3>Billing &amp; Inventory Software</h3>
                <p>Billing, customer, product, inventory, payment and business reporting solution.</p>
                <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
            </a>

            <a class="service-card color-card color-6" href="<?= BASE_URL ?>/public/templates.php">
                <div class="service-icon"><i class="bi bi-window-stack"></i></div>
                <h3>Website Development</h3>
                <p>Modern, fast, responsive and SEO-friendly dynamic website with admin CMS.</p>
                <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
            </a>

            <!-- Row 3 (Directly from User Screenshot) -->
            <a class="service-card color-card color-1" href="<?= BASE_URL ?>/public/order.php?category=E-Commerce">
                <div class="service-icon"><i class="bi bi-bag-check-fill"></i></div>
                <h3>E-commerce Website Development</h3>
                <p>Online store with products, categories, cart, checkout, payments, orders and admin control.</p>
                <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
            </a>

            <a class="service-card color-card color-2" href="<?= BASE_URL ?>/site/index.php?demo=careplus-health-clinic" target="_blank">
                <div class="service-icon"><i class="bi bi-hospital-fill"></i></div>
                <h3>Hospital / Clinic Website &amp; Management</h3>
                <p>Website and management modules for hospitals, clinics, doctors, appointments and patient services.</p>
                <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
            </a>

            <a class="service-card color-card color-3" href="<?= BASE_URL ?>/public/order.php?category=Multi-Branch">
                <div class="service-icon"><i class="bi bi-diagram-3-fill"></i></div>
                <h3>Multi-Branch / Franchise ERP</h3>
                <p>Central control for branches, admissions, users, fees, wallets and reports.</p>
                <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
            </a>

            <!-- Row 4 -->
            <a class="service-card color-card color-4" href="<?= BASE_URL ?>/public/order.php?category=CRM">
                <div class="service-icon"><i class="bi bi-people-fill"></i></div>
                <h3>CRM &amp; Enquiry Management</h3>
                <p>Lead, follow-up, enquiry, team and conversion tracking for service businesses.</p>
                <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
            </a>

            <a class="service-card color-card color-5" href="<?= BASE_URL ?>/public/order.php?category=Membership">
                <div class="service-icon"><i class="bi bi-person-vcard-fill"></i></div>
                <h3>Membership &amp; Certificate Portal</h3>
                <p>Member registration, approval, payment, ID card, QR verification and certificate portal.</p>
                <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
            </a>

            <a class="service-card color-card color-6" href="<?= BASE_URL ?>/public/order.php?category=Custom">
                <div class="service-icon"><i class="bi bi-code-slash"></i></div>
                <h3>Custom Web Portal &amp; Software</h3>
                <p>Custom PHP/MySQL portals, dashboards, workflows, reports and integrations according to your process.</p>
                <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
            </a>
        </div>

        <div style="text-align: center; margin-top: 36px;">
            <a class="btn btn-outline-brand rounded-pill px-4" href="<?= BASE_URL ?>/public/services.php">
                View All Services <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- 6. COMPLETE DIGITAL MANAGEMENT SECTION (20-Item Colorful Feature Grid) -->
<section class="section management-section soft-bg" id="features">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow"><i class="bi bi-grid-fill"></i> COMPLETE DIGITAL MANAGEMENT</span>
            <h2>From Admission to Certificate &mdash; <span>Manage Everything Digitally</span></h2>
            <p>Student lifecycle, fees, examinations, documents, attendance, payments, reports and communication in one secure system.</p>
        </div>

        <div class="management-highlight">
            <div class="management-copy">
                <div class="management-icon"><i class="bi bi-mortarboard-fill"></i></div>
                <div>
                    <h3>Institute Management Software</h3>
                    <p>Designed for Computer, Fire &amp; Safety, Paramedical, Skill, Coaching, Training, School and College operations.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a class="btn btn-light rounded-pill px-4" href="#contact" style="background: #ffffff; color: var(--ink);">
                    <i class="bi bi-play-circle-fill"></i> Book Free Demo
                </a>
                <a class="btn rounded-pill px-4" href="tel:+919044877444" style="background: transparent; color: #ffffff; border: 1.5px solid rgba(255,255,255,0.7);">
                    <i class="bi bi-telephone-fill"></i> Call 90448 77444
                </a>
            </div>
        </div>

        <div class="colorful-feature-grid">
            <div class="feature-item color-card color-1">
                <i class="bi bi-person-plus-fill main-icon"></i>
                <span><b>Online Student Admission</b><small>Digital admission form and student record creation.</small></span>
            </div>
            <div class="feature-item color-card color-2">
                <i class="bi bi-person-badge-fill main-icon"></i>
                <span><b>Student Login Panel</b><small>Secure access for student information and services.</small></span>
            </div>
            <div class="feature-item color-card color-3">
                <i class="bi bi-diagram-3-fill main-icon"></i>
                <span><b>Admin &amp; Branch Panel</b><small>Central admin with branch-wise access and controls.</small></span>
            </div>
            <div class="feature-item color-card color-4">
                <i class="bi bi-receipt-cutoff main-icon"></i>
                <span><b>Fee Management &amp; Receipt</b><small>Fee collection, dues, discounts and printable receipts.</small></span>
            </div>
            <div class="feature-item color-card color-5">
                <i class="bi bi-person-vcard-fill main-icon"></i>
                <span><b>ID Card Generation</b><small>Generate printable student or member ID cards.</small></span>
            </div>
            <div class="feature-item color-card color-6">
                <i class="bi bi-file-earmark-person-fill main-icon"></i>
                <span><b>Admit Card</b><small>Create exam admit cards with student details.</small></span>
            </div>
            <div class="feature-item color-card color-1">
                <i class="bi bi-award-fill main-icon"></i>
                <span><b>Marksheet &amp; Certificate</b><small>Generate result documents and certificates.</small></span>
            </div>
            <div class="feature-item color-card color-2">
                <i class="bi bi-laptop-fill main-icon"></i>
                <span><b>Online Examination</b><small>Online test and examination modules.</small></span>
            </div>
            <div class="feature-item color-card color-3">
                <i class="bi bi-calendar-check-fill main-icon"></i>
                <span><b>Attendance Management</b><small>Student or staff attendance records and reports.</small></span>
            </div>
            <div class="feature-item color-card color-4">
                <i class="bi bi-collection-fill main-icon"></i>
                <span><b>Course &amp; Batch Management</b><small>Manage courses, classes, sessions and batches.</small></span>
            </div>
            <div class="feature-item color-card color-5">
                <i class="bi bi-qr-code main-icon"></i>
                <span><b>Online / QR Payment</b><small>Payment workflow with QR or gateway integration readiness.</small></span>
            </div>
            <div class="feature-item color-card color-6">
                <i class="bi bi-chat-left-text-fill main-icon"></i>
                <span><b>Enquiry Management</b><small>Capture and follow up website or office enquiries.</small></span>
            </div>
            <div class="feature-item color-card color-1">
                <i class="bi bi-bar-chart-fill main-icon"></i>
                <span><b>Student Search &amp; Reports</b><small>Fast search, filters and useful reports.</small></span>
            </div>
            <div class="feature-item color-card color-2">
                <i class="bi bi-whatsapp main-icon"></i>
                <span><b>WhatsApp Integration</b><small>Call and WhatsApp actions throughout the workflow.</small></span>
            </div>
            <div class="feature-item color-card color-3">
                <i class="bi bi-phone-fill main-icon"></i>
                <span><b>Mobile Friendly Website</b><small>Responsive design for mobile, tablet and desktop.</small></span>
            </div>
            <div class="feature-item color-card color-4">
                <i class="bi bi-shield-lock-fill main-icon"></i>
                <span><b>SSL &amp; Secure Login</b><small>Secure login foundation and HTTPS-ready deployment.</small></span>
            </div>
            <div class="feature-item color-card color-5">
                <i class="bi bi-people-fill main-icon"></i>
                <span><b>Multi User Roles</b><small>Different permissions for admin, branch and staff.</small></span>
            </div>
            <div class="feature-item color-card color-6">
                <i class="bi bi-printer-fill main-icon"></i>
                <span><b>Custom Print Formats</b><small>Receipt, ID, marksheet and certificate formats.</small></span>
            </div>
            <div class="feature-item color-card color-1">
                <i class="bi bi-search main-icon"></i>
                <span><b>SEO Friendly Website</b><small>Search-friendly page structure and metadata.</small></span>
            </div>
            <div class="feature-item color-card color-2">
                <i class="bi bi-cloud-check-fill main-icon"></i>
                <span><b>Domain &amp; Hosting Support</b><small>Deployment support for domain, hosting and SSL.</small></span>
            </div>
        </div>
    </div>
</section>

<!-- 7. WHY CHOOSE SECTION -->
<section class="section why-section">
    <div class="container">
        <div class="why-section-grid">
            <div class="why-card">
                <span class="eyebrow light">WHY HYPERINFONET</span>
                <h2>Modern design, practical software and dependable long-term support.</h2>
                <p>We do more than build attractive websites. We create a complete digital ecosystem for enquiries, admissions, billing, student management, documents, reports and daily operations.</p>
                <div class="pill-grid">
                    <span><i class="bi bi-check-circle-fill"></i> SEO Friendly</span>
                    <span><i class="bi bi-check-circle-fill"></i> Mobile Responsive</span>
                    <span><i class="bi bi-check-circle-fill"></i> SSL Ready</span>
                    <span><i class="bi bi-check-circle-fill"></i> Multi Branch</span>
                    <span><i class="bi bi-check-circle-fill"></i> WhatsApp Ready</span>
                    <span><i class="bi bi-check-circle-fill"></i> Custom Development</span>
                </div>
            </div>

            <div class="support-card">
                <div class="support-icon"><i class="bi bi-headset"></i></div>
                <h3>Need custom software?</h3>
                <p>Get modules, reports, receipts, ID cards, marksheets, certificates and dashboards built around your existing workflow.</p>
                <a href="https://wa.me/919044877444?text=I%20need%20custom%20software" target="_blank" class="btn btn-light rounded-pill" style="background: #ffffff; color: var(--brand-red);">
                    Discuss Requirement
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 8. PAN-INDIA COVERAGE SECTION -->
<section class="section soft-bg national-seo-section" id="service-areas">
    <div class="container">
        <div class="national-seo-grid">
            <div>
                <span class="eyebrow">PAN-INDIA SERVICE COVERAGE</span>
                <h2 style="font-size: 36px; font-weight: 900; margin: 10px 0 14px; letter-spacing: -1px;">
                    Website &amp; Software Development <span style="color: var(--brand-blue);">for Organizations Across India</span>
                </h2>
                <p style="color: var(--muted); font-size: 15px; line-height: 1.7;">
                    HyperInfonet provides website development, institute management software, school ERP, NGO portals, billing software, e-commerce and custom web applications to organizations across India through remote project delivery and dedicated technical support.
                </p>
                <div class="seo-city-cloud">
                    <span>Delhi NCR</span><span>Mumbai</span><span>Pune</span><span>Bengaluru</span><span>Chennai</span><span>Hyderabad</span><span>Kolkata</span><span>Ahmedabad</span><span>Jaipur</span><span>Patna</span><span>Ranchi</span><span>Bhubaneswar</span><span>Guwahati</span><span>Lucknow</span><span>Noida</span><span>and all India</span>
                </div>
            </div>

            <div>
                <div class="sticky-card">
                    <span class="eyebrow">INDIA SERVICE AREAS</span>
                    <h3>Need a project anywhere in India?</h3>
                    <p>Requirement discussion, demo, development, deployment and support are completely handled online with 100% uptime guarantees.</p>
                    <a class="btn btn-brand w-100" href="#contact" style="width: 100%;">
                        Book Online Consultation <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 9. CLIENTS SECTION -->
<section class="section clients-section" id="clients">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">TRUSTED CLIENTS</span>
            <h2>Growing Organizations <span>Choose HyperInfonet</span></h2>
            <p>Organizations powered by our website and cloud management software.</p>
        </div>

        <div class="client-grid">
            <div class="client-card">
                <div class="client-letter">A</div>
                <b>AIIT COMPUTER</b>
                <small>Institute</small>
            </div>
            <div class="client-card">
                <div class="client-letter">I</div>
                <b>ITLS ACADEMY</b>
                <small>Institute</small>
            </div>
            <div class="client-card">
                <div class="client-letter">T</div>
                <b>TECH HSM INSTITUTE</b>
                <small>Institute</small>
            </div>
            <div class="client-card">
                <div class="client-letter">S</div>
                <b>Sharma Sweets</b>
                <small>Restaurant Portal</small>
            </div>
            <div class="client-card">
                <div class="client-letter">N</div>
                <b>National Fire Safety</b>
                <small>Fire Institute</small>
            </div>
            <div class="client-card">
                <div class="client-letter">G</div>
                <b>Glamour Salon &amp; Spa</b>
                <small>Salon Client</small>
            </div>
        </div>
    </div>
</section>

<!-- 10. FAQ SECTION -->
<section class="section faq-section soft-bg">
    <div class="container">
        <div class="faq-grid">
            <div>
                <span class="eyebrow">FAQ</span>
                <h2 style="font-size: 38px; font-weight: 900; margin: 10px 0 14px; letter-spacing: -1.2px;">
                    Common Questions Before You <span>Start Your Project</span>
                </h2>
                <p style="color: var(--muted); font-size: 15px; line-height: 1.7;">
                    Quick answers about websites, management software, hosting, customization, payments and support.
                </p>
            </div>

            <div>
                <div class="faq-accordion">
                    <div class="accordion-item">
                        <button class="accordion-button active" data-target="faq1">
                            Can you provide both website and management software?
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div id="faq1" class="accordion-body active">
                            Yes. The website and management software are provided together so your public website, student enquiries, fees and internal operations work as one unified digital system.
                        </div>
                    </div>

                    <div class="accordion-item">
                        <button class="accordion-button" data-target="faq2">
                            Can the software be customized for our institute or business?
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div id="faq2" class="accordion-body">
                            Yes. Modules, admission fields, fee structures, receipts, ID cards, marksheets, certificates, user roles and workflows can be customized according to your exact requirements.
                        </div>
                    </div>

                    <div class="accordion-item">
                        <button class="accordion-button" data-target="faq3">
                            Will the website work on mobile phones?
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div id="faq3" class="accordion-body">
                            Yes. The website design is 100% responsive and optimized for desktop, tablet, and 4G/5G mobile devices.
                        </div>
                    </div>

                    <div class="accordion-item">
                        <button class="accordion-button" data-target="faq4">
                            Can banners, services, clients and testimonials be changed later?
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div id="faq4" class="accordion-body">
                            Yes. Every website package includes an intuitive zero-code admin CMS where content, photos, prices, and settings can be edited anytime.
                        </div>
                    </div>

                    <div class="accordion-item">
                        <button class="accordion-button" data-target="faq5">
                            Do you support domain, hosting and SSL setup?
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div id="faq5" class="accordion-body">
                            Yes. Complete deployment support including custom domain setup, cloud hosting configuration, and automated SSL HTTPS certificates is included.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 11. CONTACT & DEMO REQUEST FORM SECTION -->
<section class="section contact-section" id="contact">
    <div class="container">
        <div class="contact-shell">
            <div class="contact-copy">
                <span class="eyebrow light">GET A FREE DEMO</span>
                <h2>Make Your Organization Professional, Connected and Fully Digital.</h2>
                <p>Share your requirement and our team will contact you with suitable website, software, modules and pricing options.</p>
                <div class="contact-points">
                    <a href="tel:+919044877444">
                        <i class="bi bi-telephone-fill"></i>
                        <span><small>Call / WhatsApp</small>+91 90448 77444</span>
                    </a>
                    <a href="mailto:support@vishalwebstudio.com">
                        <i class="bi bi-envelope-fill"></i>
                        <span><small>Email</small>support@vishalwebstudio.com</span>
                    </a>
                    <div>
                        <i class="bi bi-geo-alt-fill"></i>
                        <span><small>Office</small>656 6/200, Unity City Colony, Kalyanpur (West), Lucknow, Uttar Pradesh – 226022</span>
                    </div>
                </div>
            </div>

            <form action="<?= BASE_URL ?>/public/order.php" method="GET" class="lead-form" id="demo">
                <h3>Request Demo / Quotation</h3>
                <p>Fill in your details and our team will contact you.</p>

                <div class="form-grid-2">
                    <div>
                        <label>Name *</label>
                        <input class="form-control" name="owner_name" placeholder="Your Name" required>
                    </div>
                    <div>
                        <label>Mobile *</label>
                        <input class="form-control" name="phone" inputmode="numeric" placeholder="Mobile Number" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div>
                        <label>Business / Institute Name</label>
                        <input class="form-control" name="business_name" placeholder="e.g. Apex Institute">
                    </div>
                    <div>
                        <label>Service Required</label>
                        <select class="form-select" name="category">
                            <option value="Website + Management Software">Website + Management Software</option>
                            <option value="School / College ERP">School / College ERP</option>
                            <option value="Institute Management Software">Institute Management Software</option>
                            <option value="NGO Website / Software">NGO Website / Software</option>
                            <option value="Billing / Business Software">Billing / Business Software</option>
                            <option value="E-commerce Website">E-commerce Website</option>
                            <option value="Custom Portal / Software">Custom Portal / Software</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Requirement</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Tell us about your project..."></textarea>
                </div>

                <button type="submit" class="btn btn-brand btn-lg w-100" style="width: 100%;">
                    Send Enquiry <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</section>

<!-- 3D Hero Slider JavaScript Engine -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('#heroCarousel .hero-slide');
    const dots = document.querySelectorAll('#heroDots .hero-slider-dot');
    const prevBtn = document.getElementById('heroPrevBtn');
    const nextBtn = document.getElementById('heroNextBtn');
    
    if (slides.length <= 1) return;

    let currentIndex = 0;
    let autoSlideTimer = null;

    function showSlide(index) {
        if (index < 0) index = slides.length - 1;
        if (index >= slides.length) index = 0;
        currentIndex = index;

        slides.forEach((s, idx) => {
            s.classList.toggle('active', idx === currentIndex);
        });
        dots.forEach((d, idx) => {
            d.classList.toggle('active', idx === currentIndex);
        });
    }

    function nextSlide() {
        showSlide(currentIndex + 1);
    }

    function prevSlide() {
        showSlide(currentIndex - 1);
    }

    function startAutoSlide() {
        stopAutoSlide();
        autoSlideTimer = setInterval(nextSlide, 5000);
    }

    function stopAutoSlide() {
        if (autoSlideTimer) clearInterval(autoSlideTimer);
    }

    if (nextBtn) nextBtn.addEventListener('click', function(e) { e.preventDefault(); nextSlide(); startAutoSlide(); });
    if (prevBtn) prevBtn.addEventListener('click', function(e) { e.preventDefault(); prevSlide(); startAutoSlide(); });

    dots.forEach(dot => {
        dot.addEventListener('click', function() {
            const target = parseInt(this.dataset.slideTarget);
            showSlide(target);
            startAutoSlide();
        });
    });

    const carousel = document.getElementById('heroCarousel');
    if (carousel) {
        carousel.addEventListener('mouseenter', stopAutoSlide);
        carousel.addEventListener('mouseleave', startAutoSlide);
    }

    startAutoSlide();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
