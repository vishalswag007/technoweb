<?php
/**
 * Vishal Web Studio - Global Helper Functions
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

// Format Currency
function format_currency(float|int|string|null $amount): string {
    $val = (float)($amount ?? 0);
    return APP_CURRENCY . ' ' . number_format($val, 2);
}

// Format Date/Time
function format_date(?string $datetime, string $format = 'd M Y'): string {
    if (!$datetime) return 'N/A';
    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Exception $e) {
        return $datetime;
    }
}

function format_datetime(?string $datetime): string {
    return format_date($datetime, 'd M Y, h:i A');
}

// Slug Generator
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a-' . time() : $text;
}

// Unique ID Generators
function generate_order_number(): string {
    $year = date('Y');
    $rand = strtoupper(bin2hex(random_bytes(3)));
    return "VW-{$year}-{$rand}";
}

function generate_contract_number(): string {
    $year = date('Y');
    $rand = strtoupper(bin2hex(random_bytes(2)));
    return "VWC-{$year}-{$rand}";
}

function generate_invoice_number(): string {
    $year = date('Y');
    $rand = strtoupper(bin2hex(random_bytes(2)));
    return "INV-{$year}-{$rand}";
}

function generate_ticket_number(): string {
    $year = date('Y');
    $rand = strtoupper(bin2hex(random_bytes(2)));
    return "TKT-{$year}-{$rand}";
}

function generate_secure_token(): string {
    return bin2hex(random_bytes(32));
}

// Global Setting Fetcher
function get_setting(string $key, ?string $default = null): ?string {
    static $settingsCache = null;
    if ($settingsCache === null) {
        try {
            $stmt = db()->query("SELECT setting_key, setting_value FROM global_settings");
            $settingsCache = [];
            while ($row = $stmt->fetch()) {
                $settingsCache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            $settingsCache = [];
        }
    }
    return $settingsCache[$key] ?? $default;
}

// Activity Logging
function log_activity(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $description = null): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250);
        $stmt = db()->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $entityType, $entityId, $description, $ip, $ua]);
    } catch (Exception $e) {
        // Silently fail logging to avoid crashing critical execution
    }
}

// WhatsApp Message Generator Link
function build_whatsapp_link(string $phone, string $message): string {
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) === 10) {
        $cleanPhone = '91' . $cleanPhone; // Default India prefix if 10 digits
    }
    return 'https://api.whatsapp.com/send?phone=' . urlencode($cleanPhone) . '&text=' . urlencode($message);
}

// Render Status Badges
function render_status_badge(string $status): string {
    $status = strtolower($status);
    $map = [
        'active' => ['success', 'Active'],
        'live' => ['success', 'Live'],
        'published' => ['success', 'Published'],
        'signed' => ['success', 'Signed'],
        'paid' => ['success', 'Paid'],
        'completed' => ['success', 'Completed'],
        'resolved' => ['success', 'Resolved'],

        'new' => ['info', 'New'],
        'contacted' => ['info', 'Contacted'],
        'open' => ['info', 'Open'],
        'development' => ['info', 'In Development'],
        'in_progress' => ['info', 'In Progress'],
        'partial' => ['info', 'Partial'],

        'pending' => ['warning', 'Pending'],
        'requirements_pending' => ['warning', 'Req. Pending'],
        'contract_pending' => ['warning', 'Contract Pending'],
        'payment_pending' => ['warning', 'Payment Pending'],
        'client_review' => ['warning', 'Client Review'],
        'changes_requested' => ['warning', 'Changes Requested'],
        'waiting_for_client' => ['warning', 'Waiting for Client'],
        'expiring_soon' => ['warning', 'Expiring Soon'],
        'draft' => ['secondary', 'Draft'],

        'cancelled' => ['danger', 'Cancelled'],
        'expired' => ['danger', 'Expired'],
        'rejected' => ['danger', 'Rejected'],
        'suspended' => ['danger', 'Suspended'],
        'terminated' => ['danger', 'Terminated'],
        'failed' => ['danger', 'Failed'],
        'closed' => ['secondary', 'Closed'],
    ];

    $info = $map[$status] ?? ['secondary', ucfirst(str_replace('_', ' ', $status))];
    return '<span class="badge badge-' . $info[0] . '">' . htmlspecialchars($info[1]) . '</span>';
}

// Render Flash Notification HTML
function render_flash_messages(): string {
    $flashes = get_flashes();
    if (empty($flashes)) return '';

    $html = '<div class="toast-container">';
    foreach ($flashes as $f) {
        $type = htmlspecialchars($f['type']);
        $msg = htmlspecialchars($f['message']);
        $icon = match($type) {
            'success' => 'fa-check-circle',
            'danger', 'error' => 'fa-exclamation-circle',
            'warning' => 'fa-exclamation-triangle',
            default => 'fa-info-circle',
        };
        $html .= "
        <div class=\"toast toast-{$type} animate-slide-in\" role=\"alert\">
            <i class=\"fas {$icon}\"></i>
            <span class=\"toast-message\">{$msg}</span>
            <button type=\"button\" class=\"toast-close\" onclick=\"this.parentElement.remove();\">&times;</button>
        </div>";
    }
    $html .= '</div>';
    return $html;
}

/**
 * Safe File Uploader Helper for Photos, 3D Videos & Animations
 */
