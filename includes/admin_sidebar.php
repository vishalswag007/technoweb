<?php
/**
 * Vishal Web Studio - Super Admin Sidebar Navigation
 */
$adminNav = $adminNav ?? 'dashboard';
$businessName = get_setting('business_name', APP_NAME);

// Fetch quick pending counts for badges
$pendingOrdersCount = 0;
$pendingTicketsCount = 0;
try {
    $pendingOrdersCount = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status IN ('new', 'requirements_pending', 'contract_pending', 'payment_pending')")->fetchColumn();
    $pendingTicketsCount = (int)db()->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open', 'in_progress')")->fetchColumn();
} catch (Exception $e) {}
?>

<aside class="dashboard-sidebar">
    <div class="sidebar-header" style="height: 72px; padding: 0 16px; border-bottom: 1px solid #1e293b; display: flex; align-items: center;">
        <a href="<?= BASE_URL ?>/super-admin/index.php" class="sidebar-brand" style="display: flex; align-items: center; gap: 10px; text-decoration: none; width: 100%; min-width: 0;">
            <div style="width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(135deg, #0754b8 0%, #ef1515 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 1rem; flex-shrink: 0; box-shadow: 0 4px 10px rgba(7, 84, 184, 0.3);">
                <i class="fas fa-crown"></i>
            </div>
            <div style="min-width: 0; flex-grow: 1;">
                <div style="font-size: 0.92rem; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2;"><?= e($businessName) ?></div>
                <div style="font-size: 0.70rem; color: #ef4444; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fas fa-shield-alt" style="font-size: 0.65rem;"></i> Super Admin</div>
            </div>
        </a>
    </div>

    <ul class="sidebar-nav">
        <li class="nav-section-title">Core Management</li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/index.php" class="sidebar-link <?= $adminNav === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/clients.php" class="sidebar-link <?= $adminNav === 'clients' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Clients</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/websites.php" class="sidebar-link <?= $adminNav === 'websites' ? 'active' : '' ?>">
                <i class="fas fa-globe"></i>
                <span>Websites</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/templates.php" class="sidebar-link <?= $adminNav === 'templates' ? 'active' : '' ?>">
                <i class="fas fa-palette"></i>
                <span>Templates</span>
            </a>
        </li>

        <li class="nav-section-title">Sales & Delivery</li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/orders.php" class="sidebar-link <?= $adminNav === 'orders' ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
                <?php if ($pendingOrdersCount > 0): ?>
                    <span class="sidebar-badge"><?= $pendingOrdersCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/contracts.php" class="sidebar-link <?= $adminNav === 'contracts' ? 'active' : '' ?>">
                <i class="fas fa-file-signature"></i>
                <span>Contracts</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/invoices.php" class="sidebar-link <?= $adminNav === 'invoices' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Invoices</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/payments.php" class="sidebar-link <?= $adminNav === 'payments' ? 'active' : '' ?>">
                <i class="fas fa-receipt"></i>
                <span>Payments</span>
            </a>
        </li>

        <li class="nav-section-title">Infrastructure & Operations</li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/domains.php" class="sidebar-link <?= $adminNav === 'domains' ? 'active' : '' ?>">
                <i class="fas fa-link"></i>
                <span>Domains</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/hosting.php" class="sidebar-link <?= $adminNav === 'hosting' ? 'active' : '' ?>">
                <i class="fas fa-server"></i>
                <span>Hosting</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/support.php" class="sidebar-link <?= $adminNav === 'support' ? 'active' : '' ?>">
                <i class="fas fa-headset"></i>
                <span>Support Desk</span>
                <?php if ($pendingTicketsCount > 0): ?>
                    <span class="sidebar-badge"><?= $pendingTicketsCount ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="nav-section-title">Content & Growth</li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/frontend_cms.php" class="sidebar-link <?= $adminNav === 'frontend_cms' ? 'active' : '' ?>">
                <i class="fas fa-paint-brush"></i>
                <span>Frontend Website Editor</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/frontend_media.php" class="sidebar-link <?= $adminNav === 'frontend_media' ? 'active' : '' ?>">
                <i class="fas fa-photo-video"></i>
                <span>Photo &amp; Media Library</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/blog.php" class="sidebar-link <?= $adminNav === 'blog' ? 'active' : '' ?>">
                <i class="fas fa-newspaper"></i>
                <span>Blog Articles</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/activity_logs.php" class="sidebar-link <?= $adminNav === 'activity_logs' ? 'active' : '' ?>">
                <i class="fas fa-history"></i>
                <span>Activity Logs</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/backups.php" class="sidebar-link <?= $adminNav === 'backups' ? 'active' : '' ?>">
                <i class="fas fa-database"></i>
                <span>Backups & Export</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/super-admin/settings.php" class="sidebar-link <?= $adminNav === 'settings' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>Global Settings</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: flex-start;">
            <i class="fas fa-external-link-alt"></i> View Public Site
        </a>
    </div>
</aside>
