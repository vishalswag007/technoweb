<?php
/**
 * Vishal Web Studio - Super Admin Support Helpdesk & Threaded Ticket Desk
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Handle Reply POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_reply'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $ticketId = (int)$_POST['ticket_id'];
        $message = trim($_POST['message'] ?? '');
        $status = $_POST['status'] ?? 'in_progress';

        if (!empty($message)) {
            $insMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $insMsg->execute([$ticketId, current_user_id(), $message]);

            $pdo->prepare("UPDATE support_tickets SET status = ? WHERE id = ?")->execute([$status, $ticketId]);

            log_activity(current_user_id(), 'ticket_reply', 'support_tickets', $ticketId, "Admin replied to support ticket #{$ticketId}");
            set_flash('success', "Reply sent and ticket status updated to " . ucfirst(str_replace('_', ' ', $status)));
        }
        header('Location: ' . BASE_URL . '/super-admin/support.php?view=' . $ticketId);
        exit;
    }
}

// If viewing single ticket thread
$viewTicketId = (int)($_GET['view'] ?? 0);
$activeTicket = null;
$messages = [];

if ($viewTicketId > 0) {
    $tStmt = $pdo->prepare("SELECT t.*, cl.business_name, cl.owner_name, cl.phone as client_phone, u.email as client_email FROM support_tickets t JOIN clients cl ON t.client_id = cl.id LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    $tStmt->execute([$viewTicketId]);
    $activeTicket = $tStmt->fetch();

    if ($activeTicket) {
        $mStmt = $pdo->prepare("SELECT m.*, u.name as sender_name, u.role as sender_role FROM ticket_messages m JOIN users u ON m.user_id = u.id WHERE m.ticket_id = ? ORDER BY m.id ASC");
        $mStmt->execute([$viewTicketId]);
        $messages = $mStmt->fetchAll();
    }
}

// Fetch Tickets List
$tickets = $pdo->query("SELECT t.*, cl.business_name, cl.owner_name, (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) as message_count FROM support_tickets t JOIN clients cl ON t.client_id = cl.id ORDER BY t.id DESC")->fetchAll();

$pageTitle = 'Support Ticket Helpdesk';
$adminNav = 'support';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<?php if ($activeTicket): ?>
    <!-- Single Ticket Thread View -->
    <div style="margin-bottom: 20px;">
        <a href="<?= BASE_URL ?>/super-admin/support.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to All Tickets
        </a>
    </div>

    <div class="card" style="box-shadow: var(--shadow-md); border-radius: var(--radius-lg); margin-bottom: 24px;">
        <div class="card-header" style="background: #f8fafc; padding: 20px;">
            <div>
                <span class="badge badge-secondary"><?= e($activeTicket['ticket_number']) ?></span>
                <span class="badge badge-info"><?= e($activeTicket['category']) ?></span>
                <span class="badge badge-warning">Priority: <?= ucfirst($activeTicket['priority']) ?></span>
                <h2 style="font-size: 1.4rem; color: var(--dark); margin: 8px 0 4px;"><?= e($activeTicket['subject']) ?></h2>
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    From <strong><?= e($activeTicket['business_name']) ?></strong> (<?= e($activeTicket['owner_name']) ?>) • Opened on <?= format_datetime($activeTicket['created_at']) ?>
                </div>
            </div>
            <div>
                <?= render_status_badge($activeTicket['status']) ?>
            </div>
        </div>

        <div class="card-body" style="padding: 24px;">
            <!-- Message Stream -->
            <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 30px;">
                <?php foreach ($messages as $msg): ?>
                    <?php $isAdmin = in_array($msg['sender_role'], ['super_admin', 'admin']); ?>
                    <div style="display: flex; gap: 12px; <?= $isAdmin ? 'flex-direction: row-reverse;' : '' ?>">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: <?= $isAdmin ? 'var(--primary)' : '#10b981' ?>; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;">
                            <?= strtoupper(substr($msg['sender_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div style="max-width: 75%; background: <?= $isAdmin ? 'var(--primary-light)' : '#f1f5f9' ?>; border-radius: var(--radius-md); padding: 14px 18px; border: 1px solid <?= $isAdmin ? 'rgba(37, 99, 235, 0.2)' : 'var(--border-color)' ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: 0.82rem;">
                                <strong style="color: <?= $isAdmin ? 'var(--primary)' : 'var(--dark)' ?>;"><?= e($msg['sender_name']) ?> (<?= $isAdmin ? 'Support Admin' : 'Client' ?>)</strong>
                                <span style="color: var(--text-muted);"><?= format_datetime($msg['created_at']) ?></span>
                            </div>
                            <div style="font-size: 0.92rem; color: var(--text-main); line-height: 1.5;">
                                <?= nl2br(e($msg['message'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Reply Form -->
            <form method="POST" action="" style="background: #f8fafc; border-radius: var(--radius-md); padding: 20px; border: 1px solid var(--border-color);">
                <?= csrf_field() ?>
                <input type="hidden" name="post_reply" value="1">
                <input type="hidden" name="ticket_id" value="<?= $activeTicket['id'] ?>">

                <h4 style="font-size: 1.05rem; margin-bottom: 12px;"><i class="fas fa-reply text-primary"></i> Post Official Reply</h4>

                <div class="form-group">
                    <textarea name="message" class="form-control" rows="4" placeholder="Type your response to the client..." required></textarea>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label class="form-label" style="margin-bottom: 0;">Set Status:</label>
                        <select name="status" class="form-select" style="width: auto;">
                            <option value="in_progress" <?= $activeTicket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="waiting_for_client" <?= $activeTicket['status'] === 'waiting_for_client' ? 'selected' : '' ?>>Waiting for Client</option>
                            <option value="resolved" <?= $activeTicket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="closed" <?= $activeTicket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Reply to Client
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- All Tickets List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Support Tickets Desk</h3>
            <span class="badge badge-info"><?= count($tickets) ?> Total Tickets</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket No</th>
                        <th>Subject & Category</th>
                        <th>Client Business</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="7" class="text-center text-muted" style="padding: 30px;">No support tickets currently open.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary"><?= e($t['ticket_number']) ?></strong>
                                </td>
                                <td>
                                    <strong style="color: var(--dark);"><?= e($t['subject']) ?></strong><br>
                                    <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($t['category']) ?> • <?= $t['message_count'] ?> messages</span>
                                </td>
                                <td>
                                    <strong><?= e($t['business_name']) ?></strong><br>
                                    <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($t['owner_name']) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $t['priority'] === 'urgent' ? 'danger' : ($t['priority'] === 'high' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst($t['priority']) ?>
                                    </span>
                                </td>
                                <td><?= render_status_badge($t['status']) ?></td>
                                <td style="font-size: 0.82rem; color: var(--text-muted);">
                                    <?= format_datetime($t['created_at']) ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="<?= BASE_URL ?>/super-admin/support.php?view=<?= $t['id'] ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-comments"></i> View & Reply
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
