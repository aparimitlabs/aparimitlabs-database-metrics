<?php
/**
 * DB-Pulse Admin Handler
 *
 * Registers admin menus, enqueues plugin assets, handles the transient purge
 * POST action, and wires up the WordPress Settings API for plugin options.
 * All output is delegated to view templates in admin/views/.
 *
 * @package AplExt\DBPulse
 */

namespace AplExt\DBPulse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 *
 * Manages everything within the WordPress admin: menus, settings, and actions.
 */
class Admin {

	/**
	 * The menu/page slug for the main dashboard page.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'aplext-db-pulse';



	/**
	 * The Settings API option group name used in settings_fields().
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'aplext_dbpulse_settings_group';

	/**
	 * The wp_options key where plugin settings are stored.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'aplext_dbpulse_settings';

	/**
	 * Registers all admin-facing WordPress action hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu',                                               array( __CLASS__, 'register_admin_menu' ) );
		add_action( 'admin_init',                                               array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts',                                    array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_aplext_dbpulse_purge_transients',               array( __CLASS__, 'handle_transient_purge' ) );
	}

	/**
	 * Registers the DB-Pulse page under Tools in the WordPress admin menu.
	 *
	 * @return void
	 */
	public static function register_admin_menu() {
		add_management_page(
			__( 'DB-Pulse', 'aplext-db-pulse' ),
			__( 'DB-Pulse', 'aplext-db-pulse' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Registers plugin settings with the WordPress Settings API.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitizes and validates submitted settings before they are saved.
	 *
	 * Validates each field, reconciles the cron schedule, and busts the
	 * autoload-size cache so changes are reflected immediately.
	 *
	 * @param array $input Raw form input.
	 * @return array Sanitized settings array.
	 */
	public static function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['autoload_threshold'] = isset( $input['autoload_threshold'] )
			? absint( $input['autoload_threshold'] )
			: 800000;

		$sanitized['slow_query_ms'] = isset( $input['slow_query_ms'] )
			? absint( $input['slow_query_ms'] )
			: 50;

		// Checkbox is only present in the POST payload when checked.
		$sanitized['enable_cron'] = ! empty( $input['enable_cron'] ) ? 1 : 0;

		// Validate cron_schedule against WordPress's registered recurrence slugs.
		$raw_schedule             = isset( $input['cron_schedule'] ) ? sanitize_key( $input['cron_schedule'] ) : 'daily';
		$sanitized['cron_schedule'] = in_array( $raw_schedule, Cron::get_allowed_schedules(), true )
			? $raw_schedule
			: 'daily';

		// Reconcile cron state. Always reschedule (not just schedule) so that a
		// frequency change takes effect immediately rather than at the next firing.
		if ( $sanitized['enable_cron'] ) {
			Cron::reschedule( $sanitized['cron_schedule'] );
		} else {
			Cron::unschedule();
		}

		// Bust cached autoload size so the new threshold is reflected immediately.
		delete_transient( OptionsAnalyzer::AUTOLOAD_CACHE_KEY );

		return $sanitized;
	}

	/**
	 * Enqueues the plugin stylesheet on the DB-Pulse admin page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public static function enqueue_admin_assets( $hook_suffix ) {
		if ( 'tools_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'aplext-db-pulse-admin',
			APLEXT_DBPULSE_URL . 'admin/css/db-pulse-admin.css',
			array(),
			APLEXT_DBPULSE_VERSION
		);
	}

	/**
	 * Handles the manual transient purge form submission.
	 *
	 * Verifies capability and nonce, purges expired transients, then redirects
	 * back to the admin page with the deleted count as a query argument.
	 *
	 * @return void
	 */
	public static function handle_transient_purge() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'aplext-db-pulse' ) );
		}

		check_admin_referer( 'aplext_dbpulse_purge_action', 'aplext_dbpulse_nonce' );

		$count = OptionsAnalyzer::purge_expired_transients();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => self::PAGE_SLUG,
					'purged' => $count,
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	/**
	 * Collects all required data and renders the DB-Pulse admin page.
	 *
	 * Loads the dashboard metrics view followed by the settings form.
	 * Access is restricted to users with the manage_options capability.
	 *
	 * @return void
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$autoload      = OptionsAnalyzer::get_autoload_size();
		$transient_cnt = OptionsAnalyzer::get_expired_transient_count();
		$query_stats   = QueryMonitor::get_stats();
		$last_cron_run = Cron::get_last_run();
		$settings      = get_option( self::OPTION_NAME, array() );
		$purged        = isset( $_GET['purged'] ) ? (int) $_GET['purged'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		require APLEXT_DBPULSE_PATH . 'admin/views/admin-page.php';
	}
}
