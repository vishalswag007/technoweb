<?php
/**
 * Vishal Web Studio - Dedicated Client / Customer Login Portal (100% Isolated)
 * Strictly for Client Website Owners & Customer Admins
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pdo = db();

// Dynamic Client Website Brand Lookup
$siteSlug = trim($_GET['site'] ?? '');
$siteBrandName = null;

if (!empty($siteSlug)) {
    $sStmt = $pdo->prepare("SELECT name FROM websites WHERE slug = ?");
    $sStmt->execute([$siteSlug]);
    $siteRow = $sStmt->fetch();
    if ($siteRow) {
        $siteBrandName = $siteRow['name'];
    }
}

$displayTitle = !empty($siteBrandName) ? $siteBrandName : "Client Website Admin";
$displaySubtitle = !empty($siteBrandName) ? "Sign in to manage your website content, inquiries & services" : "Sign in to manage your live business website, orders & gallery";

// If already logged in as client, redirect to client dashboard
if (is_logged_in() && is_client()) {
    header('Location: ' . BASE_URL . '/client/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        if (attempt_login($email, $password)) {
            if (is_super_admin()) {
                set_flash('success', 'Super Admin signed into Client Management.');
                header('Location: ' . BASE_URL . '/client/index.php');
                exit;
            } else {
                set_flash('success', 'Welcome to your Website Management Panel!');
                header('Location: ' . BASE_URL . '/client/index.php');
                exit;
            }
        } else {
            $error = 'Invalid client email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($displayTitle) ?> | Client Admin Login</title>
    
    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Main 3D Styles -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/main.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 50% 0%, #e8f2ff 0%, #f4f8fc 50%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin: 0;
            padding: 0;
            color: #0f172a;
        }

        .client-login-wrapper {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .client-login-box {
            width: 100%;
            max-width: 440px;
        }

        .client-login-card {
            background: #ffffff;
            border-radius: 26px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.12), inset 0 1px 0 #ffffff;
            border: 1px solid #e2e8f0;
            padding: 38px 32px;
            position: relative;
            overflow: hidden;
        }

        .client-login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0754b8 0%, #2563eb 100%);
        }

        .client-icon-badge {
            width: 66px;
            height: 66px;
            border-radius: 22px;
            margin: 0 auto 16px;
            display: grid;
            place-items: center;
            font-size: 28px;
            color: #ffffff;
            background: linear-gradient(135deg, #0754b8 0%, #2563eb 100%);
            box-shadow: 0 10px 24px rgba(7, 84, 184, 0.28);
        }

        .form-control-custom {
            width: 100%;
            min-height: 48px;
            border-radius: 14px;
            border: 1.5px solid #cbd5e1;
            padding: 10px 16px;
            font-size: 14px;
            font-family: inherit;
            color: #0f172a;
            background: #ffffff;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
            box-sizing: border-box;
            display: block;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: #0754b8;
            box-shadow: 0 0 0 3.5px rgba(7, 84, 184, 0.15);
        }

        .client-footer-bar {
            text-align: center;
            padding: 20px;
            font-size: 12.5px;
            color: #64748b;
        }
    </style>
</head>
<body>

<?= render_flash_messages() ?>

<div class="client-login-wrapper">
    <div class="client-login-box">

        <!-- Standalone Brand Badge -->
        <div class="text-center" style="margin-bottom: 22px;">
            <a href="<?= BASE_URL ?>/index.php" style="display: inline-flex; align-items: center; gap: 10px; text-decoration: none;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #0754b8 0%, #2563eb 100%); display: grid; place-items: center; color: #ffffff; font-weight: 900; font-size: 18px; box-shadow: 0 6px 16px rgba(7,84,184,0.25);">
                    <i class="bi bi-display"></i>
                </div>
                <div style="text-align: left;">
                    <div style="font-weight: 900; font-size: 16px; color: #0f172a; line-height: 1.1;"><?= e(get_setting('business_name', 'Vishal Web Studio')) ?></div>
                    <div style="font-size: 11px; font-weight: 700; color: #0754b8; text-transform: uppercase; letter-spacing: 0.6px;">Client Cloud Manager</div>
                </div>
            </a>
        </div>

        <!-- 3D Client Login Card -->
        <div class="client-login-card">
            <div class="text-center" style="margin-bottom: 24px;">
                <div class="client-icon-badge">
                    <i class="bi bi-person-workspace"></i>
                </div>

                <h2 style="font-size: 24px; font-weight: 900; color: #0f172a; margin-bottom: 6px; letter-spacing: -0.5px;">
                    <?= e($displayTitle) ?>
                </h2>

                <p style="color: #64748b; font-size: 13.5px; line-height: 1.5; margin-top: 4px;">
                    <?= e($displaySubtitle) ?>
                </p>
            </div>

            <?php if ($error): ?>
                <div style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 12px 16px; border-radius: 14px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrf_field() ?>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 800; margin-bottom: 6px; color: #334155;">Client Email Address</label>
                    <input type="email" name="email" id="email" class="form-control-custom" placeholder="client@sharmarestaurant.com" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div style="margin-bottom: 22px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label style="font-size: 12px; font-weight: 800; margin-bottom: 0; color: #334155;">Password</label>
                        <a href="<?= BASE_URL ?>/public/forgot-password.php" style="font-size: 12px; font-weight: 700; color: #0754b8; text-decoration: none;">Forgot Password?</a>
                    </div>
                    <input type="password" name="password" id="password" class="form-control-custom" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-brand rounded-pill w-100" style="width: 100%; padding: 13px; font-size: 15px; background: linear-gradient(180deg, #0961d3 0%, #0754b8 100%); color: #ffffff; border: none; font-weight: 800; border-radius: 50px; box-shadow: 0 4px 0 #053b82, 0 8px 18px rgba(7,84,184,0.28); cursor: pointer;">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In to Website Panel
                </button>
            </form>

            <!-- 1-Click Client Access -->
            <div style="margin-top: 22px; padding-top: 16px; border-top: 1px dashed #e2e8f0; text-align: center;">
                <p style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 10px;">Quick 1-Click Client Access</p>
                <button type="button" class="btn btn-sm w-100" style="width: 100%; font-size: 13px; border-radius: 50px; background: #edf5ff; color: #0754b8; border: 1px solid #cce0ff; font-weight: 700; padding: 8px;" onclick="fillClientLogin()">
                    <i class="bi bi-person-check-fill"></i> Fill 1-Click Client Login (client@sharmarestaurant.com)
                </button>
            </div>

            <div class="text-center" style="margin-top: 20px;">
                <a href="<?= BASE_URL ?>/index.php" style="font-size: 12.5px; color: #64748b; font-weight: 700; text-decoration: none;">
                    <i class="bi bi-arrow-left"></i> Back to Homepage
                </a>
            </div>
        </div>
    </div>
</div>

<div class="client-footer-bar">
    &copy; <?= date('Y') ?> <?= e(get_setting('business_name', 'Vishal Web Studio')) ?> • Secure Client Administration Portal
</div>

<script>
function fillClientLogin() {
    document.getElementById('email').value = 'client@sharmarestaurant.com';
    document.getElementById('password').value = 'client123';
}
</script>

</body>
</html>
