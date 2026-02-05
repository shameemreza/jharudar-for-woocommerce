=== Jharudar for WooCommerce ===
Contributors: shameemreza
Donate link: https://ko-fi.com/shameemreza
Tags: woocommerce, cleanup, customers, products, orders
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 0.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The complete store cleanup toolkit for WooCommerce. Safely clean products, orders, customers, and optimize your database with GDPR compliance.

== Description ==

Jharudar (Bengali: ঝাড়ুদার, Hindi: झाड़ूदार) means sweeper or cleaner - someone who keeps spaces clean and organized. Just as a Jharudar maintains cleanliness in physical spaces, this plugin helps you maintain a tidy WooCommerce store by removing unwanted data safely and efficiently.

Managing a WooCommerce store means dealing with accumulating data over time. Test orders pile up, inactive customers clutter your database, expired coupons linger, and orphaned metadata slowly bloats your database. Jharudar gives you the tools to clean all of this, safely and efficiently.

= Why Jharudar? =

Most cleanup plugins focus on just one area - products only, or orders only, or customers only. Jharudar takes a different approach. It provides a single, unified solution for cleaning all types of WooCommerce data, including support for popular extensions that other plugins ignore.

= Core Cleanup Modules =

**Products Module**

Clean products by category, status, stock level, or date. Remove orphaned product images that waste server space. Detect and handle duplicate products. Always preview what will be deleted before confirming.

**Orders Module**

Remove orders by status, date range, or payment method. Anonymize customer data in old orders for GDPR compliance. Delete order notes and refunds selectively. Export order data before deletion.

**Customers Module**

Find and remove inactive customers who have not ordered in months. Delete zero-order accounts created from abandoned checkouts. Anonymize customer data for privacy compliance. Merge duplicate customer accounts.

**Coupons Module**

Remove expired coupons automatically. Clean up unused coupons that were never redeemed. Delete coupons by creation date or usage limits.

**Taxonomy Module**

Delete empty product categories that serve no purpose. Remove unused product tags. Clean up attributes no longer assigned to products.

= Extension Support =

Jharudar works with the WooCommerce extensions you rely on.

**WooCommerce Subscriptions**

Clean cancelled and expired subscriptions. Delete subscriptions by status or date. Remove related renewal orders when cleaning subscriptions.

**WooCommerce Memberships**

Remove cancelled or expired memberships. Filter by membership plan. Export membership data before deletion.

**WooCommerce Bookings**

Delete past or cancelled bookings. Clean bookings by date range or status. Remove associated booking resources.

**WooCommerce Appointments**

Clean past appointments. Filter by staff member or status.

**WooCommerce Product Vendors**

Remove vendor accounts and commission records. Clean vendor data by status.

= Database Optimization =

Beyond WooCommerce data, Jharudar helps you maintain a healthy WordPress database.

**Transient Cleanup**

Remove expired transients that accumulate in wp_options. Clean WooCommerce-specific transients separately.

**Orphaned Data**

Detect and remove postmeta without valid posts. Clean orphaned usermeta, termmeta, and commentmeta. Remove broken term relationships.

**Action Scheduler**

Clean completed, failed, or cancelled scheduled actions. Remove old Action Scheduler logs that can grow large over time.

**Table Maintenance**

Optimize database tables to reclaim space. View table sizes and row counts. Identify large options slowing down your site.

= Store Data Cleanup =

**Webhooks and API**

Remove failed webhook deliveries. Delete disabled webhooks. Clean unused API keys.

**Payment Tokens**

Delete expired saved payment methods. Remove tokens for deleted customers.

**Downloads**

Clean expired download permissions. Remove download logs.

**Admin Inbox**

Clear read notifications. Remove actioned notes.

= Safety First =

Jharudar is built with data safety as the top priority.

**Preview Before Delete**

Always see exactly what will be deleted before confirming. Review items in a table format with all relevant details.

**Export Before Delete**

Download a CSV or JSON backup of any data before removing it. Keep records for compliance or recovery.

**Multiple Confirmations**

Destructive actions require explicit confirmation. Type DELETE to confirm bulk operations.

**Activity Logging**

Every cleanup action is logged with timestamp, user, and details. Review what was deleted and when.

**Dry Run Mode**

Test cleanup operations without actually deleting anything. See what would be removed before committing.

= GDPR Compliance =

Meet your privacy obligations with built-in GDPR tools.

**Data Export**

Export all customer data in a portable format for subject access requests.

**Data Erasure**

Handle erasure requests by removing or anonymizing personal data.

**Order Anonymization**

