<?php
/**
 * Vishal Web Studio - Services & Solutions Showcase (HyperInfonet 100% Replication Design)
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pageTitle = 'All Website & Software Services | HyperInfonet';
$pageDescription = 'Discover our full suite of digital solutions: Institute Management Software, School & College ERP, NGO Portals, Billing Software, E-Commerce, and Custom Web Applications.';

$services = [
    [
        'title' => 'Institute Website & Management Software',
        'icon' => 'bi bi-mortarboard-fill',
        'desc' => 'Complete dynamic website and ERP for Computer, Fire & Safety, Paramedical, Skill, Coaching and Training Institutes with admissions, fees, ID cards, and marksheets.',
        'color' => 'color-1',
        'demo_slug' => 'apex-coaching-institute'
    ],
    [
        'title' => 'School Management System',
        'icon' => 'bi bi-building-fill',
        'desc' => 'School ERP for online admissions, monthly fee collection, student attendance, examination results, notice board, and parent communication.',
        'color' => 'color-2',
        'demo_slug' => 'apex-coaching-institute'
    ],
    [
        'title' => 'College Management System',
        'icon' => 'bi bi-bank2',
        'desc' => 'Digital college ERP for departments, semester courses, fee structures, faculty management, student examinations, and degree certificates.',
        'color' => 'color-3',
        'demo_slug' => 'apex-coaching-institute'
    ],
    [
        'title' => 'NGO / Trust / Society Website & Software',
        'icon' => 'bi bi-heart-fill',
        'desc' => 'Professional NGO website with member registration, online donations, project updates, events, 80G tax receipts, and volunteer certificates.',
        'color' => 'color-4',
        'demo_slug' => 'restaurant-delight'
    ],
    [
        'title' => 'Billing & Inventory Software',
        'icon' => 'bi bi-receipt-cutoff',
        'desc' => 'GST billing, barcode generator, product catalog, customer balance tracking, supplier ledger, and financial reporting software.',
        'color' => 'color-5',
        'demo_slug' => 'restaurant-delight'
    ],
    [
        'title' => 'Website Development',
        'icon' => 'bi bi-window-stack',
        'desc' => 'Modern, fast, responsive and SEO-friendly dynamic business website with zero-code admin CMS and automated WhatsApp lead intake.',
        'color' => 'color-6',
        'demo_slug' => 'nexus-digital-agency'
    ],
    [
        'title' => 'E-commerce Website Development',
        'icon' => 'bi bi-bag-check-fill',
        'desc' => 'Online store with product catalog, categories, shopping cart, checkout, payment gateway, orders, and customer admin control.',
        'color' => 'color-1',
        'demo_slug' => 'glamour-salon-spa'
    ],
    [
        'title' => 'Hospital / Clinic Website & Management',
        'icon' => 'bi bi-hospital-fill',
        'desc' => 'Medical portal with doctor credentials, OPD consultation timings, patient appointment booking, diagnostics list, and emergency hotline.',
        'color' => 'color-2',
        'demo_slug' => 'careplus-health-clinic'
    ],
    [
        'title' => 'Multi-Branch / Franchise ERP',
        'icon' => 'bi bi-diagram-3-fill',
        'desc' => 'Central administration for multiple branches, franchisee franchise fees, admissions, student wallets, and consolidated audit reports.',
        'color' => 'color-3',
        'demo_slug' => 'apex-coaching-institute'
    ],
    [
        'title' => 'CRM & Enquiry Management',
        'icon' => 'bi bi-people-fill',
        'desc' => 'Capture, assign, and follow up website and phone inquiries with team task boards and conversion pipeline reporting.',
        'color' => 'color-4',
        'demo_slug' => 'nexus-digital-agency'
    ],
    [
        'title' => 'Membership & Certificate Portal',
        'icon' => 'bi bi-person-vcard-fill',
        'desc' => 'Member registration, approval workflow, dues payment, printable membership card, QR verification, and certificate generator.',
        'color' => 'color-5',
        'demo_slug' => 'prime-real-estate'
    ],
    [
        'title' => 'Custom Web Portal & Software',
        'icon' => 'bi bi-code-slash',
        'desc' => 'Bespoke PHP/MySQL web software, custom database workflows, client dashboards, API integrations, and customized user role portals.',
        'color' => 'color-6',
        'demo_slug' => 'nexus-digital-agency'
    ]
];

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Title Banner -->
<div class="page-banner">
    <div class="container text-center">
        <span class="eyebrow light"><i class="bi bi-grid-fill"></i> ALL SERVICES &amp; ERP SOLUTIONS</span>
        <h1>Specialized Website &amp; Management Software Services</h1>
        <p>
            Explore our complete suite of ready-to-deploy software and dynamic web solutions designed for institutions and businesses across India.
        </p>
    </div>
</div>

<!-- Services Grid (Matches Screenshot) -->
<section class="section soft-bg">
    <div class="container">
        <div class="service-color-grid">
            <?php foreach ($services as $svc): ?>
                <a class="service-card color-card <?= e($svc['color']) ?>" href="<?= BASE_URL ?>/site/index.php?demo=<?= e($svc['demo_slug']) ?>" target="_blank">
                    <div class="service-icon"><i class="<?= e($svc['icon']) ?>"></i></div>
                    <h3><?= e($svc['title']) ?></h3>
                    <p><?= e($svc['desc']) ?></p>
                    <span class="details-link">View details <i class="bi bi-arrow-up-right"></i></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
