<?php
/**
 * Aparimitlabs Database Metrics Cron Scheduler
 *
 * Manages the WordPress scheduled event that automatically purges expired
 * transients on a daily basis. The last cleanup result is stored as a
 * transient so the admin dashboard can show when it last ran.
 *
 * @package Aparimitlabs\DBMetrics
 */

namespace Aparimitlabs\DBMetrics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cron
 *
 * Handles scheduling, unscheduling, and execution of the configurable DB cleanup job.
 * The cleanup frequency is set by the admin via the plugin settings page.
 */
class Cron {

	/**
	 * The WP-Cron hook name used for the daily cleanup event.
	 *
	 * @var string
	 */
	const HOOK = 'aparimitlabs_db_metrics_daily_cleanup';

	/**
	 * Transient key that stores the result of the last cron run.
	 * Value is an array with 'time' (Unix timestamp) and 'deleted' (int count).
	 *
	 * @var string
	 */
	const LAST_RUN_KEY = 'aparimitlabs_db_metrics_last_cron_run';

	/**
	 * Registers the WordPress action hook that fires when the cron event runs.
	 * Called once from Core::init_hooks().
	 *
	 * @return void
	 */
	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'run_cleanup' ) );
	}

	/**
	 * Returns the list of WP-Cron recurrence slugs the plugin allows.
	 *
	 * Validated against WordPress's registered schedules so custom schedules
	 * added by other plugins are also accepted.
	 *
	 * @return string[] Allowed recurrence keys (e.g. 'hourly', 'daily').
	 */
	public static function get_allowed_schedules() {
		return array_keys( wp_get_schedules() );
	}

	/**
	 * Schedules the cleanup event if it is not already scheduled.
	 *
	 * Safe to call multiple times — wp_next_scheduled() guards against
	 * duplicate events. Used on plugin activation.
	 *
	 * @param string $interval A valid WP-Cron recurrence key (e.g. 'daily'). Defaults to 'daily'.
	 * @return void
	 */
	public static function schedule( $interval = 'daily' ) {
		if ( ! in_array( $interval, self::get_allowed_schedules(), true ) ) {
			$interval = 'daily';
		}
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), $interval, self::HOOK );
		}
	}

	/**
	 * Replaces any existing scheduled event with a new recurrence interval.
	 *
	 * Always unschedules first so that changing the frequency from e.g.
	 * 'daily' to 'hourly' takes effect immediately rather than waiting for
	 * the next natural firing of the old event.
	 *
	 * @param string $interval A valid WP-Cron recurrence key. Defaults to 'daily'.
	 * @return void
	 */
	public static function reschedule( $interval = 'daily' ) {
		if ( ! in_array( $interval, self::get_allowed_schedules(), true ) ) {
			$interval = 'daily';
		}
		self::unschedule();
		wp_schedule_event( time(), $interval, self::HOOK );
	}

	/**
	 * Removes the scheduled cleanup event.
	 *
	 * Called on plugin deactivation and when the user disables the cron
	 * option in plugin settings.
	 *
	 * @return void
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Cron callback: purges expired transients and records the run result.
	 *
	 * The result is written to a short-lived transient (25 hours) so the
	 * admin dashboard can display it. The TTL is intentionally larger than
	 * the maximum supported interval (weekly) to ensure visibility between runs.
	 *
	 * @return void
	 */
	public static function run_cleanup() {
		// Ensure the options analyzer is available in this cron context.
		if ( ! class_exists( OptionsAnalyzer::class ) ) {
			require_once APARIMITLABS_DB_METRICS_PATH . 'includes/class-database-metrics-options-analyzer.php';
		}

		$deleted = OptionsAnalyzer::purge_expired_transients();

		set_transient(
			self::LAST_RUN_KEY,
			array(
				'time'    => time(),
				'deleted' => $deleted,
			),
			25 * HOUR_IN_SECONDS
		);
	}

	/**
	 * Returns the stored result from the most recent automated cron run, or
	 * false if the cron has not run since activation (or data has expired).
	 *
	 * @return array|false {
	 *     @type int $time    Unix timestamp of the last successful run.
	 *     @type int $deleted Number of transients purged in that run.
	 * }
	 */
	public static function get_last_run() {
		return get_transient( self::LAST_RUN_KEY );
	}
}
