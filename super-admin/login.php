<?php
/**
 * Vishal Web Studio - Dedicated Super Admin Login Portal (100% Isolated)
 * Strictly for Master Administrators & Agency Owner
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// If already logged in as super admin, redirect to super-admin dashboard
if (is_logged_in() && is_super_admin()) {
    header('Location: ' . BASE_URL . '/super-admin/index.php');
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
        $error = 'Please enter both administrator email and master password.';
    } else {
        if (attempt_login($email, $password)) {
            if (is_super_admin()) {
                set_flash('success', 'Welcome back to Master Console, Super Admin!');
                header('Location: ' . BASE_URL . '/super-admin/index.php');
                exit;
            } else {
                // If a client tried to log into Super Admin console, log them out and show error
                logout();
                $error = 'Access Denied: This console is strictly restricted to Super Administrators.';
            }
        } else {
            $error = 'Invalid administrator credentials. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Console | Master Security Login</title>
    
    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/main.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 50% 0%, #0f172a 0%, #090d16 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin: 0;
            padding: 0;
            color: #f8fafc;
        }

        .admin-login-wrapper {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .admin-login-box {
            width: 100%;
            max-width: 440px;
        }

        .admin-login-card {
            background: #1e293b;
            border-radius: 26px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border: 1px solid #334155;
            padding: 38px 32px;
            position: relative;
            overflow: hidden;
        }

        .admin-login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ef4444 0%, #0754b8 100%);
        }

        .admin-icon-badge {
            width: 68px;
            height: 68px;
            border-radius: 22px;
            margin: 0 auto 16px;
            display: grid;
            place-items: center;
            font-size: 30px;
            color: #ffffff;
            background: linear-gradient(135deg, #ef4444 0%, #0754b8 100%);
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.35);
        }

        .form-control-dark {
            width: 100%;
            min-height: 48px;
            border-radius: 14px;
            border: 1.5px solid #475569;
            padding: 10px 16px;
            font-size: 14px;
            font-family: inherit;
            color: #f8fafc;
            background: #0f172a;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.3);
            transition: all 0.2s ease;
            box-sizing: border-box;
            display: block;
        }

        .form-control-dark:focus {
            outline: none;
            border-color: #ef4444;
            box-shadow: 0 0 0 3.5px rgba(239, 68, 68, 0.2);
            background: #131d31;
        }

        .admin-footer-bar {
            text-align: center;
            padding: 20px;
            font-size: 12.5px;
            color: #64748b;
        }
    </style>
</head>
<body>

<?= render_flash_messages() ?>

<div class="admin-login-wrapper">
    <div class="admin-login-box">

        <!-- Standalone Brand Badge -->
        <div class="text-center" style="margin-bottom: 22px;">
            <a href="<?= BASE_URL ?>/index.php" style="display: inline-flex; align-items: center; gap: 10px; text-decoration: none;">
                <div style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #ef4444 0%, #0754b8 100%); display: grid; place-items: center; color: #ffffff; font-weight: 900; font-size: 20px; box-shadow: 0 6px 16px rgba(239,68,68,0.35);">
                    <i class="bi bi-shield-shaded"></i>
                </div>
                <div style="text-align: left;">
                    <div style="font-weight: 900; font-size: 17px; color: #ffffff; line-height: 1.1;"><?= e(get_setting('business_name', 'Vishal Web Studio')) ?></div>
                    <div style="font-size: 11px; font-weight: 700; color: #ef4444; text-transform: uppercase; letter-spacing: 0.8px;">Master Admin Console</div>
                </div>
            </a>
        </div>

        <!-- 3D Dark Super Admin Card -->
        <div class="admin-login-card">
            <div class="text-center" style="margin-bottom: 24px;">
                <div class="admin-icon-badge">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>

                <h2 style="font-size: 24px; font-weight: 900; color: #ffffff; margin-bottom: 6px; letter-spacing: -0.5px;">
                    Super Admin Console
                </h2>

                <p style="color: #94a3b8; font-size: 13.5px; line-height: 1.5; margin-top: 4px;">
                    Platform management, client databases, cloud servers &amp; billing
                </p>
            </div>

            <?php if ($error): ?>
                <div style="background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); padding: 12px 16px; border-radius: 14px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-exclamation-octagon-fill text-danger"></i>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrf_field() ?>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 800; margin-bottom: 6px; color: #cbd5e1;">Master Email Address</label>
                    <input type="email" name="email" id="email" class="form-control-dark" placeholder="admin@vishalwebstudio.com" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div style="margin-bottom: 22px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label style="font-size: 12px; font-weight: 800; margin-bottom: 0; color: #cbd5e1;">Master Password</label>
                        <a href="<?= BASE_URL ?>/public/forgot-password.php" style="font-size: 12px; font-weight: 700; color: #38bdf8; text-decoration: none;">Reset via OTP</a>
                    </div>
                    <input type="password" name="password" id="password" class="form-control-dark" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn rounded-pill w-100" style="width: 100%; padding: 13px; font-size: 15px; background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%); color: #ffffff; border: none; font-weight: 800; border-radius: 50px; box-shadow: 0 4px 0 #991b1b, 0 8px 18px rgba(239,68,68,0.35); cursor: pointer;">
                    <i class="bi bi-shield-lock"></i> Sign In to Super Admin
                </button>
            </form>

            <!-- 1-Click Super Admin Access -->
            <div style="margin-top: 22px; padding-top: 16px; border-top: 1px dashed #334155; text-align: center;">
                <p style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 10px;">Master Quick Access</p>
                <button type="button" class="btn btn-sm w-100" style="width: 100%; font-size: 13px; border-radius: 50px; background: rgba(7, 84, 184, 0.2); color: #60a5fa; border: 1px solid #1e3a8a; font-weight: 700; padding: 8px;" onclick="fillAdminLogin()">
                    <i class="bi bi-shield-check"></i> Fill 1-Click Super Admin (admin@vishalwebstudio.com)
                </button>
            </div>

            <div class="text-center" style="margin-top: 20px;">
                <a href="<?= BASE_URL ?>/index.php" style="font-size: 12.5px; color: #94a3b8; font-weight: 700; text-decoration: none;">
                    <i class="bi bi-arrow-left"></i> Back to Homepage
                </a>
            </div>
        </div>
    </div>
</div>

<div class="admin-footer-bar">
    &copy; <?= date('Y') ?> <?= e(get_setting('business_name', 'Vishal Web Studio')) ?> • Restricted Super Admin Control System
</div>

<script>
function fillAdminLogin() {
    document.getElementById('email').value = 'admin@vishalwebstudio.com';
    document.getElementById('password').value = 'admin123';
}
</script>

</body>
</html>
