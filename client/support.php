<?php
/**
 * Vishal Web Studio - Client Support Ticket Portal
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_client();

$client = get_current_client_record();
$pdo = db();

if (!$client) {
    header('Location: ' . BASE_URL . '/client/index.php');
    exit;
}

$clientId = (int)$client['id'];
$userId = current_user_id();

// Handle Create New Ticket POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrfToken)) {
        $subject = trim($_POST['subject'] ?? '');
        $category = trim($_POST['category'] ?? 'Content Update');
        $priority = $_POST['priority'] ?? 'medium';
        $message = trim($_POST['message'] ?? '');

        if (!empty($subject) && !empty($message)) {
            $ticketNumber = generate_ticket_number();
            $insTkt = $pdo->prepare("INSERT INTO support_tickets (ticket_number, client_id, user_id, subject, category, priority, status) VALUES (?, ?, ?, ?, ?, ?, 'open')");
            $insTkt->execute([$ticketNumber, $clientId, $userId, $subject, $category, $priority]);
            $tktId = $pdo->lastInsertId();

            $insMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $insMsg->execute([$tktId, $userId, $message]);

            log_activity($userId, 'ticket_created', 'support_tickets', $tktId, "Client created support ticket {$ticketNumber}: '{$subject}'");
            set_flash('success', "Support ticket {$ticketNumber} created! Vishal Web Studio support will respond shortly.");
            header('Location: ' . BASE_URL . '/client/support.php?view=' . $tktId);
            exit;
        }
    }
}

// Handle Client Reply to existing ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrfToken)) {
        $ticketId = (int)$_POST['ticket_id'];
        $message = trim($_POST['message'] ?? '');

        // Verify ticket belongs to this client (tenant isolation)
        $tStmt = $pdo->prepare("SELECT id FROM support_tickets WHERE id = ? AND client_id = ?");
        $tStmt->execute([$ticketId, $clientId]);
        if ($tStmt->fetch() && !empty($message)) {
            $insMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $insMsg->execute([$ticketId, $userId, $message]);

            $pdo->prepare("UPDATE support_tickets SET status = 'open' WHERE id = ?")->execute([$ticketId]);
            set_flash('success', 'Your reply has been sent to our support desk.');
        }
        header('Location: ' . BASE_URL . '/client/support.php?view=' . $ticketId);
        exit;
    }
}

// Single Ticket Thread
$viewTicketId = (int)($_GET['view'] ?? 0);
$activeTicket = null;
$messages = [];

if ($viewTicketId > 0) {
    $tStmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ? AND client_id = ?");
    $tStmt->execute([$viewTicketId, $clientId]);
    $activeTicket = $tStmt->fetch();

    if ($activeTicket) {
        $mStmt = $pdo->prepare("SELECT m.*, u.name as sender_name, u.role as sender_role FROM ticket_messages m JOIN users u ON m.user_id = u.id WHERE m.ticket_id = ? ORDER BY m.id ASC");
        $mStmt->execute([$viewTicketId]);
        $messages = $mStmt->fetchAll();
    }
}

// Tickets list for this client
$tListStmt = $pdo->prepare("SELECT t.*, (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) as message_count FROM support_tickets t WHERE t.client_id = ? ORDER BY t.id DESC");
$tListStmt->execute([$clientId]);
$tickets = $tListStmt->fetchAll();

$pageTitle = 'Help & Support Tickets';
$clientNav = 'support';
require_once dirname(__DIR__) . '/includes/client_header.php';
?>

<?php if ($activeTicket): ?>
    <div style="margin-bottom: 20px;">
        <a href="<?= BASE_URL ?>/client/support.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to All Tickets
        </a>
    </div>

    <div class="card">
        <div class="card-header" style="background: #f8fafc; padding: 20px;">
            <div>
                <span class="badge badge-secondary"><?= e($activeTicket['ticket_number']) ?></span>
                <span class="badge badge-info"><?= e($activeTicket['category']) ?></span>
                <h3 class="card-title" style="margin-top: 8px;"><?= e($activeTicket['subject']) ?></h3>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Opened on <?= format_datetime($activeTicket['created_at']) ?></span>
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
                    <div style="display: flex; gap: 12px; <?= !$isAdmin ? 'flex-direction: row-reverse;' : '' ?>">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: <?= $isAdmin ? 'var(--primary)' : '#10b981' ?>; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;">
                            <?= strtoupper(substr($msg['sender_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div style="max-width: 75%; background: <?= $isAdmin ? '#f1f5f9' : 'var(--primary-light)' ?>; border-radius: var(--radius-md); padding: 14px 18px; border: 1px solid <?= $isAdmin ? 'var(--border-color)' : 'rgba(37, 99, 235, 0.2)' ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: 0.82rem;">
                                <strong style="color: <?= $isAdmin ? 'var(--primary)' : 'var(--dark)' ?>;"><?= e($msg['sender_name']) ?> (<?= $isAdmin ? 'Studio Developer' : 'You' ?>)</strong>
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
                <input type="hidden" name="reply_ticket" value="1">
                <input type="hidden" name="ticket_id" value="<?= $activeTicket['id'] ?>">

                <h4 style="font-size: 1rem; margin-bottom: 10px;"><i class="fas fa-reply text-primary"></i> Write a Reply</h4>
                <div class="form-group">
                    <textarea name="message" class="form-control" rows="3" placeholder="Type your response to Vishal Web Studio support team..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Reply
                </button>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Tickets List -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <p class="text-muted" style="margin: 0;">Have a technical question or need changes? Create a ticket below.</p>
        <button type="button" class="btn btn-primary btn-sm" onclick="openTicketModal()">
            <i class="fas fa-plus"></i> Open New Support Ticket
        </button>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket No</th>
                        <th>Subject & Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="6" class="text-center text-muted" style="padding: 30px;">No support tickets created yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><strong class="text-primary"><?= e($t['ticket_number']) ?></strong></td>
                                <td>
                                    <strong><?= e($t['subject']) ?></strong><br>
                                    <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($t['category']) ?> • <?= $t['message_count'] ?> messages</span>
                                </td>
                                <td><span class="badge badge-secondary"><?= ucfirst($t['priority']) ?></span></td>
                                <td><?= render_status_badge($t['status']) ?></td>
                                <td style="font-size: 0.82rem; color: var(--text-muted);"><?= format_datetime($t['created_at']) ?></td>
                                <td style="text-align: right;">
                                    <a href="<?= BASE_URL ?>/client/support.php?view=<?= $t['id'] ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-comments"></i> View Thread
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

<!-- Create Ticket Modal -->
<div class="modal-backdrop" id="ticketModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-life-ring text-primary" style="margin-right: 8px;"></i> Open New Support Ticket</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="create_ticket" value="1">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="tkt_subject">Ticket Subject *</label>
                    <input type="text" name="subject" id="tkt_subject" class="form-control" placeholder="e.g. Need help updating our Sunday special menu" required>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="tkt_cat">Category</label>
                        <select name="category" id="tkt_cat" class="form-select">
                            <option value="Content Update">Content & Text Update</option>
                            <option value="Domain & DNS">Domain & SSL</option>
                            <option value="Design Tweak">Design & Styling Tweak</option>
                            <option value="Billing & Invoice">Billing & Invoice</option>
                            <option value="Other">Other Question</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="tkt_prio">Priority</label>
                        <select name="priority" id="tkt_prio" class="form-select">
                            <option value="low">Low (General Inquiry)</option>
                            <option value="medium" selected>Medium (Standard)</option>
                            <option value="high">High (Time-Sensitive)</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="tkt_msg">Detailed Description *</label>
                    <textarea name="message" id="tkt_msg" class="form-control" rows="4" placeholder="Explain what you need assistance with..." required></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Ticket</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTicketModal() {
    openModal('ticketModal');
}
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
