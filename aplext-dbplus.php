<?php
/**
 * Plugin Name:       AplExt DBPlus
 * Plugin URI:        https://github.com/aparimitlabs/aplext-dbplus
 * Description:       Monitors database query counts, total execution time, autoloaded options size, and orphan transients.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      8.2
 * Author:            Aparimit Labs
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aplext-dbplus
 * Domain Path:       /languages
	 *
 * @package AplExt\DBPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version constant.
 *
 * @var string
 */
define( 'APLEXT_DBPULSE_VERSION', '1.0.0' );

/**
 * Absolute filesystem path to the plugin root directory (with trailing slash).
 *
 * @var string
 */
define( 'APLEXT_DBPULSE_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Public URL to the plugin root directory (with trailing slash).
 * Used for enqueuing CSS/JS assets.
 *
 * @var string
 */
define( 'APLEXT_DBPULSE_URL', plugin_dir_url( __FILE__ ) );

// Bootstrap the core singleton class.
require_once APLEXT_DBPULSE_PATH . 'includes/class-db-pulse-core.php';

/**
 * Plugin activation hook.
 * Saves default settings and schedules the daily cleanup cron event.
 */
register_activation_hook( __FILE__, array( 'AplExt\DBPulse\Core', 'activate' ) );

/**
 * Plugin deactivation hook.
 * Clears the scheduled cron event.
 */
register_deactivation_hook( __FILE__, array( 'AplExt\DBPulse\Core', 'deactivate' ) );

/**
 * Initialise the plugin after all other plugins are loaded.
 * Using plugins_loaded ensures $wpdb and other globals are fully ready.
 */
add_action( 'plugins_loaded', array( 'AplExt\DBPulse\Core', 'get_instance' ) );
