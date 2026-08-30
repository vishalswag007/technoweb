<?php
/**
 * Vishal Web Studio - Visitor Contact Inquiry Handler for Client Websites
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $websiteId = (int)($_POST['website_id'] ?? 0);
    $returnUrl = $_POST['return_url'] ?? (BASE_URL . '/site/index.php');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($phone) && !empty($message) && $websiteId > 0) {
        $ins = $pdo->prepare("INSERT INTO client_inquiries (website_id, name, phone, email, subject, message) VALUES (?, ?, ?, ?, 'Website Booking Inquiry', ?)");
        $ins->execute([$websiteId, $name, $phone, $email, $message]);

        log_activity(null, 'client_inquiry_received', 'websites', $websiteId, "Received visitor inquiry from {$name} ({$phone})");
        set_flash('success', "Thank you, {$name}! Your inquiry has been sent successfully. The business will get in touch with you shortly.");
    } else {
        set_flash('danger', "Please fill in your name, phone number, and message.");
    }

    header('Location: ' . $returnUrl);
    exit;
}

header('Location: ' . BASE_URL . '/index.php');
exit;
