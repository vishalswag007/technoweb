<?php
/**
 * Vishal Web Studio - Client Admin Sidebar Navigation (Tenant-Isolated)
 */
$clientNav = $clientNav ?? 'dashboard';
$client = get_current_client_record();
$website = get_current_client_website();
$businessName = $client['business_name'] ?? 'My Website Admin';
?>

<aside class="dashboard-sidebar">
    <div class="sidebar-header" style="height: 72px; padding: 0 16px; border-bottom: 1px solid #1e293b; display: flex; align-items: center;">
        <a href="<?= BASE_URL ?>/client/index.php" class="sidebar-brand" style="display: flex; align-items: center; gap: 10px; text-decoration: none; width: 100%; min-width: 0;">
            <div style="width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(135deg, #0754b8 0%, #2563eb 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 1rem; flex-shrink: 0; box-shadow: 0 4px 10px rgba(7, 84, 184, 0.3);">
                <i class="fas fa-desktop"></i>
            </div>
            <div style="min-width: 0; flex-grow: 1;">
                <div style="font-size: 0.92rem; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2;" title="<?= e($businessName) ?>"><?= e($businessName) ?></div>
                <div style="font-size: 0.70rem; color: #10b981; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fas fa-check-circle" style="font-size: 0.65rem;"></i> Client Portal</div>
            </div>
        </a>
    </div>

    <ul class="sidebar-nav">
        <li class="nav-section-title">Overview</li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/client/index.php" class="sidebar-link <?= $clientNav === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="nav-section-title">Website Management</li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/client/content.php" class="sidebar-link <?= $clientNav === 'content' ? 'active' : '' ?>">
                <i class="fas fa-edit"></i>
                <span>Edit Website Content</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/client/pages.php" class="sidebar-link <?= $clientNav === 'pages' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i>
                <span>Custom Pages</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/client/media.php" class="sidebar-link <?= $clientNav === 'media' ? 'active' : '' ?>">
                <i class="fas fa-images"></i>
                <span>Media Library</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/client/business-info.php" class="sidebar-link <?= $clientNav === 'business-info' ? 'active' : '' ?>">
                <i class="fas fa-store"></i>
                <span>Business Info & Maps</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/client/settings.php" class="sidebar-link <?= $clientNav === 'settings' ? 'active' : '' ?>">
                <i class="fas fa-sliders-h"></i>
                <span>SEO & Theme Settings</span>
            </a>
        </li>

        <li class="nav-section-title">Account & Billing</li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/client/contract.php" class="sidebar-link <?= $clientNav === 'contract' ? 'active' : '' ?>">
                <i class="fas fa-file-signature"></i>
                <span>Signed Contract</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/client/invoices.php" class="sidebar-link <?= $clientNav === 'invoices' ? 'active' : '' ?>">
                <i class="fas fa-receipt"></i>
                <span>Invoices & Payments</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/client/support.php" class="sidebar-link <?= $clientNav === 'support' ? 'active' : '' ?>">
                <i class="fas fa-life-ring"></i>
                <span>Help & Support</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <?php if ($website): ?>
            <a href="<?= BASE_URL ?>/site/index.php?site=<?= urlencode($website['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: flex-start;">
                <i class="fas fa-external-link-alt"></i> Visit Live Website
            </a>
        <?php endif; ?>
    </div>
</aside>
