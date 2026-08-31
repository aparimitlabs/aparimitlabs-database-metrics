<?php
/**
 * Aparimitlabs Database Metrics Query Monitor
 *
 * Hooks into WordPress footer actions to read real-time query statistics
 * from the global $wpdb object. Slow-query detection relies on the
 * SAVEQUERIES constant being defined and truthy in wp-config.php.
 *
 * @package Aparimitlabs\DBMetrics
 */

namespace Aparimitlabs\DBMetrics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class QueryMonitor
 *
 * Collects and exposes database query metrics for the current HTTP request.
 */
class QueryMonitor {

	/**
	 * Registers WordPress hooks. Called once from Core::init_hooks().
	 *
	 * Attaches render_query_stats() to both the front-end and admin footers at
	 * priority 999 so it runs after all other plugins have finished firing queries.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_footer',    array( __CLASS__, 'render_query_stats' ), 999 );
		add_action( 'admin_footer', array( __CLASS__, 'render_query_stats' ), 999 );
	}

	/**
	 * Collects query statistics for the current page request.
	 *
	 * Returns total query count and cumulative execution time from $wpdb.
	 * Slow query analysis is only available when SAVEQUERIES is defined and
	 * truthy — otherwise slow_queries will be an empty array and
	 * savequeries_enabled will be false.
	 *
	 * The slow-query threshold (in milliseconds) is read from the plugin's
	 * saved settings, falling back to 50 ms if no setting exists.
	 *
	 * @return array {
	 *     @type int    $count               Total number of DB queries for this request.
	 *     @type string $total_time          Cumulative query time formatted to 2 decimal places (ms).
	 *     @type bool   $savequeries_enabled Whether SAVEQUERIES was active for this request.
	 *     @type array  $slow_queries        Queries that exceeded the configured threshold.
	 * }
	 */
	public static function get_stats() {
		global $wpdb;

		$settings       = get_option( 'aparimitlabs_db_metrics_settings', array() );
		$threshold_ms   = isset( $settings['slow_query_ms'] ) ? (int) $settings['slow_query_ms'] : 50;
		$threshold_secs = $threshold_ms / 1000.0;

		$total_queries       = (int) $wpdb->num_queries;
		$total_time          = 0.0;
		$slow_queries        = array();
		$savequeries_enabled = ( defined( 'SAVEQUERIES' ) && SAVEQUERIES );

		if ( $savequeries_enabled && ! empty( $wpdb->queries ) ) {
			foreach ( $wpdb->queries as $query ) {
				$sql     = isset( $query[0] ) ? $query[0] : '';
				$time    = isset( $query[1] ) ? (float) $query[1] : 0.0;
				$callers = isset( $query[2] ) ? $query[2] : '';

				$total_time += $time;

				if ( $time > $threshold_secs ) {
					$slow_queries[] = array(
						'sql'     => $sql,
						'time'    => number_format( $time * 1000, 2 ) . ' ms',
						'callers' => $callers,
					);
				}
			}
		}

		return array(
			'count'               => $total_queries,
			'total_time'          => number_format( $total_time * 1000, 2 ),
			'slow_queries'        => $slow_queries,
			'savequeries_enabled' => $savequeries_enabled,
		);
	}

	/**
	 * Outputs a lightweight HTML comment in the page footer containing
	 * the query count and total time for this request.
	 *
	 * Only visible to users with the manage_options capability.
	 * The comment format is intentionally minimal so it does not bloat the
	 * HTML source for regular visitors (who never reach this point due to
	 * the capability check).
	 *
	 * @return void
	 */
	public static function render_query_stats() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$stats = self::get_stats();

		printf(
			"\n<!-- Aparimitlabs Aparimitlabs Database Metrics | Queries: %s | Time: %sms -->\n",
			esc_html( (string) $stats['count'] ),
			esc_html( $stats['total_time'] )
		);
	}
}
