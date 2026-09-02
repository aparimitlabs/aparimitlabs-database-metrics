=== Aparimitlabs Database Metrics ===
Contributors: aparimitlabs
Tags: database, performance, autoload, transients, optimization
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Monitors real-time DB queries, execution time, autoloaded wp_options size, and safely purges expired transients with zero performance footprint.

== Description ==

Aparimitlabs Database Metrics is a lightweight, high-performance database optimization, cleaner, and monitoring plugin engineered for WordPress site owners, developers, and administrators wanting to speed up their website.

A slow WordPress database is the primary cause of high server CPU load, database overhead, and delayed Time to First Byte (TTFB). Common issues like bloated wp_options autoload size, accumulated expired transients, orphan database records, and hidden slow queries degrade site performance across every single page load.

This plugin provides clear diagnostic visibility into database health and performance directly inside your WordPress dashboard - with zero performance overhead or background process bloat.

= Why Choose Aparimitlabs Database Metrics? =

Unlike heavy database cleaner or query monitor plugins that constantly write to your database or run resource-intensive background tasks, Aparimitlabs Database Metrics uses a Zero-Footprint Execution Model. It executes diagnostics on-demand only when an authorized administrator views the dashboard.

= Key Features =

* **Zero-Footprint Performance:** Completely lightweight database cleaner and optimizer. No background processes, cron tracking, or external API calls dragging down site speed.
* **Autoloaded Options Analyzer:** Instantly detects total memory size of wp_options autoloaded data. Automatically alerts you when autoload size exceeds recommended limits (800 KB) to prevent database bloat.
* **Safe Transient Purger & Cleaner:** Identifies expired transients clogging your database and allows safe, one-click batch cleanup with native nonce verification.
* **Query Execution & Latency Tracker:** Monitors total query count and execution time per page load to pinpoint slow database queries during development, debugging, or speed optimization.
* **Database Health Diagnostic:** Gives instant health status on database tables, options overhead, and query performance.
* **Strict Security Standards:** Built according to strict WordPress coding standards with full capability checks (manage_options), nonces, and prepared SQL queries.

== Installation ==

1. Upload the `aparimitlabs-database-metrics` folder to `/wp-content/plugins/`, or install directly via Plugins > Add New in WordPress.
2. Activate the plugin.
3. Navigate to Tools > Aparimitlabs Database Metrics to inspect real-time database health, autoload size, and transient metrics.

== Frequently Asked Questions ==

= How do autoloaded options affect my WordPress site speed? =
WordPress loads every autoload=yes option in wp_options into memory on every single page request. When autoload size grows beyond 800 KB (often caused by uninstalled plugins or large options), site loading times and server memory consumption spike dramatically. This plugin alerts you before it slows down your site.

= Is this a safe database cleaner and transient optimizer? =
Yes. Transients are temporary cached data (such as temporary API responses or expired session caches). Deleting expired transients safely reclaims database storage without affecting your site content, posts, pages, users, or settings.

= How does this help reduce TTFB and speed up database queries? =
By identifying slow database queries, alerting on bloated wp_options autoload size, and purging expired transients, you reduce database execution latency and memory usage on every page load.

= Does this plugin run background tasks or write data to my database? =
No. Aparimitlabs Database Metrics is designed with a zero-footprint philosophy. It only executes analysis when you open the tools page in the dashboard.

== Screenshots ==

1. Autoloaded Options Size Analyzer highlighting memory usage thresholds and bloated options.
2. Transient Purger module for safely identifying and batch-cleaning expired transients.
3. Database Query Monitor settings and performance configuration interface.

== Changelog ==

= 1.0.0 =
* Initial public release. Fully compliance-checked with Aparimitlabs architectural standards.

== Upgrade Notice ==

= 1.0.0 =
Initial release of Aparimitlabs Database Metrics performance and monitoring suite.