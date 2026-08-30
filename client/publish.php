<?php
/**
 * Vishal Web Studio - 1-Click Client Live Website Publisher
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_client();

$website = get_current_client_website();
if ($website) {
    $pdo = db();
    $sql = "UPDATE websites SET status = 'live', published_at = " . (Database::getInstance()->isMySQL() ? "NOW()" : "datetime('now')") . " WHERE id = ?";
    $pdo->prepare($sql)->execute([$website['id']]);

    log_activity(current_user_id(), 'website_published_live', 'websites', $website['id'], "Client published website '{$website['name']}' to production.");
    set_flash('success', "🎉 Your website changes are now LIVE in production!");
}

header('Location: ' . BASE_URL . '/client/index.php');
exit;
