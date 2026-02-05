# Jharudar for WooCommerce - Development Tasks

**Started:** February 5, 2026  
**Target Completion:** 8 weeks  
**Current Phase:** Week 1 - Foundation

---

## Development Guidelines

### Code Quality Standards

- **Prefix:** `jharudar_` for functions, `Jharudar_` for classes, `JHARUDAR_` for constants.
- **Coding Standards:** WordPress PHP Coding Standards (WPCS).
- **Static Analysis:** PHPStan Level 2.
- **Styling:** Native WordPress and WooCommerce admin styles only.
- **Dropdowns:** Use WooCommerce SelectWoo/Select2.
- **Security:** Sanitize input, escape output, verify nonces, check capabilities.
- **Internationalization:** All strings translatable with text domain `jharudar-for-woocommerce`.
- **Content:** Humanized copy, no AI-sounding text, SEO-friendly readme.

### Development Workflow

- Complete tasks part by part to reduce mistakes.
- Run PHPCS and PHPStan after each significant change.
- Update this tasks.md after completing each task.
- Create new chat session after each phase is complete.
- Summarize completed work at the end of each session.

### Session Management

After completing each phase or feature set:
1. Mark tasks as complete in this file.
2. Create a summary of what was built.
3. Note any issues or decisions made.
4. Start new chat for next phase.

---

## Phase 1: Foundation (Week 1)

### Plugin Structure

- [x] Create main plugin file `jharudar.php` with proper headers.
- [x] Create `uninstall.php` for clean removal.
- [x] Create folder structure as per PRD architecture.
- [x] Create autoloader class.
- [x] Create activator class.
- [x] Create deactivator class.
- [x] Create main plugin class `Jharudar`.

### Admin Framework

- [x] Create admin class with menu registration.
- [x] Create dashboard page with placeholder content.
- [x] Create settings page framework.
- [x] Enqueue admin styles using WordPress native classes.
- [x] Enqueue admin scripts with proper dependencies.
- [x] Create activity log view.

### Background Processing Base

- [x] Create base background process class.
- [x] Integrate with Action Scheduler.
- [x] Create cleanup process class.

### Core Utilities

- [x] Create helper functions file.
- [x] Create logger class for activity tracking.
- [x] Create exporter base class.

### Quality Checks

- [ ] Run PHPCS and fix all issues.
- [ ] Run PHPStan Level 2 and fix all issues.
- [ ] Test plugin activation and deactivation.
- [ ] Verify admin menu appears correctly.

---

## Phase 2: Core Modules Part 1 (Week 2)

### Products Module

- [ ] Create products module class.
- [ ] Implement product listing with filters.
- [ ] Implement delete by category.
- [ ] Implement delete by status.
- [ ] Implement delete by stock status.
- [ ] Implement delete by date.
- [ ] Implement orphaned images detection.
- [ ] Create products admin view.
- [ ] Add export before delete.

### Orders Module

- [ ] Create orders module class.
- [ ] Implement order listing with filters.
- [ ] Implement delete by status.
- [ ] Implement delete by date range.
- [ ] Implement delete by payment method.
- [ ] Implement order anonymization.
- [ ] Create orders admin view.
- [ ] Add export before delete.

### Customers Module

- [ ] Create customers module class.
- [ ] Implement customer listing with filters.
- [ ] Implement delete inactive customers.
- [ ] Implement delete zero-order customers.
- [ ] Implement customer anonymization.
- [ ] Create customers admin view.
- [ ] Add export before delete.

---

## Phase 3: Core Modules Part 2 (Week 3)

### Coupons Module

- [ ] Create coupons module class.
- [ ] Implement delete expired coupons.
- [ ] Implement delete unused coupons.
- [ ] Create coupons admin view.

### Taxonomy Module

- [ ] Create taxonomy module class.
- [ ] Implement delete empty categories.
- [ ] Implement delete unused tags.
- [ ] Implement delete unused attributes.
- [ ] Create taxonomy admin view.

### Tax Rates Module

- [ ] Create tax rates module class.
- [ ] Implement delete all tax rates.
- [ ] Implement delete by country.
- [ ] Create tax rates admin view.

### Shipping Module

- [ ] Create shipping module class.
- [ ] Implement delete shipping zones.
- [ ] Implement delete shipping classes.
- [ ] Create shipping admin view.

---

## Phase 4: Extension Modules (Week 4)

