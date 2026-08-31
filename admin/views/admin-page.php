<?php
/**
 * Aparimitlabs Database Metrics Admin Page Template
 *
 * Renders the settings form followed by the metrics dashboard.
 * All variables are set by Admin::render_admin_page() before this file is loaded.
 *
 * @var array       $settings      Plugin settings array.
 * @var array       $autoload      { bytes, formatted, status, threshold }
 * @var int         $transient_cnt Number of expired transients.
 * @var array       $query_stats   { count, total_time, slow_queries[], savequeries_enabled }
 * @var array|false $last_cron_run { time, deleted } or false if never run.
 * @var int|null    $purged        Count from most recent manual purge, or null.
 *
 * @package Aparimitlabs\DBMetrics
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$autoload_threshold = isset( $settings['autoload_threshold'] ) ? (int) $settings['autoload_threshold'] : 800000;
$slow_query_ms      = isset( $settings['slow_query_ms'] )      ? (int) $settings['slow_query_ms']      : 50;
$enable_cron        = ! empty( $settings['enable_cron'] );
$cron_schedule      = isset( $settings['cron_schedule'] )      ? $settings['cron_schedule']            : 'daily';
?>

<div class="wrap database-metrics-wrap">

	<h1 class="database-metrics-title">
		<span class="database-metrics-icon" aria-hidden="true">⚡</span>
		<?php esc_html_e( 'Aparimitlabs Database Metrics', 'aparimitlabs-database-metrics' ); ?>
	</h1>

<!-- ── Settings Form ──────────────────────────────────────────────────── -->
<form method="post" action="options.php" class="database-metrics-settings-form">
	<?php settings_fields( 'aparimitlabs_db_metrics_settings_group' ); ?>

	<table class="form-table database-metrics-settings-table" role="presentation">

		<!-- Autoloaded Options Threshold -->
		<tr>
			<th scope="row">
				<label for="aparimitlabs_db_metrics_autoload_threshold">
					<?php esc_html_e( 'Autoload Size Warning Threshold', 'aparimitlabs-database-metrics' ); ?>
				</label>
			</th>
			<td>
				<div class="database-metrics-input-group">
					<input
						type="number"
						id="aparimitlabs_db_metrics_autoload_threshold"
						name="aparimitlabs_db_metrics_settings[autoload_threshold]"
						value="<?php echo esc_attr( (string) $autoload_threshold ); ?>"
						class="regular-text"
						min="1"
						step="1"
						required
					/>
					<span class="database-metrics-input-suffix"><?php esc_html_e( 'bytes', 'aparimitlabs-database-metrics' ); ?></span>
				</div>
				<p class="description">
					<?php
					printf(
						/* translators: %s: human-readable equivalent of the current value */
						esc_html__( 'Currently set to %s. The status badge turns "warning" when the total autoloaded size exceeds this value.', 'aparimitlabs-database-metrics' ),
						'<strong>' . esc_html( size_format( $autoload_threshold, 2 ) ) . '</strong>'
					);
					?>
					<br />
					<?php esc_html_e( 'Default: 800000 (800 KB).', 'aparimitlabs-database-metrics' ); ?>
				</p>
			</td>
		</tr>

		<!-- Slow Query Threshold -->
		<tr>
			<th scope="row">
				<label for="aparimitlabs_db_metrics_slow_query_ms">
					<?php esc_html_e( 'Slow Query Threshold', 'aparimitlabs-database-metrics' ); ?>
				</label>
			</th>
			<td>
				<div class="database-metrics-input-group">
					<input
						type="number"
						id="aparimitlabs_db_metrics_slow_query_ms"
						name="aparimitlabs_db_metrics_settings[slow_query_ms]"
						value="<?php echo esc_attr( (string) $slow_query_ms ); ?>"
						class="small-text"
						min="1"
						step="1"
						required
					/>
					<span class="database-metrics-input-suffix"><?php esc_html_e( 'ms', 'aparimitlabs-database-metrics' ); ?></span>
				</div>
				<p class="description">
					<?php esc_html_e( 'Queries taking longer than this value will appear in the slow query list below (requires SAVEQUERIES to be enabled). Default: 50 ms.', 'aparimitlabs-database-metrics' ); ?>
				</p>
			</td>
		</tr>

		<!-- Automated Cleanup -->
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Automated Cleanup', 'aparimitlabs-database-metrics' ); ?>
			</th>
			<td>
				<label for="aparimitlabs_db_metrics_enable_cron" class="database-metrics-checkbox-label">
					<input
						type="checkbox"
						id="aparimitlabs_db_metrics_enable_cron"
						name="aparimitlabs_db_metrics_settings[enable_cron]"
						value="1"
						<?php checked( $enable_cron ); ?>
					/>
					<?php esc_html_e( 'Automatically purge expired transients via WP-Cron.', 'aparimitlabs-database-metrics' ); ?>
				</label>
				<p class="description">
					<?php
					$next_run = wp_next_scheduled( 'aparimitlabs_db_metrics_daily_cleanup' );
					if ( $next_run ) {
						printf(
							/* translators: %s: human-readable time until next cron run */
							esc_html__( 'Next scheduled run: in %s.', 'aparimitlabs-database-metrics' ),
							esc_html( human_time_diff( time(), $next_run ) )
						);
					} elseif ( $enable_cron ) {
						esc_html_e( 'No event scheduled yet — it will be created when you save.', 'aparimitlabs-database-metrics' );
					} else {
						esc_html_e( 'Automated cleanup is currently disabled.', 'aparimitlabs-database-metrics' );
					}
					?>
				</p>
			</td>
		</tr>

		<!-- Cleanup Frequency -->
		<tr id="aparimitlabs-cron-frequency-row">
			<th scope="row">
				<label for="aparimitlabs_db_metrics_cron_schedule">
					<?php esc_html_e( 'Cleanup Frequency', 'aparimitlabs-database-metrics' ); ?>
				</label>
			</th>
			<td>
				<select
					id="aparimitlabs_db_metrics_cron_schedule"
					name="aparimitlabs_db_metrics_settings[cron_schedule]"
					class="regular-text"
				>
					<?php
					$wp_schedules = wp_get_schedules();
					uasort(
						$wp_schedules,
						function ( $a, $b ) {
							return $a['interval'] <=> $b['interval'];
						}
					);
					foreach ( $wp_schedules as $key => $schedule ) {
						printf(
							'<option value="%s"%s>%s</option>',
							esc_attr( $key ),
							selected( $cron_schedule, $key, false ),
							esc_html( $schedule['display'] )
						);
					}
					?>
				</select>
				<p class="description">
					<?php esc_html_e( 'How often WP-Cron should automatically purge expired transients. Changes take effect immediately on save.', 'aparimitlabs-database-metrics' ); ?>
				</p>
			</td>
		</tr>

	</table>

	<?php submit_button( __( 'Save Settings', 'aparimitlabs-database-metrics' ) ); ?>

