<?php
/**
 * Vishal Web Studio - Database Installer & Auto-Migrator
 * CLI & Web Compatible
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

echo "========================================================\n";
echo " Vishal Web Studio - Database Setup & Seeder\n";
echo "========================================================\n\n";

try {
    $pdo = db();
    $driver = Database::getInstance()->getDriver();
    echo "[*] Connected to database using driver: {$driver}\n";

    // Read schema.sql
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("schema.sql not found at {$schemaFile}");
    }

    $sql = file_get_contents($schemaFile);

    if ($driver === 'sqlite') {
        // Adapt MySQL schema statements for SQLite
        $sql = preg_replace('/ENGINE=InnoDB.*?;/i', ';', $sql);
        $sql = preg_replace('/INT AUTO_INCREMENT PRIMARY KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/ENUM\([^)]+\)/i', 'VARCHAR(50)', $sql);
        $sql = preg_replace('/ON UPDATE CURRENT_TIMESTAMP/i', '', $sql);
        $sql = preg_replace('/TINYINT\(1\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/LONGTEXT/i', 'TEXT', $sql);
    }

    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }
    echo "[+] Database schema created successfully.\n";

    // Seed Global Settings
    $defaultSettings = [
        'business_name' => 'Vishal Web Studio',
        'tagline' => 'Professional Websites for Your Business',
        'email' => 'contact@vishalwebstudio.com',
        'phone' => '+91 98765 43210',
        'whatsapp' => '919876543210',
        'address' => '102, Cyber Tower, Sector 62, Noida, NCR, India',
        'currency_symbol' => '₹',
        'currency_code' => 'INR',
        'tax_rate' => '18.00',
        'tax_name' => 'GST',
        'whatsapp_order_msg' => "Hello {client_name}, thank you for your order {order_number} for {business_name}! We are reviewing your requirements.",
        'whatsapp_contract_msg' => "Hello {client_name}, your website contract {contract_number} is ready for digital signature: {contract_url}",
        'whatsapp_payment_msg' => "Hello {client_name}, we received your payment of {amount} for {business_name}. Thank you!",
        'whatsapp_live_msg' => "Congratulations {client_name}! Your official website {website_url} is now LIVE. Manage it anytime at: {admin_url}",
        'smtp_host' => 'smtp.mailtrap.io',
        'smtp_port' => '587',
        'smtp_user' => '',
        'smtp_pass' => '',
        'razorpay_key' => 'rzp_test_sampleKey123',
        'razorpay_secret' => 'sampleSecretKey123',
        'session_timeout' => '7200',
    ];

    $setStmt = $pdo->prepare("INSERT OR IGNORE INTO global_settings (setting_key, setting_value) VALUES (:key, :val)");
    if ($driver === 'mysql') {
        $setStmt = $pdo->prepare("INSERT INTO global_settings (setting_key, setting_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    }

    foreach ($defaultSettings as $key => $val) {
        $setStmt->execute([':key' => $key, ':val' => $val]);
    }
    echo "[+] Global business settings configured.\n";

    // Seed Super Admin User
    $adminEmail = 'admin@vishalwebstudio.com';
    $chkAdmin = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $chkAdmin->execute([$adminEmail]);
    $adminUser = $chkAdmin->fetch();

    $adminPassHash = password_hash('admin123', PASSWORD_DEFAULT);
    if (!$adminUser) {
        $insAdmin = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, phone, status, created_at) VALUES (?, ?, ?, 'super_admin', '+91 98765 43210', 'active', datetime('now'))");
        if ($driver === 'mysql') {
            $insAdmin = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, phone, status, created_at) VALUES (?, ?, ?, 'super_admin', '+91 98765 43210', 'active', NOW())");
        }
        $insAdmin->execute(['Vishal Yaduvansi', $adminEmail, $adminPassHash]);
        $adminId = $pdo->lastInsertId();
        echo "[+] Super Admin user created: {$adminEmail} / admin123\n";
    } else {
        $adminId = $adminUser['id'];
        echo "[*] Super Admin user already exists.\n";
    }

    // Seed Ready-Made Templates
    $templates = [
        [
            'name' => 'Royal Spice Restaurant & Cafe',
            'slug' => 'restaurant-delight',
            'category' => 'Restaurant',
            'tagline' => 'Exquisite Dining & Authentic Culinary Experience',
            'description' => 'A mouthwatering website template designed for fine-dining restaurants, cafes, bakeries, and cloud kitchens. Features interactive food menus, table reservation inquiries, chef specials, photo gallery, and WhatsApp direct ordering.',
            'price' => 14999.00,
            'default_theme_color' => '#d97706',
            'features' => json_encode(['Interactive Food Menu', 'Table Booking Form', 'Chef Specials Banner', 'Filterable Photo Gallery', 'WhatsApp Quick Order', 'Customer Reviews Carousel', 'Google Maps Location']),
            'default_pages' => json_encode(['Home', 'About Us', 'Menu', 'Gallery', 'Reservations', 'Contact Us']),
            'sort_order' => 1,
            'is_featured' => 1
        ],
        [
            'name' => 'Glow & Glam Beauty Salon & Spa',
            'slug' => 'salon-elegance',
            'category' => 'Salon',
            'tagline' => 'Luxury Hair, Skin Care & Wellness Treatments',
            'description' => 'Modern and elegant design tailored for beauty parlors, hair salons, nail bars, and luxury wellness spas. Includes treatment price lists, stylist profiles, appointment booking request form, and before/after gallery.',
            'price' => 12999.00,
            'default_theme_color' => '#db2777',
            'features' => json_encode(['Service Rate Cards', 'Stylist Portfolio', 'Appointment Inquiries', 'Spa Package Showcase', 'WhatsApp Consultations', 'Instagram Feed Ready', 'Client Testimonials']),
            'default_pages' => json_encode(['Home', 'About', 'Treatments & Pricing', 'Our Team', 'Gallery', 'Book Appointment', 'Contact']),
            'sort_order' => 2,
            'is_featured' => 1
        ],
        [
            'name' => 'Apex Career Academy & Institute',
            'slug' => 'coaching-institute',
            'category' => 'Coaching',
            'tagline' => 'Empowering Minds for Competitive Exams & Career Success',
            'description' => 'Professional academic portal for coaching institutes, tuition centers, competitive exam academies (IIT-JEE, NEET, UPSC), and computer training centers. Features course listings, faculty bios, student results, and admission inquiry forms.',
            'price' => 17999.00,
            'default_theme_color' => '#2563eb',
            'features' => json_encode(['Comprehensive Course Catalog', 'Admission Inquiry Engine', 'Faculty & Mentor Bios', 'Toppers Hall of Fame', 'Downloadable Syllabus / Notes', 'Fee Structure Breakdown', 'WhatsApp Admission Desk']),
            'default_pages' => json_encode(['Home', 'About Institute', 'Courses', 'Faculty', 'Results & Testimonials', 'Admissions', 'Contact']),
            'sort_order' => 3,
            'is_featured' => 1
        ],
        [
            'name' => 'Elite Realty & Properties',
            'slug' => 'real-estate-hub',
            'category' => 'Real Estate',
            'tagline' => 'Premium Residential & Commercial Property Consultants',
            'description' => 'High-converting real estate broker and developer website. Showcases featured properties, floor plans, pricing, location highlights, mortgage estimation calculators, and instant site visit booking.',
            'price' => 19999.00,
            'default_theme_color' => '#059669',
            'features' => json_encode(['Property Listing Cards', 'Interactive Floor Plans', 'Schedule Site Visit', 'Price & EMI Highlights', 'Location Advantages Map', 'Brochure Download', 'WhatsApp Property Broker']),
            'default_pages' => json_encode(['Home', 'About Us', 'Properties', 'Projects', 'Why Invest', 'Schedule Visit', 'Contact']),
            'sort_order' => 4,
            'is_featured' => 1
        ],
        [
            'name' => 'CarePlus Multispeciality Clinic',
            'slug' => 'medical-clinic',
            'category' => 'Medical',
            'tagline' => 'Compassionate Care, Advanced Medical Expertise',
            'description' => 'Trustworthy medical website for clinics, private doctors, dental centers, diagnostics labs, and hospitals. Includes doctor profiles, OPD schedules, treatments offered, health tips blog, and appointment booking.',
            'price' => 16999.00,
            'default_theme_color' => '#0284c7',
            'features' => json_encode(['Doctor Directory & Specialties', 'OPD Timing Schedule', 'Instant Appointment Form', 'Emergency Hotline Bar', 'Patient Testimonials', 'Health Checkup Packages', 'Google Maps Direct Navigation']),
            'default_pages' => json_encode(['Home', 'About Clinic', 'Doctors', 'Treatments', 'Packages', 'Book Appointment', 'Contact']),
            'sort_order' => 5,
            'is_featured' => 1
        ],
        [
            'name' => 'Nexus Digital Agency & Business',
            'slug' => 'modern-agency',
            'category' => 'Business',
            'tagline' => 'Driving Growth with Modern Solutions & Tech',
            'description' => 'Sleek, tech-forward corporate agency template for IT companies, consultancies, legal firms, marketing agencies, and startups. Showcase case studies, client logos, service packages, and lead generation.',
            'price' => 15999.00,
            'default_theme_color' => '#7c3aed',
            'features' => json_encode(['Corporate Services Matrix', 'Case Studies & Metrics', 'Client Logo Showcase', 'Tiered Pricing Plans', 'Lead Capture Funnel', 'Interactive FAQs', 'WhatsApp Business Connect']),
            'default_pages' => json_encode(['Home', 'About Us', 'Services', 'Case Studies', 'Pricing', 'Blog', 'Contact']),
            'sort_order' => 6,
            'is_featured' => 1
        ],
        [
            'name' => 'Creative Folio & Personal Brand',
            'slug' => 'creative-portfolio',
            'category' => 'Portfolio',
            'tagline' => 'Visual Storyteller, Designer & Creative Consultant',
            'description' => 'Minimalist, high-impact personal portfolio for freelance designers, photographers, consultants, developers, and creators. Highlights experience, resume download, project showcase, and inquiry form.',
            'price' => 9999.00,
            'default_theme_color' => '#e11d48',
            'features' => json_encode(['Visual Work Grid', 'Interactive Project Modal', 'Experience & Skills Timeline', 'Downloadable Resume PDF', 'Testimonial Quotes', 'Direct Project Inquiries']),
            'default_pages' => json_encode(['Home', 'About Me', 'Portfolio', 'Resume', 'Services', 'Contact']),
            'sort_order' => 7,
            'is_featured' => 0
        ],
        [
            'name' => 'UrbanMart Retail & Online Store',
            'slug' => 'ecommerce-store',
            'category' => 'E-commerce',
            'tagline' => 'Curated Products Delivered to Your Doorstep',
            'description' => 'Clean e-commerce product catalog designed for local boutiques, grocery stores, organic shops, and artisanal brands with WhatsApp direct checkout and catalog browsing.',
            'price' => 21999.00,
            'default_theme_color' => '#ea580c',
            'features' => json_encode(['Product Catalog Grid', 'Category Filters', 'Product Detail Viewer', 'WhatsApp 1-Click Buy', 'Customer Reviews', 'Store Policies & Shipping Info']),
            'default_pages' => json_encode(['Home', 'Products', 'Categories', 'Special Offers', 'About Store', 'Contact']),
            'sort_order' => 8,
            'is_featured' => 0
        ]
    ];

    $tplStmt = $pdo->prepare("SELECT id FROM templates WHERE slug = ?");
    $insTpl = $pdo->prepare("INSERT INTO templates (name, slug, category, tagline, description, price, default_theme_color, features, default_pages, sort_order, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");

    foreach ($templates as $t) {
        $tplStmt->execute([$t['slug']]);
        if (!$tplStmt->fetch()) {
            $insTpl->execute([
                $t['name'],
                $t['slug'],
                $t['category'],
                $t['tagline'],
                $t['description'],
                $t['price'],
                $t['default_theme_color'],
                $t['features'],
                $t['default_pages'],
                $t['sort_order'],
                $t['is_featured']
            ]);
        }
    }
    echo "[+] 8 Ready-Made Website Templates seeded.\n";

    // Seed Demo Client (Sharma Family Restaurant)
    $clientEmail = 'client@sharmarestaurant.com';
    $chkClientUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $chkClientUser->execute([$clientEmail]);
    $clientUser = $chkClientUser->fetch();

    $clientPassHash = password_hash('client123', PASSWORD_DEFAULT);
    if (!$clientUser) {
        $insClUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, phone, status) VALUES (?, ?, ?, 'client', '+91 98111 22233', 'active')");
        $insClUser->execute(['Ramesh Sharma', $clientEmail, $clientPassHash]);
        $clientUserId = $pdo->lastInsertId();

        $insClient = $pdo->prepare("INSERT INTO clients (user_id, business_name, owner_name, phone, whatsapp, email, address, city, business_category, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
        $insClient->execute([
            $clientUserId,
            'Sharma Sweets & Restaurant',
            'Ramesh Sharma',
            '+91 98111 22233',
            '919811122233',
            $clientEmail,
            'Shop 14-16, Main Market, Sector 18',
            'Noida, UP',
            'Restaurant',
            'Valued long-term client with high seasonal traffic.'
        ]);
        $clientId = $pdo->lastInsertId();

        // Get Restaurant Template ID
        $getTpl = $pdo->query("SELECT id FROM templates WHERE slug = 'restaurant-delight' LIMIT 1")->fetch();
        $templateId = $getTpl ? $getTpl['id'] : 1;

        // Seed Client Website
        $insWeb = $pdo->prepare("INSERT INTO websites (client_id, template_id, name, slug, domain, tagline, theme_color, meta_title, meta_description, status, ssl_active, views_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'live', 1, 1420)");
        $insWeb->execute([
            $clientId,
            $templateId,
            'Sharma Sweets & Restaurant',
            'sharma-sweets',
            'www.sharmasweetsnoida.com',
            'Authentic North Indian Delicacies & Traditional Sweets Since 1994',
            '#d97706',
            'Sharma Sweets & Restaurant | Best North Indian Food in Noida',
            'Visit Sharma Sweets & Restaurant for pure desi ghee sweets, mouthwatering thalis, chaat, and fine dining with family. Order online or book a table!',
        ]);
        $websiteId = $pdo->lastInsertId();

        // Seed Website Sections
        $sections = [
            [
                'section_key' => 'hero',
                'title' => 'Taste the Authentic Flavors of India',
                'subtitle' => 'Serving pure, handcrafted traditional sweets and delicious multi-cuisine dining prepared with love and organic spices.',
                'content_json' => json_encode([
                    'badge' => 'Welcome to Sharma Sweets & Restaurant',
                    'primary_btn_text' => 'Explore Our Menu',
                    'primary_btn_link' => '#menu',
                    'secondary_btn_text' => 'Book a Table',
                    'secondary_btn_link' => '#reservations',
                    'image' => 'assets/images/mockups/restaurant-hero.jpg',
                    'stat1_num' => '30+', 'stat1_label' => 'Years of Heritage',
                    'stat2_num' => '150+', 'stat2_label' => 'Delicious Dishes',
                    'stat3_num' => '50k+', 'stat3_label' => 'Happy Foodies'
                ])
            ],
            [
                'section_key' => 'about',
                'title' => 'Our Culinary Journey Since 1994',
                'subtitle' => 'From a humble sweet shop in 1994 to Noida’s favorite family restaurant, our commitment to purity and traditional taste remains unbroken.',
                'content_json' => json_encode([
                    'experience_years' => '30',
                    'highlight_text' => '100% Pure Desi Ghee & Fresh Farm Ingredients',
                    'paragraph_1' => 'Sharma Sweets & Restaurant was founded with a singular vision: to deliver unforgettable culinary memories. Every batch of Gulab Jamun, Kaju Katli, and Rasmalai is made fresh daily using pure ingredients.',
                    'paragraph_2' => 'Our air-conditioned family restaurant welcomes you with a warm ambiance, attentive service, and an extensive menu covering North Indian, Tandoori, Chinese, and South Indian delicacies.'
                ])
            ],
            [
                'section_key' => 'contact',
                'title' => 'Visit Us or Order Delivery',
                'subtitle' => 'Have a question or craving something special? Call us or drop by our restaurant today!',
                'content_json' => json_encode([
                    'phone' => '+91 98111 22233',
                    'whatsapp' => '919811122233',
                    'email' => 'orders@sharmasweetsnoida.com',
                    'address' => 'Shop 14-16, Main Market, Sector 18, Noida, Uttar Pradesh 201301',
                    'hours' => 'Mon - Sun: 09:00 AM - 11:00 PM',
                    'maps_embed' => 'https://maps.google.com'
                ])
            ]
        ];

        $insSec = $pdo->prepare("INSERT INTO website_sections (website_id, section_key, title, subtitle, content_json, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $sort = 1;
        foreach ($sections as $sec) {
            $insSec->execute([$websiteId, $sec['section_key'], $sec['title'], $sec['subtitle'], $sec['content_json'], $sort++]);
        }

        // Seed Services / Menu Items
        $services = [
            ['Special Dal Makhani & Shahi Paneer', 'Slow cooked overnight with rich cream and aromatic royal spices.', 320.00, 'Per Portion', 'fas fa-utensils'],
            ['Amritsari Kulcha with Chole', 'Crispy layered tandoori bread served with spicy tangy chickpeas.', 220.00, '2 Pcs with Raita', 'fas fa-bread-slice'],
            ['Pure Desi Ghee Gulab Jamun', 'Melt-in-mouth hot dumplings soaked in cardamom saffron syrup.', 180.00, 'Box of 4 Pcs', 'fas fa-cookie-bite'],
            ['Royal Vegetarian Thali', 'Complete feast with 3 curries, dal, jeera rice, 2 naans, raita, sweet & papad.', 380.00, 'Per Thali', 'fas fa-concierge-bell'],
            ['Premium Kaju Katli', 'Export quality silver leaf diamond cashew fudge made with zero adulteration.', 950.00, 'Per 1 Kg Box', 'fas fa-gift'],
            ['Outdoor Catering & Party Hall', 'Full-service luxury catering for weddings, birthdays, and corporate gatherings.', 750.00, 'Per Plate Starting', 'fas fa-glass-cheers']
        ];
        $insSvc = $pdo->prepare("INSERT INTO services (website_id, title, description, price, price_label, icon, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        $sSort = 1;
        foreach ($services as $svc) {
            $insSvc->execute([$websiteId, $svc[0], $svc[1], $svc[2], $svc[3], $svc[4], $sSort++]);
        }

        // Seed Testimonials
        $testimonials = [
            ['Amit Verma', 'Local Guide (5 Stars)', 'The Dal Makhani and Garlic Naan here are unbeatable in all of Noida. My entire family comes here every Sunday!', 5],
            ['Pooja Singhania', 'Food Blogger', 'Their Rasmalai is legendary! The staff is courteous, service is fast, and the atmosphere is cozy.', 5],
            ['Vikram Malhotra', 'Corporate Event Lead', 'Sharma Sweets catered our office Diwali party for 200 people. Impeccable setup and everyone loved the sweets!', 5]
        ];
        $insTest = $pdo->prepare("INSERT INTO testimonials (website_id, client_name, client_title, content, rating, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $tSort = 1;
        foreach ($testimonials as $tst) {
            $insTest->execute([$websiteId, $tst[0], $tst[1], $tst[2], $tst[3], $tSort++]);
        }

        // Seed FAQs
        $faqs = [
            ['Do you offer home delivery in Noida and Greater Noida?', 'Yes, we provide free home delivery within 5 km on all orders above ₹500. We are also available on Zomato and Swiggy.'],
            ['Is parking available near your restaurant?', 'Yes, convenient paid and valet parking is available in the Sector 18 multi-level parking complex right across from our entrance.'],
            ['Can we book tables in advance for large family dinners?', 'Absolutely! You can call us at +91 98111 22233 or submit the reservation form on this website.']
        ];
        $insFaq = $pdo->prepare("INSERT INTO faqs (website_id, question, answer, sort_order) VALUES (?, ?, ?, ?)");
        $fSort = 1;
        foreach ($faqs as $fq) {
            $insFaq->execute([$websiteId, $fq[0], $fq[1], $fSort++]);
        }

        // Seed Order
        $orderNumber = 'VW-2026-00001';
        $insOrd = $pdo->prepare("INSERT INTO orders (order_number, client_id, template_id, business_name, owner_name, email, phone, whatsapp, business_category, business_address, required_pages, required_features, color_preference, additional_requirements, amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')");
        $insOrd->execute([
            $orderNumber,
            $clientId,
            $templateId,
            'Sharma Sweets & Restaurant',
            'Ramesh Sharma',
            $clientEmail,
            '+91 98111 22233',
            '919811122233',
            'Restaurant',
            'Shop 14-16, Sector 18, Noida',
            json_encode(['Home', 'Menu', 'About', 'Catering', 'Contact']),
            json_encode(['Food Menu', 'Table Booking', 'WhatsApp Direct', 'Customer Reviews', 'Google Maps']),
            'Warm Gold / Amber (#d97706)',
            'Please ensure the pure desi ghee badge is prominently highlighted on the homepage.',
            14999.00
        ]);
        $orderId = $pdo->lastInsertId();

        // Seed Contract (Signed)
        $contractToken = 'c7f9d8e2a1b4c3e5f6a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0';
        $contractNumber = 'VWC-2026-001';
        $contractContent = "<h3>WEBSITE DEVELOPMENT AND HOSTING AGREEMENT</h3><p>This Agreement is entered into by and between <strong>Vishal Web Studio</strong> ('Developer') and <strong>Sharma Sweets & Restaurant</strong> represented by <strong>Ramesh Sharma</strong> ('Client').</p><h4>1. SCOPE OF SERVICES</h4><p>Developer agrees to build a customized, mobile-friendly restaurant website featuring responsive menus, reservation inquiries, WhatsApp direct connect, and zero-code content management admin access.</p><h4>2. TOTAL COMPENSATION & TERMS</h4><p>The total agreed fee is <strong>₹14,999.00</strong> including 1 year of high-speed NVMe cloud hosting and SSL certificate.</p><h4>3. WARRANTIES & REVISIONS</h4><p>Developer provides 30 days of complimentary post-launch bug fixes, performance monitoring, and content maintenance.</p>";
        
        $signatureSvg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='300' height='100'><path d='M10 80 Q 50 10, 90 70 T 170 30 T 250 80' fill='none' stroke='%232563eb' stroke-width='3'/><text x='15' y='95' font-family='Arial' font-size='12' fill='%23666'>Ramesh Sharma (Verified Digital Signature)</text></svg>";
        $contractHash = hash('sha256', $contractContent . 'Ramesh Sharma' . 'client@sharmarestaurant.com');

        $insCnt = $pdo->prepare("INSERT INTO contracts (contract_number, token, order_id, client_id, title, package_name, price, payment_terms, timeline, contract_content, contract_version, status, signed_at, signature_method, signature_data, signer_name, signer_email, signer_ip, contract_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'signed', datetime('now', '-5 days'), 'draw', ?, 'Ramesh Sharma', ?, '103.21.124.89', ?)");
        if ($driver === 'mysql') {
            $insCnt = $pdo->prepare("INSERT INTO contracts (contract_number, token, order_id, client_id, title, package_name, price, payment_terms, timeline, contract_content, contract_version, status, signed_at, signature_method, signature_data, signer_name, signer_email, signer_ip, contract_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'signed', DATE_SUB(NOW(), INTERVAL 5 DAY), 'draw', ?, 'Ramesh Sharma', ?, '103.21.124.89', ?)");
        }
        $insCnt->execute([
            $contractNumber,
            $contractToken,
            $orderId,
            $clientId,
            'Website Development & Cloud Management Contract - Sharma Sweets',
            'Restaurant Premium Package',
            14999.00,
            '50% Advance upon signing, 50% upon final website deployment.',
            '7 Business Days from contract execution',
            $contractContent,
            $signatureSvg,
            $clientEmail,
            $contractHash
        ]);

        // Seed Invoice
        $invoiceNumber = 'INV-2026-00001';
        $itemsJson = json_encode([
            ['desc' => 'Restaurant Delight Premium Website Setup & Responsive Design', 'amount' => 12711.02],
            ['desc' => '1-Year NVMe Fast Cloud Hosting + Free SSL Certificate', 'amount' => 0.00],
            ['desc' => 'Domain Setup & DNS Configuration (sharmasweetsnoida.com)', 'amount' => 0.00]
        ]);
        $subtotal = 12711.02;
        $taxAmount = 2287.98; // 18% GST
        $total = 14999.00;

        $insInv = $pdo->prepare("INSERT INTO invoices (invoice_number, client_id, order_id, subtotal, tax_rate, tax_amount, discount, total, paid_amount, status, due_date, items_json) VALUES (?, ?, ?, ?, 18.00, ?, 0.00, ?, ?, 'paid', date('now', '+10 days'), ?)");
        if ($driver === 'mysql') {
            $insInv = $pdo->prepare("INSERT INTO invoices (invoice_number, client_id, order_id, subtotal, tax_rate, tax_amount, discount, total, paid_amount, status, due_date, items_json) VALUES (?, ?, ?, ?, 18.00, ?, 0.00, ?, ?, 'paid', DATE_ADD(CURDATE(), INTERVAL 10 DAY), ?)");
        }
        $insInv->execute([$invoiceNumber, $clientId, $orderId, $subtotal, $taxAmount, $total, $total, $itemsJson]);
        $invoiceId = $pdo->lastInsertId();

        // Seed Payment
        $insPay = $pdo->prepare("INSERT INTO payments (client_id, order_id, invoice_id, amount, payment_method, transaction_id, status, notes) VALUES (?, ?, ?, ?, 'upi', 'UPI-REF-9938210984', 'completed', 'Full settlement received via Google Pay UPI.')");
        $insPay->execute([$clientId, $orderId, $invoiceId, $total]);

        // Seed Domain & Hosting records
        $insDom = $pdo->prepare("INSERT INTO domains (client_id, website_id, domain_name, registrar, registration_date, expiry_date, renewal_cost, status) VALUES (?, ?, 'www.sharmasweetsnoida.com', 'GoDaddy India', date('now', '-30 days'), date('now', '+335 days'), 999.00, 'active')");
        if ($driver === 'mysql') {
            $insDom = $pdo->prepare("INSERT INTO domains (client_id, website_id, domain_name, registrar, registration_date, expiry_date, renewal_cost, status) VALUES (?, ?, 'www.sharmasweetsnoida.com', 'GoDaddy India', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 335 DAY), 999.00, 'active')");
        }
        $insDom->execute([$clientId, $websiteId]);

        $insHost = $pdo->prepare("INSERT INTO hosting (client_id, website_id, provider, plan_name, server_ip, disk_space, start_date, expiry_date, renewal_cost, status) VALUES (?, ?, 'Hostinger Cloud India', 'Cloud Startup NVMe', '185.199.108.153', '20 GB NVMe', date('now', '-30 days'), date('now', '+335 days'), 3499.00, 'active')");
        if ($driver === 'mysql') {
            $insHost = $pdo->prepare("INSERT INTO hosting (client_id, website_id, provider, plan_name, server_ip, disk_space, start_date, expiry_date, renewal_cost, status) VALUES (?, ?, 'Hostinger Cloud India', 'Cloud Startup NVMe', '185.199.108.153', '20 GB NVMe', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 335 DAY), 3499.00, 'active')");
        }
        $insHost->execute([$clientId, $websiteId]);

        // Seed Support Ticket
        $insTkt = $pdo->prepare("INSERT INTO support_tickets (ticket_number, client_id, user_id, subject, category, priority, status) VALUES ('TKT-2026-101', ?, ?, 'Add Weekend Special Banner on Homepage', 'Content Update', 'medium', 'open')");
        $insTkt->execute([$clientId, $clientUserId]);
        $ticketId = $pdo->lastInsertId();

        $insMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, 'Hello Vishal team, could you please help us add a special banner for our Sunday Unlimited Thali promo?')");
        $insMsg->execute([$ticketId, $clientUserId]);

        echo "[+] Demo Client, Website, Contract, Invoice, Payment, Domain, Hosting & Support Ticket seeded.\n";
    }

    // Seed Public Blog Posts
    $blogs = [
        [
            'title' => '10 Reasons Why Every Local Business Needs a Fast, Mobile Website in 2026',
            'slug' => 'why-local-business-needs-website-2026',
            'summary' => 'Over 82% of customers search on their smartphones before visiting a shop or booking a service. Discover how a professional digital storefront transforms local walk-ins into loyal repeat buyers.',
            'category' => 'Business Growth',
            'content' => '<p>In today\'s digital-first economy, your website is your business\'s 24/7 digital headquarters. Whether you operate a fine-dining restaurant, a wellness spa, an educational institute, or a consulting agency, modern customers evaluate your credibility within 3 seconds of visiting your link.</p><h4>1. First Impressions Determine Trust</h4><p>A website that takes more than 3 seconds to load loses 53% of its visitors. Modern responsive designs built with clean code and lightweight CSS guarantee blistering load speeds across all 4G and 5G networks.</p><h4>2. Direct WhatsApp Conversion</h4><p>Integrating 1-click WhatsApp buttons eliminates friction, allowing prospective clients to message your team instantly with order inquiries and appointment requests.</p>',
            'tags' => 'Web Design, Local SEO, WhatsApp Integration, Business Growth'
        ],
        [
            'title' => 'How Zero-Code CMS Panels Empower Non-Technical Business Owners',
            'slug' => 'how-zero-code-cms-empowers-business-owners',
            'summary' => 'Never wait days for a web developer to change a phone number or menu price. Explore how modern client admin dashboards put you in total control.',
            'category' => 'Technology',
            'content' => '<p>Historically, maintaining a business website required hiring a specialized web agency for every minor text modification. At Vishal Web Studio, we provide every client with their own dedicated, secure, zero-code admin panel.</p><p>With intuitive forms for hero banners, services, pricing rate cards, customer reviews, and photo galleries, updates are applied to the live website with a single click of the "Publish Changes" button.</p>',
            'tags' => 'CMS, Website Builder, Small Business, Productivity'
        ],
        [
            'title' => 'The Complete Website Launch Checklist: From Template to First Sale',
            'slug' => 'complete-website-launch-checklist',
            'summary' => 'Step-by-step blueprint covering domain registration, SSL certificates, SEO meta optimization, contact routing, and Google Maps integration.',
            'category' => 'Web Development',
            'content' => '<p>Launching a high-converting website involves more than just aesthetics. Here is our battle-tested 6-step checklist to ensure your brand stands out from day one:</p><ul><li><strong>High-Impact Hero Section:</strong> Clear value proposition and prominent Call to Action.</li><li><strong>Verified Social Proof:</strong> Real customer testimonials and rating stars.</li><li><strong>Mobile First Navigation:</strong> Seamless fingertip navigation and click-to-call buttons.</li><li><strong>SSL Security & Fast Hosting:</strong> HTTPS encryption and reliable NVMe server infrastructure.</li></ul>',
            'tags' => 'Checklist, Launch, SEO, Web Strategy'
        ]
    ];

    $chkBlog = $pdo->prepare("SELECT id FROM blog_posts WHERE slug = ?");
    $insBlog = $pdo->prepare("INSERT INTO blog_posts (author_id, title, slug, summary, content, category, tags, meta_title, meta_description, status, views) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', 320)");
    foreach ($blogs as $b) {
        $chkBlog->execute([$b['slug']]);
        if (!$chkBlog->fetch()) {
            $insBlog->execute([
                $adminId,
                $b['title'],
                $b['slug'],
                $b['summary'],
                $b['content'],
                $b['category'],
                $b['tags'],
                $b['title'] . ' | Vishal Web Studio Blog',
                $b['summary']
            ]);
        }
    }
    echo "[+] Public Blog posts seeded.\n";

    // Activity Log
    $insLog = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address) VALUES (?, 'system_install', 'system', 1, 'Vishal Web Studio database initialized and seeded successfully.', '127.0.0.1')");
    $insLog->execute([$adminId]);

    echo "\n========================================================\n";
    echo " INSTALLATION COMPLETED SUCCESSFULLY!\n";
    echo "========================================================\n";
    echo "Default Logins:\n";
    echo "  * Super Admin: admin@vishalwebstudio.com / admin123\n";
    echo "  * Demo Client: client@sharmarestaurant.com / client123\n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "\n[!] Installation Error: " . $e->getMessage() . "\n";
    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
        echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
    }
    exit(1);
}