### Subscriptions Module

- [ ] Create subscriptions module class.
- [ ] Detect WooCommerce Subscriptions.
- [ ] Implement delete cancelled subscriptions.
- [ ] Implement delete expired subscriptions.
- [ ] Create subscriptions admin view.

### Memberships Module

- [ ] Create memberships module class.
- [ ] Detect WooCommerce Memberships.
- [ ] Implement delete cancelled memberships.
- [ ] Implement delete expired memberships.
- [ ] Create memberships admin view.

### Bookings Module

- [ ] Create bookings module class.
- [ ] Detect WooCommerce Bookings.
- [ ] Implement delete by status.
- [ ] Implement delete past bookings.
- [ ] Create bookings admin view.

### Appointments Module

- [ ] Create appointments module class.
- [ ] Detect WooCommerce Appointments.
- [ ] Implement delete by status.
- [ ] Implement delete past appointments.
- [ ] Create appointments admin view.

### Product Vendors Module

- [ ] Create vendors module class.
- [ ] Detect WooCommerce Product Vendors.
- [ ] Implement vendor data cleanup.
- [ ] Create vendors admin view.

---

## Phase 5: Store Data and Database (Week 5)

### Webhooks Module

- [ ] Create webhooks module class.
- [ ] Implement delete failed webhooks.
- [ ] Implement delete disabled webhooks.
- [ ] Create webhooks admin view.

### API Keys Module

- [ ] Create API keys module class.
- [ ] Implement delete unused API keys.
- [ ] Create API keys admin view.

### Payment Tokens Module

- [ ] Create payment tokens module class.
- [ ] Implement delete expired tokens.
- [ ] Create payment tokens admin view.

### Downloads Module

- [ ] Create downloads module class.
- [ ] Implement delete expired permissions.
- [ ] Implement delete download logs.
- [ ] Create downloads admin view.

### Admin Inbox Module

- [ ] Create admin inbox module class.
- [ ] Implement delete read notes.
- [ ] Implement delete actioned notes.
- [ ] Create admin inbox admin view.

### Reserved Stock Module

- [ ] Create reserved stock module class.
- [ ] Implement clear expired reservations.
- [ ] Create reserved stock admin view.

### Database Module

- [ ] Create database module class.
- [ ] Implement transients cleanup.
- [ ] Implement sessions cleanup.
- [ ] Implement orphaned data cleanup.
- [ ] Implement table optimization.
- [ ] Create database admin view.

### Action Scheduler Module

- [ ] Create action scheduler module class.
- [ ] Implement delete completed actions.
- [ ] Implement delete failed actions.
- [ ] Implement delete action logs.
- [ ] Create action scheduler admin view.

---

## Phase 6: WordPress and GDPR (Week 6)

### WordPress Cleanup Module

- [ ] Create WordPress cleanup module class.
- [ ] Implement delete post revisions.
- [ ] Implement delete auto-drafts.
- [ ] Implement delete trashed content.
- [ ] Implement delete spam comments.
- [ ] Create WordPress cleanup admin view.

### GDPR Module

- [ ] Create GDPR module class.
- [ ] Implement customer data export.
- [ ] Implement customer data erasure.
- [ ] Implement order anonymization.
- [ ] Implement retention policies.
- [ ] Create GDPR admin view.

### Activity Logging

- [ ] Create activity log class.
- [ ] Implement log all cleanup operations.
- [ ] Create activity log admin view.

---

## Phase 7: Advanced Features (Week 7)

### Scheduled Cleanup

- [ ] Create scheduler class.
- [ ] Implement scheduled task management.
- [ ] Create retention rules system.
- [ ] Create scheduler admin view.

### WP-CLI Commands

- [ ] Create CLI commands class.
- [ ] Implement products commands.
- [ ] Implement orders commands.
- [ ] Implement customers commands.
- [ ] Implement database commands.
- [ ] Implement general commands.

### REST API

- [ ] Create REST API class.
- [ ] Implement status endpoint.
- [ ] Implement cleanup endpoints.
- [ ] Add authentication and permissions.

### Sample Data Generator

- [ ] Create sample generator class.
- [ ] Implement generate orders.
- [ ] Implement generate products.
- [ ] Implement generate customers.
- [ ] Create generator admin view.

### Import/Export Rules

- [ ] Create rules import/export class.
- [ ] Implement export cleanup rules.
- [ ] Implement import cleanup rules.

