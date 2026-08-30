<?php
/**
 * Vishal Web Studio - Super Admin Database & Website Backup & Disaster Recovery Center
 * Complete Backup and Restore options for Full Database SQL and Individual Client Website JSON bundles.
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();
$snapDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'snapshots';
if (!file_exists($snapDir)) {
    @mkdir($snapDir, 0777, true);
}

// Generate Full SQL Dump String Helper
function generate_sql_dump(PDO $pdo): string {
    $out = "-- ==========================================================\n";
    $out .= "-- Vishal Web Studio - Full Database Backup Dump\n";
    $out .= "-- Exported on: " . date('Y-m-d H:i:s') . "\n";
    $out .= "-- Generator: Automated Disaster Recovery Engine\n";
    $out .= "-- ==========================================================\n\n";

    $tables = [
        'users', 'clients', 'templates', 'websites', 'website_sections', 
        'website_pages', 'website_settings', 'services', 'gallery', 
        'testimonials', 'faqs', 'orders', 'contracts', 'invoices', 
        'payments', 'domains', 'hosting', 'support_tickets', 
        'ticket_messages', 'blog_posts', 'global_settings', 
        'frontend_slides', 'frontend_media', 'activity_logs'
    ];

    foreach ($tables as $tbl) {
        try {
            $rows = $pdo->query("SELECT * FROM {$tbl}")->fetchAll();
            $out .= "-- Table: {$tbl} (" . count($rows) . " rows)\n";
            foreach ($rows as $r) {
                $cols = array_keys($r);
                $vals = array_map(function($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote((string)$v);
                }, array_values($r));
                $out .= "INSERT INTO `{$tbl}` (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', $vals) . ");\n";
            }
            $out .= "\n";
        } catch (Exception $e) {}
    }
    return $out;
}

// Execute SQL Dump String Helper
function execute_sql_dump(PDO $pdo, string $sqlContent): array {
    $driver = Database::getInstance()->getDriver();
    $executed = 0;
    $errors = [];

    // Remove comments
    $lines = explode("\n", $sqlContent);
    $cleanSql = '';
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
            continue;
        }
        $cleanSql .= $line . "\n";
    }

    $statements = array_filter(array_map('trim', explode(';', $cleanSql)));

    if ($driver === 'mysql') {
        try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;"); } catch (Exception $e) {}
    }

    try {
        $pdo->beginTransaction();
        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                if ($driver === 'sqlite') {
                    $stmt = preg_replace('/INSERT INTO `?([a-zA-Z0-9_]+)`?/i', 'INSERT OR REPLACE INTO `$1`', $stmt);
                    $stmt = preg_replace('/ENGINE=InnoDB.*?;/i', '', $stmt);
                }
                $pdo->exec($stmt);
                $executed++;
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = $e->getMessage();
    }

    if ($driver === 'mysql') {
        try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;"); } catch (Exception $e) {}
    }

    return [
        'success' => empty($errors),
        'executed' => $executed,
        'errors' => $errors
    ];
}

// Restore Website JSON Bundle Helper
function restore_website_bundle(PDO $pdo, array $bundle): array {
    if (!isset($bundle['website']) || !isset($bundle['sections'])) {
        return ['success' => false, 'error' => 'Invalid website backup bundle format.'];
    }

    $site = $bundle['website'];
    $sections = $bundle['sections'] ?? [];
    $pages = $bundle['pages'] ?? [];
    $services = $bundle['services'] ?? [];
    $faqs = $bundle['faqs'] ?? [];
    $testimonials = $bundle['testimonials'] ?? [];

    try {
        $pdo->beginTransaction();
        $siteId = (int)$site['id'];
        $exists = $pdo->query("SELECT id FROM websites WHERE id = {$siteId}")->fetchColumn();

        if ($exists) {
            $upd = $pdo->prepare("UPDATE websites SET name = ?, slug = ?, client_id = ?, template_id = ?, status = ?, primary_color = ?, secondary_color = ? WHERE id = ?");
            $upd->execute([$site['name'], $site['slug'], $site['client_id'], $site['template_id'], $site['status'], $site['primary_color'] ?? '#0754b8', $site['secondary_color'] ?? '#ef1515', $siteId]);
        } else {
            $ins = $pdo->prepare("INSERT INTO websites (id, name, slug, client_id, template_id, status, primary_color, secondary_color) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$siteId, $site['name'], $site['slug'], $site['client_id'], $site['template_id'], $site['status'], $site['primary_color'] ?? '#0754b8', $site['secondary_color'] ?? '#ef1515']);
        }

        // Recreate sections
        $pdo->prepare("DELETE FROM website_sections WHERE website_id = ?")->execute([$siteId]);
        $secIns = $pdo->prepare("INSERT INTO website_sections (website_id, section_name, section_type, title, subtitle, content, is_visible, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($sections as $s) {
            $secIns->execute([$siteId, $s['section_name'], $s['section_type'], $s['title'], $s['subtitle'], $s['content'], $s['is_visible'] ?? 1, $s['sort_order'] ?? 0]);
        }

        // Recreate pages
        $pdo->prepare("DELETE FROM website_pages WHERE website_id = ?")->execute([$siteId]);
        $pgIns = $pdo->prepare("INSERT INTO website_pages (website_id, title, slug, content, is_published, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($pages as $p) {
            $pgIns->execute([$siteId, $p['title'], $p['slug'], $p['content'] ?? '', $p['is_published'] ?? 1, $p['sort_order'] ?? 0]);
        }

        // Recreate services
        $pdo->prepare("DELETE FROM services WHERE website_id = ?")->execute([$siteId]);
        $srvIns = $pdo->prepare("INSERT INTO services (website_id, name, description, price, icon) VALUES (?, ?, ?, ?, ?)");
        foreach ($services as $srv) {
            $srvIns->execute([$siteId, $srv['name'], $srv['description'], $srv['price'] ?? 0, $srv['icon'] ?? 'bi bi-check-circle']);
        }

        // Recreate FAQs
        $pdo->prepare("DELETE FROM faqs WHERE website_id = ?")->execute([$siteId]);
        $faqIns = $pdo->prepare("INSERT INTO faqs (website_id, question, answer, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($faqs as $f) {
            $faqIns->execute([$siteId, $f['question'], $f['answer'], $f['sort_order'] ?? 0]);
        }

        // Recreate testimonials
        $pdo->prepare("DELETE FROM testimonials WHERE website_id = ?")->execute([$siteId]);
        $testIns = $pdo->prepare("INSERT INTO testimonials (website_id, client_name, client_role, review_text, rating) VALUES (?, ?, ?, ?, ?)");
        foreach ($testimonials as $t) {
            $testIns->execute([$siteId, $t['client_name'], $t['client_role'], $t['review_text'], $t['rating'] ?? 5]);
        }

        $pdo->commit();
        return ['success' => true, 'site_name' => $site['name']];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 1. Handle Download Live SQL Backup
if (isset($_GET['action']) && $_GET['action'] === 'download_sql') {
    $filename = 'vishal_web_studio_backup_' . date('Y-m-d_His') . '.sql';
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo generate_sql_dump($pdo);
    log_activity(current_user_id(), 'backup_downloaded', 'system', null, 'Super Admin downloaded full database SQL backup.');
    exit;
}

// 2. Handle Create Server Point-in-Time Snapshot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_server_snapshot'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrfToken)) {
        $snapName = 'snapshot_' . date('Y-m-d_His') . '.sql';
        $snapPath = $snapDir . DIRECTORY_SEPARATOR . $snapName;
        $sqlData = generate_sql_dump($pdo);
        file_put_contents($snapPath, $sqlData);

        log_activity(current_user_id(), 'snapshot_created', 'backups', null, "Created server snapshot '{$snapName}'");
        set_flash('success', "New point-in-time snapshot '{$snapName}' generated successfully on server!");
        header('Location: ' . BASE_URL . '/super-admin/backups.php');
        exit;
    }
}

// 3. Handle Restore from Uploaded SQL File
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_uploaded_sql'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        if (!empty($_FILES['sql_file']['tmp_name'])) {
            $sqlContent = file_get_contents($_FILES['sql_file']['tmp_name']);
            $res = execute_sql_dump($pdo, $sqlContent);
            if ($res['success']) {
                log_activity(current_user_id(), 'database_restored', 'system', null, "Restored database from uploaded file ({$res['executed']} statements).");
                set_flash('success', "Database successfully restored! Executed {$res['executed']} SQL statements.");
            } else {
                set_flash('danger', 'Database restore encountered errors: ' . implode(', ', $res['errors']));
            }
        } else {
            set_flash('danger', 'Please choose a valid .sql backup file to restore.');
        }
        header('Location: ' . BASE_URL . '/super-admin/backups.php');
        exit;
    }
}

// 4. Handle 1-Click Restore from Existing Server Snapshot
if (isset($_GET['restore_snapshot'])) {
    $snapFile = basename($_GET['restore_snapshot']);
    $fullPath = $snapDir . DIRECTORY_SEPARATOR . $snapFile;
    if (file_exists($fullPath)) {
        $sqlContent = file_get_contents($fullPath);
        $res = execute_sql_dump($pdo, $sqlContent);
        if ($res['success']) {
            log_activity(current_user_id(), 'snapshot_restored', 'system', null, "Restored database from server snapshot '{$snapFile}' ({$res['executed']} statements).");
            set_flash('success', "Database successfully restored from snapshot '{$snapFile}'! ({$res['executed']} statements executed).");
        } else {
            set_flash('danger', 'Snapshot restore failed: ' . implode(', ', $res['errors']));
        }
    } else {
        set_flash('danger', 'Selected snapshot file does not exist on server.');
    }
    header('Location: ' . BASE_URL . '/super-admin/backups.php');
    exit;
}

// 5. Handle Delete Server Snapshot
if (isset($_GET['delete_snapshot'])) {
    $snapFile = basename($_GET['delete_snapshot']);
    $fullPath = $snapDir . DIRECTORY_SEPARATOR . $snapFile;
    if (file_exists($fullPath)) {
        @unlink($fullPath);
        set_flash('success', "Snapshot '{$snapFile}' deleted from server.");
    }
    header('Location: ' . BASE_URL . '/super-admin/backups.php');
    exit;
}

// 6. Handle Download Snapshot File
if (isset($_GET['download_snapshot'])) {
    $snapFile = basename($_GET['download_snapshot']);
    $fullPath = $snapDir . DIRECTORY_SEPARATOR . $snapFile;
    if (file_exists($fullPath)) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $snapFile . '"');
        readfile($fullPath);
        exit;
    }
}

// 7. Handle Single Website JSON Restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_website_json'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        if (!empty($_FILES['json_file']['tmp_name'])) {
            $jsonContent = file_get_contents($_FILES['json_file']['tmp_name']);
            $bundle = json_decode($jsonContent, true);
            if ($bundle) {
                $res = restore_website_bundle($pdo, $bundle);
                if ($res['success']) {
                    log_activity(current_user_id(), 'website_json_restored', 'websites', null, "Restored website '{$res['site_name']}' from JSON backup.");
                    set_flash('success', "Client website '{$res['site_name']}' restored successfully with all pages, sections, services & FAQs!");
                } else {
                    set_flash('danger', 'Website restore failed: ' . $res['error']);
                }
            } else {
                set_flash('danger', 'Invalid JSON file format.');
            }
        }
        header('Location: ' . BASE_URL . '/super-admin/backups.php');
        exit;
    }
}

// 8. Handle Single Website JSON Export
if (isset($_GET['export_site'])) {
    $siteId = (int)$_GET['export_site'];
    $stmt = $pdo->prepare("SELECT * FROM websites WHERE id = ?");
    $stmt->execute([$siteId]);
    $site = $stmt->fetch();

    if ($site) {
        $sections = $pdo->query("SELECT * FROM website_sections WHERE website_id = {$siteId}")->fetchAll();
        $pages = $pdo->query("SELECT * FROM website_pages WHERE website_id = {$siteId}")->fetchAll();
        $services = $pdo->query("SELECT * FROM services WHERE website_id = {$siteId}")->fetchAll();
        $faqs = $pdo->query("SELECT * FROM faqs WHERE website_id = {$siteId}")->fetchAll();
        $testimonials = $pdo->query("SELECT * FROM testimonials WHERE website_id = {$siteId}")->fetchAll();

        $bundle = [
            'meta' => [
                'exported_at' => date('c'),
                'generator' => 'Vishal Web Studio Backup Engine',
                'version' => '1.0'
            ],
            'website' => $site,
            'sections' => $sections,
            'pages' => $pages,
            'services' => $services,
            'faqs' => $faqs,
            'testimonials' => $testimonials
        ];

        $jsonName = 'website_backup_' . slugify($site['name']) . '_' . date('Y-m-d') . '.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $jsonName . '"');
        echo json_encode($bundle, JSON_PRETTY_PRINT);
        log_activity(current_user_id(), 'website_json_exported', 'websites', $siteId, "Exported JSON bundle for website '{$site['name']}'");
        exit;
    }
}

// List all server snapshots
$snapshots = [];
if (is_dir($snapDir)) {
    $files = scandir($snapDir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..' && str_ends_with($f, '.sql')) {
            $fPath = $snapDir . DIRECTORY_SEPARATOR . $f;
            $snapshots[] = [
                'filename' => $f,
                'path' => $fPath,
                'size' => filesize($fPath),
                'size_formatted' => filesize($fPath) >= 1048576 ? round(filesize($fPath) / 1048576, 2) . ' MB' : round(filesize($fPath) / 1024, 1) . ' KB',
                'created_at' => filemtime($fPath)
            ];
        }
    }
    // Sort newest first
    usort($snapshots, fn($a, $b) => $b['created_at'] <=> $a['created_at']);
}

// Fetch all websites for individual backup selection
$websites = $pdo->query("SELECT w.*, c.business_name FROM websites w JOIN clients c ON w.client_id = c.id ORDER BY w.id DESC")->fetchAll();

$pageTitle = 'Backups & Disaster Recovery';
$adminNav = 'backups';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<!-- Header Actions -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <div>
        <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
            Complete full-system SQL database snapshots, 1-click restore recovery, and individual client website JSON importers.
        </p>
    </div>

    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <form method="POST" action="" style="margin: 0;">
            <?= csrf_field() ?>
            <input type="hidden" name="create_server_snapshot" value="1">
            <button type="submit" class="btn btn-secondary btn-sm">
                <i class="fas fa-camera"></i> Create Snapshot Now
            </button>
        </form>
        <button type="button" class="btn btn-danger btn-sm" onclick="openModal('restoreSqlModal')" style="box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);">
            <i class="fas fa-history"></i> Restore Database (.sql)
        </button>
        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('restoreJsonModal')" style="box-shadow: 0 4px 12px rgba(7, 84, 184, 0.25);">
            <i class="fas fa-file-import"></i> Restore Website (.json)
        </button>
    </div>
</div>

<!-- Overview Cards Grid -->
<div class="grid-3" style="margin-bottom: 30px; gap: 20px;">
    <!-- 1. Full System SQL Backup -->
    <div class="card" style="box-shadow: var(--shadow-md); border-radius: var(--radius-lg); display: flex; flex-direction: column; justify-content: space-between;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-database text-primary" style="margin-right: 8px;"></i> Full Database Backup</h3>
        </div>
        <div class="card-body">
            <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 18px;">
                Download a complete SQL dump of all database tables (Clients, Websites, Orders, Contracts, Payments, Blog, Media, Invoices &amp; Settings).
            </p>
            <a href="<?= BASE_URL ?>/super-admin/backups.php?action=download_sql" class="btn btn-primary btn-sm w-100" style="width: 100%; justify-content: center;">
                <i class="fas fa-download"></i> Download Full SQL Backup (.sql)
            </a>
        </div>
    </div>

    <!-- 2. Restore Database Engine -->
    <div class="card" style="box-shadow: var(--shadow-md); border-radius: var(--radius-lg); display: flex; flex-direction: column; justify-content: space-between; border-left: 4px solid #ef4444;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-undo-alt text-danger" style="margin-right: 8px;"></i> Database Restore Engine</h3>
        </div>
        <div class="card-body">
            <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 18px;">
                Upload and apply any previously saved <code>.sql</code> backup file to completely restore system tables and records with safety checks.
            </p>
            <button type="button" class="btn btn-danger btn-sm w-100" style="width: 100%; justify-content: center;" onclick="openModal('restoreSqlModal')">
                <i class="fas fa-upload"></i> Upload &amp; Restore SQL Backup
            </button>
        </div>
    </div>

    <!-- 3. Snapshot Health & Security -->
    <div class="card" style="box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); background: #f8fafc;">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-shield-alt text-success" style="margin-right: 8px;"></i> Snapshot Health &amp; Security</h3>
        </div>
        <div class="card-body" style="font-size: 0.85rem; color: #334155; line-height: 1.6;">
            <p style="margin-bottom: 6px;"><i class="fas fa-check-circle text-success"></i> <strong>Automated Driver:</strong> Active MySQL / SQLite engine.</p>
            <p style="margin-bottom: 6px;"><i class="fas fa-check-circle text-success"></i> <strong>Tenant Portability:</strong> Portable JSON website bundles.</p>
            <p style="margin-bottom: 0;"><i class="fas fa-check-circle text-success"></i> <strong>SHA-256 Signatures:</strong> Digital contracts tamper-proofed.</p>
        </div>
    </div>
</div>

<!-- =========================================================================
     SERVER POINT-IN-TIME SNAPSHOTS (WITH 1-CLICK RESTORE)
     ========================================================================= -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h3 class="card-title"><i class="fas fa-history text-info" style="margin-right: 8px;"></i> Server Point-in-Time Snapshots (<?= count($snapshots) ?>)</h3>
        <span style="font-size: 0.82rem; color: #64748b;">Stored locally in <code>/backups/snapshots/</code></span>
    </div>
    <div class="table-responsive">
        <?php if (empty($snapshots)): ?>
            <div style="padding: 30px; text-align: center; color: #64748b;">
                <i class="fas fa-hdd" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                <strong>No Server Snapshots Created Yet</strong>
                <p style="font-size: 0.85rem; margin-top: 4px;">Click "Create Snapshot Now" at the top right to save an instant point-in-time restore point.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Snapshot File</th>
                        <th>Created Date &amp; Time</th>
                        <th>File Size</th>
                        <th>Backup Type</th>
                        <th style="text-align: right; width: 220px;">Actions &amp; Restore</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($snapshots as $snap): ?>
                        <tr>
                            <td>
                                <strong style="color: #0f172a;"><i class="fas fa-file-code text-primary" style="margin-right: 6px;"></i> <?= e($snap['filename']) ?></strong>
                            </td>
                            <td style="font-size: 0.85rem; color: #475569;">
                                <?= date('d M Y, h:i A', $snap['created_at']) ?>
                            </td>
                            <td>
                                <span class="badge badge-secondary" style="font-size: 11px;"><?= $snap['size_formatted'] ?></span>
                            </td>
                            <td>
                                <span class="badge badge-primary" style="font-size: 11px;"><i class="fas fa-database"></i> Full Database SQL</span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= BASE_URL ?>/super-admin/backups.php?download_snapshot=<?= urlencode($snap['filename']) ?>" class="btn btn-secondary btn-sm" title="Download Snapshot">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <a href="<?= BASE_URL ?>/super-admin/backups.php?restore_snapshot=<?= urlencode($snap['filename']) ?>" class="btn btn-danger btn-sm" title="Restore this snapshot" data-confirm="WARNING: Restoring this snapshot will replace current database tables with data from this snapshot. Are you sure you want to proceed?">
                                    <i class="fas fa-undo-alt"></i> Restore Now
                                </a>
                                <a href="<?= BASE_URL ?>/super-admin/backups.php?delete_snapshot=<?= urlencode($snap['filename']) ?>" class="btn btn-secondary btn-sm" title="Delete Snapshot" data-confirm="Delete this snapshot file from the server?">
                                    <i class="fas fa-trash text-danger"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- =========================================================================
     INDIVIDUAL CLIENT WEBSITE EXPORTS & IMPORTER
     ========================================================================= -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h3 class="card-title"><i class="fas fa-file-archive text-warning" style="margin-right: 8px;"></i> Individual Client Website JSON Exports &amp; Restore</h3>
        <button type="button" class="btn btn-secondary btn-sm" onclick="openModal('restoreJsonModal')">
            <i class="fas fa-file-import text-primary"></i> Restore Website (.json)
        </button>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Website Name</th>
                    <th>Client Business</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($websites as $w): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--dark);"><?= e($w['name']) ?></strong><br>
                            <span style="font-size: 0.78rem; color: var(--text-muted);"><?= e($w['domain'] ?? $w['slug']) ?></span>
                        </td>
                        <td><?= e($w['business_name']) ?></td>
                        <td><?= render_status_badge($w['status']) ?></td>
                        <td style="font-size: 0.82rem; color: var(--text-muted);"><?= format_date($w['created_at']) ?></td>
                        <td style="text-align: right;">
                            <a href="<?= BASE_URL ?>/super-admin/backups.php?export_site=<?= $w['id'] ?>" class="btn btn-secondary btn-sm" title="Export JSON Website Bundle">
                                <i class="fas fa-file-download"></i> Export JSON
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Restore Database SQL -->
<div class="modal-backdrop" id="restoreSqlModal">
    <div class="modal-dialog" style="max-width: 600px;">
        <div class="modal-header" style="background: #fef2f2; border-bottom: 1px solid #fee2e2;">
            <h3 class="modal-title" style="color: #991b1b;"><i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i> Restore Database (.sql)</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="restore_uploaded_sql" value="1">

            <div class="modal-body">
                <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: 10px; padding: 14px; margin-bottom: 18px; color: #9f1239; font-size: 0.88rem; line-height: 1.5;">
                    <strong><i class="fas fa-shield-alt"></i> Caution &amp; Disaster Recovery Notice:</strong><br>
                    Restoring a database file will execute the SQL dump statements against the database. Please ensure your backup file is from a trusted source.
                </div>

                <div class="form-group">
                    <label class="form-label" for="sql_file_input">Select .sql Database Backup File *</label>
                    <input type="file" name="sql_file" id="sql_file_input" class="form-control" accept=".sql,text/plain" required>
                    <div class="form-help">Accepted: .sql dump files generated by Vishal Web Studio Backup Engine.</div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-undo-alt"></i> Start Database Restore</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Restore Website JSON -->
<div class="modal-backdrop" id="restoreJsonModal">
    <div class="modal-dialog" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-file-import text-primary" style="margin-right: 8px;"></i> Restore / Import Client Website (.json)</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="restore_website_json" value="1">

            <div class="modal-body">
                <p class="text-muted" style="font-size: 0.88rem; margin-bottom: 16px;">
                    Upload a previously exported <code>.json</code> website bundle. All pages, sections, services, testimonials, and FAQs will be restored.
                </p>

                <div class="form-group">
                    <label class="form-label" for="json_file_input">Select .json Website Backup File *</label>
                    <input type="file" name="json_file" id="json_file_input" class="form-control" accept=".json,application/json" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-file-import"></i> Restore Website</button>
            </div>
        </form>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
