<?php
/**
 * Vishal Web Studio - Super Admin Activity Logs & Audit Trail
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Optional Clear Logs
if (isset($_POST['clear_logs'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrfToken)) {
        $pdo->exec("DELETE FROM activity_logs");
        log_activity(current_user_id(), 'logs_cleared', 'system', null, 'Activity audit logs cleared.');
        set_flash('info', 'Activity logs cleared.');
        header('Location: ' . BASE_URL . '/super-admin/activity_logs.php');
        exit;
    }
}

// Fetch Activity Logs
$logs = $pdo->query("SELECT a.*, u.name as user_name, u.role as user_role FROM activity_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 200")->fetchAll();

$pageTitle = 'Activity Logs & Audit Trail';
$adminNav = 'activity_logs';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
        Showing latest <strong><?= count($logs) ?></strong> events. All sensitive interactions are immutably timestamped.
    </p>

    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to clear the audit history?');">
        <?= csrf_field() ?>
        <input type="hidden" name="clear_logs" value="1">
        <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--danger);">
            <i class="fas fa-trash"></i> Clear Audit Log
        </button>
    </form>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User & Role</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Target Entity</th>
                    <th>IP Address & Device</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center text-muted" style="padding: 30px;">No activity logged yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td style="font-size: 0.82rem; color: var(--text-muted); white-space: nowrap;">
                                <?= format_datetime($l['created_at']) ?>
                            </td>
                            <td>
                                <strong><?= e($l['user_name'] ?? 'System') ?></strong><br>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><?= ucfirst(str_replace('_', ' ', $l['user_role'] ?? 'System')) ?></span>
                            </td>
                            <td>
                                <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.78rem;"><?= e($l['action']) ?></code>
                            </td>
                            <td style="font-size: 0.88rem; color: var(--dark); font-weight: 500;">
                                <?= e($l['description']) ?>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?= e($l['entity_type'] ?? 'General') ?> #<?= e($l['entity_id'] ?? '-') ?></span>
                            </td>
                            <td style="font-size: 0.78rem; color: var(--text-muted);">
                                <code><?= e($l['ip_address']) ?></code>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
