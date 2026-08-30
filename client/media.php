<?php
/**
 * Vishal Web Studio - Client Media Library, Photos, 3D Videos & Animations
 * With live image dimensions, aspect ratios, file size counts, and target website display placement guides.
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_client();

$client = get_current_client_record();
$website = get_current_client_website();
$pdo = db();

if (!$website) die("<h3>No website found.</h3>");

$websiteId = $website['id'];
$clientId = $client['id'];

// Handle Media Upload POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_media'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrfToken)) {
        $title = trim($_POST['title'] ?? 'Media Item');
        $category = trim($_POST['category'] ?? 'General');

        if (!empty($_FILES['media_file']['name'])) {
            $uploadRes = upload_file($_FILES['media_file'], 'media', ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif', 'avif', 'mp4', 'webm', 'mov', 'json']);
            if ($uploadRes['success']) {
                $fileUrl = $uploadRes['path'];

                // Insert into gallery
                $insGal = $pdo->prepare("INSERT INTO gallery (website_id, title, category, image_path) VALUES (?, ?, ?, ?)");
                $insGal->execute([$websiteId, $title, $category, $fileUrl]);

                // Insert into media
                $insMed = $pdo->prepare("INSERT INTO media (website_id, client_id, file_name, file_path, file_size, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insMed->execute([$websiteId, $clientId, $uploadRes['original_name'], $fileUrl, $uploadRes['size'], $uploadRes['media_type'], current_user_id()]);

                log_activity(current_user_id(), 'media_uploaded', 'gallery', (int)$pdo->lastInsertId(), "Uploaded {$uploadRes['media_type']} '{$title}' to media library");
                set_flash('success', ucfirst($uploadRes['media_type']) . " '{$title}' uploaded successfully!");
            } else {
                set_flash('danger', $uploadRes['error']);
            }
        }
        header('Location: ' . BASE_URL . '/client/media.php');
        exit;
    }
}

// Handle Delete Media
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM gallery WHERE id = ? AND website_id = ?")->execute([$delId, $websiteId]);
    set_flash('success', 'Media item removed from your website.');
    header('Location: ' . BASE_URL . '/client/media.php');
    exit;
}

$galleryItems = $pdo->query("SELECT * FROM gallery WHERE website_id = {$websiteId} ORDER BY sort_order ASC, id DESC")->fetchAll();

$pageTitle = 'Photos, 3D Videos & Media Assets';
$clientNav = 'media';
require_once dirname(__DIR__) . '/includes/client_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <div>
        <p class="text-muted" style="margin: 0; font-size: 0.95rem;">
            Total Uploaded Media: <strong><?= count($galleryItems) ?></strong> items (Photos, 3D Videos &amp; Animations).
        </p>
    </div>

    <button type="button" class="btn btn-primary btn-sm" onclick="openMediaModal()" style="box-shadow: 0 4px 12px rgba(7,84,184,0.25);">
        <i class="fas fa-cloud-upload-alt"></i> Upload Photo, Video or Animation
    </button>
</div>

<!-- Image Sizes & Aspect Ratio Placement Standard Guide for Client -->
<div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border: 1.5px solid #bbf7d0;">
    <div class="card-body" style="padding: 16px 20px;">
        <h4 style="margin: 0 0 10px; font-size: 0.95rem; color: #166534; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-ruler-combined text-success"></i> Recommended Image Dimensions &amp; Aspect Ratios for Your Website:
        </h4>
        <div class="grid-4" style="gap: 12px;">
            <div style="background: #ffffff; padding: 10px 12px; border-radius: 8px; border: 1px solid #dcfce7;">
                <div style="font-size: 0.75rem; font-weight: 800; color: #15803d; text-transform: uppercase;">1. Brand Logo</div>
                <div style="font-size: 0.95rem; font-weight: 900; color: #0f172a;">512 &times; 512 px (1:1)</div>
                <div style="font-size: 0.73rem; color: #166534;">📍 Top Header &amp; Footer</div>
            </div>
            <div style="background: #ffffff; padding: 10px 12px; border-radius: 8px; border: 1px solid #dcfce7;">
                <div style="font-size: 0.75rem; font-weight: 800; color: #15803d; text-transform: uppercase;">2. Hero Banner</div>
                <div style="font-size: 0.95rem; font-weight: 900; color: #0f172a;">1920 &times; 800 px (2.4:1)</div>
                <div style="font-size: 0.73rem; color: #166534;">📍 Main Page Hero Top</div>
            </div>
            <div style="background: #ffffff; padding: 10px 12px; border-radius: 8px; border: 1px solid #dcfce7;">
                <div style="font-size: 0.75rem; font-weight: 800; color: #15803d; text-transform: uppercase;">3. Gallery / Products</div>
                <div style="font-size: 0.95rem; font-weight: 900; color: #0f172a;">800 &times; 600 px (4:3)</div>
                <div style="font-size: 0.73rem; color: #166534;">📍 Photo Gallery &amp; Cards</div>
            </div>
            <div style="background: #ffffff; padding: 10px 12px; border-radius: 8px; border: 1px solid #dcfce7;">
                <div style="font-size: 0.75rem; font-weight: 800; color: #15803d; text-transform: uppercase;">4. Promo Reels / Video</div>
                <div style="font-size: 0.95rem; font-weight: 900; color: #0f172a;">1920 &times; 1080 px (16:9)</div>
                <div style="font-size: 0.73rem; color: #166534;">📍 3D Video Showcase</div>
            </div>
        </div>
    </div>
</div>

<!-- Gallery Grid -->
<div class="grid-3" style="gap: 20px;">
    <?php if (empty($galleryItems)): ?>
        <div class="card" style="grid-column: 1 / -1; padding: 40px; text-align: center;">
            <div style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 10px;"><i class="fas fa-photo-video"></i></div>
            <h3>No Media in Library</h3>
            <p class="text-muted">Upload high-resolution photos, 3D product videos, promo reels, or animated graphics to showcase on your website.</p>
        </div>
    <?php else: ?>
        <?php foreach ($galleryItems as $item): ?>
            <?php
            $meta = get_image_metadata($item['image_path']);
            $ext = strtolower(pathinfo($item['image_path'], PATHINFO_EXTENSION));
            $isVideo = in_array($ext, ['mp4', 'webm', 'mov', 'm4v']);
            $isJson = in_array($ext, ['json']);
            ?>
            <div class="card" style="box-shadow: 0 10px 25px rgba(0,0,0,0.06); border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; border: 1.5px solid #e2e8f0;">
                <div style="height: 190px; background: #0f172a; overflow: hidden; position: relative;">
                    <?php if ($isVideo): ?>
                        <video src="<?= BASE_URL . '/' . e($item['image_path']) ?>" controls style="width: 100%; height: 100%; object-fit: cover;"></video>
                        <span class="badge badge-primary" style="position: absolute; top: 10px; right: 10px; background: #2563eb; color: #fff;">
                            <i class="fas fa-video"></i> Video
                        </span>
                    <?php elseif ($isJson): ?>
                        <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #1e293b; color: #38bdf8;">
                            <i class="fas fa-cubes" style="font-size: 3rem; margin-bottom: 8px;"></i>
                            <span style="font-size: 11px; font-weight: 700;">Lottie 3D Animation</span>
                        </div>
                        <span class="badge badge-info" style="position: absolute; top: 10px; right: 10px;">
                            <i class="fas fa-magic"></i> Animation
                        </span>
                    <?php elseif ($meta['exists']): ?>
                        <img src="<?= BASE_URL . '/' . e($item['image_path']) ?>" alt="<?= e($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary) 0%, #1e293b 100%); color: #ffffff;">
                            <i class="fas fa-image" style="font-size: 2.5rem;"></i>
                        </div>
                    <?php endif; ?>

                    <span class="badge badge-secondary" style="position: absolute; top: 10px; left: 10px; background: rgba(15,23,42,0.85); color: #fff; font-size: 11px;">
                        <?= e($item['category']) ?>
                    </span>
                </div>

                <div class="card-body" style="padding: 16px; display: flex; flex-direction: column; justify-content: space-between; flex-grow: 1; background: #ffffff;">
                    <div>
                        <strong style="font-size: 0.95rem; color: #0f172a; display: block; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($item['title'] ?: 'Media Asset') ?></strong>
                        
                        <!-- Measured Dimensions, Aspect Ratio & File Size Badges -->
                        <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 10px;">
                            <span class="badge badge-primary" style="font-size: 10px;"><?= $meta['dimensions'] ?></span>
                            <span class="badge badge-secondary" style="font-size: 10px;">Ratio: <?= $meta['ratio'] ?></span>
                            <span class="badge badge-secondary" style="font-size: 10px;"><?= $meta['size_formatted'] ?></span>
                        </div>

                        <!-- Target Display Location -->
                        <div style="font-size: 0.76rem; color: #15803d; background: #f0fdf4; padding: 4px 8px; border-radius: 6px; border: 1px solid #dcfce7; margin-bottom: 8px;">
                            <i class="fas fa-map-marker-alt text-danger"></i> <strong>Placement:</strong> Public Website Gallery / Card
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                        <button type="button" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 5px 10px;" onclick="copyToClipboard('<?= e($item['image_path']) ?>', this)">
                            <i class="fas fa-copy"></i> Copy Path
                        </button>
                        <a href="<?= BASE_URL ?>/client/media.php?delete=<?= $item['id'] ?>" class="btn btn-secondary btn-sm" title="Delete Media" data-confirm="Remove this media item from your website gallery?">
                            <i class="fas fa-trash text-danger"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Upload Media Modal -->
<div class="modal-backdrop" id="mediaModal">
    <div class="modal-dialog" style="max-width: 620px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-cloud-upload-alt text-primary" style="margin-right: 8px;"></i> Upload Media (Photo, Video, Animation)</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="upload_media" value="1">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="med_type_guide">Select Image Type &amp; Recommended Specs *</label>
                    <select id="med_type_guide" class="form-select" onchange="updateClientUploadSpecs(this.value)">
                        <option value="gallery">Gallery Photo (800 × 600 px, Ratio 4:3 &rarr; Public Website Gallery)</option>
                        <option value="logo">Brand Logo (512 × 512 px, Ratio 1:1 &rarr; Website Navbar &amp; Footer)</option>
                        <option value="banner">Hero Banner (1920 × 800 px, Ratio 2.4:1 &rarr; Website Hero Top)</option>
                        <option value="video">3D Video / Reel (1920 × 1080 px, Ratio 16:9 &rarr; Video Showcase)</option>
                    </select>
                </div>

                <div id="clientSpecNotice" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 0.82rem; color: #166534;">
                    <strong>📐 Recommended:</strong> <span id="clientSpecDims">800 × 600 px</span> | <strong>Ratio:</strong> <span id="clientSpecRatio">4:3 Standard Photo</span><br>
                    <strong>📍 Display Location:</strong> <span id="clientSpecPlace">Website Gallery Showcase Grid</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="med_file">Select Media File *</label>
                    <input type="file" name="media_file" id="med_file" class="form-control" accept="image/*,video/*,.json" required>
                    <div class="form-help">Accepted: Photos (JPG, PNG, WEBP, SVG), Videos (MP4, WEBM), Animations (JSON, GIF). Max: 25MB.</div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="med_title">Title / Caption *</label>
                        <input type="text" name="title" id="med_title" class="form-control" placeholder="e.g. 3D Restaurant Tour / Special Thali" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="med_cat">Category Tag</label>
                        <input type="text" name="category" id="med_cat" class="form-control" value="Gallery" placeholder="e.g. Menu, Promo, Video Reel, Interior">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-cloud-upload-alt"></i> Upload &amp; Save Media</button>
            </div>
        </form>
    </div>
</div>

<script>
const clientSpecMap = {
    'gallery': { dims: '800 × 600 px', ratio: '4:3 Standard Photo', place: 'Website Gallery Showcase Grid', cat: 'Gallery' },
    'logo': { dims: '512 × 512 px', ratio: '1:1 Square', place: 'Website Top Header & Footer Branding', cat: 'Branding' },
    'banner': { dims: '1920 × 800 px', ratio: '2.4:1 / 21:9 Ultra-Wide', place: 'Website Main Hero Banner', cat: 'Hero Banner' },
    'video': { dims: '1920 × 1080 px', ratio: '16:9 Widescreen', place: 'Video Tour & Promotion Section', cat: 'Videos' }
};

function updateClientUploadSpecs(val) {
    const info = clientSpecMap[val] || clientSpecMap['gallery'];
    document.getElementById('clientSpecDims').textContent = info.dims;
    document.getElementById('clientSpecRatio').textContent = info.ratio;
    document.getElementById('clientSpecPlace').textContent = info.place;
    document.getElementById('med_cat').value = info.cat;
}

function openMediaModal() {
    openModal('mediaModal');
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
