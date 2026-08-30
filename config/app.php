<?php
/**
 * Vishal Web Studio - Global App Configuration
 */

if (session_status() === PHP_SESSION_NONE) {
    // Secure session cookie settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// App Information
define('APP_NAME', 'Vishal Web Studio');
define('APP_TAGLINE', 'Professional Websites for Your Business');
define('APP_VERSION', '1.0.0');
define('APP_EMAIL', 'contact@vishalwebstudio.com');
define('APP_PHONE', '+91 98765 43210');
define('APP_WHATSAPP', '919876543210');
define('APP_CURRENCY', '₹');
define('APP_CURRENCY_CODE', 'INR');

// Base Paths & URLs
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage');
define('DATABASE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'database');

// Determine Base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if ($scriptDir === '.' || $scriptDir === '/' || $scriptDir === '\\') {
    $baseFolder = '';
} else {
    $baseFolder = trim($scriptDir, '/');
    $baseFolder = preg_replace('#/(super-admin|client|site|public|api)$#i', '', '/' . $baseFolder);
    $baseFolder = trim($baseFolder, '/');
    if ($baseFolder === '.') $baseFolder = '';
}

define('BASE_URL', rtrim($protocol . $host . ($baseFolder !== '' ? '/' . $baseFolder : ''), '/'));
define('UPLOADS_URL', BASE_URL . '/uploads');
define('ASSETS_URL', BASE_URL . '/assets');

// Security & CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf_token(?string $token): bool {
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Flash Message Helpers
function set_flash(string $type, string $message): void {
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array {
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

// Global Sanitization & Output Escaping
function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return trim(strip_tags((string)$data));
}
