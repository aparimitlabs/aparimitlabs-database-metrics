<?php
/**
 * DB-Pulse Admin Page Template
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
 * @package AplExt\DBPulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$autoload_threshold = isset( $settings['autoload_threshold'] ) ? (int) $settings['autoload_threshold'] : 800000;
$slow_query_ms      = isset( $settings['slow_query_ms'] )      ? (int) $settings['slow_query_ms']      : 50;
$enable_cron        = ! empty( $settings['enable_cron'] );
$cron_schedule      = isset( $settings['cron_schedule'] )      ? $settings['cron_schedule']            : 'daily';
?>

<div class="wrap db-pulse-wrap">

	<h1 class="db-pulse-title">
		<span class="db-pulse-icon" aria-hidden="true">⚡</span>
		<?php esc_html_e( 'DB-Pulse', 'aplext-db-pulse' ); ?>
	</h1>

<!-- ── Settings Form ──────────────────────────────────────────────────── -->
<form method="post" action="options.php" class="db-pulse-settings-form">
	<?php settings_fields( 'aplext_dbpulse_settings_group' ); ?>

	<table class="form-table db-pulse-settings-table" role="presentation">

		<!-- Autoloaded Options Threshold -->
		<tr>
			<th scope="row">
				<label for="aplext_dbpulse_autoload_threshold">
					<?php esc_html_e( 'Autoload Size Warning Threshold', 'aplext-db-pulse' ); ?>
				</label>
			</th>
			<td>
				<div class="db-pulse-input-group">
					<input
						type="number"
						id="aplext_dbpulse_autoload_threshold"
						name="aplext_dbpulse_settings[autoload_threshold]"
						value="<?php echo esc_attr( (string) $autoload_threshold ); ?>"
						class="regular-text"
						min="1"
						step="1"
						required
					/>
					<span class="db-pulse-input-suffix"><?php esc_html_e( 'bytes', 'aplext-db-pulse' ); ?></span>
				</div>
				<p class="description">
					<?php
					printf(
						/* translators: %s: human-readable equivalent of the current value */
						esc_html__( 'Currently set to %s. The status badge turns "warning" when the total autoloaded size exceeds this value.', 'aplext-db-pulse' ),
						'<strong>' . esc_html( size_format( $autoload_threshold, 2 ) ) . '</strong>'
					);
					?>
					<br />
					<?php esc_html_e( 'Default: 800000 (800 KB).', 'aplext-db-pulse' ); ?>
				</p>
			</td>
		</tr>

		<!-- Slow Query Threshold -->
		<tr>
			<th scope="row">
				<label for="aplext_dbpulse_slow_query_ms">
					<?php esc_html_e( 'Slow Query Threshold', 'aplext-db-pulse' ); ?>
				</label>
			</th>
			<td>
				<div class="db-pulse-input-group">
					<input
						type="number"
						id="aplext_dbpulse_slow_query_ms"
						name="aplext_dbpulse_settings[slow_query_ms]"
						value="<?php echo esc_attr( (string) $slow_query_ms ); ?>"
						class="small-text"
						min="1"
						step="1"
						required
					/>
					<span class="db-pulse-input-suffix"><?php esc_html_e( 'ms', 'aplext-db-pulse' ); ?></span>
				</div>
				<p class="description">
					<?php esc_html_e( 'Queries taking longer than this value will appear in the slow query list below (requires SAVEQUERIES to be enabled). Default: 50 ms.', 'aplext-db-pulse' ); ?>
				</p>
			</td>
		</tr>

		<!-- Automated Cleanup -->
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Automated Cleanup', 'aplext-db-pulse' ); ?>
			</th>
			<td>
				<label for="aplext_dbpulse_enable_cron" class="db-pulse-checkbox-label">
					<input
						type="checkbox"
						id="aplext_dbpulse_enable_cron"
						name="aplext_dbpulse_settings[enable_cron]"
						value="1"
						<?php checked( $enable_cron ); ?>
					/>
					<?php esc_html_e( 'Automatically purge expired transients via WP-Cron.', 'aplext-db-pulse' ); ?>
				</label>
				<p class="description">
					<?php
					$next_run = wp_next_scheduled( 'aplext_dbpulse_daily_cleanup' );
					if ( $next_run ) {
						printf(
							/* translators: %s: human-readable time until next cron run */
							esc_html__( 'Next scheduled run: in %s.', 'aplext-db-pulse' ),
							esc_html( human_time_diff( time(), $next_run ) )
						);
					} elseif ( $enable_cron ) {
						esc_html_e( 'No event scheduled yet — it will be created when you save.', 'aplext-db-pulse' );
					} else {
						esc_html_e( 'Automated cleanup is currently disabled.', 'aplext-db-pulse' );
					}
					?>
				</p>
			</td>
		</tr>

		<!-- Cleanup Frequency -->
		<tr id="aplext-cron-frequency-row">
			<th scope="row">
				<label for="aplext_dbpulse_cron_schedule">
					<?php esc_html_e( 'Cleanup Frequency', 'aplext-db-pulse' ); ?>
				</label>
			</th>
			<td>
				<select
					id="aplext_dbpulse_cron_schedule"
					name="aplext_dbpulse_settings[cron_schedule]"
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
					<?php esc_html_e( 'How often WP-Cron should automatically purge expired transients. Changes take effect immediately on save.', 'aplext-db-pulse' ); ?>
				</p>
			</td>
		</tr>

	</table>

	<?php submit_button( __( 'Save Settings', 'aplext-db-pulse' ) ); ?>

