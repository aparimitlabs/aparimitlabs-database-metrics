<?php
/**
 * DB-Pulse Options Analyzer
 *
 * Provides static utility methods for inspecting and cleaning up the
 * wp_options table: measuring autoloaded data size and purging expired
 * transients in safe, batched loops.
 *
 * @package AplExt\DBPulse
 */

namespace AplExt\DBPulse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OptionsAnalyzer
 *
 * Analyzes wp_options table health: autoloaded size and expired transients.
 */
class OptionsAnalyzer {

	/**
	 * Transient key used to cache the autoload size calculation.
	 *
	 * @var string
	 */
	const AUTOLOAD_CACHE_KEY = 'aplext_dbpulse_autoload_size';

	/**
	 * How long (in seconds) to cache the autoload size result.
	 * Avoids running a SUM(OCTET_LENGTH) query on every admin page load.
	 *
	 * @var int
	 */
	const AUTOLOAD_CACHE_TTL = 300; // 5 minutes.

	/**
	 * Calculates the total byte size of all autoloaded options in wp_options.
	 *
	 * The result is cached as a transient for AUTOLOAD_CACHE_TTL seconds to
	 * prevent repeated expensive aggregate queries. The cache is busted when
	 * the user updates plugin settings (handled in the admin sanitize callback).
	 *
	 * @return array {
	 *     @type int    $bytes     Raw byte count.
	 *     @type string $formatted Human-readable size string (e.g. "1.23 MB").
	 *     @type string $status    'good' when below threshold, 'warning' otherwise.
	 *     @type int    $threshold Configured threshold in bytes.
	 * }
	 */
	public static function get_autoload_size() {
		$cached = get_transient( self::AUTOLOAD_CACHE_KEY );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$settings  = get_option( 'aplext_dbpulse_settings', array() );
		$threshold = isset( $settings['autoload_threshold'] ) ? (int) $settings['autoload_threshold'] : 800000;

		// Static query — no user-supplied variables, so prepare() is not required.
		// WordPress supports both 'yes' (pre-6.0) and 'on' (6.0+) autoload values.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$bytes = (int) $wpdb->get_var(
			"SELECT SUM( OCTET_LENGTH( option_value ) )
			 FROM {$wpdb->options}
			 WHERE autoload = 'yes' OR autoload = 'on'"
		);

		$result = array(
			'bytes'     => $bytes,
			'formatted' => size_format( $bytes, 2 ),
			'status'    => $bytes > $threshold ? 'warning' : 'good',
			'threshold' => $threshold,
		);

		set_transient( self::AUTOLOAD_CACHE_KEY, $result, self::AUTOLOAD_CACHE_TTL );

		return $result;
	}

	/**
	 * Counts the number of expired transients currently stored in wp_options.
	 *
	 * A transient is considered expired when its corresponding timeout option
	 * (prefixed with _transient_timeout_) holds a Unix timestamp in the past.
	 *
	 * @return int Number of expired transients found.
	 */
	public static function get_expired_transient_count() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( option_name )
				 FROM {$wpdb->options}
				 WHERE option_name LIKE %s
				 AND option_value < %d",
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				time()
			)
		);
	}

	/**
	 * Purges all expired transients from wp_options in a safe, batched loop.
	 *
	 * Strategy: fetch only the timeout keys, then for each one derive the
	 * matching value key and delete both using delete_option(). This avoids a
	 * single massive DELETE statement that could cause memory or lock issues on
	 * large sites (per §5 guideline on memory management).
	 *
	 * Using delete_option() rather than a direct DELETE query ensures WordPress
	 * object cache consistency — cached transient values are invalidated
	 * immediately for the current request.
	 *
	 * @return int Number of transient pairs (timeout + value) deleted.
	 */
	public static function purge_expired_transients() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$expired_timeouts = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name
				 FROM {$wpdb->options}
				 WHERE option_name LIKE %s
				 AND option_value < %d",
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				time()
			)
		);

		if ( empty( $expired_timeouts ) ) {
			return 0;
		}

		$deleted_count = 0;

		foreach ( $expired_timeouts as $timeout_key ) {
			// Derive the value key by removing the '_timeout' segment.
			$transient_key = str_replace( '_transient_timeout_', '_transient_', $timeout_key );

			delete_option( $timeout_key );
			delete_option( $transient_key );

			$deleted_count++;
		}

		// Bust the autoload-size cache since deleting transients changes the total.
		delete_transient( self::AUTOLOAD_CACHE_KEY );

		return $deleted_count;
	}
}
