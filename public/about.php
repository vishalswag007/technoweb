<?php
/**
 * Vishal Web Studio - About Us (HyperInfonet 100% Replication Design)
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pageTitle = 'About Us | HYPERINFONET IT SOLUTIONS PVT. LTD.';
$pageDescription = 'Discover HyperInfonet - India\'s trusted partner for institute management ERP, school software, and dynamic website development.';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Title Banner -->
<div class="page-banner">
    <div class="container text-center">
        <span class="eyebrow light"><i class="bi bi-info-circle-fill"></i> WHO WE ARE</span>
        <h1>About HYPERINFONET IT SOLUTIONS PVT. LTD.</h1>
        <p>
            Building high-speed dynamic websites, cloud management ERPs, and automated workflows for organizations across India.
        </p>
    </div>
</div>

<section class="section soft-bg">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 40px; align-items: center; margin-bottom: 50px;">
            <div>
                <span class="eyebrow">OUR MISSION</span>
                <h2 style="font-size: 36px; font-weight: 900; margin: 10px 0 16px; letter-spacing: -1px;">
                    Modern Technology, Practical Software &amp; Long-Term Support
                </h2>
                <p style="color: var(--muted); font-size: 15px; line-height: 1.8; margin-bottom: 16px;">
                    HyperInfonet was founded with a clear objective: to make digital management software accessible, simple, and dependable for educational institutes, schools, colleges, NGOs, and businesses across India.
                </p>
                <p style="color: var(--muted); font-size: 15px; line-height: 1.8; margin-bottom: 24px;">
                    We combine a fast, SEO-friendly public website with secure administrative panels for student lifecycle, fee receipts, digital admit cards, marksheets, certificates, attendance, and instant communication.
                </p>
                <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                    <div>
                        <strong style="font-size: 2rem; color: var(--brand-blue); display: block; font-weight: 900;">1500+</strong>
                        <span style="font-size: 0.85rem; color: var(--muted);">Happy Clients</span>
                    </div>
                    <div>
                        <strong style="font-size: 2rem; color: var(--brand-blue); display: block; font-weight: 900;">2000+</strong>
                        <span style="font-size: 0.85rem; color: var(--muted);">Web Projects</span>
                    </div>
                    <div>
                        <strong style="font-size: 2rem; color: var(--brand-blue); display: block; font-weight: 900;">25+</strong>
                        <span style="font-size: 0.85rem; color: var(--muted);">Software Modules</span>
                    </div>
                </div>
            </div>

            <div class="why-card">
                <span class="eyebrow light">CORE CAPABILITIES</span>
                <h2>One Connected Ecosystem</h2>
                <p>Everything your organization needs in one centralized system.</p>
                <div class="pill-grid">
                    <span><i class="bi bi-check-circle-fill"></i> Multi-Branch</span>
                    <span><i class="bi bi-check-circle-fill"></i> ID Card Print</span>
                    <span><i class="bi bi-check-circle-fill"></i> Fee Invoicing</span>
                    <span><i class="bi bi-check-circle-fill"></i> Admit Cards</span>
                    <span><i class="bi bi-check-circle-fill"></i> Marksheets</span>
                    <span><i class="bi bi-check-circle-fill"></i> 24x7 WhatsApp</span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