</form>

<!-- ── Purge Notices ──────────────────────────────────────────────────── -->
<?php if ( null !== $purged && $purged > 0 ) : ?>
	<div class="notice notice-success is-dismissible database-metrics-notice">
		<p>
			<?php
			printf(
				/* translators: %d: number of transients deleted */
				esc_html__( 'Successfully purged %d expired transient(s) from the database.', 'aparimitlabs-database-metrics' ),
				(int) $purged
			);
			?>
		</p>
	</div>
<?php elseif ( 0 === $purged ) : ?>
	<div class="notice notice-info is-dismissible database-metrics-notice">
		<p><?php esc_html_e( 'No expired transients were found. The database is already clean.', 'aparimitlabs-database-metrics' ); ?></p>
	</div>
<?php endif; ?>

<!-- ── Metrics Table ──────────────────────────────────────────────────── -->
<table class="widefat fixed striped database-metrics-table" role="grid">
	<thead>
		<tr>
			<th scope="col" style="width: 40%;"><?php esc_html_e( 'Metric', 'aparimitlabs-database-metrics' ); ?></th>
			<th scope="col" style="width: 30%;"><?php esc_html_e( 'Current Value', 'aparimitlabs-database-metrics' ); ?></th>
			<th scope="col" style="width: 30%;"><?php esc_html_e( 'Status', 'aparimitlabs-database-metrics' ); ?></th>
		</tr>
	</thead>
	<tbody>

		<!-- Autoloaded Options Size -->
		<tr>
			<td>
				<strong><?php esc_html_e( 'Autoloaded Options Size', 'aparimitlabs-database-metrics' ); ?></strong>
				<span class="database-metrics-meta">
					<?php
					printf(
						/* translators: %s: threshold formatted string e.g. "800 KB" */
						esc_html__( 'Threshold: %s', 'aparimitlabs-database-metrics' ),
						esc_html( size_format( $autoload['threshold'], 0 ) )
					);
					?>
				</span>
			</td>
			<td><code><?php echo esc_html( $autoload['formatted'] ); ?></code></td>
			<td>
				<?php if ( 'good' === $autoload['status'] ) : ?>
					<span class="database-metrics-badge database-metrics-badge--good">
						✔ <?php esc_html_e( 'Healthy', 'aparimitlabs-database-metrics' ); ?>
					</span>
				<?php else : ?>
					<span class="database-metrics-badge database-metrics-badge--warning">
						⚠ <?php esc_html_e( 'High Bloat', 'aparimitlabs-database-metrics' ); ?>
					</span>
				<?php endif; ?>
			</td>
		</tr>

		<!-- Expired Transients -->
		<tr>
			<td>
				<strong><?php esc_html_e( 'Expired Transients', 'aparimitlabs-database-metrics' ); ?></strong>
				<?php if ( false !== $last_cron_run ) : ?>
					<span class="database-metrics-meta">
						<?php
						printf(
							/* translators: 1: human-readable time difference, 2: number of deleted transients */
							esc_html__( 'Auto-cleaned %1$s ago (%2$d removed)', 'aparimitlabs-database-metrics' ),
							esc_html( human_time_diff( $last_cron_run['time'], time() ) ),
							(int) $last_cron_run['deleted']
						);
						?>
					</span>
				<?php endif; ?>
			</td>
			<td><code><?php echo esc_html( (string) $transient_cnt ); ?></code></td>
			<td>
				<?php if ( $transient_cnt > 0 ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
						<input type="hidden" name="action" value="aparimitlabs_db_metrics_purge_transients" />
						<?php wp_nonce_field( 'aparimitlabs_db_metrics_purge_action', 'aparimitlabs_db_metrics_nonce' ); ?>
						<button type="submit" class="button button-secondary database-metrics-purge-btn">
							🗑 <?php esc_html_e( 'Purge Now', 'aparimitlabs-database-metrics' ); ?>
						</button>
					</form>
				<?php else : ?>
					<span class="database-metrics-badge database-metrics-badge--good">
						✔ <?php esc_html_e( 'Clean', 'aparimitlabs-database-metrics' ); ?>
					</span>
				<?php endif; ?>
			</td>
		</tr>

		<!-- Page Queries -->
		<tr>
			<td><strong><?php esc_html_e( 'Page Queries (this request)', 'aparimitlabs-database-metrics' ); ?></strong></td>
			<td><code><?php echo esc_html( (string) $query_stats['count'] ); ?></code></td>
			<td>—</td>
		</tr>

		<!-- Total Query Time -->
		<tr>
			<td><strong><?php esc_html_e( 'Total Query Time (this request)', 'aparimitlabs-database-metrics' ); ?></strong></td>
			<td>
				<?php if ( $query_stats['savequeries_enabled'] ) : ?>
					<code><?php echo esc_html( $query_stats['total_time'] ); ?> ms</code>
				<?php else : ?>
					<em class="database-metrics-unavailable">
						<?php esc_html_e( 'Unavailable — SAVEQUERIES not enabled', 'aparimitlabs-database-metrics' ); ?>
					</em>
				<?php endif; ?>
			</td>
			<td>—</td>
		</tr>

	</tbody>
</table>

<!-- ── Slow Query Details ─────────────────────────────────────────────── -->
<?php if ( $query_stats['savequeries_enabled'] ) : ?>
	<div class="database-metrics-section">
		<?php if ( ! empty( $query_stats['slow_queries'] ) ) : ?>
			<details class="database-metrics-slow-queries">
				<summary class="database-metrics-slow-queries__toggle">
					<span class="database-metrics-badge database-metrics-badge--warning" style="margin-right:8px;">
						<?php echo esc_html( (string) count( $query_stats['slow_queries'] ) ); ?>
					</span>
					<?php
					printf(
						/* translators: %d: slow query count */
						esc_html( _n( '%d Slow Query Detected', '%d Slow Queries Detected', count( $query_stats['slow_queries'] ), 'aparimitlabs-database-metrics' ) ),
						count( $query_stats['slow_queries'] )
					);
					printf(
						/* translators: %d: configured threshold in milliseconds */
						esc_html__( ' (threshold: %d ms)', 'aparimitlabs-database-metrics' ),
						(int) $slow_query_ms
					);
					?>
				</summary>
				<div class="database-metrics-slow-queries__body">
					<?php foreach ( $query_stats['slow_queries'] as $index => $sq ) : ?>
						<div class="database-metrics-query-item">
							<div class="database-metrics-query-item__header">
								<span class="database-metrics-badge database-metrics-badge--warning">
									<?php echo esc_html( $sq['time'] ); ?>
								</span>
								<span class="database-metrics-query-item__num">
									#<?php echo esc_html( (string) ( $index + 1 ) ); ?>
								</span>
							</div>
							<pre class="database-metrics-query-item__sql"><?php echo esc_html( $sq['sql'] ); ?></pre>
							<?php if ( ! empty( $sq['callers'] ) ) : ?>
								<p class="database-metrics-query-item__callers">
									<strong><?php esc_html_e( 'Called by:', 'aparimitlabs-database-metrics' ); ?></strong>
									<?php echo esc_html( $sq['callers'] ); ?>
								</p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</details>
		<?php else : ?>
			<div class="database-metrics-no-slow">
				<span class="database-metrics-badge database-metrics-badge--good">✔</span>
				<?php
				printf(
					/* translators: %d: configured threshold in milliseconds */
					esc_html__( 'No slow queries detected on this request (threshold: %d ms).', 'aparimitlabs-database-metrics' ),
					(int) $slow_query_ms
				);
				?>
			</div>
		<?php endif; ?>
	</div>
<?php else : ?>
	<div class="database-metrics-section database-metrics-savequeries-notice">
		<span class="database-metrics-badge database-metrics-badge--neutral">ℹ</span>
		<?php esc_html_e( 'Slow query analysis is disabled. To enable it, add the following to your ', 'aparimitlabs-database-metrics' ); ?>
		<code>wp-config.php</code>:
		<pre class="database-metrics-code-hint">define( 'SAVEQUERIES', true );</pre>
	</div>
<?php endif; ?>

</div><!-- .database-metrics-wrap -->
