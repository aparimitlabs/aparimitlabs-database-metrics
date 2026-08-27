<?php
/**
 * DB-Pulse Core Bootstrap
 *
 * Singleton class responsible for loading all plugin dependencies and
 * registering top-level WordPress hooks. All other classes are loaded
 * exclusively from this file via explicit require_once statements —
 * no autoloaders are used.
 *
 * @package AplExt\DBPulse
 */

namespace AplExt\DBPulse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Core
 *
 * Central bootstrap and dependency loader for the DB-Pulse plugin.
 */
class Core {

	/**
	 * Singleton instance.
	 *
	 * @var Core|null
	 */
	private static $instance = null;

	/**
	 * Returns the single instance of this class, creating it on first call.
	 *
	 * @return Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — use get_instance() instead.
	 * Loads all dependencies then registers global hooks.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Includes all required class files.
	 *
	 * Admin-only classes are loaded only inside the WordPress admin to avoid
	 * unnecessary overhead on front-end requests.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once APLEXT_DBPULSE_PATH . 'includes/class-db-pulse-query-monitor.php';
		require_once APLEXT_DBPULSE_PATH . 'includes/class-db-pulse-options-analyzer.php';
		require_once APLEXT_DBPULSE_PATH . 'includes/class-db-pulse-cron.php';

		if ( is_admin() ) {
			require_once APLEXT_DBPULSE_PATH . 'admin/class-db-pulse-admin.php';
			Admin::init();
		}
	}

	/**
	 * Registers global WordPress action/filter hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		QueryMonitor::init();
		Cron::init();
	}

	/**
	 * Plugin activation callback.
	 *
	 * Saves default settings when none exist yet, then schedules the cleanup
	 * cron event at the configured frequency if the enable_cron setting is active.
	 *
	 * NOTE: The cron class is explicitly required here because this static method
	 * is called by register_activation_hook() before plugins_loaded fires and
	 * before the constructor (and load_dependencies) has run.
	 *
	 * @return void
	 */
	public static function activate() {
		// Ensure cron class is available during activation.
		if ( ! class_exists( Cron::class ) ) {
			require_once APLEXT_DBPULSE_PATH . 'includes/class-db-pulse-cron.php';
		}

		// Only write defaults when no settings exist to preserve user config on re-activation.
		if ( false === get_option( 'aplext_dbpulse_settings' ) ) {
			add_option(
				'aplext_dbpulse_settings',
				array(
					'autoload_threshold' => 800000, // 800 KB in bytes.
					'slow_query_ms'      => 50,     // Milliseconds.
					'enable_cron'        => 1,      // Cleanup enabled by default.
					'cron_schedule'      => 'daily', // WP-Cron recurrence key.
				)
			);
		}

		// Schedule cron at the configured frequency (or default 'daily').
		$settings = get_option( 'aplext_dbpulse_settings', array() );
		if ( ! empty( $settings['enable_cron'] ) ) {
			$interval = isset( $settings['cron_schedule'] ) ? $settings['cron_schedule'] : 'daily';
			Cron::schedule( $interval );
		}
	}

	/**
	 * Plugin deactivation callback.
	 *
	 * Clears the scheduled cron event. Settings and cached data are retained
	 * so they are available immediately on re-activation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'aplext_dbpulse_daily_cleanup' );
	}
}
