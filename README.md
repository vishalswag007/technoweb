# Vishal Web Studio — Multi-Client Website Business Platform

A complete, production-ready, responsive multi-tenant web platform for website development businesses, designed to showcase services, sell ready-made website templates, intake customer requirements, digitally execute contracts with HTML5 Canvas signatures, generate tax invoices, track domains and hosting, and provide every client with an isolated, zero-code content management panel.

---

## 🌟 Key Features

### Layer 1: Public Business Website & Marketplace
- **Modern SaaS Aesthetics**: Interactive sticky header, glassmorphism badges, animated mockup window, statistics counters, and FontAwesome 6 icons.
- **Why Choose Us**: 8 value pillars covering Mobile-First speed, Zero-Code CMS, WhatsApp lead capture, and SSL security.
- **Service Categories**: 9+ dedicated services (Restaurant, Salon, Coaching, Real Estate, Medical Clinic, E-commerce, Business Agency, Portfolio).
- **Template Marketplace**: Multi-category filterable gallery with live responsive preview switcher (Desktop, Tablet, Mobile) and 1-click "Use This Template" order CTA.
- **Requirements Intake**: Multi-step order form generating unique Order IDs (e.g., `VW-2026-00001`), saving business info, logo/assets uploads, and preferences.
- **15-Stage Order Pipeline Tracker**: Visual progress stepper from *New Order* to *Published Live*.
- **Digital Signature Gateway**: Token-based contract signing portal (`/public/contract.php?token=...`) supporting HTML5 Canvas drawing, typed stylized SVG signatures, and image upload with SHA-256 cryptographic locking.
- **Tax Invoices**: Print/PDF-ready invoice viewer with GST breakdown, discount, balance due, and WhatsApp invoice sharing.
- **Blog System**: Public blog articles with SEO meta tags, categories, view counters, and social share links.

### Layer 2: Super Admin Control Center (`/super-admin/`)
- **Executive Dashboard**: Real-time stats (Clients, Live Websites, Pending Orders, Contracts, Payments, Revenue, Support Tickets) and audit stream.
- **Client CRM**: Full client CRUD, status toggle, reset password, and **"Login as Client" impersonation**.
- **Website Registry & Cloner**: 1-Click **"Create Website From Template"** that deep-clones default pages, services, menus, and FAQs into isolated tenant records.
- **Template Engine**: Add/edit/duplicate marketplace templates, set category, pricing, default colors, and layout presets.
- **Order Pipeline Manager**: Update orders through all 15 stages, view client requirements, and trigger 1-click contract and website generation.
- **Contract Builder**: Template compiler with dynamic merge tags (`{{client_name}}`, `{{package}}`, `{{price}}`, `{{timeline}}`), tamper-proof hash generation, and PDF export.
- **Invoices & Payments**: Tax invoice generator, line items ledger, manual payment recording, and Razorpay/Stripe readiness.
- **Domains & Hosting**: Registrar tracking, SSL monitoring, renewal cost alerts, server IP records, and WhatsApp expiry reminders.
- **Support Helpdesk**: Ticket triage with priority flags and threaded replies between admin and clients.
- **Disaster Recovery & Backups**: 1-click full database SQL export and single-website portable JSON archive export.
- **Global Settings**: Agency branding, WhatsApp message templates, SMTP, and session security.

### Layer 3: Client Admin Panel & Dynamic Website Renderer (`/client/` & `/site/`)
- **Strict Tenant Isolation**: Server-side authorization ensures clients can only access and modify their own records.
- **Zero-Code Website Builder**: Form-based editing for Hero banners, About Us story, Services/Menu items, FAQs, and Contact details.
- **Photo Gallery**: Media library to upload photos that immediately render on the live client website.
- **Custom Pages**: Manage extra pages with custom slugs and SEO meta tags.
- **Theme Color Customizer**: Dynamic accent color picker that styles the client website in real-time.
- **1-Click Live Publisher**: Instant publish toggle that synchronizes saved drafts to live production.
- **Dynamic Multi-Tenant Renderer (`/site/index.php`)**: Dynamically renders client websites (`?site=slug`) and template demos (`?demo=slug`) with mobile navigation, services catalog, gallery, testimonials, and visitor inquiry booking forms.

---

## 🚀 Quick Start Guide

### Option 1: 1-Click Windows Runner
Double-click `run.bat` or run in terminal:
```bat
run.bat
```
This automatically runs database migrations and starts the local PHP web server at **http://localhost:8000**.

### Option 2: Manual CLI / XAMPP
```bash
# 1. Run the database installer
php database/installer.php

# 2. Start the built-in development server
php -S localhost:8000
```
Open **http://localhost:8000** in your browser.

---

## 🔑 Default Credentials

| Portal | Email | Password | Role |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@vishalwebstudio.com` | `admin123` | Master Super Admin |
| **Demo Client** | `client@sharmarestaurant.com` | `client123` | Restaurant Client Admin |

> [!TIP]
> On the login page (`/public/login.php`), use the **Quick 1-Click Demo Login** buttons to autofill and test both roles instantly.

---

## 🗄️ Database Architecture
- **Automatic Engine Detection**: Connects to MySQL/MariaDB (default XAMPP `localhost`, user `root`) or auto-switches to local SQLite (`database/vishal_web_studio.sqlite`) for zero-config immediate testing.
- **Foreign Keys & Cascading**: Full relational integrity across 24 entities.

---

## 📱 WhatsApp Action Integration
Configured with dynamic URL deep-linking:
- `Order Confirmation`
- `Contract Signing Link`
- `Tax Invoice Receipt`
- `Domain Expiry Alert`
- `Live Website Announcement`
