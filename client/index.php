<?php
/**
 * Vishal Web Studio - Client Admin Overview Dashboard (Tenant-Isolated)
 * 2x4 Mini Design System with 8 High-Conversion Metric Cards
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_client();

$client = get_current_client_record();
$website = get_current_client_website();
$pdo = db();

if (!$client) {
    $pageTitle = 'Client Dashboard';
    $clientNav = 'dashboard';
    require_once dirname(__DIR__) . '/includes/client_header.php';
    echo '<div class="card" style="padding: 40px; text-align: center; margin: 30px auto; max-width: 600px;">
            <div style="font-size: 2.5rem; color: var(--secondary); margin-bottom: 12px;"><i class="fas fa-user-lock"></i></div>
            <h3>No Client Account Linked</h3>
            <p class="text-muted">Please log in using a client account, or select a client from the Super Admin CRM.</p>
            <a href="' . BASE_URL . '/super-admin/clients.php" class="btn btn-primary" style="margin-top: 15px;">Go to Clients CRM <i class="fas fa-arrow-right"></i></a>
          </div>';
    require_once dirname(__DIR__) . '/includes/dashboard_footer.php';
    exit;
}

$clientId = (int)$client['id'];

// Client-specific isolated metrics using prepared statements
$cStmt = $pdo->prepare("SELECT id, contract_number, status, token, price FROM contracts WHERE client_id = ? ORDER BY id DESC LIMIT 1");
$cStmt->execute([$clientId]);
$contract = $cStmt->fetch();

$iStmt = $pdo->prepare("SELECT id, invoice_number, status, total, paid_amount FROM invoices WHERE client_id = ? ORDER BY id DESC LIMIT 1");
$iStmt->execute([$clientId]);
$invoice = $iStmt->fetch();

$tStmt = $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE client_id = ? AND status IN ('open', 'in_progress')");
$tStmt->execute([$clientId]);
$openTickets = (int)$tStmt->fetchColumn();

$servicesCount = 0;
$galleryCount = 0;
$inquiriesCount = 0;
$pagesCount = 0;
if ($website && !empty($website['id'])) {
    $sStmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE website_id = ?");
    $sStmt->execute([$website['id']]);
    $servicesCount = (int)$sStmt->fetchColumn();

    $gStmt = $pdo->prepare("SELECT COUNT(*) FROM gallery WHERE website_id = ?");
    $gStmt->execute([$website['id']]);
    $galleryCount = (int)$gStmt->fetchColumn();

    $inqStmt = $pdo->prepare("SELECT COUNT(*) FROM client_inquiries WHERE website_id = ?");
    $inqStmt->execute([$website['id']]);
    $inquiriesCount = (int)$inqStmt->fetchColumn();

    $pgStmt = $pdo->prepare("SELECT COUNT(*) FROM website_pages WHERE website_id = ?");
    $pgStmt->execute([$website['id']]);
    $pagesCount = (int)$pgStmt->fetchColumn();
}

$pageTitle = 'Dashboard Overview';
$clientNav = 'dashboard';
require_once dirname(__DIR__) . '/includes/client_header.php';
?>

<!-- Welcome Banner -->
<div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; border-radius: var(--radius-lg); padding: 26px 30px; margin-bottom: 24px; box-shadow: var(--shadow-md); border: 1px solid #334155;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <span class="badge badge-success" style="margin-bottom: 8px;">Client Control Center</span>
            <h2 style="color: #ffffff; font-size: 1.7rem; font-weight: 900; margin-bottom: 4px;">Welcome, <?= e($client['owner_name']) ?>!</h2>
            <p style="color: #94a3b8; font-size: 0.92rem; margin: 0;">
                Managing <strong><?= e($client['business_name']) ?></strong> website content, menu catalog, invoices &amp; leads.
            </p>
        </div>

        <?php if ($website): ?>
            <div style="display: flex; gap: 10px;">
                <a href="<?= BASE_URL ?>/site/index.php?site=<?= urlencode($website['slug']) ?>" target="_blank" class="btn btn-primary btn-sm">
                    <i class="fas fa-external-link-alt"></i> Visit Live Website
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ==========================================================================
     2x4 MINI DASHBOARD DESIGN SYSTEM (8 Compact 3D Stat Tabs)
     ========================================================================== -->
<div class="dashboard-mini-grid">
    <!-- Row 1: Tab 1 - Website Status -->
    <div class="mini-stat-card">
        <div class="mini-stat-content">
            <div class="mini-stat-label">Website Status</div>
            <div class="mini-stat-val"><?= $website ? ucfirst($website['status']) : 'Pending' ?></div>
            <div class="mini-stat-badge" style="color: var(--success);"><i class="fas fa-lock"></i> SSL Secured</div>
        </div>
        <div class="mini-stat-icon blue"><i class="fas fa-globe"></i></div>
    </div>

    <!-- Row 1: Tab 2 - Services / Menu Items -->
    <div class="mini-stat-card accent-green">
        <div class="mini-stat-content">
            <div class="mini-stat-label">Menu &amp; Services</div>
            <div class="mini-stat-val"><?= $servicesCount ?> <small style="font-size: 0.8rem; font-weight: 700; color: #64748b;">Items</small></div>
            <div class="mini-stat-badge" style="color: var(--success);"><i class="fas fa-check-circle"></i> Catalog Active</div>
        </div>
        <div class="mini-stat-icon green"><i class="fas fa-utensils"></i></div>
    </div>

    <!-- Row 1: Tab 3 - Photo Gallery -->
    <div class="mini-stat-card accent-orange">
        <div class="mini-stat-content">
            <div class="mini-stat-label">Gallery Photos</div>
            <div class="mini-stat-val"><?= $galleryCount ?> <small style="font-size: 0.8rem; font-weight: 700; color: #64748b;">Photos</small></div>
            <div class="mini-stat-badge" style="color: var(--warning);"><i class="fas fa-camera"></i> High-Res Media</div>
        </div>
        <div class="mini-stat-icon orange"><i class="fas fa-images"></i></div>
    </div>

    <!-- Row 1: Tab 4 - Custom Pages -->
    <div class="mini-stat-card accent-purple">
        <div class="mini-stat-content">
            <div class="mini-stat-label">Custom Pages</div>
            <div class="mini-stat-val"><?= $pagesCount ?> <small style="font-size: 0.8rem; font-weight: 700; color: #64748b;">Pages</small></div>
            <div class="mini-stat-badge" style="color: #8b5cf6;"><i class="fas fa-layer-group"></i> SEO Indexed</div>
        </div>
        <div class="mini-stat-icon purple"><i class="fas fa-file-alt"></i></div>
    </div>

    <!-- Row 2: Tab 5 - Digital Contract -->
    <div class="mini-stat-card accent-pink">
        <div class="mini-stat-content">
            <div class="mini-stat-label">Digital Contract</div>
            <div class="mini-stat-val" style="font-size: 1.15rem;"><?= $contract ? ucfirst($contract['status']) : 'Active' ?></div>
            <div class="mini-stat-badge" style="color: #db2777;"><i class="fas fa-file-signature"></i> Legal Protected</div>
        </div>
        <div class="mini-stat-icon pink"><i class="fas fa-signature"></i></div>
    </div>

    <!-- Row 2: Tab 6 - Invoices & Billing -->
    <div class="mini-stat-card accent-cyan">
        <div class="mini-stat-content">
            <div class="mini-stat-label">Billing Status</div>
            <div class="mini-stat-val" style="font-size: 1.15rem;"><?= $invoice ? ucfirst($invoice['status']) : 'Settled' ?></div>
            <div class="mini-stat-badge" style="color: #0891b2;"><i class="fas fa-receipt"></i> <?= format_currency($invoice['total'] ?? 0) ?></div>
        </div>
        <div class="mini-stat-icon cyan"><i class="fas fa-wallet"></i></div>
    </div>

    <!-- Row 2: Tab 7 - Customer Inquiries / Leads -->
    <div class="mini-stat-card">
        <div class="mini-stat-content">
            <div class="mini-stat-label">Customer Inquiries</div>
            <div class="mini-stat-val"><?= $inquiriesCount ?> <small style="font-size: 0.8rem; font-weight: 700; color: #64748b;">Leads</small></div>
            <div class="mini-stat-badge" style="color: var(--primary);"><i class="fas fa-user-check"></i> Direct Orders</div>
        </div>
        <div class="mini-stat-icon blue"><i class="fas fa-bullhorn"></i></div>
    </div>

    <!-- Row 2: Tab 8 - Support & Helpdesk -->
    <div class="mini-stat-card accent-green">
        <div class="mini-stat-content">
            <div class="mini-stat-label">Support Helpdesk</div>
            <div class="mini-stat-val"><?= $openTickets > 0 ? $openTickets . ' Open' : '24/7 Active' ?></div>
            <div class="mini-stat-badge" style="color: var(--success);"><i class="fab fa-whatsapp"></i> Priority Care</div>
        </div>
        <div class="mini-stat-icon green"><i class="fas fa-headset"></i></div>
    </div>
</div>

<!-- Quick Action Shortcuts -->
<div class="card" style="margin-bottom: 28px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bolt text-warning" style="margin-right: 8px;"></i> Quick Website Actions</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <a href="<?= BASE_URL ?>/client/content.php" class="btn btn-secondary" style="padding: 16px; flex-direction: column; gap: 8px; text-align: center;">
                <i class="fas fa-edit text-primary" style="font-size: 1.5rem;"></i>
                <strong>Edit Hero &amp; Text</strong>
                <span class="text-muted" style="font-size: 0.75rem;">Modify titles, banners &amp; story</span>
            </a>

            <a href="<?= BASE_URL ?>/client/content.php#servicesSection" class="btn btn-secondary" style="padding: 16px; flex-direction: column; gap: 8px; text-align: center;">
                <i class="fas fa-utensils text-success" style="font-size: 1.5rem;"></i>
                <strong>Manage Services / Menu</strong>
                <span class="text-muted" style="font-size: 0.75rem;"><?= $servicesCount ?> active items listed</span>
            </a>

            <a href="<?= BASE_URL ?>/client/media.php" class="btn btn-secondary" style="padding: 16px; flex-direction: column; gap: 8px; text-align: center;">
                <i class="fas fa-images text-warning" style="font-size: 1.5rem;"></i>
                <strong>Photo Gallery &amp; Media</strong>
                <span class="text-muted" style="font-size: 0.75rem;">Upload food, salon &amp; shop photos</span>
            </a>

            <a href="<?= BASE_URL ?>/client/pages.php" class="btn btn-secondary" style="padding: 16px; flex-direction: column; gap: 8px; text-align: center;">
                <i class="fas fa-file-code text-info" style="font-size: 1.5rem;"></i>
                <strong>Custom Landing Pages</strong>
                <span class="text-muted" style="font-size: 0.75rem;">Create menu cards &amp; special offers</span>
            </a>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
