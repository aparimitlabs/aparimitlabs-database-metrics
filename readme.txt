=== Aparimitlabs Database Metrics ===
Contributors: aparimitlabs
Tags: database, performance, monitor, transients, wpdb
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Monitors database query counts, execution time, autoloaded options size, and cleans expired transients with zero performance footprint.

== Description ==

Aparimitlabs Database Metrics is a lightweight, high-performance database optimization and monitoring tool designed for WordPress developers and site administrators.

It gives you clear, real-time metrics on database health directly inside your WordPress admin tools without running heavy background processes or dragging down site speed.

= Key Features =

* **Zero Footprint:** Loads strictly when stats are viewed—no background overhead or external dependencies.
* **Autoload Size Detection:** Highlights bloated `wp_options` data exceeding recommended thresholds.
* **Transient Purger:** Safely counts and batch-cleans expired transients using secure native nonces.
* **Query Performance Counter:** Displays real-time query counts and flags slow database executions.
* **Strict Security Standards:** Built using full capability checks, input sanitization, nonces, and prepared SQL queries.

== Installation ==

1. Upload the `database-metrics` directory to the `/wp-content/plugins/` directory, or install via the WordPress Plugins menu.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Tools > Aparimitlabs Database Metrics Dashboard** to view your database metrics and manage transients.

== Frequently Asked Questions ==

= Does Aparimitlabs Database Metrics run continuously in the background? =
No. Aparimitlabs Database Metrics is engineered to have a zero-footprint execution model. It only calculates metrics when an authorized administrator accesses the metrics dashboard or query stats.

= Does deleting transients affect my site content? =
No. Transients are temporary cached data. Clearing expired transients safely frees up space in your database without touching posts, pages, options, or user data.

== Screenshots ==

1. Overview Dashboard displaying real-time database query counts, total execution time, and system health status.
2. Autoloaded Options Size Analyzer highlighting memory usage thresholds and bloated options.
3. Transient Purger module for safely identifying and batch-cleaning expired transients.
4. Database Query Monitor settings and performance configuration interface.

== Changelog ==

= 1.0.0 =
* Initial public release. Fully compliance-checked with Aparimitlabs architectural standards.

== Upgrade Notice ==

= 1.0.0 =
Initial release of Aparimitlabs Database Metrics performance and monitoring suite.