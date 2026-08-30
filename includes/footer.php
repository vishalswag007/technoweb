<?php
/**
 * Vishal Web Studio - Public Footer (Dynamic 3D Customizable Design)
 */
$siteEmail = get_setting('email', 'support@vishalwebstudio.com');
$sitePhone = get_setting('phone', '+91 90448 77444');
$siteWhatsApp = get_setting('whatsapp', '919044877444');
$siteAddress = get_setting('address', '102, Cyber Tower, Sector 62, Noida, NCR, India');

$footerBrandName = get_setting('footer_business_name', get_setting('business_name', 'Vishal Web Studio'));
$footerCopyright = get_setting('footer_copyright_text', '© ' . date('Y') . ' ' . $footerBrandName . '. All rights reserved.');
$footerTagline = get_setting('footer_tagline', 'Leading Website Development & Management Software Company in India');
$footerAbout = get_setting('footer_about_text', 'Professional websites, cloud management software and custom business portals for education, NGOs, healthcare, retail and growing organizations.');
?>

<!-- Prefooter CTA Banner -->
<section class="prefooter-cta">
    <div class="container">
        <div class="prefooter-inner">
            <div>
                <span class="eyebrow light"><i class="bi bi-stars"></i> LET'S BUILD YOUR DIGITAL SYSTEM</span>
                <h2><?= e($footerTagline) ?></h2>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <a class="btn btn-light btn-lg rounded-pill" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $sitePhone)) ?>" style="background: #ffffff; color: var(--ink);">
                    <i class="bi bi-telephone-fill"></i> Call Now
                </a>
                <a class="btn btn-wa btn-lg rounded-pill" href="<?= build_whatsapp_link($siteWhatsApp, "Hello, I need a website and management software demo.") ?>" target="_blank">
                    <i class="bi bi-whatsapp"></i> WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 4-Column Mega Dark 3D Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Col 1: Logo & Mission -->
            <div>
                <a class="site-logo" href="<?= BASE_URL ?>/index.php" style="color: #ffffff; margin-bottom: 14px;">
                    <div class="site-logo-badge"><i class="bi bi-display"></i></div>
                    <span style="color:#ffffff; font-weight:900; font-size:18px;"><?= e($footerBrandName) ?> <small style="display:block; font-size:10px; color:var(--brand-red); letter-spacing:0.5px; font-weight:700;">ENTERPRISE CLOUD PLATFORM</small></span>
                </a>
                <p style="margin-top: 12px; font-size: 13px; line-height: 1.7; color: #b7c5d8;">
                    <?= e($footerAbout) ?>
                </p>
                <div class="footer-social">
                    <a href="https://facebook.com" target="_blank" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://youtube.com" target="_blank" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="https://linkedin.com" target="_blank" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>

            <!-- Col 2: Company Links -->
            <div>
                <h6>Company</h6>
                <a href="<?= BASE_URL ?>/public/about.php">About Us</a>
                <a href="<?= BASE_URL ?>/public/services.php">Services</a>
                <a href="<?= BASE_URL ?>/index.php#service-areas">India Service Areas</a>
                <a href="<?= BASE_URL ?>/public/blog.php">SEO Blog</a>
                <a href="<?= BASE_URL ?>/public/track-order.php">Case Studies & Tracker</a>
                <a href="<?= BASE_URL ?>/public/portfolio.php">Portfolio</a>
                <a href="<?= BASE_URL ?>/index.php#clients">Clients</a>
                <a href="<?= BASE_URL ?>/public/contact.php">Contact</a>
            </div>

            <!-- Col 3: Popular Solutions -->
            <div>
                <h6>Popular Solutions</h6>
                <a href="<?= BASE_URL ?>/site/index.php?demo=coaching-institute" target="_blank">Institute Management ERP</a>
                <a href="<?= BASE_URL ?>/site/index.php?demo=coaching-institute" target="_blank">School &amp; College ERP</a>
                <a href="<?= BASE_URL ?>/site/index.php?demo=medical-clinic" target="_blank">Hospital &amp; Clinic Software</a>
                <a href="<?= BASE_URL ?>/public/order.php?category=NGO">NGO Cloud Management</a>
                <a href="<?= BASE_URL ?>/public/templates.php">Website Development</a>
                <a href="<?= BASE_URL ?>/site/index.php?demo=restaurant-delight" target="_blank">Restaurant POS &amp; Dining</a>
                <a href="<?= BASE_URL ?>/site/index.php?demo=salon-elegance" target="_blank">Salon &amp; Spa Appointments</a>
            </div>

            <!-- Col 4: Contact -->
            <div>
                <h6>Contact</h6>
                <p class="footer-contact">
                    <i class="bi bi-geo-alt"></i>
                    <span><?= e($siteAddress) ?></span>
                </p>
                <p class="footer-contact">
                    <i class="bi bi-telephone"></i>
                    <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $sitePhone)) ?>"><?= e($sitePhone) ?></a>
                </p>
                <p class="footer-contact">
                    <i class="bi bi-envelope"></i>
                    <a href="mailto:<?= e($siteEmail) ?>"><?= e($siteEmail) ?></a>
                </p>
            </div>
        </div>

        <hr>
        <div class="footer-bottom">
            <span><?= e($footerCopyright) ?></span>
            <span>SEO Friendly &bull; Fast &bull; 3D Glassmorphic &bull; SSL Secured</span>
        </div>
    </div>
</footer>

<!-- Floating Action Buttons (Exact HyperInfonet Replication) -->
<a class="floating-wa" href="<?= build_whatsapp_link($siteWhatsApp, "I need website and management software") ?>" target="_blank" aria-label="WhatsApp">
    <i class="bi bi-whatsapp"></i>
</a>
<a class="floating-call" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $sitePhone)) ?>" aria-label="Call">
    <i class="bi bi-telephone-fill"></i>
</a>
<button class="floating-top" id="scrollTopBtn" aria-label="Scroll to top">
    <i class="bi bi-arrow-up"></i>
</button>

<!-- Interactive Scripts -->
<script src="<?= ASSETS_URL ?>/js/toast.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Scroll to Top Toggler
    const topBtn = document.getElementById('scrollTopBtn');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            topBtn.classList.add('active');
        } else {
            topBtn.classList.remove('active');
        }
    });
    if (topBtn) {
        topBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // FAQ Accordion Interactivity
    const faqButtons = document.querySelectorAll('.faq-accordion .accordion-button');
    faqButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const targetBody = document.getElementById(targetId);
            const isCurrentlyActive = btn.classList.contains('active');

            // Close all
            faqButtons.forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.faq-accordion .accordion-body').forEach(b => b.classList.remove('active'));

            if (!isCurrentlyActive && targetBody) {
                btn.classList.add('active');
                targetBody.classList.add('active');
            }
        });
    });

    // 3D Navigation Dropdown Handlers
    document.querySelectorAll('.has-dropdown').forEach(item => {
        const toggle = item.querySelector('.dropdown-toggle');
        if (toggle) {
            toggle.addEventListener('click', (e) => {
                if (window.innerWidth < 992) {
                    e.preventDefault();
                    item.classList.toggle('is-open');
                }
            });
        }
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.has-dropdown')) {
            document.querySelectorAll('.has-dropdown.is-open').forEach(el => el.classList.remove('is-open'));
        }
    });
});
</script>
</body>
</html>
