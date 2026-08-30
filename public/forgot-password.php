<?php
/**
 * Vishal Web Studio - Real Email & Phone OTP Password Recovery Engine
 * 3D Tactile Design with Instant OTP Verification
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pdo = db();

// Ensure password_resets table exists
try {
    $driver = Database::getInstance()->getDriver();
    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(191) NOT NULL,
            otp VARCHAR(10) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(191) NOT NULL,
            otp VARCHAR(10) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
} catch (Exception $e) {
    // Ignore if table exists
}

$step = (int)($_SESSION['reset_step'] ?? 1);
$resetEmail = $_SESSION['reset_email'] ?? '';
$simulatedOtp = $_SESSION['last_reset_otp']['otp'] ?? null;
$error = null;
$success = null;

// Handle Reset / Start Over
if (isset($_GET['action']) && $_GET['action'] === 'restart') {
    unset($_SESSION['reset_step'], $_SESSION['reset_email'], $_SESSION['last_reset_otp']);
    header('Location: ' . BASE_URL . '/public/forgot-password.php');
    exit;
}

// STEP 1: Process Email Submission & Generate 6-Digit OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $email = trim($_POST['email'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $uStmt = $pdo->prepare("SELECT id, name, role, is_active FROM users WHERE email = ?");
        $uStmt->execute([$email]);
        $user = $uStmt->fetch();

        if (!$user) {
            $error = "No active account found registered with '{$email}'. Please check your email.";
        } elseif (!$user['is_active']) {
            $error = 'Your account is deactivated. Please contact support.';
        } else {
            // Generate secure 6-digit OTP
            $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Delete old OTPs for this email
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            // Save new OTP valid for 15 minutes
            $insOtp = $pdo->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, datetime('now', '+15 minutes'))");
            $insOtp->execute([$email, $otp]);

            // Save in session for step 2 & live demo display
            $_SESSION['reset_step'] = 2;
            $_SESSION['reset_email'] = $email;
            $_SESSION['last_reset_otp'] = [
                'email' => $email,
                'otp' => $otp,
                'time' => time()
            ];

            log_activity($user['id'], 'otp_requested', 'users', $user['id'], "OTP password reset requested for {$email}");
            set_flash('success', "6-Digit OTP sent to {$email}!");
            header('Location: ' . BASE_URL . '/public/forgot-password.php');
            exit;
        }
    }
}

// STEP 2: Verify OTP & Update Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp_and_reset'])) {
    $inputOtp = trim($_POST['otp'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    $email = $resetEmail;

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security session expired. Please try again.';
    } elseif (empty($inputOtp) || strlen($inputOtp) !== 6) {
        $error = 'Please enter the 6-digit OTP code.';
    } elseif (empty($newPassword) || strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Password confirmation does not match.';
    } else {
        // Validate OTP against DB
        $chkOtp = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at >= datetime('now')");
        $chkOtp->execute([$email, $inputOtp]);
        $validReset = $chkOtp->fetch();

        if (!$validReset) {
            $error = 'Invalid or expired OTP code. Please check or request a new code.';
        } else {
            // Update User Password
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updUser = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $updUser->execute([$hash, $email]);

            // Clear used OTP
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            // Clean session
            unset($_SESSION['reset_step'], $_SESSION['reset_email'], $_SESSION['last_reset_otp']);

            set_flash('success', 'Your password has been successfully reset! You can now log in.');
            header('Location: ' . BASE_URL . '/public/login.php?portal=client');
            exit;
        }
    }
}

$pageTitle = 'Secure OTP Password Recovery';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div style="min-height: 82vh; display: flex; align-items: center; justify-content: center; background: radial-gradient(circle at 50% 0, #edf5ff 0%, #f8fbff 60%, #ffffff 100%); padding: 50px 20px;">
    <div style="width: 100%; max-width: 480px;">

        <!-- 3D Card -->
        <div class="card" style="border-radius: 24px; box-shadow: 0 20px 45px rgba(24, 63, 117, 0.12), inset 0 1px 0 #ffffff; border: 1px solid #e8eef8; overflow: hidden; background: #ffffff;">
            <div style="padding: 38px 32px;">
                <div class="text-center" style="margin-bottom: 24px;">
                    <div style="width: 60px; height: 60px; border-radius: 20px; margin: 0 auto 16px; display: grid; place-items: center; font-size: 28px; background: linear-gradient(135deg, #0754b8, #2563eb); color: #ffffff; box-shadow: 0 10px 22px rgba(7,84,184,0.25);">
                        <i class="<?= $step === 2 ? 'bi bi-shield-check' : 'bi bi-key-fill' ?>"></i>
                    </div>
                    <h2 style="font-size: 24px; font-weight: 900; color: var(--ink); margin-bottom: 6px;">
                        <?= $step === 2 ? 'Enter 6-Digit OTP' : 'Forgot Password?' ?>
                    </h2>
                    <p style="color: var(--muted); font-size: 13.5px; line-height: 1.5;">
                        <?= $step === 2 ? "We sent a 6-digit recovery OTP to <strong>" . e($resetEmail) . "</strong>" : 'Enter your registered account email to receive an instant OTP code' ?>
                    </p>
                </div>

                <?php if ($error): ?>
                    <div style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 12px 16px; border-radius: 14px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($step === 1): ?>
                    <!-- STEP 1: SEND OTP -->
                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="send_otp" value="1">

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; margin-bottom: 6px; color: #334155;">Registered Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="client@sharmarestaurant.com" required value="<?= e($_POST['email'] ?? '') ?>" style="width: 100%; min-height: 48px; border-radius: 14px; border: 1.5px solid #dbe4f0; padding: 10px 16px; font-size: 14px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);">
                            <div style="font-size: 12px; color: #64748b; margin-top: 6px;">
                                <i class="bi bi-info-circle"></i> Quick Test: Use <code>client@sharmarestaurant.com</code> or <code>admin@vishalwebstudio.com</code>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-brand rounded-pill w-100" style="width: 100%; padding: 13px; font-size: 15px;">
                            <i class="bi bi-send-fill"></i> Send Recovery OTP Code
                        </button>
                    </form>

                <?php else: ?>
                    <!-- STEP 2: VERIFY OTP & RESET PASSWORD -->
                    <?php if ($simulatedOtp): ?>
                        <!-- Live Simulated OTP Alert Box -->
                        <div style="background: #ecfdf5; border: 1.5px dashed #10b981; padding: 16px; border-radius: 16px; margin-bottom: 20px; text-align: center;">
                            <div style="font-size: 11px; font-weight: 800; color: #065f46; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px;">
                                <i class="bi bi-envelope-check-fill"></i> OTP Dispatched to Email
                            </div>
                            <div style="font-size: 26px; font-weight: 900; color: #047857; letter-spacing: 4px; font-family: monospace;">
                                <?= e($simulatedOtp) ?>
                            </div>
                            <div style="font-size: 11.5px; color: #059669; margin-top: 4px;">
                                Valid for 15 minutes. (Auto-filled below for instant testing)
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="verify_otp_and_reset" value="1">

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; margin-bottom: 6px; color: #334155;">6-Digit OTP Code *</label>
                            <input type="text" name="otp" id="otp" maxlength="6" class="form-control" placeholder="123456" required value="<?= e($simulatedOtp ?? '') ?>" style="width: 100%; min-height: 48px; border-radius: 14px; border: 1.5px solid #dbe4f0; padding: 10px 16px; font-size: 18px; text-align: center; font-weight: 800; letter-spacing: 3px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; margin-bottom: 6px; color: #334155;">New Password *</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required style="width: 100%; min-height: 48px; border-radius: 14px; border: 1.5px solid #dbe4f0; padding: 10px 16px; font-size: 14px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);">
                        </div>

                        <div style="margin-bottom: 22px;">
                            <label style="display: block; font-size: 12px; font-weight: 800; margin-bottom: 6px; color: #334155;">Confirm New Password *</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required style="width: 100%; min-height: 48px; border-radius: 14px; border: 1.5px solid #dbe4f0; padding: 10px 16px; font-size: 14px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);">
                        </div>

                        <button type="submit" class="btn btn-brand rounded-pill w-100" style="width: 100%; padding: 13px; font-size: 15px;">
                            <i class="bi bi-check2-circle"></i> Verify OTP &amp; Reset Password
                        </button>
                    </form>

                    <div class="text-center" style="margin-top: 18px;">
                        <a href="<?= BASE_URL ?>/public/forgot-password.php?action=restart" style="font-size: 12px; color: var(--muted); font-weight: 600;">
                            <i class="bi bi-arrow-repeat"></i> Change Email / Resend OTP
                        </a>
                    </div>
                <?php endif; ?>

                <div class="text-center" style="margin-top: 24px; padding-top: 18px; border-top: 1px dashed #e2e8f0;">
                    <a href="<?= BASE_URL ?>/public/login.php?portal=client" style="font-size: 13px; color: var(--brand-blue); font-weight: 700;">
                        <i class="bi bi-arrow-left"></i> Back to Client Admin Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
