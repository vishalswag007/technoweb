<?php
/**
 * Vishal Web Studio - Contact Us (HyperInfonet 100% Replication Design)
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$success = false;
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Website & Software Inquiry');
    $message = trim($_POST['message'] ?? '');

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif (empty($name) || empty($phone)) {
        $error = 'Please fill out your name and phone number.';
    } else {
        try {
            log_activity(null, 'public_inquiry', 'contact', null, "Inquiry from {$name} ({$phone}): {$subject}");
            $success = true;
        } catch (Exception $e) {
            $error = 'Error sending message. Please try again.';
        }
    }
}

$pageTitle = 'Contact Us | HYPERINFONET IT SOLUTIONS PVT. LTD.';
$siteEmail = get_setting('email', 'support@vishalwebstudio.com');
$sitePhone = get_setting('phone', '+91 90448 77444');
$siteAddress = get_setting('address', '656 6/200, Unity City Colony, Kalyanpur (West), Lucknow, Uttar Pradesh – 226022');

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Title Banner -->
<div class="page-banner">
    <div class="container text-center">
        <span class="eyebrow light"><i class="bi bi-headset"></i> GET IN TOUCH</span>
        <h1>Contact Our Technology Experts</h1>
        <p>
            Discuss your website, management software, or custom ERP requirements with our team.
        </p>
    </div>
</div>

<section class="section contact-section" id="contact">
    <div class="container">
        <div class="contact-shell">
            <div class="contact-copy">
                <span class="eyebrow light">GET A FREE DEMO</span>
                <h2>Make Your Organization Professional, Connected and Fully Digital.</h2>
                <p>Share your requirement and our team will contact you with suitable website, software, modules and pricing options.</p>
                
                <div class="contact-points">
                    <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $sitePhone)) ?>">
                        <i class="bi bi-telephone-fill"></i>
                        <span><small>Call / WhatsApp</small><?= e($sitePhone) ?></span>
                    </a>
                    <a href="mailto:<?= e($siteEmail) ?>">
                        <i class="bi bi-envelope-fill"></i>
                        <span><small>Email</small><?= e($siteEmail) ?></span>
                    </a>
                    <div>
                        <i class="bi bi-geo-alt-fill"></i>
                        <span><small>Office</small><?= e($siteAddress) ?></span>
                    </div>
                </div>
            </div>

            <form method="POST" action="" class="lead-form" id="demo">
                <?= csrf_field() ?>
                <h3>Request Demo / Quotation</h3>
                <p>Fill in your details and our team will contact you.</p>

                <?php if ($success): ?>
                    <div style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 14px; border-radius: 12px; font-size: 14px; margin-bottom: 16px;">
                        <i class="bi bi-check-circle-fill"></i> Thank you! Your request has been received. Our team will contact you shortly.
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 14px; border-radius: 12px; font-size: 14px; margin-bottom: 16px;">
                        <i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <div class="form-grid-2">
                    <div>
                        <label>Name *</label>
                        <input class="form-control" name="name" placeholder="Your Name" required>
                    </div>
                    <div>
                        <label>Mobile *</label>
                        <input class="form-control" name="phone" inputmode="numeric" placeholder="Mobile Number" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div>
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" placeholder="name@example.com">
                    </div>
                    <div>
                        <label>Service Required</label>
                        <select class="form-select" name="subject">
                            <option value="Website + Management Software">Website + Management Software</option>
                            <option value="School / College ERP">School / College ERP</option>
                            <option value="Institute Management Software">Institute Management Software</option>
                            <option value="NGO Website / Software">NGO Website / Software</option>
                            <option value="Billing / Business Software">Billing / Business Software</option>
                            <option value="E-commerce Website">E-commerce Website</option>
                            <option value="Custom Portal / Software">Custom Portal / Software</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Requirement</label>
                    <textarea class="form-control" name="message" rows="3" placeholder="Tell us about your project..."></textarea>
                </div>

                <button type="submit" class="btn btn-brand btn-lg w-100" style="width: 100%;">
                    Send Enquiry <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
