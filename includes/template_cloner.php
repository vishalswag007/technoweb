<?php
/**
 * Vishal Web Studio - Template Cloner & Independent Tenant Website Provisioner
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/helpers.php';

class TemplateCloner {

    /**
     * Clone template into a fully independent client website
     */
    public static function createWebsiteFromTemplate(int $clientId, int $templateId, string $websiteName, ?string $domain = null): int {
        $pdo = db();

        // 1. Fetch Template
        $tStmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
        $tStmt->execute([$templateId]);
        $template = $tStmt->fetch();

        if (!$template) {
            throw new Exception("Template ID {$templateId} not found.");
        }

        // 2. Fetch Client Info
        $cStmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $cStmt->execute([$clientId]);
        $client = $cStmt->fetch();

        if (!$client) {
            throw new Exception("Client ID {$clientId} not found.");
        }

        // 3. Generate unique slug
        $baseSlug = slugify($websiteName);
        $slug = $baseSlug;
        $counter = 1;

        $chkSlug = $pdo->prepare("SELECT id FROM websites WHERE slug = ?");
        while (true) {
            $chkSlug->execute([$slug]);
            if (!$chkSlug->fetch()) break;
            $slug = $baseSlug . '-' . (++$counter);
        }

        // 4. Create Website record
        $insWeb = $pdo->prepare("INSERT INTO websites (client_id, template_id, name, slug, domain, tagline, theme_color, meta_title, meta_description, status, ssl_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'development', 1, " . (Database::getInstance()->isMySQL() ? "NOW()" : "datetime('now')") . ")");
        $insWeb->execute([
            $clientId,
            $templateId,
            $websiteName,
            $slug,
            $domain ?: $slug . '.' . ($_SERVER['HTTP_HOST'] ?? 'vishalwebstudio.com'),
            $template['tagline'] ?: "Welcome to {$websiteName}",
            $template['default_theme_color'] ?: '#2563eb',
            "{$websiteName} | {$template['category']} Services",
            "Official website of {$websiteName}. Explore our services, reviews, and get in touch with our team today."
        ]);

        $websiteId = (int)$pdo->lastInsertId();

        // 5. Clone Default Sections with Tenant Content
        $sections = self::generateDefaultSections($template, $client, $websiteName);
        $insSec = $pdo->prepare("INSERT INTO website_sections (website_id, section_key, title, subtitle, content_json, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        
        $sortOrder = 1;
        foreach ($sections as $sec) {
            $insSec->execute([
                $websiteId,
                $sec['section_key'],
                $sec['title'],
                $sec['subtitle'],
                $sec['content_json'],
                $sortOrder++
            ]);
        }

        // 6. Clone Default Pages
        $defaultPages = json_decode($template['default_pages'] ?? '[]', true);
        if (empty($defaultPages)) {
            $defaultPages = ['Home', 'About Us', 'Services', 'Gallery', 'Contact Us'];
        }

        $insPage = $pdo->prepare("INSERT INTO website_pages (website_id, title, slug, meta_title, meta_description, content, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'published')");
        $pSort = 1;
        foreach ($defaultPages as $pTitle) {
            $pSlug = slugify($pTitle);
            $insPage->execute([
                $websiteId,
                $pTitle,
                $pSlug,
                "{$pTitle} - {$websiteName}",
                "Learn more about {$pTitle} at {$websiteName}.",
                "<p>Welcome to our {$pTitle} page. Content is currently being updated by the business administrator.</p>",
                $pSort++
            ]);
        }

        // 7. Clone Default Services
        $defaultServices = self::generateDefaultServices($template['category'], $websiteName);
        $insSvc = $pdo->prepare("INSERT INTO services (website_id, title, description, price, price_label, icon, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        $sSort = 1;
        foreach ($defaultServices as $svc) {
            $insSvc->execute([
                $websiteId,
                $svc['title'],
                $svc['description'],
                $svc['price'],
                $svc['price_label'],
                $svc['icon'],
                $sSort++
            ]);
        }

        // 8. Clone Default FAQs
        $defaultFaqs = self::generateDefaultFaqs($template['category'], $websiteName, $client);
        $insFaq = $pdo->prepare("INSERT INTO faqs (website_id, question, answer, sort_order) VALUES (?, ?, ?, ?)");
        $fSort = 1;
        foreach ($defaultFaqs as $faq) {
            $insFaq->execute([
                $websiteId,
                $faq['question'],
                $faq['answer'],
                $fSort++
            ]);
        }

        // 9. Clone Default Testimonials
        $defaultTestimonials = self::generateDefaultTestimonials($websiteName);
        $insTest = $pdo->prepare("INSERT INTO testimonials (website_id, client_name, client_title, content, rating, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $tSort = 1;
        foreach ($defaultTestimonials as $test) {
            $insTest->execute([
                $websiteId,
                $test['name'],
                $test['title'],
                $test['content'],
                $test['rating'],
                $tSort++
            ]);
        }

        // 10. Provision Default Domain & Hosting records if missing
        $chkDom = $pdo->prepare("SELECT id FROM domains WHERE website_id = ?");
        $chkDom->execute([$websiteId]);
        if (!$chkDom->fetch()) {
            $domName = $domain ?: $slug . '.vishalwebstudio.com';
            $insDom = $pdo->prepare("INSERT INTO domains (client_id, website_id, domain_name, registrar, registration_date, expiry_date, renewal_cost, status) VALUES (?, ?, ?, 'Cloudflare / GoDaddy', " . (Database::getInstance()->isMySQL() ? "CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR)" : "date('now'), date('now', '+1 year')") . ", 999.00, 'active')");
            $insDom->execute([$clientId, $websiteId, $domName]);
        }

        $chkHost = $pdo->prepare("SELECT id FROM hosting WHERE website_id = ?");
        $chkHost->execute([$websiteId]);
        if (!$chkHost->fetch()) {
            $insHost = $pdo->prepare("INSERT INTO hosting (client_id, website_id, provider, plan_name, server_ip, disk_space, start_date, expiry_date, renewal_cost, status) VALUES (?, ?, 'High-Speed NVMe Cloud', 'Startup Web Cloud', '185.199.108.153', '10 GB NVMe', " . (Database::getInstance()->isMySQL() ? "CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR)" : "date('now'), date('now', '+1 year')") . ", 2999.00, 'active')");
            $insHost->execute([$clientId, $websiteId]);
        }

        log_activity(
            current_user_id(),
            'website_cloned',
            'websites',
            $websiteId,
            "Created new website '{$websiteName}' for client '{$client['business_name']}' from template '{$template['name']}'"
        );

        return $websiteId;
    }

    private static function generateDefaultSections(array $template, array $client, string $websiteName): array {
        $category = $template['category'] ?? 'Business';

        return [
            [
                'section_key' => 'hero',
                'title' => "Welcome to {$websiteName}",
                'subtitle' => $template['tagline'] ?: "Delivering top-tier {$category} services with passion, reliability, and excellence.",
                'content_json' => json_encode([
                    'badge' => "Official {$category} Portal",
                    'primary_btn_text' => 'Explore Services',
                    'primary_btn_link' => '#services',
                    'secondary_btn_text' => 'Get in Touch',
                    'secondary_btn_link' => '#contact',
                    'stat1_num' => '10+', 'stat1_label' => 'Years Experience',
                    'stat2_num' => '100%', 'stat2_label' => 'Client Satisfaction',
                    'stat3_num' => '500+', 'stat3_label' => 'Projects Delivered'
                ])
            ],
            [
                'section_key' => 'about',
                'title' => "About {$websiteName}",
                'subtitle' => "Dedicated to providing unforgettable value, personalized care, and modern expertise for every customer.",
                'content_json' => json_encode([
                    'experience_years' => '10',
                    'highlight_text' => 'Premium Quality & Dedicated Customer Support',
                    'paragraph_1' => "At {$websiteName}, we combine industry-tested craftsmanship with the latest innovations to meet your specific expectations.",
                    'paragraph_2' => "Our experienced team is always prepared to guide you through personalized solutions designed to bring you the best possible results."
                ])
            ],
            [
                'section_key' => 'contact',
                'title' => "Connect with {$websiteName}",
                'subtitle' => "We are here to assist you. Call us, send a WhatsApp message, or drop by our location.",
                'content_json' => json_encode([
                    'phone' => $client['phone'] ?: '+91 98765 43210',
                    'whatsapp' => $client['whatsapp'] ?: '919876543210',
                    'email' => $client['email'] ?: 'info@' . slugify($websiteName) . '.com',
                    'address' => $client['address'] ?: 'Main Business Hub, City Center',
                    'hours' => 'Mon - Sat: 09:00 AM - 08:00 PM',
                    'maps_embed' => 'https://maps.google.com'
                ])
            ]
        ];
    }

    private static function generateDefaultServices(string $category, string $websiteName): array {
        return match(strtolower($category)) {
            'restaurant' => [
                ['title' => 'Signature Chef Dishes', 'description' => 'Delicious recipes prepared fresh daily with authentic herbs and organic ingredients.', 'price' => 299.00, 'price_label' => 'Per Serving', 'icon' => 'fas fa-utensils'],
                ['title' => 'Special Beverages & Shakes', 'description' => 'Refreshing mocktails, fresh fruit smoothies, and handcrafted coffees.', 'price' => 149.00, 'price_label' => 'Per Drink', 'icon' => 'fas fa-glass-martini-alt'],
                ['title' => 'Event & Party Catering', 'description' => 'Full-scale luxury catering tailored for birthdays, family parties, and corporate lunches.', 'price' => 699.00, 'price_label' => 'Per Guest Starting', 'icon' => 'fas fa-users'],
            ],
            'salon' => [
                ['title' => 'Hair Styling & Spa', 'description' => 'Trendsetting haircuts, keratin treatments, and nourishing hair spa treatments.', 'price' => 999.00, 'price_label' => 'Per Session', 'icon' => 'fas fa-cut'],
                ['title' => 'Glow Facial & Skin Therapy', 'description' => 'Hydrating botanical facials tailored for glowing, rejuvenated skin.', 'price' => 1499.00, 'price_label' => 'Per Session', 'icon' => 'fas fa-spa'],
                ['title' => 'Bridal & Party Makeover', 'description' => 'High-definition bridal makeup and comprehensive styling packages.', 'price' => 4999.00, 'price_label' => 'Starting Package', 'icon' => 'fas fa-heart'],
            ],
            'coaching' => [
                ['title' => 'Comprehensive Foundation Batch', 'description' => 'Daily interactive classroom lectures with step-by-step conceptual clarity.', 'price' => 15000.00, 'price_label' => 'Annual Course Fee', 'icon' => 'fas fa-graduation-cap'],
                ['title' => 'Mock Test Series & Analytics', 'description' => 'Real-time simulated exam tests with in-depth rank and strength reports.', 'price' => 3999.00, 'price_label' => 'Test Pack', 'icon' => 'fas fa-chart-line'],
                ['title' => '1-on-1 Doubt Clearing Sessions', 'description' => 'Dedicated senior mentor sessions for solving tricky problems and exam strategy.', 'price' => 2499.00, 'price_label' => 'Monthly', 'icon' => 'fas fa-user-check'],
            ],
            default => [
                ['title' => 'Core Professional Consulting', 'description' => 'Strategic advisory and execution designed to accelerate your business goals.', 'price' => 4999.00, 'price_label' => 'Standard Package', 'icon' => 'fas fa-briefcase'],
                ['title' => 'Premium Implementation', 'description' => 'End-to-end management, dedicated resource allocation, and priority support.', 'price' => 9999.00, 'price_label' => 'Complete Plan', 'icon' => 'fas fa-rocket'],
                ['title' => 'Annual Maintenance & Support', 'description' => 'Regular health checks, proactive optimization, and 24/7 emergency hotline.', 'price' => 2999.00, 'price_label' => 'Per Month', 'icon' => 'fas fa-headset'],
            ]
        };
    }

    private static function generateDefaultFaqs(string $category, string $websiteName, array $client): array {
        return [
            ['question' => "What are the working hours of {$websiteName}?", 'answer' => "We are open Monday through Saturday from 09:00 AM to 08:00 PM. For emergency inquiries, reach us on WhatsApp."],
            ['question' => "How can I book an appointment or place an order?", 'answer' => "You can easily submit the inquiry form on our contact page or click the green WhatsApp button to chat directly with our representative."],
            ['question' => "What payment methods do you accept?", 'answer' => "We accept all major payment modes including UPI (Google Pay, PhonePe, Paytm), Net Banking, Credit/Debit Cards, and Cash on Delivery / In-person."]
        ];
    }

    private static function generateDefaultTestimonials(string $websiteName): array {
        return [
            ['name' => 'Rahul Sharma', 'title' => 'Verified Customer', 'content' => "Outstanding experience with {$websiteName}! Professional, courteous, and timely service.", 'rating' => 5],
            ['name' => 'Neha Gupta', 'title' => 'Regular Client', 'content' => "The quality and attention to detail exceeded my expectations. Highly recommended to everyone!", 'rating' => 5],
            ['name' => 'Sanjay Verma', 'title' => 'Business Partner', 'content' => "Great team to work with. Fast communication and exceptional value.", 'rating' => 5]
        ];
    }
}
