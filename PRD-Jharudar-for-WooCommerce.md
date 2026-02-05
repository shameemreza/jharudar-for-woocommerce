# Product Requirements Document (PRD)
## Jharudar for WooCommerce - Complete Store Data Management Suite

**Version:** 0.0.1  
**Author:** Shameem Reza
**Author URI:** shameem.dev
**Date:** February 5, 2026  
**Status:** Planning Phase

---

**Why "Jharudar"?**

Jharudar (Bengali: ঝাড়ুদার, Hindi: झाड़ूदार) is a word meaning "sweeper" or "cleaner" - someone who keeps spaces clean and organized. Just as a Jharudar maintains cleanliness in physical spaces, this plugin keeps your WooCommerce store clean, optimized, and free of unnecessary data. Built with care to help store owners maintain a healthy, high-performing WooCommerce database.

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Market Analysis and Competitive Landscape](#market-analysis-and-competitive-landscape)
3. [Plugin Naming and Branding](#plugin-naming-and-branding)
4. [Gap Analysis and Opportunities](#gap-analysis-and-opportunities)
5. [Product Vision and Goals](#product-vision-and-goals)
6. [Feature Requirements](#feature-requirements)
7. [Technical Requirements](#technical-requirements)
8. [UI/UX Guidelines](#uiux-guidelines)
9. [Integration Requirements](#integration-requirements)
10. [Release Strategy](#release-strategy)
11. [Success Metrics](#success-metrics)

---

## Executive Summary

### Background

Based on research of WooCommerce.com marketplace and WordPress.org plugin directory, there is strong demand for comprehensive data cleanup solutions. Store owners frequently need to clean test data, remove old records, comply with GDPR requirements, and optimize database performance. However, existing solutions are fragmented - each focusing on only one data type.

### Market Opportunity

| Competitor | Price | Active Installs | Rating | Key Gap |
|------------|-------|-----------------|--------|---------|
| Product Cleaner for WooCommerce | $29/year | 100+ | Not available | Products only, no subscriptions or memberships support. |
| Product Duplicate Finder and Remover | $49/year | Not available | Not available | Duplicates only. |
| Customers Cleanup | $29/year | Not available | Not available | Customers only. |
| Complete Product Cleaner | Free | Less than 10 | 5 out of 5 | Limited features, new plugin. |
| Store Toolkit | Free | 30,000+ | 4.8 out of 5 | Toolkit approach, not specialized. |
| Prune WooCommerce Orders | Free | Not available | 5 out of 5 | Orders only. |

**Key Insight:** No single plugin offers comprehensive cleanup for all WooCommerce data types with support for popular extensions like Subscriptions, Memberships, Bookings, and Appointments.

### Proposed Solution

A comprehensive, all-in-one data management and cleanup solution that:

- Covers all WooCommerce data types including products, orders, customers, and coupons.
- Supports popular WooCommerce extensions such as Subscriptions, Memberships, Bookings, and Appointments.
- Provides modern, native WordPress admin UI following WooCommerce design patterns.
- Offers both selective and bulk operations with preview before deletion.
- Includes GDPR compliance tools for data erasure and anonymization.
- Features background processing for large datasets to prevent timeouts.
- Provides data export before deletion for safety and compliance.

---

## Market Analysis and Competitive Landscape

### Competitor Deep Dive

#### Store Toolkit for WooCommerce (Free - WordPress.org)

**Strengths:**

- Over 30,000 active installs with 4.8 out of 5 rating.
- Comprehensive deletion tools for multiple data types including products, orders, coupons, tax rates, and shipping classes.
- HPOS compatibility declared.
- WP-CLI support for command-line operations.
- Quick enhancements beyond cleanup functionality.
- CRON-based automated cleanup scheduling.
- Delete download permissions and WooCommerce logs.

**Weaknesses:**

- "Nuke" terminology may concern cautious users.
- No selective filtering before deletion.
- No preview or undo capability.
- No background processing for large operations.
- User interface appears dated.
- No export before delete functionality.
- No GDPR-specific tools.

**Features to Learn From:**

- Delete by order status filtering.
- Delete by order date filtering.
- Delete by product category filtering.
- Generate sample orders for testing purposes.
- WP-CLI integration for automation.
- Re-link rogue products to Simple Product Type.
- Delete corrupt product variations.
- Refresh product transients.
- Filter orders by billing or shipping country.
- Filter orders by payment method.

#### Advanced Database Cleaner (Free/Premium - WordPress.org)

**Strengths:**

- Over 100,000 active installs with 4.9 out of 5 rating.
- Comprehensive database cleanup including revisions, auto-drafts, transients, and orphaned data.
- Preview items before deletion with counts.
- Database table management with optimization and repair.
- Options table management with autoload detection.
- Cron jobs management and cleanup.
- Scheduled automatic cleanup tasks.
- Keep last X days retention policy.
- Action Scheduler cleanup in premium version.
- Multisite support in premium version.

**Weaknesses:**

- Not WooCommerce-specific.
- No understanding of WooCommerce data relationships.
- Premium required for advanced features.

**Features to Learn From:**

- Delete duplicate meta entries.
- Detect large options slowing down site.
- Display database size freed before cleaning.
- Database analytics with charts.
- Identify data ownership by plugin.

#### Product Cleaner for WooCommerce ($29/year - WooCommerce.com)

**Strengths:**

- Real-time progress tracking during operations.
- Filter options available before deletion.
- Abort capability during processing.
- Image cleanup options included.

**Weaknesses:**

- Products only, no other data types.
- No subscription or membership support.
- No order or customer cleanup.
- No export before delete.

#### Complete Product Cleaner (Free - WordPress.org)

**Strengths:**

- Background processing via Action Scheduler.
- Tabbed interface for organization.
- Orphaned image detection feature.
- Expanded to include orders, customers, taxonomies, and coupons.

**Weaknesses:**

- Very new plugin with few installs.
- Basic user interface.
- No extension support.
- No scheduled cleanup.

#### Customers Cleanup ($29/year - WooCommerce.com)

**Strengths:**

- Identifies inactive customers automatically.
- Background batch processing.
- Admin email notifications.
- Fake and bot account detection.

**Weaknesses:**

- Customers only, no other data types.
- No direct deletion, report only approach.
- No GDPR anonymization tools.

### User Pain Points from Forum Research

**Performance Issues:**

- Stores with over 10,000 orders experiencing significant slowdowns.
- Orphaned metadata bloating the database over time.
- Expired transients accumulating without cleanup.

**Test Data Cleanup:**

- Developers need to reset staging and development sites regularly.
- Demo data removal required before going live.
- Seasonal inventory changes requiring bulk updates.

**GDPR Compliance:**

- Customer data erasure requests must be handled properly.
- Order anonymization needs for data retention compliance.
- Subscription data retention policies required.

**Extension Data:**

- Expired subscriptions accumulating in the database.
- Cancelled memberships data remaining indefinitely.
- Old booking records taking up space.

**Orphaned Data:**

- Orphaned postmeta from deleted products and orders.
- Unused term relationships cluttering the database.
- Detached order items from deleted orders.
- Abandoned sessions consuming storage.

---

## Plugin Naming and Branding

### Naming Constraints

Per WordPress.org Plugin Guidelines (Rule 17):

- Plugin name cannot start with "WooCommerce" due to trademark restrictions.
- Must follow format such as "Plugin Name for WooCommerce".
- Must be unique and not imply official affiliation.

### Selected Name

| Attribute | Value |
|-----------|-------|
| Plugin Name | Jharudar for WooCommerce |
| Slug | jharudar-for-woocommerce |
| Short Name | Jharudar |
| Tagline | The Complete Store Cleanup Toolkit |
| Meaning | Bengali and Hindi word meaning sweeper or cleaner |

### Why Jharudar

- **100% Unique:** No trademark conflicts and no existing products with this name.
- **Meaningful:** Direct translation to the plugin purpose as sweeper or cleaner.
- **Memorable:** Unusual word that stands out in plugin directories.
- **Authentic:** Reflects the developer cultural heritage.
- **Storytelling:** Creates engagement as people will want to know the meaning.
- **Brandable:** Can become a recognized name in the WooCommerce ecosystem.

### Successful Precedents for Non-English Names

- Ubuntu is an African word meaning humanity.
- Kubernetes is Greek for helmsman.
- Akismet is an invented word.

### Branding Guidelines

The plugin will follow native WordPress and WooCommerce admin styling with a modern touch. This means using:

- WordPress admin color schemes and components.
- WooCommerce admin patterns and conventions.
- Native form elements, tables, and notices.
- WordPress button styles and modal dialogs.
- Consistent typography following WordPress admin standards.

The goal is to make the plugin feel like a natural extension of WooCommerce rather than a third-party addition with custom styling.

---

## Gap Analysis and Opportunities

### Feature Gaps vs Competitors

| Feature | Jharudar (Planned) | Store Toolkit | Adv DB Cleaner | Product Cleaner | Opportunity |
|---------|-------------------|---------------|----------------|-----------------|-------------|
| Product Cleanup | Planned | Yes | No | Yes | High priority. |
| Order Cleanup | Planned | Yes | No | No | High priority. |
| Customer Cleanup | Planned | No | No | No | High priority. |
| Booking Cleanup | Planned | Yes | No | No | High priority. |
| Subscription Cleanup | Planned | Yes | No | No | High priority. |
| Membership Cleanup | Planned | Yes | No | No | High priority. |
| Product Vendors | Planned | No | No | No | Unique feature. |
| Coupon Cleanup | Planned | Yes | No | No | Medium priority. |
| Tax Rate Cleanup | Planned | Yes | No | No | Medium priority. |
| Shipping Config Cleanup | Planned | No | No | No | Unique feature. |
| Webhook Cleanup | Planned | No | No | No | Unique feature. |
| API Keys Cleanup | Planned | No | No | No | Unique feature. |
| Payment Tokens Cleanup | Planned | No | No | No | Unique feature. |
| Download Permissions | Planned | Yes | No | No | High priority. |
| Download Logs | Planned | No | No | No | Unique feature. |
| Admin Inbox Cleanup | Planned | No | No | No | Unique feature. |
| Reserved Stock Cleanup | Planned | No | No | No | Unique feature. |
| Action Scheduler Cleanup | Planned | No | Premium only | No | High priority. |
| Orphaned Data Detection | Planned | No | Yes | Yes | High priority. |
| Duplicate Meta Cleanup | Planned | No | Yes | No | High priority. |
| Post Revisions Cleanup | Planned | No | Yes | No | Medium priority. |
| WordPress Content Cleanup | Planned | No | Yes | No | Medium priority. |
| Background Processing | Planned | No | Yes | Yes | High priority. |
| Progress Tracking | Planned | No | Yes | Yes | High priority. |
| Export Before Delete | Planned | No | No | No | Unique feature. |
| Dry Run Mode | Planned | No | Yes | No | High priority. |
| GDPR Tools | Planned | No | No | No | Unique feature. |
| Retention Policies | Planned | No | Yes | No | High priority. |
| Database Analytics | Planned | No | Premium only | No | High priority. |
| WP-CLI Support | Planned | Yes | No | No | High priority. |
| REST API | Planned | No | No | No | Unique feature. |
| Sample Data Generation | Planned | Yes | No | No | Medium priority. |
| Activity Logging | Planned | No | No | No | Unique feature. |

### Unique Differentiators to Build

- **All-in-One Solution:** Single plugin covering all WooCommerce data types including products, orders, customers, coupons, webhooks, payment tokens, downloads, and more.
- **Extension Support:** Full support for WooCommerce Subscriptions, Memberships, Bookings, Appointments, and Product Vendors.
- **Safety First:** Export before delete, preview mode, dry run capability, and multiple confirmation steps.
- **GDPR Compliance:** Built-in data erasure, anonymization, and retention policy tools.
- **Performance Insights:** Database size analytics showing impact before and after cleanup operations.
- **Scheduled Cleanup:** Automate recurring cleanup tasks with retention policies using WP Cron.
- **Audit Trail:** Comprehensive logging of all cleanup actions for compliance and debugging.
- **Action Scheduler Cleanup:** First plugin to offer dedicated cleanup for Action Scheduler data which commonly bloats databases.
- **Database Health Dashboard:** Visual overview of database health with cleanup recommendations.
- **Dry Run Mode:** Preview exactly what will be deleted before committing to the operation.
- **Sample Data Generation:** Built-in tool to generate test data for development and staging environments.

---

## Product Vision and Goals

### Vision Statement

To be the most comprehensive, safest, and easiest-to-use data management solution for WooCommerce stores, supporting all major extensions and providing peace of mind through safety features and compliance tools.

### Primary Goals

| Goal | Metric | Target |
|------|--------|--------|
| Comprehensive Coverage | Data types supported | 15 or more types |
| Extension Support | Popular extensions supported | 5 or more major extensions |
| User Safety | Data loss incidents | Zero incidents |
| Performance | Large store support | 100,000 or more items |
| User Satisfaction | Plugin rating | 4.8 or higher stars |
| Market Position | Active installs in Year 1 | 10,000 or more |

### Target Users

- **Store Administrators:** Managing live stores, need to clean test data or old records.
- **Developers:** Setting up staging environments, resetting demo data.
- **Agencies:** Managing multiple client stores efficiently.
- **Enterprise:** Large stores with compliance requirements.

---

## Feature Requirements

### Core Module: Products

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete All Products | P0 | Bulk delete all products and variations. |
| Delete by Category | P0 | Filter and delete by product category. |
| Delete by Status | P0 | Filter by draft, pending, private, or published status. |
| Delete by Stock Status | P1 | Filter by out of stock or low stock items. |
| Delete by Date | P1 | Delete products older than specified days. |
| Delete by Type | P1 | Filter by simple, variable, grouped, or external type. |
| Delete Product Images | P0 | Option to include or exclude images in deletion. |
| Delete Orphaned Images | P0 | Scan and remove unused product images. |
| Export Before Delete | P0 | CSV or JSON export before deletion. |
| Preview Selection | P0 | Show what will be deleted before confirming. |
| Duplicate Detection | P2 | Find and remove duplicate products. |

### Core Module: Orders

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete by Status | P0 | Filter by order status. |
| Delete by Date Range | P0 | Delete orders between specified dates. |
| Delete by Payment Method | P1 | Filter by payment gateway. |
| Delete by Customer | P1 | Delete all orders from specific customer. |
| Anonymize Orders | P0 | GDPR-compliant anonymization of personal data. |
| Delete Order Notes | P1 | Remove all order notes. |
| Delete Refunds | P1 | Remove refund records. |
| Export Before Delete | P0 | CSV export before deletion. |
| Retain Analytics | P1 | Option to keep analytics data when deleting orders. |

### Core Module: Customers

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Inactive | P0 | Delete customers with no orders in specified months. |
| Delete by Role | P0 | Filter by user role. |
| Delete by Registration Date | P1 | Delete customers registered before specified date. |
| Delete Zero-Order | P0 | Delete customers who never placed an order. |
| Delete Failed Checkout | P1 | Delete accounts created from failed checkouts. |
| Anonymize Customers | P0 | GDPR anonymization of customer data. |
| Merge Duplicates | P2 | Find and merge duplicate accounts. |
| Export Before Delete | P0 | Export customer data before deletion. |
| Exclude Admin Users | P0 | Safety check to prevent admin account deletion. |

### Core Module: Coupons

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Expired | P0 | Remove expired coupons. |
| Delete Unused | P1 | Remove coupons never used. |
| Delete by Date | P1 | Remove coupons created before specified date. |
| Delete by Type | P2 | Filter by discount type. |
| Delete by Usage Limit | P2 | Remove coupons that have reached usage limits. |
| Export Before Delete | P1 | Backup before removal. |

### Core Module: Tax Rates

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete All Tax Rates | P1 | Remove all configured tax rates. |
| Delete by Country | P2 | Filter tax rates by country. |
| Delete by Tax Class | P2 | Filter by tax class. |
| Export Before Delete | P1 | Backup tax configuration before removal. |

### Core Module: Shipping Configuration

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Shipping Zones | P2 | Remove shipping zone configurations. |
| Delete Shipping Methods | P2 | Remove shipping methods from zones. |
| Delete Shipping Classes | P1 | Remove unused shipping classes. |
| Export Before Delete | P2 | Backup shipping configuration. |

### Core Module: Taxonomy and Terms

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Empty Categories | P0 | Remove categories with no products. |
| Delete Unused Tags | P0 | Remove tags not assigned to any products. |
| Delete Attributes | P1 | Remove product attributes. |
| Delete Brands | P2 | Remove brand taxonomies if applicable. |
| Merge Terms | P2 | Combine duplicate terms. |

### Extension Module: WooCommerce Subscriptions

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Cancelled | P0 | Remove cancelled subscriptions. |
| Delete Expired | P0 | Remove expired subscriptions. |
| Delete by Status | P0 | Filter by subscription status. |
| Delete by Date | P1 | Delete subscriptions older than specified period. |
| Delete Related Orders | P1 | Option to delete renewal orders. |
| Export Before Delete | P0 | Subscription data export. |

### Extension Module: WooCommerce Memberships

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Cancelled | P0 | Remove cancelled memberships. |
| Delete Expired | P0 | Remove expired memberships. |
| Delete by Plan | P1 | Filter by membership plan. |
| Delete by Status | P0 | Filter by membership status. |
| Export Before Delete | P0 | Membership data export. |

### Extension Module: WooCommerce Bookings

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete by Status | P0 | Filter by booking status. |
| Delete by Date Range | P0 | Delete bookings in specified date range. |
| Delete Past Bookings | P0 | Delete bookings before today. |
| Delete with Orders | P1 | Option to delete related orders. |
| Delete Resources | P2 | Remove booking resources. |
| Export Before Delete | P0 | Booking data export. |

### Extension Module: WooCommerce Appointments

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete by Status | P0 | Filter by appointment status. |
| Delete Past | P0 | Delete past appointments. |
| Delete by Staff | P1 | Filter by staff member. |
| Export Before Delete | P0 | Appointment data export. |

### Extension Module: WooCommerce Product Vendors

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Vendor Data | P2 | Remove vendor accounts and data. |
| Delete by Status | P2 | Filter by vendor status. |
| Delete Commission Records | P2 | Remove old commission records. |
| Export Before Delete | P2 | Vendor data export. |

### Core Module: Webhooks and API

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Failed Webhooks | P1 | Remove webhooks with delivery failures. |
| Delete Disabled Webhooks | P1 | Remove disabled webhook configurations. |
| Delete Old API Keys | P1 | Remove unused REST API keys. |
| Delete Rate Limit Data | P2 | Clear API rate limiting records. |
| View Webhook Logs | P1 | Preview webhook delivery logs before cleanup. |

### Core Module: Payment Data

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Expired Tokens | P1 | Remove expired saved payment methods. |
| Delete Orphaned Tokens | P1 | Remove tokens for deleted customers. |
| Anonymize Payment Data | P1 | GDPR-compliant payment data anonymization. |

### Core Module: Downloads

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Expired Permissions | P0 | Remove expired download permissions. |
| Delete Download Logs | P1 | Clear download activity logs. |
| Delete by Product | P1 | Remove permissions for specific products. |
| Delete by Customer | P1 | Remove permissions for specific customers. |

### Core Module: Admin Inbox

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Read Notes | P1 | Remove read admin inbox notifications. |
| Delete Actioned Notes | P1 | Remove notes that have been actioned. |
| Delete by Date | P1 | Remove notes older than specified date. |
| Delete All Notes | P2 | Clear entire admin inbox. |

### Core Module: Reserved Stock

| Feature | Priority | Description |
|---------|----------|-------------|
| Clear Expired Reservations | P0 | Remove stock reserved from abandoned checkouts. |
| View Reserved Items | P1 | Preview currently reserved stock. |

### Database Optimization Module

| Feature | Priority | Description |
|---------|----------|-------------|
| Clean Transients | P0 | Remove expired transients from wp_options. |
| Clean WooCommerce Transients | P0 | Remove WooCommerce-specific transients. |
| Clean Sessions | P0 | Remove expired WooCommerce sessions. |
| Clean Orphaned Postmeta | P0 | Remove postmeta without valid posts. |
| Clean Orphaned Usermeta | P0 | Remove usermeta without valid users. |
| Clean Orphaned Termmeta | P1 | Remove termmeta without valid terms. |
| Clean Orphaned Commentmeta | P1 | Remove commentmeta without valid comments. |
| Clean Orphaned Order Item Meta | P1 | Remove order item meta without valid items. |
| Clean Orphaned Relationships | P1 | Remove term relationships without valid posts. |
| Clean Duplicate Meta | P1 | Remove duplicate meta entries. |
| Clean oEmbed Caches | P2 | Remove oEmbed cache data. |
| Optimize Tables | P1 | Run OPTIMIZE TABLE commands. |
| Repair Tables | P2 | Repair corrupted database tables. |
| Database Size Report | P0 | Show before and after size comparison. |
| Table Analysis | P1 | Show row counts and sizes for all tables. |
| Detect Large Options | P1 | Identify options slowing down autoload. |
| Manage Autoload | P2 | Toggle autoload status for options. |
| Detect Plugin Tables | P2 | Identify tables created by plugins. |
| Delete Orphaned Tables | P2 | Remove tables from uninstalled plugins. |

### Action Scheduler Module

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Completed Actions | P0 | Remove successfully completed scheduled actions. |
| Delete Failed Actions | P0 | Remove failed scheduled actions. |
| Delete Cancelled Actions | P1 | Remove cancelled scheduled actions. |
| Delete Action Logs | P0 | Clear Action Scheduler execution logs. |
| Delete by Date | P1 | Remove actions older than specified date. |
| Delete by Group | P1 | Filter actions by group name. |
| View Pending Actions | P1 | Preview pending actions before cleanup. |

### WordPress General Cleanup Module

| Feature | Priority | Description |
|---------|----------|-------------|
| Delete Post Revisions | P1 | Remove all post revisions. |
| Delete Auto-Drafts | P1 | Remove auto-saved drafts. |
| Delete Trashed Posts | P0 | Permanently remove trashed posts. |
| Delete Trashed Pages | P0 | Permanently remove trashed pages. |
| Delete Spam Comments | P0 | Remove all spam comments. |
| Delete Trashed Comments | P0 | Permanently remove trashed comments. |
| Delete Pending Comments | P2 | Remove unapproved pending comments. |
| Delete Pingbacks | P2 | Remove pingback comments. |
| Delete Trackbacks | P2 | Remove trackback comments. |
| Keep Last X Revisions | P1 | Option to retain recent revisions per post. |

### GDPR Compliance Module

| Feature | Priority | Description |
|---------|----------|-------------|
| Customer Data Export | P0 | Export all customer data. |
| Customer Data Erasure | P0 | Handle GDPR erasure requests. |
| Order Anonymization | P0 | Anonymize personal data in orders. |
| Consent Cleanup | P1 | Remove old consent records. |
| Audit Log | P0 | Log all data operations. |
| Retention Policies | P1 | Auto-cleanup based on retention rules. |

### Advanced Features

| Feature | Priority | Description |
|---------|----------|-------------|
| Scheduled Cleanup | P1 | WP Cron scheduled tasks with configurable intervals. |
| Retention Policies | P1 | Auto-cleanup based on keep last X days or items rules. |
| WP-CLI Commands | P1 | Command-line interface support for all modules. |
| REST API | P2 | Programmatic access via REST endpoints. |
| Multi-site Support | P2 | Network-wide operations for WordPress Multisite. |
| Import/Export Rules | P2 | Share cleanup configurations between sites. |
| Sample Data Generator | P2 | Generate test orders, products, and customers for development. |
| Backup Reminder | P1 | Prompt users to backup before large operations. |
| Activity Dashboard | P1 | Overview of database health and cleanup recommendations. |
| Cleanup History | P1 | Log of all cleanup operations performed. |
| Email Notifications | P2 | Notify admins after scheduled cleanup completes. |
| Dry Run Mode | P1 | Preview what would be deleted without actual deletion. |

---

## Development Guidelines

### Code Quality Standards

- **Prefix:** Use `jharudar_` for functions, `Jharudar_` for classes, `JHARUDAR_` for constants.
- **Coding Standards:** Follow WordPress PHP Coding Standards (WPCS) strictly.
- **Static Analysis:** Run PHPStan Level 2 and fix all issues.
- **Testing Tools:** Run WP PHPCS and Plugin Check before each phase completion.

### Styling Guidelines

- **Admin UI:** Use only native WordPress and WooCommerce admin styles.
- **Dropdowns:** Use WooCommerce SelectWoo/Select2 for enhanced dropdowns.
- **Color Scheme:** Follow WordPress admin color scheme automatically.
- **Buttons:** Use WordPress native button classes (button, button-primary, button-secondary).
- **Notices:** Use WordPress native notice classes (notice, notice-success, notice-error, notice-warning, notice-info).
- **Tables:** Use WP_List_Table or native WordPress table classes.

### Content Guidelines

- **Humanized Copy:** All text must be natural and human-written, no AI-sounding phrases.
- **Readme File:** SEO-friendly, no competitor mentions, clear feature descriptions.
- **No Emojis:** Avoid emojis in all plugin text and documentation.
- **Complete Sentences:** All text must be complete sentences ending with proper punctuation.

### Security Requirements

- **Input:** Sanitize all user input immediately upon receipt.
- **Output:** Escape all output at the point of rendering.
- **Nonces:** Use nonce verification for all form submissions and AJAX requests.
- **Capabilities:** Check user capabilities before any sensitive operation.
- **Direct Access:** Prevent direct file access with ABSPATH check.
- **Prepared Statements:** Use $wpdb->prepare() for all database queries with user input.

### Session Management

- **Part by Part:** Complete development in phases to reduce mistakes.
- **Task Tracking:** Update tasks.md after completing each task.
- **New Sessions:** Create new chat session after each phase completion.
- **Summaries:** Create detailed summary at the end of each session.

---

## Technical Requirements

### Minimum Requirements

| Requirement | Version |
|-------------|---------|
| WordPress | 6.4 or higher |
| WooCommerce | 8.0 or higher |
| PHP | 8.0 or higher |
| MySQL | 5.7 or higher, or MariaDB 10.3 or higher |

### Recommended Requirements

| Requirement | Version |
|-------------|---------|
| WordPress | 6.7 or higher |
| WooCommerce | 9.0 or higher |
| PHP | 8.2 or higher |
| MySQL | 8.0 or higher |

### Compatibility Requirements

| Compatibility | Requirement |
|---------------|-------------|
| HPOS (High-Performance Order Storage) | Required for order operations. |
| WooCommerce Blocks | Required for compatibility. |
| PHP 8.3 | Required for full functionality. |
| WordPress Multisite | Recommended for agency use. |
| WooCommerce Subscriptions 6.0 or higher | Required for subscriptions module. |
| WooCommerce Memberships 1.25 or higher | Required for memberships module. |
| WooCommerce Bookings 2.0 or higher | Required for bookings module. |
| WooCommerce Appointments 5.0 or higher | Required for appointments module. |

### Architecture Requirements

```
jharudar-for-woocommerce/
├── jharudar.php                   # Main plugin file
├── uninstall.php                  # Clean uninstall
├── readme.txt                     # WordPress.org readme
├── assets/
│   ├── css/
│   │   └── admin.css              # Admin styles
│   ├── js/
│   │   └── admin.js               # Admin scripts
│   └── images/
│       └── icon.svg               # Plugin icon
├── includes/
│   ├── class-jharudar.php         # Main plugin class
│   ├── class-loader.php           # Autoloader
│   ├── class-activator.php        # Activation hooks
│   ├── class-deactivator.php      # Deactivation hooks
│   ├── admin/
│   │   ├── class-admin.php        # Admin interface
│   │   ├── class-dashboard.php    # Dashboard with health overview
│   │   ├── class-settings.php     # Settings page
│   │   └── views/                 # Admin view templates
│   ├── modules/
│   │   ├── core/
│   │   │   ├── class-products.php     # Products module
│   │   │   ├── class-orders.php       # Orders module
│   │   │   ├── class-customers.php    # Customers module
│   │   │   ├── class-coupons.php      # Coupons module
│   │   │   ├── class-taxonomy.php     # Taxonomy module
│   │   │   ├── class-tax-rates.php    # Tax rates module
│   │   │   └── class-shipping.php     # Shipping configuration module
│   │   ├── extensions/
│   │   │   ├── class-subscriptions.php # Subscriptions module
│   │   │   ├── class-memberships.php   # Memberships module
│   │   │   ├── class-bookings.php      # Bookings module
│   │   │   ├── class-appointments.php  # Appointments module
│   │   │   └── class-vendors.php       # Product Vendors module
│   │   ├── store/
│   │   │   ├── class-webhooks.php     # Webhooks module
│   │   │   ├── class-api-keys.php     # API keys module
│   │   │   ├── class-payment-tokens.php # Payment tokens module
│   │   │   ├── class-downloads.php    # Downloads module
│   │   │   ├── class-admin-inbox.php  # Admin inbox module
│   │   │   └── class-reserved-stock.php # Reserved stock module
│   │   ├── database/
│   │   │   ├── class-database.php     # Database optimization
│   │   │   ├── class-transients.php   # Transients cleanup
│   │   │   ├── class-sessions.php     # Sessions cleanup
│   │   │   ├── class-orphaned-data.php # Orphaned data cleanup
│   │   │   ├── class-action-scheduler.php # Action Scheduler cleanup
│   │   │   └── class-table-analyzer.php # Table analysis
│   │   └── wordpress/
│   │       ├── class-revisions.php    # Post revisions
│   │       ├── class-drafts.php       # Auto-drafts
│   │       ├── class-trash.php        # Trashed content
│   │       └── class-comments.php     # Comments cleanup
│   ├── background/
│   │   ├── class-background-process.php  # Base processor
│   │   └── class-cleanup-process.php     # Cleanup processor
│   ├── export/
│   │   ├── class-exporter.php     # Export handler
│   │   └── class-csv-exporter.php # CSV export
│   ├── gdpr/
│   │   ├── class-gdpr.php         # GDPR handler
│   │   ├── class-anonymizer.php   # Data anonymizer
│   │   └── class-retention.php    # Retention policies
│   ├── automation/
│   │   ├── class-scheduler.php    # Scheduled cleanup
│   │   ├── class-retention-rules.php # Retention rules
│   │   └── class-notifications.php # Email notifications
│   ├── tools/
│   │   ├── class-sample-generator.php # Sample data generator
│   │   ├── class-rules-import.php # Import cleanup rules
│   │   └── class-rules-export.php # Export cleanup rules
│   ├── cli/
│   │   └── class-cli-commands.php # WP-CLI commands
│   ├── api/
│   │   └── class-rest-api.php     # REST API endpoints
│   └── logging/
│       └── class-activity-log.php # Activity logging
├── languages/
│   └── jharudar-for-woocommerce.pot
└── vendor/                        # Composer dependencies
```

### Security Requirements

**Input Validation:**

- Sanitize all user inputs using WordPress sanitization functions.
- Validate data types and ranges before processing.
- Use prepared statements for all SQL queries.

**Capability Checks:**

- Require manage_woocommerce capability for all operations.
- Additional checks for sensitive operations like bulk deletion.
- Role-based access for different modules.

**Nonce Verification:**

- All AJAX requests must include and verify nonce.
- All form submissions must verify nonce.
- Use separate nonces for different operations.

**Data Protection:**

- No direct database manipulation without safeguards.
- Use transactional operations where possible.
- Show backup prompts before destructive actions.

### Performance Requirements

**Background Processing:**

- Use Action Scheduler for large operations.
- Process in batches of 20 to 50 items.
- Support resume capability after timeout.

**Memory Management:**

- Process data in chunks to avoid memory exhaustion.
- Clear object cache between batches.
- Monitor memory usage during operations.

**Database Optimization:**

- Use proper indexes for queries.
- Batch DELETE operations to avoid table locks.
- Use LIMIT clauses for large queries.

---

## UI/UX Guidelines

### Design Principles

**Native WordPress Feel:**

- Use WordPress admin components and patterns.
- Follow WordPress admin color scheme.
- Maintain consistency with WooCommerce admin interface.
- Use WordPress core JavaScript libraries where applicable.

**Safety First:**

- Display clear warnings before destructive actions.
- Implement multiple confirmation steps for bulk operations.
- Always show preview before executing deletion.
- Require explicit confirmation for permanent deletion.

**Progressive Disclosure:**

- Show basic options first to avoid overwhelming users.
- Place advanced options in collapsible sections.
- Provide help text readily available for all options.

**Responsive Design:**

- Ensure functionality works on all screen sizes.
- Maintain mobile-friendly admin interface.

### Admin Interface Structure

```
WooCommerce > Jharudar
├── Dashboard
│   ├── Database Health Overview
│   ├── Quick Stats (counts by data type)
│   ├── Cleanup Recommendations
│   ├── Recent Activity
│   └── Quick Actions
├── Products
│   ├── All Products
│   ├── By Category
│   ├── By Status
│   ├── By Stock Status
│   ├── Orphaned Images
│   └── Duplicates
├── Orders
│   ├── By Status
│   ├── By Date Range
│   ├── By Payment Method
│   └── Anonymization
├── Customers
│   ├── Inactive Customers
│   ├── Zero Order Customers
│   ├── Guest Customers
│   └── GDPR Requests
├── Coupons
│   ├── Expired
│   ├── Unused
│   └── By Date
├── Taxonomy
│   ├── Empty Categories
│   ├── Unused Tags
│   └── Unused Attributes
├── Extensions
│   ├── Subscriptions (if active)
│   ├── Memberships (if active)
│   ├── Bookings (if active)
│   ├── Appointments (if active)
│   └── Product Vendors (if active)
├── Store Data
│   ├── Tax Rates
│   ├── Shipping Zones
│   ├── Webhooks
│   ├── API Keys
│   ├── Payment Tokens
│   ├── Downloads
│   ├── Admin Inbox
│   └── Reserved Stock
├── Database
│   ├── Transients
│   ├── Sessions
│   ├── Orphaned Data
│   ├── Action Scheduler
│   ├── Table Analysis
│   └── Optimize Tables
├── WordPress
│   ├── Revisions
│   ├── Auto-Drafts
│   ├── Trashed Content
│   └── Comments
├── GDPR Tools
│   ├── Data Export
│   ├── Data Erasure
│   ├── Anonymization
│   └── Retention Policies
├── Automation
│   ├── Scheduled Tasks
│   ├── Retention Rules
│   └── Email Notifications
├── Activity Log
├── Tools
│   ├── Sample Data Generator
│   ├── Import Rules
│   └── Export Rules
└── Settings
```

### UI Components

**Progress Indicator:**

The progress indicator displays during long-running operations showing:

- Current operation name.
- Visual progress bar with percentage.
- Items processed out of total items.
- Time elapsed.
- Estimated time remaining.
- Stop and Pause buttons.

**Confirmation Dialog:**

Before any destructive action, display a confirmation dialog showing:

- Warning heading.
- Summary of items to be deleted.
- Checkbox for acknowledging backup completion.
- Checkbox for understanding the action cannot be undone.
- Text input requiring user to type DELETE to confirm.
- Cancel and Delete Permanently buttons.

**Preview Panel:**

Before deletion, show a preview panel containing:

- Title describing what will be deleted.
- Table with relevant columns for the data type.
- Row count with indication of additional items.
- Export to CSV button.
- Select All checkbox.
- Proceed to Delete button.

### Color Coding

The plugin will use WordPress admin native colors for consistency:

- Success states use WordPress success green.
- Warning states use WordPress warning orange.
- Danger states use WordPress error red.
- Info states use WordPress info blue.
- Neutral states use WordPress grey.

---

## Integration Requirements

### WooCommerce Subscriptions Integration

```php
// Check if Subscriptions is active.
if ( class_exists( 'WC_Subscriptions' ) ) {
    // Register Subscriptions module.
    $this->register_module( new Jharudar_Subscriptions() );
}

// Use Subscriptions API.
$subscriptions = wcs_get_subscriptions( array(
    'subscription_status' => array( 'cancelled', 'expired' ),
    'subscriptions_per_page' => -1,
) );
```

### WooCommerce Memberships Integration

```php
// Check if Memberships is active.
if ( function_exists( 'wc_memberships' ) ) {
    // Register Memberships module.
    $this->register_module( new Jharudar_Memberships() );
}

// Query memberships.
$user_memberships = wc_memberships_get_user_memberships( $user_id, array(
    'status' => array( 'cancelled', 'expired' ),
) );
```

### WooCommerce Bookings Integration

```php
// Check if Bookings is active.
if ( class_exists( 'WC_Bookings' ) ) {
    // Register Bookings module.
    $this->register_module( new Jharudar_Bookings() );
}

// Query bookings.
$bookings = WC_Booking_Data_Store::get_booking_ids_by( array(
    'status' => array( 'cancelled', 'complete' ),
    'date_before' => strtotime( '-1 year' ),
) );
```

### HPOS Compatibility

```php
// Declare HPOS compatibility.
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 
            'custom_order_tables', 
            __FILE__, 
            true 
        );
    }
} );

// Use HPOS-compatible queries.
$orders = wc_get_orders( array(
    'status' => 'completed',
    'date_before' => '2024-01-01',
    'limit' => -1,
) );
```

### Action Scheduler Integration

```php
// Schedule background task.
as_schedule_single_action( 
    time(), 
    'jharudar_process_batch', 
    array(
        'type' => 'orders',
        'batch' => 1,
        'args' => array( 'status' => 'completed' ),
    ),
    'jharudar'
);

// Process batch.
add_action( 'jharudar_process_batch', function( $type, $batch, $args ) {
    $processor = new Jharudar_Background_Process();
    $processor->process_batch( $type, $batch, $args );
}, 10, 3 );
```

---

## Release Strategy

### Full Feature Launch (v0.0.1)

The plugin will launch with all planned features from day one. No phased releases or feature gating. Users get the complete, powerful tool immediately.

**Target Development Time:** 30 days.

**Core Modules (All Included):**

- Products module with category, status, stock, date, type filtering, orphaned images, and duplicate detection.
- Orders module with status, date range, payment method filtering, and anonymization.
- Customers module with inactive, zero-order, guest customer cleanup and GDPR tools.
- Coupons module with expired, unused, and date-based cleanup.
- Taxonomy module with empty categories, unused tags, and attribute cleanup.
- Tax Rates module for tax configuration cleanup.
- Shipping Configuration module for zones, methods, and classes.

**Extension Modules (All Included):**

- WooCommerce Subscriptions support for cancelled and expired subscriptions.
- WooCommerce Memberships support for membership cleanup by status and plan.
- WooCommerce Bookings support for past and cancelled bookings.
- WooCommerce Appointments support for appointment cleanup.
- WooCommerce Product Vendors support for vendor data cleanup.

**Store Data Modules (All Included):**

- Webhooks and API module for failed webhooks, API keys, and rate limits.
- Payment Data module for expired and orphaned payment tokens.
- Downloads module for expired permissions and download logs.
- Admin Inbox module for notification cleanup.
- Reserved Stock module for abandoned checkout stock reservations.

**Database Modules (All Included):**

- Transients cleanup including WooCommerce-specific transients.
- Sessions cleanup for expired customer sessions.
- Orphaned data cleanup for postmeta, usermeta, termmeta, commentmeta, and relationships.
- Duplicate meta detection and cleanup.
- Action Scheduler cleanup for completed, failed, and cancelled actions.
- Table optimization and repair tools.
- Database size analytics and table analysis.
- oEmbed cache cleanup.

**WordPress Cleanup Module (All Included):**

- Post revisions with configurable retention.
- Auto-drafts cleanup.
- Trashed content cleanup.
- Spam, pending, and trashed comments.
- Pingbacks and trackbacks.

**GDPR Compliance Module (All Included):**

- Customer data export.
- Customer data erasure.
- Order anonymization.
- Consent cleanup.
- Audit logging.
- Retention policies.

**Advanced Features (All Included):**

- Background processing via Action Scheduler.
- Real-time progress tracking.
- Export before delete for all data types.
- Preview and dry run mode.
- Scheduled cleanup with configurable intervals.
- Retention policies with keep last X days/items.
- Complete WP-CLI command suite.
- REST API endpoints.
- Activity dashboard with health overview.
- Cleanup history and activity logging.
- Email notifications for scheduled tasks.
- Sample data generator for development.
- Import and export cleanup rules.
- Backup reminder prompts.

**Admin Interface (All Included):**

- Modern, native WordPress and WooCommerce styling.
- Dashboard with database health overview.
- Organized menu structure with all modules accessible.
- Responsive design for all screen sizes.
- Multiple confirmation steps for safety.

### Development Milestones

| Week | Focus Area | Deliverables |
|------|------------|--------------|
| Week 1 | Foundation | Plugin architecture, autoloader, admin framework, background processing base. |
| Week 2 | Core Modules Part 1 | Products, Orders, Customers modules with full filtering and export. |
| Week 3 | Core Modules Part 2 | Coupons, Taxonomy, Tax Rates, Shipping modules. |
| Week 4 | Extension Modules | Subscriptions, Memberships, Bookings, Appointments, Vendors modules. |
| Week 5 | Store and Database | Webhooks, API Keys, Payment Tokens, Downloads, Admin Inbox, Reserved Stock, Database optimization, Action Scheduler cleanup. |
| Week 6 | WordPress and GDPR | WordPress content cleanup, GDPR tools, Anonymization, Retention policies. |
| Week 7 | Advanced Features | Scheduled cleanup, WP-CLI commands, REST API, Activity logging, Sample data generator. |
| Week 8 | Polish and Testing | UI refinement, comprehensive testing, documentation, security audit, translation preparation. |

### WordPress.org Submission

After v0.0.1 development is complete:

- Prepare readme.txt following WordPress.org guidelines.
- Create screenshots showing all key features.
- Create plugin icon (256x256) and banner (772x250 and 1544x500).
- Complete security audit using Plugin Check.
- Ensure all coding standards compliance.
- Submit for review.

### Future Considerations (Post v0.0.1)

- WooCommerce.com Marketplace submission if premium features are added.
- Multisite network-wide operations enhancement.
- Additional extension support based on user requests.
- Performance optimizations based on real-world usage data.

---

## Success Metrics

### Technical Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Code Coverage | 80% or higher | PHPUnit tests |
| PHPCS Score | 0 errors | WordPress Coding Standards |
| Page Load Impact | Less than 50ms | Query Monitor |
| Memory Usage | Less than 64MB | PHP memory_get_usage |
| Background Job Success | 99% or higher | Action Scheduler logs |

### User Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Plugin Rating | 4.8 or higher stars | WordPress.org |
| Active Installs in Year 1 | 10,000 or more | WordPress.org stats |
| Support Response Time | Less than 24 hours | Support tickets |
| Support Resolution | 90% or higher | Resolved tickets |
| User Satisfaction | 90% or higher | User surveys |

---

## Appendix A: Competitor Feature Matrix

| Feature | Jharudar | Store Toolkit | Adv DB Cleaner | Complete Cleaner | Customers Cleanup |
|---------|----------|---------------|----------------|------------------|-------------------|
| Products | Planned | Yes | No | Yes | No |
| Variations | Planned | Yes | No | Yes | No |
| Product Images | Planned | Yes | No | Yes | No |
| Orphaned Images | Planned | No | No | Yes | No |
| Orders | Planned | Yes | No | Yes | No |
| Order by Status | Planned | Yes | No | No | No |
| Order by Date | Planned | Yes | No | No | No |
| Customers | Planned | No | No | Yes | Yes |
| Inactive Customers | Planned | No | No | No | Yes |
| Coupons | Planned | Yes | No | Yes | No |
| Tax Rates | Planned | Yes | No | No | No |
| Shipping Zones | Planned | No | No | No | No |
| Shipping Classes | Planned | Yes | No | No | No |
| Subscriptions | Planned | Yes | No | No | No |
| Memberships | Planned | Yes | No | No | No |
| Bookings | Planned | Yes | No | No | No |
| Appointments | Planned | No | No | No | No |
| Product Vendors | Planned | No | No | No | No |
| Categories | Planned | Yes | No | Yes | No |
| Tags | Planned | Yes | No | Yes | No |
| Attributes | Planned | Yes | No | No | No |
| Webhooks | Planned | No | No | No | No |
| API Keys | Planned | No | No | No | No |
| Payment Tokens | Planned | No | No | No | No |
| Download Permissions | Planned | Yes | No | No | No |
| Download Logs | Planned | No | No | No | No |
| Admin Inbox | Planned | No | No | No | No |
| Reserved Stock | Planned | No | No | No | No |
| Transients | Planned | No | Yes | No | No |
| Sessions | Planned | No | No | No | No |
| Orphaned Postmeta | Planned | No | Yes | No | No |
| Orphaned Usermeta | Planned | No | Yes | No | No |
| Orphaned Termmeta | Planned | No | Yes | No | No |
| Duplicate Meta | Planned | No | Yes | No | No |
| Post Revisions | Planned | No | Yes | No | No |
| Auto-Drafts | Planned | No | Yes | No | No |
| Trashed Content | Planned | No | Yes | No | No |
| Spam Comments | Planned | No | Yes | No | No |
| oEmbed Caches | Planned | No | Yes | No | No |
| Action Scheduler | Planned | No | Premium | No | No |
| Table Optimization | Planned | No | Yes | No | No |
| Table Repair | Planned | No | Yes | No | No |
| Database Analytics | Planned | No | Premium | No | No |
| Background Process | Planned | No | Yes | Yes | Yes |
| Progress Tracking | Planned | No | Yes | Yes | No |
| Export Before Delete | Planned | No | No | No | No |
| Preview/Dry Run | Planned | No | Yes | No | Yes |
| GDPR Tools | Planned | No | No | No | No |
| Retention Policies | Planned | No | Yes | No | No |
| Scheduled Cleanup | Planned | Yes | Yes | No | No |
| WP-CLI | Planned | Yes | No | No | No |
| REST API | Planned | No | No | No | No |
| HPOS Support | Planned | Yes | No | Yes | Yes |
| Activity Log | Planned | No | No | No | No |
| Sample Data Gen | Planned | Yes | No | No | No |
| Multisite Support | Planned | No | Premium | No | No |
| Price | Free | Free | Free/Premium | Free | $29/year |

---

## Appendix B: Database Tables Affected

### WordPress Core Tables

| Table | Operations | Notes |
|-------|------------|-------|
| wp_posts | DELETE | Products, orders, coupons, revisions, auto-drafts. |
| wp_postmeta | DELETE | Associated metadata, orphaned meta cleanup. |
| wp_comments | DELETE | Order notes, product reviews, spam, pingbacks, trackbacks. |
| wp_commentmeta | DELETE | Note metadata, orphaned meta cleanup. |
| wp_terms | DELETE | Categories, tags, attributes. |
| wp_termmeta | DELETE | Term metadata, orphaned meta cleanup. |
| wp_term_taxonomy | DELETE | Term taxonomy relationships. |
| wp_term_relationships | DELETE | Post-term relationships, orphaned cleanup. |
| wp_users | DELETE | Customer accounts. |
| wp_usermeta | DELETE | User metadata, orphaned meta cleanup. |
| wp_options | DELETE | Transients, oEmbed caches, plugin settings. |

### HPOS Tables

| Table | Operations | Notes |
|-------|------------|-------|
| wp_wc_orders | DELETE | Orders using HPOS. |
| wp_wc_orders_meta | DELETE | Order metadata. |
| wp_wc_order_addresses | DELETE | Billing and shipping addresses. |
| wp_wc_order_operational_data | DELETE | Operational data. |

### WooCommerce Custom Tables

| Table | Operations | Notes |
|-------|------------|-------|
| wp_woocommerce_order_items | DELETE | Order line items. |
| wp_woocommerce_order_itemmeta | DELETE | Line item metadata. |
| wp_woocommerce_sessions | DELETE | Customer sessions. |
| wp_woocommerce_api_keys | DELETE | REST API keys. |
| wp_woocommerce_downloadable_product_permissions | DELETE | Download permissions. |
| wp_woocommerce_attribute_taxonomies | DELETE | Product attributes. |
| wp_woocommerce_tax_rates | DELETE | Tax rate configurations. |
| wp_woocommerce_tax_rate_locations | DELETE | Tax rate locations. |
| wp_woocommerce_shipping_zones | DELETE | Shipping zones. |
| wp_woocommerce_shipping_zone_locations | DELETE | Zone locations. |
| wp_woocommerce_shipping_zone_methods | DELETE | Shipping methods. |
| wp_woocommerce_payment_tokens | DELETE | Saved payment methods. |
| wp_woocommerce_payment_tokenmeta | DELETE | Token metadata. |
| wp_woocommerce_log | DELETE | WooCommerce logs. |
| wp_wc_webhooks | DELETE | Webhook configurations. |
| wp_wc_download_log | DELETE | Download activity logs. |
| wp_wc_product_meta_lookup | SYNC | Product lookup cache. |
| wp_wc_customer_lookup | SYNC | Customer lookup cache. |
| wp_wc_order_stats | SYNC | Order analytics. |
| wp_wc_order_product_lookup | SYNC | Order product relationships. |
| wp_wc_order_tax_lookup | SYNC | Order tax relationships. |
| wp_wc_order_coupon_lookup | SYNC | Order coupon relationships. |
| wp_wc_category_lookup | SYNC | Category hierarchy cache. |
| wp_wc_reserved_stock | DELETE | Reserved stock from checkouts. |
| wp_wc_rate_limits | DELETE | API rate limiting data. |
| wp_wc_admin_notes | DELETE | Admin inbox notifications. |
| wp_wc_admin_note_actions | DELETE | Note action buttons. |

### Action Scheduler Tables

| Table | Operations | Notes |
|-------|------------|-------|
| wp_actionscheduler_actions | DELETE | Scheduled and completed actions. |
| wp_actionscheduler_claims | DELETE | Action claims. |
| wp_actionscheduler_groups | DELETE | Action groups. |
| wp_actionscheduler_logs | DELETE | Action execution logs. |

### Extension Tables

| Table | Operations | Notes |
|-------|------------|-------|
| wp_wc_bookings_availability | DELETE | Booking availability rules (Bookings). |
| wp_wcs_* | DELETE | Subscription related tables (Subscriptions). |
| Various vendor tables | DELETE | Vendor commission and data (Product Vendors). |

---

## Appendix C: WP-CLI Commands Reference

```bash
# Products
wp jharudar products delete --all
wp jharudar products delete --category=uncategorized
wp jharudar products delete --status=draft
wp jharudar products delete --older-than="6 months"
wp jharudar products delete --stock-status=outofstock
wp jharudar products images --orphaned --delete
wp jharudar products duplicates --list
wp jharudar products duplicates --delete

# Orders
wp jharudar orders delete --all
wp jharudar orders delete --status=completed --older-than="1 year"
wp jharudar orders delete --status=failed,cancelled --older-than="30 days"
wp jharudar orders anonymize --older-than="2 years"
wp jharudar orders export --status=completed --format=csv

# Customers
wp jharudar customers delete --no-orders
wp jharudar customers delete --inactive --older-than="1 year"
wp jharudar customers delete --role=customer --no-orders
wp jharudar customers anonymize --older-than="3 years"
wp jharudar customers export --all --format=csv

# Coupons
wp jharudar coupons delete --expired
wp jharudar coupons delete --unused
wp jharudar coupons delete --older-than="1 year"

# Taxonomy
wp jharudar taxonomy delete-empty-categories
wp jharudar taxonomy delete-unused-tags
wp jharudar taxonomy delete-unused-attributes

# Subscriptions (if active)
wp jharudar subscriptions delete --status=cancelled,expired
wp jharudar subscriptions delete --older-than="2 years"

# Memberships (if active)
wp jharudar memberships delete --status=cancelled,expired
wp jharudar memberships delete --plan=basic --status=expired

# Bookings (if active)
wp jharudar bookings delete --status=cancelled
wp jharudar bookings delete --past --older-than="1 year"

# Store Data
wp jharudar webhooks delete --failed
wp jharudar webhooks delete --disabled
wp jharudar api-keys delete --unused
wp jharudar downloads delete-expired
wp jharudar downloads clean-logs
wp jharudar tax-rates delete --all
wp jharudar shipping delete-zones
wp jharudar admin-inbox clear --read
wp jharudar reserved-stock clear

# Action Scheduler
wp jharudar scheduler delete --status=complete --older-than="30 days"
wp jharudar scheduler delete --status=failed
wp jharudar scheduler delete --status=cancelled
wp jharudar scheduler clean-logs --older-than="7 days"

# Database
wp jharudar db status
wp jharudar db optimize
wp jharudar db repair
wp jharudar db clean-transients
wp jharudar db clean-sessions
wp jharudar db clean-orphaned-meta --type=post
wp jharudar db clean-orphaned-meta --type=user
wp jharudar db clean-orphaned-meta --type=term
wp jharudar db clean-orphaned-meta --type=comment
wp jharudar db clean-duplicates
wp jharudar db clean-oembed
wp jharudar db analyze --table=wp_postmeta

# WordPress Content
wp jharudar wordpress delete-revisions
wp jharudar wordpress delete-revisions --keep=5
wp jharudar wordpress delete-auto-drafts
wp jharudar wordpress delete-trash
wp jharudar wordpress delete-spam-comments
wp jharudar wordpress delete-pingbacks

# GDPR
wp jharudar gdpr export --user=123 --format=json
wp jharudar gdpr erase --user=123
wp jharudar gdpr anonymize --user=123

# Automation
wp jharudar schedule list
wp jharudar schedule add --task=clean-transients --interval=daily
wp jharudar schedule remove --task=clean-transients

# Tools
wp jharudar generate --type=orders --count=100
wp jharudar generate --type=products --count=50
wp jharudar generate --type=customers --count=25

# General
wp jharudar status
wp jharudar log --tail=100
wp jharudar log --export --format=csv
wp jharudar dry-run orders delete --status=completed --older-than="1 year"
```

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-02-05 | Shameem Reza | Initial PRD. |
| 1.1 | 2026-02-05 | Shameem Reza | Renamed to Jharudar for WooCommerce. |
| 1.2 | 2026-02-05 | Shameem Reza | Humanized content, removed emojis, updated branding guidelines to use native WordPress and WooCommerce styling, removed old plugin references. |
| 1.3 | 2026-02-05 | Shameem Reza | Comprehensive feature expansion based on WooCommerce data structure analysis and competitor research. Added modules for Tax Rates, Shipping Configuration, Webhooks and API, Payment Data, Downloads, Admin Inbox, Reserved Stock, Action Scheduler, and WordPress General Cleanup. Added Advanced Database Cleaner to competitor analysis. Expanded database tables reference and WP-CLI commands. |
| 1.4 | 2026-02-05 | Shameem Reza | Updated release strategy to full feature launch in v0.0.1. Removed phased release approach. All features included from day one with 8-week development timeline. |
| 1.5 | 2026-02-05 | Shameem Reza | Added Development Guidelines section covering code quality, styling, content, security, and session management standards. |

---

This PRD is a living document and will be updated as the project progresses.