---

## Phase 8: Polish and Testing (Week 8)

### Dashboard

- [ ] Build comprehensive dashboard.
- [ ] Add database health overview.
- [ ] Add cleanup recommendations.
- [ ] Add quick stats by data type.

### UI Refinement

- [ ] Review all admin views for consistency.
- [ ] Ensure responsive design.
- [ ] Add progress indicators.
- [ ] Add confirmation dialogs.
- [ ] Add dry run mode UI.

### Documentation

- [ ] Write comprehensive readme.txt.
- [ ] Create inline help text.
- [ ] Document all hooks and filters.

### Testing

- [ ] Test all modules individually.
- [ ] Test background processing.
- [ ] Test with large datasets.
- [ ] Test with all supported extensions.
- [ ] Security audit.

### Final Checks

- [ ] Run PHPCS final check.
- [ ] Run PHPStan Level 2 final check.
- [ ] Run Plugin Check tool.
- [ ] Create plugin icon and banner.
- [ ] Prepare for WordPress.org submission.

---

## Completed Tasks

### Session 1 - February 5, 2026

**Focus:** PRD Creation and Planning

- [x] Created comprehensive PRD with all features.
- [x] Analyzed competitors (Store Toolkit, Advanced Database Cleaner, Product Cleaner, Customers Cleanup).
- [x] Analyzed WooCommerce data structures.
- [x] Identified 17 unique features not offered by competitors.
- [x] Defined 8-week development timeline.
- [x] Created this tasks.md file.

### Session 2 - February 5, 2026

**Focus:** Phase 1 Foundation - Core Structure

- [x] Created main plugin file `jharudar.php` with version checks, HPOS compatibility.
- [x] Created `uninstall.php` with clean data removal.
- [x] Created folder structure: includes, admin, modules, assets.
- [x] Created `Jharudar_Autoloader` class for dynamic class loading.
- [x] Created `Jharudar_Activator` class with Action Scheduler integration.
- [x] Created `Jharudar_Deactivator` class for cleanup on deactivation.
- [x] Created main `Jharudar` singleton class.
- [x] Created `Jharudar_Admin` class with tab-based navigation.
- [x] Created dashboard view with stats and quick actions.
- [x] Created settings view with all configuration options.
- [x] Created activity log view with filters and pagination.
- [x] Created `admin.css` with native WordPress styling.
- [x] Created `admin.js` with SelectWoo integration.
- [x] Created `jharudar-functions.php` helper functions.
- [x] Created `Jharudar_Logger` class for activity tracking.
- [x] Created `Jharudar_Background_Process` abstract class for Action Scheduler.
- [x] Created `Jharudar_Cleanup_Process` class for cleanup operations.
- [x] Created `Jharudar_Exporter` class for CSV/JSON exports.

---

## Session Notes

### Session 1 Notes

- Plugin name: Jharudar for WooCommerce (Bengali/Hindi for sweeper/cleaner).
- Full feature launch in v0.0.1, no phased releases.
- Native WordPress and WooCommerce styling only.
- Use SelectWoo for dropdowns.
- Run PHPCS and PHPStan Level 2 after each phase.
- Create new chat session after each phase completion.

### Session 2 Notes

- Focused on building the complete foundation structure.
- All files use `jharudar_` prefix for functions, `Jharudar_` for classes.
- HPOS compatibility declared for WooCommerce custom order tables.
- Action Scheduler used for background processing instead of WP Cron.
- Logger stores activity in single WordPress option (max 1000 entries).
- Admin uses native WordPress `nav-tab-wrapper` for tab navigation.
- Dashboard shows store stats (products, orders, customers, coupons).
- Dashboard includes database health overview (transients, orphans, revisions).
- Settings include: batch size, confirmation requirements, activity logging, email notifications.
- Activity log includes filtering by action, object type, and date range.

---

## Next Steps

Complete Phase 1: Foundation by:
1. Run PHPCS and fix all issues.
2. Run PHPStan Level 2 and fix all issues.
3. Test plugin activation and deactivation.
4. Verify admin menu appears correctly under WooCommerce.
5. Test dashboard stats display.
6. Test settings save functionality.
7. Test activity log display and filtering.

After Phase 1 completion:
- Create summary of Phase 1 work.
- Start new chat session for Phase 2.
- Begin Products and Orders module development.