Anonymize personal data in orders while preserving order records for accounting.

**Retention Policies**

Set automatic cleanup rules based on data age. Keep data only as long as needed.

= Background Processing =

Large stores need cleanup tools that do not timeout. Jharudar uses Action Scheduler to process large datasets in the background. Clean thousands of orders or products without worrying about server limits.

Real-time progress tracking shows you exactly what is happening. Pause or cancel operations at any time.

= WP-CLI Support =

Automate cleanup tasks from the command line. Schedule cleanup scripts in cron jobs. Integrate with deployment workflows.

= REST API =

Access cleanup functionality programmatically. Build custom integrations or automation tools.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/jharudar-for-woocommerce` or install directly from the WordPress plugin directory.
2. Activate the plugin through the Plugins screen in WordPress.
3. Navigate to WooCommerce > Jharudar to access the cleanup dashboard.
4. Review the database health overview and cleanup recommendations.
5. Configure settings according to your needs before running cleanup operations.

= Requirements =

* WordPress 6.4 or higher
* WooCommerce 8.0 or higher
* PHP 8.0 or higher

= Recommended =

* WordPress 6.7 or higher
* WooCommerce 9.0 or higher
* PHP 8.2 or higher

== Frequently Asked Questions ==

= Is it safe to delete data with this plugin? =

Jharudar includes multiple safety features. You always see a preview of what will be deleted. You can export data before deletion. Bulk operations require typing DELETE to confirm. All actions are logged for audit purposes. That said, deleted data cannot be recovered from the plugin itself, so we recommend maintaining regular database backups.

= Will this slow down my store? =

No. Jharudar uses background processing for large operations. Cleanup tasks run in small batches using Action Scheduler, so your store remains responsive for customers. The plugin adds minimal overhead during normal operation.

= Does it work with WooCommerce HPOS? =

Yes. Jharudar is fully compatible with High-Performance Order Storage. It uses WooCommerce APIs that work with both traditional post-based storage and HPOS.

= Can I undo a cleanup operation? =

Cleanup operations are permanent. This is by design - the goal is to actually remove unwanted data and free up space. Use the export feature to create backups before deletion if you need the ability to restore data.

= How do I clean up test data from my staging site? =

Navigate to the relevant module (Products, Orders, etc.) and use the filters to select test data. Preview the selection, then proceed with deletion. For complete resets, the Dashboard provides quick actions to clean all data of each type.

= Does this plugin work on WordPress Multisite? =

Basic functionality works on Multisite installations. Each site manages its own cleanup operations independently.

= How do I report issues or suggest features? =

Visit the plugin support forum on WordPress.org. Include details about your WordPress version, WooCommerce version, and any error messages when reporting issues.

== Screenshots ==

1. Dashboard showing database health overview and quick actions.
2. Products cleanup interface with category and status filters.
3. Orders cleanup with date range and status filtering.
4. Database optimization tools showing transient and orphaned data counts.
5. Settings page for configuring batch size and safety options.
6. Activity log showing cleanup history.

== Changelog ==

= 0.0.1 =
* Initial release.
* Products cleanup module with category, status, stock, and date filtering.
* Orphaned product image detection and cleanup.
* Orders cleanup module with status, date, and payment method filtering.
* Order anonymization for GDPR compliance.
* Customers cleanup module for inactive and zero-order accounts.
* Coupons cleanup for expired and unused coupons.
* Taxonomy cleanup for empty categories, unused tags, and attributes.
* Tax rates cleanup module.
* Shipping configuration cleanup module.
* WooCommerce Subscriptions support.
* WooCommerce Memberships support.
* WooCommerce Bookings support.
* WooCommerce Appointments support.
* WooCommerce Product Vendors support.
* Webhooks and API keys cleanup.
* Payment tokens cleanup.
* Download permissions and logs cleanup.
* Admin inbox cleanup.
* Reserved stock cleanup.
* Database transient cleanup.
* Session cleanup.
* Orphaned metadata detection and cleanup.
* Action Scheduler cleanup.
* Post revision and auto-draft cleanup.
* Trashed content and spam comment cleanup.
* GDPR data export and erasure tools.
* Retention policy configuration.
* Background processing via Action Scheduler.
* Export before delete for all data types.
* Preview and dry run mode.
* Activity logging for audit trails.
* Email notifications for completed tasks.
* WP-CLI command support.
* REST API endpoints.
* Sample data generator for development.

== Upgrade Notice ==

= 0.0.1 =
Initial release of Jharudar for WooCommerce. A complete store cleanup toolkit with support for all major WooCommerce data types and popular extensions.