</form>

<!-- ── Purge Notices ──────────────────────────────────────────────────── -->
<?php if ( null !== $purged && $purged > 0 ) : ?>
	<div class="notice notice-success is-dismissible db-pulse-notice">
		<p>
			<?php
			printf(
				/* translators: %d: number of transients deleted */
				esc_html__( 'Successfully purged %d expired transient(s) from the database.', 'aplext-db-pulse' ),
				(int) $purged
			);
			?>
		</p>
	</div>
<?php elseif ( 0 === $purged ) : ?>
	<div class="notice notice-info is-dismissible db-pulse-notice">
		<p><?php esc_html_e( 'No expired transients were found. The database is already clean.', 'aplext-db-pulse' ); ?></p>
	</div>
<?php endif; ?>

<!-- ── Metrics Table ──────────────────────────────────────────────────── -->
<table class="widefat fixed striped db-pulse-table" role="grid">
	<thead>
		<tr>
			<th scope="col" style="width: 40%;"><?php esc_html_e( 'Metric', 'aplext-db-pulse' ); ?></th>
			<th scope="col" style="width: 30%;"><?php esc_html_e( 'Current Value', 'aplext-db-pulse' ); ?></th>
			<th scope="col" style="width: 30%;"><?php esc_html_e( 'Status', 'aplext-db-pulse' ); ?></th>
		</tr>
	</thead>
	<tbody>

		<!-- Autoloaded Options Size -->
		<tr>
			<td>
				<strong><?php esc_html_e( 'Autoloaded Options Size', 'aplext-db-pulse' ); ?></strong>
				<span class="db-pulse-meta">
					<?php
					printf(
						/* translators: %s: threshold formatted string e.g. "800 KB" */
						esc_html__( 'Threshold: %s', 'aplext-db-pulse' ),
						esc_html( size_format( $autoload['threshold'], 0 ) )
					);
					?>
				</span>
			</td>
			<td><code><?php echo esc_html( $autoload['formatted'] ); ?></code></td>
			<td>
				<?php if ( 'good' === $autoload['status'] ) : ?>
					<span class="db-pulse-badge db-pulse-badge--good">
						✔ <?php esc_html_e( 'Healthy', 'aplext-db-pulse' ); ?>
					</span>
				<?php else : ?>
					<span class="db-pulse-badge db-pulse-badge--warning">
						⚠ <?php esc_html_e( 'High Bloat', 'aplext-db-pulse' ); ?>
					</span>
				<?php endif; ?>
			</td>
		</tr>

		<!-- Expired Transients -->
		<tr>
			<td>
				<strong><?php esc_html_e( 'Expired Transients', 'aplext-db-pulse' ); ?></strong>
				<?php if ( false !== $last_cron_run ) : ?>
					<span class="db-pulse-meta">
						<?php
						printf(
							/* translators: 1: human-readable time difference, 2: number of deleted transients */
							esc_html__( 'Auto-cleaned %1$s ago (%2$d removed)', 'aplext-db-pulse' ),
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
						<input type="hidden" name="action" value="aplext_dbpulse_purge_transients" />
						<?php wp_nonce_field( 'aplext_dbpulse_purge_action', 'aplext_dbpulse_nonce' ); ?>
						<button type="submit" class="button button-secondary db-pulse-purge-btn">
							🗑 <?php esc_html_e( 'Purge Now', 'aplext-db-pulse' ); ?>
						</button>
					</form>
				<?php else : ?>
					<span class="db-pulse-badge db-pulse-badge--good">
						✔ <?php esc_html_e( 'Clean', 'aplext-db-pulse' ); ?>
					</span>
				<?php endif; ?>
			</td>
		</tr>

		<!-- Page Queries -->
		<tr>
			<td><strong><?php esc_html_e( 'Page Queries (this request)', 'aplext-db-pulse' ); ?></strong></td>
			<td><code><?php echo esc_html( (string) $query_stats['count'] ); ?></code></td>
			<td>—</td>
		</tr>

		<!-- Total Query Time -->
		<tr>
			<td><strong><?php esc_html_e( 'Total Query Time (this request)', 'aplext-db-pulse' ); ?></strong></td>
			<td>
				<?php if ( $query_stats['savequeries_enabled'] ) : ?>
					<code><?php echo esc_html( $query_stats['total_time'] ); ?> ms</code>
				<?php else : ?>
					<em class="db-pulse-unavailable">
						<?php esc_html_e( 'Unavailable — SAVEQUERIES not enabled', 'aplext-db-pulse' ); ?>
					</em>
				<?php endif; ?>
			</td>
			<td>—</td>
		</tr>

	</tbody>
</table>

<!-- ── Slow Query Details ─────────────────────────────────────────────── -->
<?php if ( $query_stats['savequeries_enabled'] ) : ?>
	<div class="db-pulse-section">
		<?php if ( ! empty( $query_stats['slow_queries'] ) ) : ?>
			<details class="db-pulse-slow-queries">
				<summary class="db-pulse-slow-queries__toggle">
					<span class="db-pulse-badge db-pulse-badge--warning" style="margin-right:8px;">
						<?php echo esc_html( (string) count( $query_stats['slow_queries'] ) ); ?>
					</span>
					<?php
					printf(
						/* translators: %d: slow query count */
						esc_html( _n( '%d Slow Query Detected', '%d Slow Queries Detected', count( $query_stats['slow_queries'] ), 'aplext-db-pulse' ) ),
						count( $query_stats['slow_queries'] )
					);
					printf(
						/* translators: %d: configured threshold in milliseconds */
						esc_html__( ' (threshold: %d ms)', 'aplext-db-pulse' ),
						$slow_query_ms
					);
					?>
				</summary>
				<div class="db-pulse-slow-queries__body">
					<?php foreach ( $query_stats['slow_queries'] as $index => $sq ) : ?>
						<div class="db-pulse-query-item">
							<div class="db-pulse-query-item__header">
								<span class="db-pulse-badge db-pulse-badge--warning">
									<?php echo esc_html( $sq['time'] ); ?>
								</span>
								<span class="db-pulse-query-item__num">
									#<?php echo esc_html( (string) ( $index + 1 ) ); ?>
								</span>
							</div>
							<pre class="db-pulse-query-item__sql"><?php echo esc_html( $sq['sql'] ); ?></pre>
							<?php if ( ! empty( $sq['callers'] ) ) : ?>
								<p class="db-pulse-query-item__callers">
									<strong><?php esc_html_e( 'Called by:', 'aplext-db-pulse' ); ?></strong>
									<?php echo esc_html( $sq['callers'] ); ?>
								</p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</details>
		<?php else : ?>
			<div class="db-pulse-no-slow">
				<span class="db-pulse-badge db-pulse-badge--good">✔</span>
				<?php
				printf(
					/* translators: %d: configured threshold in milliseconds */
					esc_html__( 'No slow queries detected on this request (threshold: %d ms).', 'aplext-db-pulse' ),
					$slow_query_ms
				);
				?>
			</div>
		<?php endif; ?>
	</div>
<?php else : ?>
	<div class="db-pulse-section db-pulse-savequeries-notice">
		<span class="db-pulse-badge db-pulse-badge--neutral">ℹ</span>
		<?php esc_html_e( 'Slow query analysis is disabled. To enable it, add the following to your ', 'aplext-db-pulse' ); ?>
		<code>wp-config.php</code>:
		<pre class="db-pulse-code-hint">define( 'SAVEQUERIES', true );</pre>
	</div>
<?php endif; ?>

</div><!-- .db-pulse-wrap -->
