<?php
/**
 * Vishal Web Studio - Authentication & Access Control (RBAC & Multi-Tenant Isolation)
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/helpers.php';

// Session Lifetime Check
$sessionTimeout = (int)get_setting('session_timeout', '7200');
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
    session_unset();
    session_destroy();
    session_start();
    set_flash('warning', 'Your session has expired. Please log in again.');
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}
$_SESSION['last_activity'] = time();

// Auth State Queries
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    static $userCache = null;
    if ($userCache === null) {
        $stmt = db()->prepare("SELECT id, name, email, role, phone, avatar, status FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userCache = $stmt->fetch() ?: null;
    }
    return $userCache;
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function is_super_admin(): bool {
    $u = current_user();
    return $u && in_array($u['role'], ['super_admin', 'admin']);
}

function is_client(): bool {
    $u = current_user();
    return $u && $u['role'] === 'client';
}

function is_impersonating(): bool {
    return !empty($_SESSION['impersonator_id']);
}

// Authentication Actions
function attempt_login(string $email, string $password): bool {
    $email = trim(strtolower($email));
    $stmt = db()->prepare("SELECT id, name, email, password_hash, role, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    if ($user['status'] !== 'active') {
        set_flash('danger', 'Your account has been deactivated. Please contact support.');
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    // Login successful
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['last_activity'] = time();

    // Update last_login
    $upd = db()->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?");
    if (Database::getInstance()->isMySQL()) {
        $upd = db()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    }
    $upd->execute([$user['id']]);

    log_activity($user['id'], 'user_login', 'users', $user['id'], "User {$user['name']} logged in successfully.");
    return true;
}

function logout(): void {
    $uid = current_user_id();
    if ($uid) {
        log_activity($uid, 'user_logout', 'users', $uid, "User logged out.");
    }
    unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['impersonator_id']);
    session_destroy();
}

// Impersonation ("Login as Client")
function impersonate_client(int $clientUserId): bool {
    if (!is_super_admin() && !is_impersonating()) {
        return false;
    }

    $stmt = db()->prepare("SELECT id, name, email, role FROM users WHERE id = ? AND role = 'client'");
    $stmt->execute([$clientUserId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        return false;
    }

    $originalAdminId = $_SESSION['impersonator_id'] ?? $_SESSION['user_id'];
    $_SESSION['impersonator_id'] = $originalAdminId;
    $_SESSION['user_id'] = $targetUser['id'];
    $_SESSION['user_role'] = 'client';
    $_SESSION['user_name'] = $targetUser['name'];
    $_SESSION['user_email'] = $targetUser['email'];

    log_activity($originalAdminId, 'impersonate_client', 'users', $targetUser['id'], "Super Admin impersonated client: {$targetUser['name']}");
    return true;
}

function revert_impersonation(): bool {
    if (!is_impersonating()) {
        return false;
    }

    $adminId = $_SESSION['impersonator_id'];
    $stmt = db()->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();

    if (!$admin) {
        logout();
        return false;
    }

    unset($_SESSION['impersonator_id']);
    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['user_role'] = $admin['role'];
    $_SESSION['user_name'] = $admin['name'];
    $_SESSION['user_email'] = $admin['email'];

    log_activity($admin['id'], 'revert_impersonation', 'users', $admin['id'], "Super Admin reverted from impersonation.");
    return true;
}

// Access Guards
function require_auth(): void {
    if (!is_logged_in()) {
        set_flash('danger', 'Please log in to continue.');
        header('Location: ' . BASE_URL . '/public/login.php');
        exit;
    }
}

function require_super_admin(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to the Super Admin Console.');
        header('Location: ' . BASE_URL . '/super-admin/login.php');
        exit;
    }
    if (!is_super_admin()) {
        http_response_code(403);
        set_flash('danger', 'Access denied. Administrator privileges required.');
        header('Location: ' . BASE_URL . '/super-admin/login.php');
        exit;
    }
}

function require_client(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to your Client Management Panel.');
        header('Location: ' . BASE_URL . '/client/login.php');
        exit;
    }
    if (!is_client() && !is_super_admin()) {
        http_response_code(403);
        set_flash('danger', 'Access denied. Client account required.');
        header('Location: ' . BASE_URL . '/client/login.php');
        exit;
    }
}

// Multi-Tenant Isolation Helpers
function get_current_client_record(): ?array {
    $uid = current_user_id();
    if (!$uid) return null;

    static $clientCache = null;
    if ($clientCache === null) {
        $stmt = db()->prepare("SELECT * FROM clients WHERE user_id = ?");
        $stmt->execute([$uid]);
        $client = $stmt->fetch() ?: null;

        // If logged in as Super Admin, load first active client so Super Admin can preview/manage client portal
        if (!$client && is_super_admin()) {
            $client = db()->query("SELECT * FROM clients WHERE status = 'active' ORDER BY id ASC LIMIT 1")->fetch() ?: null;
        }

        $clientCache = $client;
    }
    return $clientCache;
}

function get_current_client_website(): ?array {
    $client = get_current_client_record();
    if (!$client) return null;

    static $websiteCache = null;
    if ($websiteCache === null) {
        $stmt = db()->prepare("SELECT * FROM websites WHERE client_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$client['id']]);
        $websiteCache = $stmt->fetch() ?: null;
    }
    return $websiteCache;
}

function verify_tenant_website_access(int $websiteId): array {
    // If Super Admin, allow access to any website
    if (is_super_admin()) {
        $stmt = db()->prepare("SELECT * FROM websites WHERE id = ?");
        $stmt->execute([$websiteId]);
        $site = $stmt->fetch();
        if (!$site) {
            http_response_code(404);
            set_flash('danger', 'Website record not found.');
            header('Location: ' . BASE_URL . '/super-admin/websites.php');
            exit;
        }
        return $site;
    }

    // Client user - STRICT ISOLATION
    $client = get_current_client_record();
    if (!$client) {
        http_response_code(403);
        set_flash('danger', 'Unauthorized tenant access.');
        header('Location: ' . BASE_URL . '/client/index.php');
        exit;
    }

    $stmt = db()->prepare("SELECT * FROM websites WHERE id = ? AND client_id = ?");
    $stmt->execute([$websiteId, $client['id']]);
    $site = $stmt->fetch();

    if (!$site) {
        http_response_code(403);
        log_activity(current_user_id(), 'security_breach_attempt', 'websites', $websiteId, "Unauthorized access attempt to website ID {$websiteId}");
        set_flash('danger', 'Security violation: You do not have permission to access this website.');
        header('Location: ' . BASE_URL . '/client/index.php');
        exit;
    }

    return $site;
}
