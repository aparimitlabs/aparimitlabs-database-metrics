<?php
/**
 * Aparimitlabs Database Metrics Uninstall Handler
 *
 * Fires when the plugin is deleted (not deactivated) from the WordPress admin.
 * Removes all stored options, transients, and scheduled events to leave
 * the site in a completely clean state.
 *
 * @package Aparimitlabs\DBMetrics
 */

// Block direct execution — WordPress sets this constant before calling this file.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin settings.
delete_option( 'aparimitlabs_db_metrics_settings' );

// Remove cached autoload-size result.
delete_transient( 'aparimitlabs_db_metrics_autoload_size' );

// Remove the last cron-run timestamp stored by the cleanup job.
delete_transient( 'aparimitlabs_db_metrics_last_cron_run' );

// Clear the scheduled daily cleanup event so it never fires again.
wp_clear_scheduled_hook( 'aparimitlabs_db_metrics_daily_cleanup' );
