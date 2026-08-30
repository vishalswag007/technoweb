<?php
/**
 * Vishal Web Studio - Super Admin Executive Dashboard
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Compute Statistics
$stats = [
    'total_clients'    => (int)$pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'active_clients'   => (int)$pdo->query("SELECT COUNT(*) FROM clients WHERE status = 'active'")->fetchColumn(),
    'total_websites'   => (int)$pdo->query("SELECT COUNT(*) FROM websites")->fetchColumn(),
    'live_websites'    => (int)$pdo->query("SELECT COUNT(*) FROM websites WHERE status = 'live'")->fetchColumn(),
    'pending_orders'   => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status NOT IN ('completed', 'cancelled', 'published')")->fetchColumn(),
    'pending_contracts'=> (int)$pdo->query("SELECT COUNT(*) FROM contracts WHERE status != 'signed'")->fetchColumn(),
    'pending_payments' => (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE status != 'paid'")->fetchColumn(),
    'total_revenue'    => (float)$pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'")->fetchColumn(),
    'open_tickets'     => (int)$pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open', 'in_progress')")->fetchColumn(),
];

// Fetch Recent Orders
$recentOrders = $pdo->query("SELECT o.*, t.name as template_name, c.business_name as client_biz FROM orders o LEFT JOIN templates t ON o.template_id = t.id LEFT JOIN clients c ON o.client_id = c.id ORDER BY o.id DESC LIMIT 5")->fetchAll();

// Fetch Recent Activity Logs
$recentLogs = $pdo->query("SELECT a.*, u.name as user_name FROM activity_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 8")->fetchAll();

$pageTitle = 'Executive Business Dashboard';
$adminNav = 'dashboard';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<!-- Quick Action Shortcuts Bar -->
<div style="display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;">
    <a href="<?= BASE_URL ?>/super-admin/clients.php?action=new" class="btn btn-secondary btn-sm">
        <i class="fas fa-user-plus text-primary"></i> Add Client
    </a>
    <a href="<?= BASE_URL ?>/super-admin/websites.php?action=new" class="btn btn-secondary btn-sm">
        <i class="fas fa-globe text-success"></i> Create Website From Template
    </a>
    <a href="<?= BASE_URL ?>/super-admin/contracts.php?action=new" class="btn btn-secondary btn-sm">
        <i class="fas fa-file-signature text-warning"></i> Generate Contract
    </a>
    <a href="<?= BASE_URL ?>/super-admin/invoices.php?action=new" class="btn btn-secondary btn-sm">
        <i class="fas fa-file-invoice-dollar text-info"></i> Create Invoice
    </a>
    <a href="<?= BASE_URL ?>/super-admin/backups.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-database text-danger"></i> Database Backup
    </a>
</div>

<!-- 3D Stats Grid -->
<div class="admin-stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Clients</div>
            <div class="stat-value"><?= $stats['total_clients'] ?></div>
            <div style="font-size: 0.82rem; color: var(--success); font-weight: 700;">
                <i class="fas fa-check-circle"></i> <?= $stats['active_clients'] ?> Active Accounts
            </div>
        </div>
        <div class="stat-icon primary"><i class="fas fa-users"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Websites Managed</div>
            <div class="stat-value"><?= $stats['total_websites'] ?></div>
            <div style="font-size: 0.82rem; color: var(--success); font-weight: 700;">
                <i class="fas fa-broadcast-tower"></i> <?= $stats['live_websites'] ?> Live in Production
            </div>
        </div>
        <div class="stat-icon success"><i class="fas fa-globe"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Pending Orders</div>
            <div class="stat-value"><?= $stats['pending_orders'] ?></div>
            <div style="font-size: 0.82rem; color: var(--warning); font-weight: 700;">
                <i class="fas fa-clock"></i> <?= $stats['pending_contracts'] ?> Contracts Pending
            </div>
        </div>
        <div class="stat-icon warning"><i class="fas fa-shopping-cart"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value" style="font-size: 22px;"><?= format_currency($stats['total_revenue']) ?></div>
            <div style="font-size: 0.82rem; color: var(--brand-blue); font-weight: 700;">
                <i class="fas fa-receipt"></i> <?= $stats['pending_payments'] ?> Unpaid Invoices
            </div>
        </div>
        <div class="stat-icon info"><i class="fas fa-rupee-sign"></i></div>
    </div>
</div>

<!-- Main 2-Column Section: Orders & Activity Stream -->
<div class="grid-2" style="grid-template-columns: 1.35fr 0.65fr; gap: 24px;">
    <!-- Recent Orders Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-shopping-bag text-primary" style="margin-right: 8px;"></i> Recent Orders & Pipeline</h3>
            <a href="<?= BASE_URL ?>/super-admin/orders.php" class="btn btn-secondary btn-sm">View All Orders</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Client & Business</th>
                        <th>Template / Plan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="6" class="text-center text-muted" style="padding: 24px;">No orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $ord): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary"><?= e($ord['order_number']) ?></strong><br>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?= format_date($ord['created_at']) ?></span>
                                </td>
                                <td>
                                    <strong><?= e($ord['business_name']) ?></strong><br>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><?= e($ord['owner_name']) ?> (<?= e($ord['phone']) ?>)</span>
                                </td>
                                <td><?= e($ord['template_name'] ?? 'Custom Build') ?></td>
                                <td><strong><?= format_currency($ord['amount']) ?></strong></td>
                                <td><?= render_status_badge($ord['status']) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/super-admin/orders.php?view=<?= $ord['id'] ?>" class="btn btn-secondary btn-sm" title="Manage Order">
                                        <i class="fas fa-cog"></i> Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity Audit Stream -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history text-info" style="margin-right: 8px;"></i> Audit Activity Feed</h3>
            <a href="<?= BASE_URL ?>/super-admin/activity_logs.php" class="btn btn-secondary btn-sm" title="View Full Log"><i class="fas fa-list"></i></a>
        </div>
        <div class="card-body" style="padding: 16px;">
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($recentLogs as $log): ?>
                    <div style="display: flex; gap: 12px; font-size: 0.88rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div>
                            <div style="color: var(--dark); font-weight: 600;"><?= e($log['description']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                <i class="fas fa-clock"></i> <?= format_datetime($log['created_at']) ?> • IP: <?= e($log['ip_address']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
