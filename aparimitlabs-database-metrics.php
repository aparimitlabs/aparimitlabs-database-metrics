<?php
/**
 * Plugin Name: Aparimitlabs Database Metrics
 * Plugin URI: https://github.com/aparimitlabs/aparimitlabs-database-metrics
 * Description: Monitors database query counts, total execution time, autoloaded options size, and orphan transients.
 * Version: 1.0.0
 * Author: Aparimit Labs
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: aparimitlabs-database-metrics
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.2
 *
 * @package Aparimitlabs_Database_Metrics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version constant.
 *
 * @var string
 */
define( 'APARIMITLABS_DB_METRICS_VERSION', '1.0.0' );

/**
 * Absolute filesystem path to the plugin root directory (with trailing slash).
 *
 * @var string
 */
define( 'APARIMITLABS_DB_METRICS_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Public URL to the plugin root directory (with trailing slash).
 * Used for enqueuing CSS/JS assets.
 *
 * @var string
 */
define( 'APARIMITLABS_DB_METRICS_URL', plugin_dir_url( __FILE__ ) );

// Bootstrap the core singleton class.
require_once APARIMITLABS_DB_METRICS_PATH . 'includes/class-database-metrics-core.php';

/**
 * Plugin activation hook.
 * Saves default settings and schedules the daily cleanup cron event.
 */
register_activation_hook( __FILE__, array( 'Aparimitlabs\DBMetrics\Core', 'activate' ) );

/**
 * Plugin deactivation hook.
 * Clears the scheduled cron event.
 */
register_deactivation_hook( __FILE__, array( 'Aparimitlabs\DBMetrics\Core', 'deactivate' ) );

/**
 * Initialise the plugin after all other plugins are loaded.
 * Using plugins_loaded ensures $wpdb and other globals are fully ready.
 */
add_action( 'plugins_loaded', array( 'Aparimitlabs\DBMetrics\Core', 'get_instance' ) );
