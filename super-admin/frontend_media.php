<?php
/**
 * Vishal Web Studio - Super Admin Frontend Website Media Library
 * Centralized upload and management for public website photos, hero banners, slider images, and graphics.
 * With exact image dimensions, aspect ratios, file size counters, and target display placement indicators.
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_super_admin();

$pdo = db();

// Handle Media Upload POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_frontend_media'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Session expired. Please try again.');
    } else {
        $title = trim($_POST['title'] ?? 'Frontend Asset');
        $category = trim($_POST['category'] ?? 'general');

        if (!empty($_FILES['media_file']['name'])) {
            $uploadRes = upload_file($_FILES['media_file'], 'frontend', ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif', 'avif', 'mp4', 'webm', 'json']);
            if ($uploadRes['success']) {
                $fileUrl = $uploadRes['path'];

                $insMed = $pdo->prepare("INSERT INTO frontend_media (title, category, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?)");
                $insMed->execute([$title, $category, $fileUrl, $uploadRes['size'], $uploadRes['media_type']]);

                log_activity(current_user_id(), 'frontend_media_uploaded', 'frontend_media', (int)$pdo->lastInsertId(), "Uploaded frontend photo '{$title}' to category '{$category}'");
                set_flash('success', "Photo '{$title}' uploaded successfully to frontend media library!");
            } else {
                set_flash('danger', $uploadRes['error']);
            }
        }
        header('Location: ' . BASE_URL . '/super-admin/frontend_media.php');
        exit;
    }
}

// Handle Delete Media
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT file_path FROM frontend_media WHERE id = ?");
    $stmt->execute([$delId]);
    $file = $stmt->fetch();

    if ($file) {
        $fullPath = ROOT_PATH . DIRECTORY_SEPARATOR . $file['file_path'];
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
        $pdo->prepare("DELETE FROM frontend_media WHERE id = ?")->execute([$delId]);
        set_flash('success', 'Media asset removed successfully.');
    }
    header('Location: ' . BASE_URL . '/super-admin/frontend_media.php');
    exit;
}

$activeCategory = $_GET['cat'] ?? 'all';
$query = "SELECT * FROM frontend_media";
if ($activeCategory !== 'all') {
    $query .= " WHERE category = " . $pdo->quote($activeCategory);
}
$query .= " ORDER BY id DESC";
$mediaList = $pdo->query($query)->fetchAll();

// Calculate total storage size and media count
$totalCount = (int)$pdo->query("SELECT COUNT(*) FROM frontend_media")->fetchColumn();
$totalBytes = (int)$pdo->query("SELECT SUM(file_size) FROM frontend_media")->fetchColumn();
$totalSizeFormatted = $totalBytes >= 1048576 ? round($totalBytes / 1048576, 2) . ' MB' : round($totalBytes / 1024, 1) . ' KB';

// Placement guide map
$categoryPlacements = [
    'banners' => ['name' => 'Hero & Sliders', 'dims' => '1920 × 800 px', 'ratio' => '2.4:1 / 16:9', 'place' => 'Homepage Top 3D Carousel Banner'],
    'logos' => ['name' => 'Logos & Icons', 'dims' => '512 × 512 px', 'ratio' => '1:1 Square', 'place' => 'Top Navbar, Footer & Sidebar Branding'],
    'gallery' => ['name' => 'Gallery & Sections', 'dims' => '800 × 600 px', 'ratio' => '4:3 Standard', 'place' => 'Sector Cards & Portfolio Showcase'],
    'promo' => ['name' => 'Promotional Artwork', 'dims' => '1200 × 630 px', 'ratio' => '1.91:1 / 16:9', 'place' => 'Social Share & Feature Callout Cards'],
    'general' => ['name' => 'General Media', 'dims' => 'Auto', 'ratio' => 'Custom', 'place' => 'Content Pages & Blog Articles']
];

$pageTitle = 'Frontend Website Photo & Media Library';
$adminNav = 'frontend_media';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <div>
        <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
            Centralized photo library with live measured pixel sizes, aspect ratios, and target website display placement guides.
        </p>
    </div>

    <div style="display: flex; gap: 10px;">
        <a href="<?= BASE_URL ?>/super-admin/frontend_cms.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-sliders-h"></i> Frontend Website Editor
        </a>
        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('uploadMediaModal')" style="box-shadow: 0 4px 14px rgba(7, 84, 184, 0.28);">
            <i class="fas fa-cloud-upload-alt"></i> Upload New Photo / Media
        </button>
    </div>
</div>

<!-- =========================================================================
     STORAGE METRICS & IMAGE DIMENSION / ASPECT RATIO PLACEMENT GUIDE
     ========================================================================= -->
<div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%); border: 1.5px solid #bfdbfe;">
    <div class="card-body" style="padding: 18px 22px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <h4 style="margin: 0; font-size: 1.05rem; color: #1e3a8a; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-ruler-combined text-primary"></i> Image Sizes, Aspect Ratios &amp; Placement Standard
            </h4>
            <div style="display: flex; gap: 12px; align-items: center;">
                <span class="badge badge-primary" style="font-size: 12px; padding: 6px 12px;"><i class="fas fa-images"></i> Total Photos: <?= $totalCount ?></span>
                <span class="badge badge-secondary" style="font-size: 12px; padding: 6px 12px;"><i class="fas fa-hdd"></i> Total Storage: <?= $totalSizeFormatted ?></span>
            </div>
        </div>

        <div class="grid-4" style="gap: 14px;">
            <?php foreach ($categoryPlacements as $catKey => $catInfo): ?>
                <?php if ($catKey === 'general') continue; ?>
                <div style="background: #ffffff; padding: 12px 14px; border-radius: 10px; border: 1px solid #dbeafe; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                    <div style="font-size: 0.78rem; font-weight: 800; color: #0284c7; text-transform: uppercase; margin-bottom: 4px;">
                        <?= e($catInfo['name']) ?>
                    </div>
                    <div style="font-size: 1.02rem; font-weight: 900; color: #0f172a;"><?= e($catInfo['dims']) ?></div>
                    <div style="font-size: 0.8rem; color: #475569; margin: 3px 0;"><strong>Ratio:</strong> <?= e($catInfo['ratio']) ?></div>
                    <div style="font-size: 0.74rem; color: #0369a1; background: #e0f2fe; padding: 3px 6px; border-radius: 6px; display: inline-block;">
                        📍 Place: <?= e($catInfo['place']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
    <a href="?cat=all" class="btn btn-sm <?= $activeCategory === 'all' ? 'btn-primary' : 'btn-secondary' ?>">All Photos &amp; Media (<?= count($mediaList) ?>)</a>
    <a href="?cat=banners" class="btn btn-sm <?= $activeCategory === 'banners' ? 'btn-primary' : 'btn-secondary' ?>">Hero &amp; Sliders</a>
    <a href="?cat=gallery" class="btn btn-sm <?= $activeCategory === 'gallery' ? 'btn-primary' : 'btn-secondary' ?>">Gallery &amp; Sections</a>
    <a href="?cat=logos" class="btn btn-sm <?= $activeCategory === 'logos' ? 'btn-primary' : 'btn-secondary' ?>">Logos &amp; Icons</a>
    <a href="?cat=promo" class="btn btn-sm <?= $activeCategory === 'promo' ? 'btn-primary' : 'btn-secondary' ?>">Promotional Artwork</a>
</div>

<!-- Media Grid -->
<div class="grid-4" style="gap: 20px;">
    <?php if (empty($mediaList)): ?>
        <div class="card" style="grid-column: 1 / -1; padding: 40px; text-align: center;">
            <div style="font-size: 3rem; color: #cbd5e1; margin-bottom: 12px;"><i class="fas fa-images"></i></div>
            <h3>No Photos in Media Library</h3>
            <p class="text-muted">Click the "Upload New Photo / Media" button above to upload hero slider images (1920x800 px), logos (512x512 px), or section artwork.</p>
        </div>
    <?php else: ?>
        <?php foreach ($mediaList as $item): ?>
            <?php
            $meta = get_image_metadata($item['file_path']);
            $ext = strtolower(pathinfo($item['file_path'], PATHINFO_EXTENSION));
            $isVideo = in_array($ext, ['mp4', 'webm', 'mov']);
            $fullUrl = BASE_URL . '/' . e($item['file_path']);
            $placement = $categoryPlacements[$item['category']]['place'] ?? 'Public Website Content';
            ?>
            <div class="card" style="box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-radius: 14px; overflow: hidden; display: flex; flex-direction: column;">
                <div style="height: 160px; background: #0f172a; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    <?php if ($isVideo): ?>
                        <video src="<?= $fullUrl ?>" style="width: 100%; height: 100%; object-fit: cover;"></video>
                        <span class="badge badge-primary" style="position: absolute; top: 8px; right: 8px;"><i class="fas fa-video"></i> Video</span>
                    <?php elseif ($meta['exists']): ?>
                        <img src="<?= $fullUrl ?>" alt="<?= e($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="color: #64748b; font-size: 2.5rem;"><i class="fas fa-file-image"></i></div>
                    <?php endif; ?>

                    <span class="badge badge-secondary" style="position: absolute; top: 8px; left: 8px; background: rgba(15,23,42,0.85); color: #fff; font-size: 11px;">
                        <?= e(ucfirst($item['category'])) ?>
                    </span>
                </div>

                <div class="card-body" style="padding: 14px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <strong style="display: block; font-size: 0.92rem; color: #0f172a; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= e($item['title']) ?>"><?= e($item['title']) ?></strong>
                        
                        <!-- Measured Dimensions, Aspect Ratio & Size Badges -->
                        <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px;">
                            <span class="badge badge-primary" style="font-size: 10px;"><?= $meta['dimensions'] ?></span>
                            <span class="badge badge-secondary" style="font-size: 10px;">Ratio: <?= $meta['ratio'] ?></span>
                            <span class="badge badge-secondary" style="font-size: 10px;"><?= $meta['size_formatted'] ?></span>
                        </div>

                        <!-- Target Display Location -->
                        <div style="font-size: 0.76rem; color: #0284c7; background: #f0f9ff; padding: 4px 8px; border-radius: 6px; border: 1px solid #e0f2fe; margin-bottom: 6px;">
                            <i class="fas fa-map-marker-alt text-danger"></i> <strong>Place:</strong> <?= e($placement) ?>
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px; margin-top: 10px;">
                        <button type="button" class="btn btn-secondary btn-sm" style="flex-grow: 1; font-size: 11px; padding: 6px 10px;" onclick="copyToClipboard('<?= e($item['file_path']) ?>', this)">
                            <i class="fas fa-copy"></i> Copy Path
                        </button>
                        <a href="<?= BASE_URL ?>/super-admin/frontend_media.php?delete=<?= $item['id'] ?>" class="btn btn-secondary btn-sm" style="padding: 6px 10px; color: #ef4444;" title="Delete Photo" data-confirm="Delete this photo permanently from the server?">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Upload Media Modal -->
<div class="modal-backdrop" id="uploadMediaModal">
    <div class="modal-dialog" style="max-width: 650px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-cloud-upload-alt text-primary" style="margin-right: 8px;"></i> Upload Frontend Photo / Asset</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="upload_frontend_media" value="1">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="med_category">Select Image Type &amp; Target Placement *</label>
                    <select name="category" id="med_category" class="form-select" onchange="updateUploadSpecs(this.value)">
                        <option value="banners">Hero &amp; Sliders (1920 × 800 px, Ratio 2.4:1 / 16:9 &rarr; Homepage Carousel)</option>
                        <option value="logos">Logos &amp; Favicons (512 × 512 px, Ratio 1:1 &rarr; Top Header &amp; Footer)</option>
                        <option value="gallery">Gallery &amp; Sections (800 × 600 px, Ratio 4:3 &rarr; Sector Cards &amp; Showcase)</option>
                        <option value="promo">Promotional Artwork (1200 × 630 px, Ratio 1.91:1 &rarr; Social Share &amp; Promo)</option>
                        <option value="general">General Media (Custom Dimensions &rarr; Pages &amp; Articles)</option>
                    </select>
                </div>

                <div id="uploadSpecNotice" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 0.82rem; color: #166534;">
                    <strong>📐 Recommended:</strong> <span id="specDims">1920 × 800 px</span> | <strong>Ratio:</strong> <span id="specRatio">2.4:1 (or 16:9)</span><br>
                    <strong>📍 Display Placement:</strong> <span id="specPlace">Public Website Homepage Hero Carousel Banner</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="file_input">Select Photo / Graphic File *</label>
                    <input type="file" name="media_file" id="file_input" class="form-control" accept="image/*,video/*,.json" required>
                    <div class="form-help">Accepted: JPG, PNG, WEBP, SVG, GIF, AVIF, MP4 (Max: 20MB)</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="med_title">Photo Title / Caption *</label>
                    <input type="text" name="title" id="med_title" class="form-control" placeholder="e.g. Modern High-Tech Cloud Banner" required>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-cloud-upload-alt"></i> Upload to Media Library</button>
            </div>
        </form>
    </div>
</div>

<script>
const specMap = {
    'banners': { dims: '1920 × 800 px', ratio: '2.4:1 / 21:9 Ultra-Wide (or 16:9)', place: 'Homepage Top 3D Hero Carousel Banner' },
    'logos': { dims: '512 × 512 px / 400 × 100 px', ratio: '1:1 Square or 4:1 Horizontal', place: 'Top Navbar, Footer Branding & Super Admin Sidebar' },
    'gallery': { dims: '800 × 600 px', ratio: '4:3 Standard Photo', place: 'Sector Showcase Cards & Portfolio Grid' },
    'promo': { dims: '1200 × 630 px', ratio: '1.91:1 / 16:9', place: 'Social Share Cards & Feature Callout Promos' },
    'general': { dims: 'Auto Sizing', ratio: 'Custom Ratio', place: 'Content Pages & Blog Articles' }
};

function updateUploadSpecs(cat) {
    const info = specMap[cat] || specMap['general'];
    document.getElementById('specDims').textContent = info.dims;
    document.getElementById('specRatio').textContent = info.ratio;
    document.getElementById('specPlace').textContent = info.place;
}

function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-success"></i> Copied!';
        setTimeout(() => { btn.innerHTML = original; }, 2000);
    });
}
</script>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