function upload_file(array $file, string $subfolder = 'media', array $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif', 'avif', 'mp4', 'webm', 'mov', 'm4v', 'ogv', 'json']): array {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error occurred.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        return ['success' => false, 'error' => 'Invalid file format (' . $ext . '). Allowed: ' . implode(', ', $allowedExts)];
    }

    $targetDir = UPLOADS_PATH . DIRECTORY_SEPARATOR . $subfolder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $prefix = in_array($ext, ['mp4', 'webm', 'mov', 'm4v']) ? 'vid_' : (in_array($ext, ['json']) ? 'anim_' : 'img_');
    $filename = $prefix . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $relativePath = 'uploads/' . $subfolder . '/' . $filename;
        $mediaType = 'image';
        if (in_array($ext, ['mp4', 'webm', 'mov', 'm4v', 'ogv'])) {
            $mediaType = 'video';
        } elseif (in_array($ext, ['json', 'svg', 'gif'])) {
            $mediaType = 'animation';
        }

        return [
            'success' => true,
            'path' => $relativePath,
            'filename' => $filename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'ext' => $ext,
            'media_type' => $mediaType
        ];
    }

    return ['success' => false, 'error' => 'Failed to move uploaded file to target folder.'];
}

/**
 * Website Specific Settings (Per Tenant)
 */
function get_website_setting(int $websiteId, string $key, string $default = ''): string {
    static $cache = [];
    if (!isset($cache[$websiteId])) {
        $cache[$websiteId] = [];
        try {
            $stmt = db()->prepare("SELECT setting_key, setting_value FROM website_settings WHERE website_id = ?");
            $stmt->execute([$websiteId]);
            while ($row = $stmt->fetch()) {
                $cache[$websiteId][$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {}
    }
    return $cache[$websiteId][$key] ?? $default;
}

function set_website_setting(int $websiteId, string $key, string $value): bool {
    $pdo = db();
    try {
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO website_settings (website_id, setting_key, setting_value) VALUES (?, ?, ?)");
        if (Database::getInstance()->isMySQL()) {
            $stmt = $pdo->prepare("INSERT INTO website_settings (website_id, setting_key, setting_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        }
        return $stmt->execute([$websiteId, $key, $value]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Image Dimension, Aspect Ratio & Size Helper
 */
function get_image_metadata(?string $relativePath): array {
    if (empty($relativePath)) {
        return [
            'exists' => false,
            'width' => 0,
            'height' => 0,
            'dimensions' => 'No Image',
            'ratio' => 'N/A',
            'size' => 0,
            'size_formatted' => '0 KB',
            'ext' => 'N/A'
        ];
    }

    $fullPath = ROOT_PATH . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
    if (!file_exists($fullPath) || is_dir($fullPath)) {
        return [
            'exists' => false,
            'width' => 0,
            'height' => 0,
            'dimensions' => 'File Missing',
            'ratio' => 'N/A',
            'size' => 0,
            'size_formatted' => '0 KB',
            'ext' => strtolower(pathinfo($relativePath, PATHINFO_EXTENSION))
        ];
    }

    $size = filesize($fullPath);
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $sizeFormatted = $size >= 1048576 ? round($size / 1048576, 2) . ' MB' : round($size / 1024, 1) . ' KB';

    $info = @getimagesize($fullPath);
    if (!$info) {
        return [
            'exists' => true,
            'width' => 0,
            'height' => 0,
            'dimensions' => strtoupper($ext) . ' Asset',
            'ratio' => 'Vector/Media',
            'size' => $size,
            'size_formatted' => $sizeFormatted,
            'ext' => $ext
        ];
    }

    $w = $info[0];
    $h = $info[1];

    $ratio = 'Custom';
    if ($w > 0 && $h > 0) {
        $decimal = round($w / $h, 2);
        if ($decimal == 1.0) {
            $ratio = '1:1 (Square)';
        } elseif ($decimal == 1.78 || $decimal == 1.77) {
            $ratio = '16:9 (Widescreen)';
        } elseif ($decimal == 1.33) {
            $ratio = '4:3 (Standard)';
        } elseif ($decimal == 1.5) {
            $ratio = '3:2 (Photo)';
        } elseif ($decimal >= 2.3 && $decimal <= 2.45) {
            $ratio = '2.4:1 / 21:9 (Ultra-Wide Banner)';
        } elseif ($decimal >= 2.9 && $decimal <= 3.2) {
            $ratio = '3:1 (Panoramic Slider)';
        } elseif ($decimal == 0.56 || $decimal == 0.57) {
            $ratio = '9:16 (Vertical)';
        } elseif ($decimal == 0.75) {
            $ratio = '3:4 (Portrait)';
        } else {
            $gcd = function($a, $b) use (&$gcd) { return ($a % $b) ? $gcd($b, $a % $b) : $b; };
            $g = $gcd($w, $h);
            $rw = round($w / $g);
            $rh = round($h / $g);
            if ($rw <= 24 && $rh <= 24) {
                $ratio = "{$rw}:{$rh}";
            } else {
                $ratio = "{$decimal}:1";
            }
        }
    }

    return [
        'exists' => true,
        'width' => $w,
        'height' => $h,
        'dimensions' => "{$w} × {$h} px",
        'ratio' => $ratio,
        'size' => $size,
        'size_formatted' => $sizeFormatted,
        'ext' => $ext
    ];
}

