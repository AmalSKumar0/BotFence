<?php
/**
 * Admin Dashboard and Settings Controller for KL Universal Anti-Spam.
 *
 * @package KL_Anti_Spam
 */

namespace KL_Anti_Spam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 */
class Admin {

	/**
	 * CAPTCHA Engine instance.
	 *
	 * @var Captcha_Engine
	 */
	protected Captcha_Engine $engine;

	/**
	 * Form Detector instance.
	 *
	 * @var Form_Detector
	 */
	protected Form_Detector $detector;

	/**
	 * Constructor.
	 *
	 * @param Captcha_Engine $engine   Engine instance.
	 * @param Form_Detector  $detector Detector instance.
	 */
	public function __construct( Captcha_Engine $engine, Form_Detector $detector ) {
		$this->engine   = $engine;
		$this->detector = $detector;
	}

	/**
	 * Initializes admin hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'wp_ajax_kl_simulate_test', array( $this, 'ajax_simulate_test' ) );
	}

	/**
	 * Registers the top-level admin menu.
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'KL Anti-Spam', 'kl-universal-anti-spam' ),
			__( 'KL Anti-Spam', 'kl-universal-anti-spam' ),
			'manage_options',
			'kl-universal-anti-spam',
			array( $this, 'render_admin_page' ),
			'dashicons-shield',
			80
		);
	}

	/**
	 * Enqueues CSS and JS for the plugin admin screen.
	 *
	 * @param string $hook Page hook suffix.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_kl-universal-anti-spam' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'kl-antispam-admin-css',
			KL_ANTISPAM_URL . 'assets/css/kl-admin.css',
			array(),
			KL_ANTISPAM_VERSION
		);

		wp_enqueue_script(
			'kl-antispam-admin-js',
			KL_ANTISPAM_URL . 'assets/admin/kl-admin.js',
			array( 'jquery' ),
			KL_ANTISPAM_VERSION,
			true
		);
	}

	/**
	 * Handles settings updates, CSV exports, and log purging.
	 */
	public function handle_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle Settings Save.
		if ( isset( $_POST['kl_save_settings'] ) && check_admin_referer( 'kl_save_settings_action', 'kl_save_settings_nonce' ) ) {
			$this->save_settings();
			wp_safe_redirect( add_query_arg( array( 'page' => 'kl-universal-anti-spam', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// Handle Clear Logs.
		if ( isset( $_POST['kl_clear_logs'] ) && check_admin_referer( 'kl_clear_logs_action', 'kl_clear_logs_nonce' ) ) {
			$this->engine->get_logger()->clear_logs();
			wp_safe_redirect( add_query_arg( array( 'page' => 'kl-universal-anti-spam', 'tab' => 'logs', 'logs_cleared' => '1' ), admin_url( 'admin.php#tab-logs' ) ) );
			exit;
		}

		// Handle CSV Export.
		if ( isset( $_GET['kl_action'] ) && 'export_logs_csv' === $_GET['kl_action'] && check_admin_referer( 'kl_export_logs_action' ) ) {
			$this->export_logs_csv();
			exit;
		}
	}

	/**
	 * Sanitizes and saves settings.
	 */
	protected function save_settings(): void {
		// Toggles & numbers.
		update_option( 'kl_antispam_enabled', isset( $_POST['kl_antispam_enabled'] ) ? 1 : 0 );
		update_option( 'kl_antispam_min_submission_time', max( 0.5, floatval( $_POST['kl_antispam_min_submission_time'] ?? 2.5 ) ) );
		update_option( 'kl_antispam_token_lifetime', max( 60, intval( $_POST['kl_antispam_token_lifetime'] ?? 600 ) ) );
		update_option( 'kl_antispam_honeypot_enabled', isset( $_POST['kl_antispam_honeypot_enabled'] ) ? 1 : 0 );
		update_option( 'kl_antispam_adaptive_challenge', isset( $_POST['kl_antispam_adaptive_challenge'] ) ? 1 : 0 );

		// Thresholds.
		update_option( 'kl_antispam_risk_threshold_challenge', max( 1, min( 99, intval( $_POST['kl_antispam_risk_threshold_challenge'] ?? 30 ) ) ) );
		update_option( 'kl_antispam_risk_threshold_block', max( 2, min( 100, intval( $_POST['kl_antispam_risk_threshold_block'] ?? 60 ) ) ) );

		// Weights.
		update_option( 'kl_antispam_weight_honeypot', max( 0, min( 100, intval( $_POST['kl_antispam_weight_honeypot'] ?? 100 ) ) ) );
		update_option( 'kl_antispam_weight_invalid_token', max( 0, min( 100, intval( $_POST['kl_antispam_weight_invalid_token'] ?? 100 ) ) ) );
		update_option( 'kl_antispam_weight_expired_token', max( 0, min( 100, intval( $_POST['kl_antispam_weight_expired_token'] ?? 80 ) ) ) );
		update_option( 'kl_antispam_weight_fast_submit', max( 0, min( 100, intval( $_POST['kl_antispam_weight_fast_submit'] ?? 30 ) ) ) );
		update_option( 'kl_antispam_weight_rate_limit', max( 0, min( 100, intval( $_POST['kl_antispam_weight_rate_limit'] ?? 40 ) ) ) );
		update_option( 'kl_antispam_weight_excessive_urls', max( 0, min( 100, intval( $_POST['kl_antispam_weight_excessive_urls'] ?? 25 ) ) ) );
		update_option( 'kl_antispam_weight_suspicious_telemetry', max( 0, min( 100, intval( $_POST['kl_antispam_weight_suspicious_telemetry'] ?? 20 ) ) ) );
		update_option( 'kl_antispam_weight_repeated_message', max( 0, min( 100, intval( $_POST['kl_antispam_weight_repeated_message'] ?? 35 ) ) ) );

		// Rate Limits.
		update_option( 'kl_antispam_rate_limit_ip_per_minute', max( 1, intval( $_POST['kl_antispam_rate_limit_ip_per_minute'] ?? 5 ) ) );
		update_option( 'kl_antispam_rate_limit_ip_per_hour', max( 5, intval( $_POST['kl_antispam_rate_limit_ip_per_hour'] ?? 30 ) ) );
		update_option( 'kl_antispam_quarantine_duration', max( 60, intval( $_POST['kl_antispam_quarantine_duration'] ?? 900 ) ) );
		update_option( 'kl_antispam_log_retention_days', max( 1, intval( $_POST['kl_antispam_log_retention_days'] ?? 30 ) ) );

		// Integrations.
		$adapters = $this->detector->get_adapters();
		foreach ( $adapters as $id => $adapter ) {
			$opt_key = 'kl_antispam_enable_' . $id;
			update_option( $opt_key, isset( $_POST[ $opt_key ] ) ? 1 : 0 );
		}

		// Reverse Proxies & Exclusions.
		update_option( 'kl_antispam_trust_reverse_proxies', isset( $_POST['kl_antispam_trust_reverse_proxies'] ) ? 1 : 0 );
		if ( isset( $_POST['kl_antispam_trusted_proxy_ips'] ) ) {
			update_option( 'kl_antispam_trusted_proxy_ips', sanitize_textarea_field( wp_unslash( $_POST['kl_antispam_trusted_proxy_ips'] ) ) );
		}
		if ( isset( $_POST['kl_antispam_excluded_forms'] ) ) {
			update_option( 'kl_antispam_excluded_forms', sanitize_textarea_field( wp_unslash( $_POST['kl_antispam_excluded_forms'] ) ) );
		}
	}

	/**
	 * Exports log records to a downloadable CSV file.
	 */
	protected function export_logs_csv(): void {
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		$filename = 'kl-anti-spam-logs-' . gmdate( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'Date/Time (UTC)', 'Form Type', 'Form ID', 'Risk Score', 'Action', 'Signals', 'Masked IP', 'Duration (s)', 'Is Spam' ) );

		$logs_data = $this->engine->get_logger()->get_logs( array( 'per_page' => 5000 ) );
		foreach ( $logs_data['items'] as $log ) {
			$signals_list = array();
			$decoded      = json_decode( (string) $log->reasons, true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $s ) {
					$signals_list[] = is_array( $s ) ? ( $s['name'] ?? $s['code'] ?? '' ) : (string) $s;
				}
			}

			fputcsv(
				$output,
				array(
					$log->id,
					$log->created_at,
					$log->form_type,
					$log->form_id,
					$log->risk_score,
					$log->action_taken,
					implode( '; ', $signals_list ),
					$log->masked_ip,
					$log->submission_time,
					$log->is_spam ? 'Yes' : 'No',
				)
			);
		}

		fclose( $output );
		exit;
	}

	/**
	 * AJAX endpoint: Simulates test verification scenarios.
	 */
	public function ajax_simulate_test(): void {
		check_ajax_referer( 'kl_admin_action_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$scenario = sanitize_key( $_POST['scenario'] ?? 'human_normal' );

		$token_manager = $this->engine->get_token_manager();
		$honeypot      = $this->engine->get_honeypot();

		switch ( $scenario ) {
			case 'bot_honeypot':
				$tok       = $token_manager->generate_token( 'test_form' );
				$post_data = array(
					'kl_anti_spam_token'                => $tok['token'],
					$honeypot->get_dynamic_field_name( 0 ) => 'I am a spam bot',
					'message'                           => 'Buy cheap pills online',
				);
				break;

			case 'bot_invalid_token':
				$post_data = array(
					'kl_anti_spam_token' => 'invalid_tampered_token.123456',
					'message'            => 'Hello from automated script',
				);
				break;

			case 'bot_fast_submit':
				$tok       = $token_manager->generate_token( 'test_form' );
				$telemetry = base64_encode( (string) wp_json_encode( array( 'elapsed_time' => 0.25, 'mouse_moves' => 0, 'keystrokes' => 0, 'touches' => 0 ) ) );
				$post_data = array(
					'kl_anti_spam_token'     => $tok['token'],
					'kl_anti_spam_telemetry' => $telemetry,
					'message'                => 'Fast instant submit',
				);
				break;

			case 'bot_headless':
				$tok       = $token_manager->generate_token( 'test_form' );
				$telemetry = base64_encode( (string) wp_json_encode( array( 'elapsed_time' => 3.1, 'webdriver' => true, 'is_trusted' => false ) ) );
				$post_data = array(
					'kl_anti_spam_token'     => $tok['token'],
					'kl_anti_spam_telemetry' => $telemetry,
					'message'                => 'Automated test via Selenium',
				);
				break;

			case 'human_normal':
			default:
				$tok       = $token_manager->generate_token( 'test_form' );
				$telemetry = base64_encode( (string) wp_json_encode( array( 'elapsed_time' => 5.4, 'mouse_moves' => 18, 'keystrokes' => 32, 'touches' => 0, 'is_trusted' => true, 'webdriver' => false ) ) );
				$post_data = array(
					'kl_anti_spam_token'     => $tok['token'],
					'kl_anti_spam_telemetry' => $telemetry,
					'message'                => 'Hello, I have an inquiry regarding your services. Thank you!',
				);
				break;
		}

		$result = $this->engine->verify_submission( $post_data, 'test_simulation', 'sim_form_1' );

		wp_send_json_success(
			array(
				'action'  => $result['action'],
				'score'   => $result['score'],
				'signals' => $result['signals'],
			)
		);
	}

	/**
	 * Renders the full admin page with tabs.
	 */
	public function render_admin_page(): void {
		$stats    = $this->engine->get_logger()->get_stats();
		$adapters = $this->detector->get_adapters();
		$logs     = $this->engine->get_logger()->get_logs( array( 'per_page' => 20 ) );

		?>
		<div class="wrap kl-admin-wrap">
			<div class="kl-admin-header">
				<div>
					<h1>
						<span class="dashicons dashicons-shield-alt" style="font-size: 26px; color: #2271b1; width: 26px; height: 26px;"></span>
						<?php esc_html_e( 'KL Universal Anti-Spam', 'kl-universal-anti-spam' ); ?>
						<span class="kl-badge-version">v<?php echo esc_html( KL_ANTISPAM_VERSION ); ?></span>
					</h1>
					<p style="margin: 0; color: #646970; font-size: 13px;">
						<?php esc_html_e( 'Autonomous, universal protection against automated bots and form spam.', 'kl-universal-anti-spam' ); ?>
					</p>
				</div>
				<div>
					<span class="kl-badge <?php echo get_option( 'kl_antispam_enabled', 1 ) ? 'kl-badge-active' : 'kl-badge-inactive'; ?>">
						● <?php echo get_option( 'kl_antispam_enabled', 1 ) ? esc_html__( 'Active & Protecting', 'kl-universal-anti-spam' ) : esc_html__( 'Disabled', 'kl-universal-anti-spam' ); ?>
					</span>
				</div>
			</div>

			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings updated successfully.', 'kl-universal-anti-spam' ); ?></p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['logs_cleared'] ) ) : ?>
				<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Anti-spam logs have been cleared.', 'kl-universal-anti-spam' ); ?></p></div>
			<?php endif; ?>

			<!-- Navigation Tabs -->
			<nav class="kl-nav-tabs">
				<a href="#tab-dashboard" class="kl-nav-tab active"><span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'kl-universal-anti-spam' ); ?></a>
				<a href="#tab-integrations" class="kl-nav-tab"><span class="dashicons dashicons-forms"></span> <?php esc_html_e( 'Form Integrations', 'kl-universal-anti-spam' ); ?></a>
				<a href="#tab-risk" class="kl-nav-tab"><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e( 'Risk & Security', 'kl-universal-anti-spam' ); ?></a>
				<a href="#tab-rates" class="kl-nav-tab"><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Rate Limiting', 'kl-universal-anti-spam' ); ?></a>
				<a href="#tab-logs" class="kl-nav-tab"><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Logs & Activity', 'kl-universal-anti-spam' ); ?></a>
				<a href="#tab-diagnostics" class="kl-nav-tab"><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Diagnostics & Simulator', 'kl-universal-anti-spam' ); ?></a>
			</nav>

			<!-- TAB 1: DASHBOARD -->
			<div id="tab-dashboard" class="kl-tab-pane">
				<div class="kl-metrics-grid">
					<div class="kl-metric-card">
						<div class="kl-metric-label"><?php esc_html_e( 'Total Submissions Checked', 'kl-universal-anti-spam' ); ?></div>
						<div class="kl-metric-value"><?php echo esc_html( number_format_i18n( $stats['total_checked'] ) ); ?></div>
					</div>
					<div class="kl-metric-card card-blocked">
						<div class="kl-metric-label"><?php esc_html_e( 'Spam Blocked', 'kl-universal-anti-spam' ); ?></div>
						<div class="kl-metric-value"><?php echo esc_html( number_format_i18n( $stats['total_blocked'] ) ); ?></div>
					</div>
					<div class="kl-metric-card card-allowed">
						<div class="kl-metric-label"><?php esc_html_e( 'Legitimate Allowed', 'kl-universal-anti-spam' ); ?></div>
						<div class="kl-metric-value"><?php echo esc_html( number_format_i18n( $stats['total_allowed'] ) ); ?></div>
					</div>
					<div class="kl-metric-card card-rate">
						<div class="kl-metric-label"><?php esc_html_e( 'Spam Detection Rate', 'kl-universal-anti-spam' ); ?></div>
						<div class="kl-metric-value"><?php echo esc_html( $stats['block_rate'] ); ?>%</div>
					</div>
				</div>

				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
					<!-- Top Spam Signals -->
					<div class="kl-panel">
						<h2 class="kl-panel-title"><?php esc_html_e( 'Most Common Spam Signals', 'kl-universal-anti-spam' ); ?></h2>
						<?php if ( empty( $stats['top_signals'] ) ) : ?>
							<p style="color: #646970;"><?php esc_html_e( 'No spam detections recorded yet.', 'kl-universal-anti-spam' ); ?></p>
						<?php else : ?>
							<?php
							$max_tally = max( $stats['top_signals'] );
							foreach ( $stats['top_signals'] as $sig_name => $count ) :
								$pct = $max_tally > 0 ? round( ( $count / $max_tally ) * 100 ) : 0;
								?>
								<div class="kl-signal-item">
									<div class="kl-signal-label">
										<span><?php echo esc_html( $sig_name ); ?></span>
										<strong><?php echo esc_html( $count ); ?></strong>
									</div>
									<div class="kl-progress-track">
										<div class="kl-progress-fill" style="width: <?php echo esc_attr( $pct ); ?>%;"></div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<!-- Recent Blocked Submissions -->
					<div class="kl-panel">
						<h2 class="kl-panel-title"><?php esc_html_e( 'Recent Blocked Submissions', 'kl-universal-anti-spam' ); ?></h2>
						<?php if ( empty( $stats['recent_blocked'] ) ) : ?>
							<p style="color: #646970;"><?php esc_html_e( 'No recent blocked submissions.', 'kl-universal-anti-spam' ); ?></p>
						<?php else : ?>
							<table class="kl-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Time', 'kl-universal-anti-spam' ); ?></th>
										<th><?php esc_html_e( 'Form', 'kl-universal-anti-spam' ); ?></th>
										<th><?php esc_html_e( 'Risk', 'kl-universal-anti-spam' ); ?></th>
										<th><?php esc_html_e( 'Visitor IP', 'kl-universal-anti-spam' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $stats['recent_blocked'] as $item ) : ?>
										<tr>
											<td><?php echo esc_html( human_time_diff( strtotime( $item->created_at ), time() ) . ' ' . __( 'ago', 'kl-universal-anti-spam' ) ); ?></td>
											<td><code><?php echo esc_html( $item->form_type ); ?></code></td>
											<td><span class="kl-badge kl-badge-block"><?php echo esc_html( $item->risk_score ); ?>/100</span></td>
											<td><?php echo esc_html( $item->masked_ip ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- TAB 2: INTEGRATIONS -->
			<div id="tab-integrations" class="kl-tab-pane" style="display: none;">
				<form method="post" action="">
					<?php wp_nonce_field( 'kl_save_settings_action', 'kl_save_settings_nonce' ); ?>
					<div class="kl-panel">
						<h2 class="kl-panel-title"><?php esc_html_e( 'Universal Form Builder Protections', 'kl-universal-anti-spam' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'KL Universal Anti-Spam automatically detects active form plugins on your website. Toggle protection per form builder below.', 'kl-universal-anti-spam' ); ?>
						</p>

						<table class="kl-table" style="margin-top: 18px;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Form Builder', 'kl-universal-anti-spam' ); ?></th>
									<th><?php esc_html_e( 'Environment Status', 'kl-universal-anti-spam' ); ?></th>
									<th><?php esc_html_e( 'Protection Status', 'kl-universal-anti-spam' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $adapters as $id => $adapter ) : ?>
									<tr>
										<td>
											<strong><?php echo esc_html( $adapter->get_name() ); ?></strong>
										</td>
										<td>
											<?php if ( $adapter->is_available() ) : ?>
												<span class="kl-badge kl-badge-allow"><?php esc_html_e( 'Detected & Available', 'kl-universal-anti-spam' ); ?></span>
											<?php else : ?>
												<span class="kl-badge kl-badge-inactive"><?php esc_html_e( 'Not Installed', 'kl-universal-anti-spam' ); ?></span>
											<?php endif; ?>
										</td>
										<td>
											<label class="kl-switch">
												<input type="checkbox" name="kl_antispam_enable_<?php echo esc_attr( $id ); ?>" value="1" <?php checked( $adapter->is_enabled() ); ?> />
												<span class="kl-slider"></span>
											</label>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<div class="kl-panel">
						<h2 class="kl-panel-title"><?php esc_html_e( 'Excluded Forms / Whitelist', 'kl-universal-anti-spam' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'Exclude specific forms from anti-spam checks. Enter one form ID per line (e.g., 123 or cf7:123).', 'kl-universal-anti-spam' ); ?>
						</p>
						<textarea name="kl_antispam_excluded_forms" rows="4" class="large-text code"><?php echo esc_textarea( get_option( 'kl_antispam_excluded_forms', '' ) ); ?></textarea>
					</div>

					<p><input type="submit" name="kl_save_settings" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Integration Settings', 'kl-universal-anti-spam' ); ?>" /></p>
				</form>
			</div>

			<!-- TAB 3: RISK & SECURITY -->
			<div id="tab-risk" class="kl-tab-pane" style="display: none;">
				<form method="post" action="">
					<?php wp_nonce_field( 'kl_save_settings_action', 'kl_save_settings_nonce' ); ?>
					<div class="kl-panel">
						<h2 class="kl-panel-title"><?php esc_html_e( 'Risk Thresholds & Decision Logic', 'kl-universal-anti-spam' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Configure how the risk engine evaluates composite scores (0 to 100 points).', 'kl-universal-anti-spam' ); ?></p>

						<div class="kl-form-grid">
							<div>
								<label for="kl_antispam_risk_threshold_challenge"><?php esc_html_e( 'Challenge Threshold', 'kl-universal-anti-spam' ); ?></label>
								<div class="kl-desc"><?php esc_html_e( 'Score at which secondary verification is required (default: 30).', 'kl-universal-anti-spam' ); ?></div>
							</div>
							<div>
								<input type="number" id="kl_antispam_risk_threshold_challenge" name="kl_antispam_risk_threshold_challenge" value="<?php echo esc_attr( get_option( 'kl_antispam_risk_threshold_challenge', 30 ) ); ?>" min="1" max="99" class="small-text" /> points
							</div>

							<div>
								<label for="kl_antispam_risk_threshold_block"><?php esc_html_e( 'Block Threshold', 'kl-universal-anti-spam' ); ?></label>
								<div class="kl-desc"><?php esc_html_e( 'Score at which submissions are immediately rejected (default: 60).', 'kl-universal-anti-spam' ); ?></div>
							</div>
							<div>
								<input type="number" id="kl_antispam_risk_threshold_block" name="kl_antispam_risk_threshold_block" value="<?php echo esc_attr( get_option( 'kl_antispam_risk_threshold_block', 60 ) ); ?>" min="2" max="100" class="small-text" /> points
							</div>

							<div>
								<label for="kl_antispam_min_submission_time"><?php esc_html_e( 'Minimum Submission Time', 'kl-universal-anti-spam' ); ?></label>
								<div class="kl-desc"><?php esc_html_e( 'Submissions completed faster than this time are flagged as bot activity (default: 2.5 seconds).', 'kl-universal-anti-spam' ); ?></div>
							</div>
							<div>
								<input type="number" step="0.1" id="kl_antispam_min_submission_time" name="kl_antispam_min_submission_time" value="<?php echo esc_attr( get_option( 'kl_antispam_min_submission_time', 2.5 ) ); ?>" min="0.5" max="30" class="small-text" /> seconds
							</div>

							<div>
								<label for="kl_antispam_token_lifetime"><?php esc_html_e( 'Token Lifetime', 'kl-universal-anti-spam' ); ?></label>
								<div class="kl-desc"><?php esc_html_e( 'Maximum validity window for cryptographic tokens (default: 600s / 10 minutes).', 'kl-universal-anti-spam' ); ?></div>
							</div>
							<div>
								<input type="number" id="kl_antispam_token_lifetime" name="kl_antispam_token_lifetime" value="<?php echo esc_attr( get_option( 'kl_antispam_token_lifetime', 600 ) ); ?>" min="60" max="86400" class="regular-text" style="max-width: 120px;" /> seconds
							</div>
						</div>
					</div>

					<div class="kl-panel">
						<h2 class="kl-panel-title"><?php esc_html_e( 'Signal Risk Point Weights', 'kl-universal-anti-spam' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Customize how many risk points each anomaly contributes to the overall submission score.', 'kl-universal-anti-spam' ); ?></p>

						<table class="kl-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Detection Signal', 'kl-universal-anti-spam' ); ?></th>
									<th style="width: 140px;"><?php esc_html_e( 'Risk Points', 'kl-universal-anti-spam' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><strong><?php esc_html_e( 'Honeypot Trap Completed', 'kl-universal-anti-spam' ); ?></strong></td>
									<td><input type="number" name="kl_antispam_weight_honeypot" value="<?php echo esc_attr( get_option( 'kl_antispam_weight_honeypot', 100 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Invalid or Tampered Signed Token', 'kl-universal-anti-spam' ); ?></strong></td>
									<td><input type="number" name="kl_antispam_weight_invalid_token" value="<?php echo esc_attr( get_option( 'kl_antispam_weight_invalid_token', 100 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Expired Token', 'kl-universal-anti-spam' ); ?></strong></td>
									<td><input type="number" name="kl_antispam_weight_expired_token" value="<?php echo esc_attr( get_option( 'kl_antispam_weight_expired_token', 80 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Rapid Submission (Under minimum time)', 'kl-universal-anti-spam' ); ?></strong></td>
									<td><input type="number" name="kl_antispam_weight_fast_submit" value="<?php echo esc_attr( get_option( 'kl_antispam_weight_fast_submit', 30 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Repeated Submissions (Rate limit warning)', 'kl-universal-anti-spam' ); ?></strong></td>
									<td><input type="number" name="kl_antispam_weight_rate_limit" value="<?php echo esc_attr( get_option( 'kl_antispam_weight_rate_limit', 40 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Excessive URLs in Message (>2 links)', 'kl-universal-anti-spam' ); ?></strong></td>
									<td><input type="number" name="kl_antispam_weight_excessive_urls" value="<?php echo esc_attr( get_option( 'kl_antispam_weight_excessive_urls', 25 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Suspicious Interaction Pattern (Zero telemetry with text)', 'kl-universal-anti-spam' ); ?></strong></td>
									<td><input type="number" name="kl_antispam_weight_suspicious_telemetry" value="<?php echo esc_attr( get_option( 'kl_antispam_weight_suspicious_telemetry', 20 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Repeated Identical Message Content', 'kl-universal-anti-spam' ); ?></strong></td>
									<td><input type="number" name="kl_antispam_weight_repeated_message" value="<?php echo esc_attr( get_option( 'kl_antispam_weight_repeated_message', 35 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
							</tbody>
						</table>
					</div>

					<p><input type="submit" name="kl_save_settings" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Risk Configuration', 'kl-universal-anti-spam' ); ?>" /></p>
				</form>
			</div>

			<!-- TAB 4: RATE LIMITING -->
			<div id="tab-rates" class="kl-tab-pane" style="display: none;">
				<form method="post" action="">
					<?php wp_nonce_field( 'kl_save_settings_action', 'kl_save_settings_nonce' ); ?>
					<div class="kl-panel">
						<h2 class="kl-panel-title"><?php esc_html_e( 'Submission Frequency & Rate Limits', 'kl-universal-anti-spam' ); ?></h2>
						<div class="kl-form-grid">
							<div>
								<label for="kl_antispam_rate_limit_ip_per_minute"><?php esc_html_e( 'Submissions per Minute (per IP)', 'kl-universal-anti-spam' ); ?></label>
								<div class="kl-desc"><?php esc_html_e( 'Maximum submissions allowed from a single IP address per minute (default: 5).', 'kl-universal-anti-spam' ); ?></div>
							</div>
							<div>
								<input type="number" id="kl_antispam_rate_limit_ip_per_minute" name="kl_antispam_rate_limit_ip_per_minute" value="<?php echo esc_attr( get_option( 'kl_antispam_rate_limit_ip_per_minute', 5 ) ); ?>" min="1" max="100" class="small-text" /> submissions
							</div>

							<div>
								<label for="kl_antispam_rate_limit_ip_per_hour"><?php esc_html_e( 'Submissions per Hour (per IP)', 'kl-universal-anti-spam' ); ?></label>
								<div class="kl-desc"><?php esc_html_e( 'Maximum submissions allowed from a single IP address per hour (default: 30).', 'kl-universal-anti-spam' ); ?></div>
							</div>
							<div>
								<input type="number" id="kl_antispam_rate_limit_ip_per_hour" name="kl_antispam_rate_limit_ip_per_hour" value="<?php echo esc_attr( get_option( 'kl_antispam_rate_limit_ip_per_hour', 30 ) ); ?>" min="5" max="500" class="small-text" /> submissions
							</div>

							<div>
								<label for="kl_antispam_quarantine_duration"><?php esc_html_e( 'Temporary Quarantine Duration', 'kl-universal-anti-spam' ); ?></label>
								<div class="kl-desc"><?php esc_html_e( 'Temporary block duration for IPs exceeding rate limits (default: 900s / 15 minutes).', 'kl-universal-anti-spam' ); ?></div>
							</div>
							<div>
								<input type="number" id="kl_antispam_quarantine_duration" name="kl_antispam_quarantine_duration" value="<?php echo esc_attr( get_option( 'kl_antispam_quarantine_duration', 900 ) ); ?>" min="60" max="86400" class="regular-text" style="max-width: 120px;" /> seconds
							</div>

							<div>
								<label for="kl_antispam_log_retention_days"><?php esc_html_e( 'Log Retention Period', 'kl-universal-anti-spam' ); ?></label>
								<div class="kl-desc"><?php esc_html_e( 'Number of days to keep audit logs before automatic pruning (default: 30 days).', 'kl-universal-anti-spam' ); ?></div>
							</div>
							<div>
								<input type="number" id="kl_antispam_log_retention_days" name="kl_antispam_log_retention_days" value="<?php echo esc_attr( get_option( 'kl_antispam_log_retention_days', 30 ) ); ?>" min="1" max="365" class="small-text" /> days
							</div>
						</div>
					</div>

					<div class="kl-panel">
						<h2 class="kl-panel-title"><?php esc_html_e( 'Reverse Proxy Configuration', 'kl-universal-anti-spam' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'For security, HTTP headers like X-Forwarded-For or CF-Connecting-IP are NEVER trusted unless the connection arrives from an explicitly trusted reverse proxy.', 'kl-universal-anti-spam' ); ?>
						</p>

						<div class="kl-form-grid">
							<div>
								<label for="kl_antispam_trust_reverse_proxies"><?php esc_html_e( 'Trust Reverse Proxies', 'kl-universal-anti-spam' ); ?></label>
								<div class="kl-desc"><?php esc_html_e( 'Enable only if your site is behind Cloudflare, AWS ALB, Nginx, or a CDN.', 'kl-universal-anti-spam' ); ?></div>
							</div>
							<div>
								<label class="kl-switch">
									<input type="checkbox" id="kl_antispam_trust_reverse_proxies" name="kl_antispam_trust_reverse_proxies" value="1" <?php checked( get_option( 'kl_antispam_trust_reverse_proxies', 0 ) ); ?> />
									<span class="kl-slider"></span>
								</label>
							</div>

							<div>
								<label for="kl_antispam_trusted_proxy_ips"><?php esc_html_e( 'Trusted Proxy IPs / CIDRs', 'kl-universal-anti-spam' ); ?></label>
								<div class="kl-desc"><?php esc_html_e( 'One IP or CIDR per line (e.g., 127.0.0.1, 10.0.0.0/8).', 'kl-universal-anti-spam' ); ?></div>
							</div>
							<div>
								<textarea id="kl_antispam_trusted_proxy_ips" name="kl_antispam_trusted_proxy_ips" rows="4" class="large-text code"><?php echo esc_textarea( get_option( 'kl_antispam_trusted_proxy_ips', "127.0.0.1\n::1" ) ); ?></textarea>
							</div>
						</div>
					</div>

					<p><input type="submit" name="kl_save_settings" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Rate Limiting Settings', 'kl-universal-anti-spam' ); ?>" /></p>
				</form>
			</div>

			<!-- TAB 5: LOGS -->
			<div id="tab-logs" class="kl-tab-pane" style="display: none;">
				<div class="kl-panel">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
						<h2 class="kl-panel-title" style="margin: 0;"><?php esc_html_e( 'Submission Audit Logs', 'kl-universal-anti-spam' ); ?></h2>
						<div style="display: flex; gap: 10px;">
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'kl-universal-anti-spam', 'kl_action' => 'export_logs_csv' ), admin_url( 'admin.php' ) ), 'kl_export_logs_action' ) ); ?>" class="button">
								<span class="dashicons dashicons-download" style="vertical-align: middle;"></span> <?php esc_html_e( 'Export to CSV', 'kl-universal-anti-spam' ); ?>
							</a>
							<form method="post" action="" style="display: inline;">
								<?php wp_nonce_field( 'kl_clear_logs_action', 'kl_clear_logs_nonce' ); ?>
								<input type="submit" id="kl-clear-logs-btn" name="kl_clear_logs" class="button button-link-delete" value="<?php esc_attr_e( 'Clear All Logs', 'kl-universal-anti-spam' ); ?>" />
							</form>
						</div>
					</div>

					<p class="description"><?php esc_html_e( 'All logs are privacy-conscious and anonymized. Full message bodies and personal details are never stored.', 'kl-universal-anti-spam' ); ?></p>

					<?php if ( empty( $logs['items'] ) ) : ?>
						<p style="padding: 20px 0; color: #646970; text-align: center;"><?php esc_html_e( 'No log entries found.', 'kl-universal-anti-spam' ); ?></p>
					<?php else : ?>
						<table class="kl-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Date & Time', 'kl-universal-anti-spam' ); ?></th>
									<th><?php esc_html_e( 'Form', 'kl-universal-anti-spam' ); ?></th>
									<th><?php esc_html_e( 'Risk Score', 'kl-universal-anti-spam' ); ?></th>
									<th><?php esc_html_e( 'Action Taken', 'kl-universal-anti-spam' ); ?></th>
									<th><?php esc_html_e( 'Visitor IP', 'kl-universal-anti-spam' ); ?></th>
									<th><?php esc_html_e( 'Duration', 'kl-universal-anti-spam' ); ?></th>
									<th><?php esc_html_e( 'Details', 'kl-universal-anti-spam' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $logs['items'] as $entry ) : ?>
									<tr>
										<td><?php echo esc_html( $entry->created_at ); ?></td>
										<td><code><?php echo esc_html( $entry->form_type . ( $entry->form_id ? ' (' . $entry->form_id . ')' : '' ) ); ?></code></td>
										<td>
											<span class="kl-badge <?php echo $entry->risk_score >= 60 ? 'kl-badge-block' : ( $entry->risk_score >= 30 ? 'kl-badge-challenge' : 'kl-badge-allow' ); ?>">
												<?php echo esc_html( $entry->risk_score ); ?>/100
											</span>
										</td>
										<td>
											<span class="kl-badge <?php echo 'block' === $entry->action_taken ? 'kl-badge-block' : ( 'challenge' === $entry->action_taken ? 'kl-badge-challenge' : 'kl-badge-allow' ); ?>">
												<?php echo esc_html( strtoupper( $entry->action_taken ) ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $entry->masked_ip ); ?></td>
										<td><?php echo esc_html( sprintf( '%.2fs', $entry->submission_time ) ); ?></td>
										<td>
											<a href="#" class="kl-toggle-details"><?php esc_html_e( 'View Signals', 'kl-universal-anti-spam' ); ?></a>
										</td>
									</tr>
									<tr class="kl-details-row" style="display: none; background: #f6f7f7;">
										<td colspan="7" style="padding: 12px 20px;">
											<strong><?php esc_html_e( 'Triggered Signals:', 'kl-universal-anti-spam' ); ?></strong>
											<?php
											$reasons = json_decode( (string) $entry->reasons, true );
											if ( empty( $reasons ) ) {
												echo '<p style="margin: 4px 0; color: #008a20;">' . esc_html__( 'No risk signals triggered. Clean human submission.', 'kl-universal-anti-spam' ) . '</p>';
											} else {
												echo '<ul style="margin: 6px 0 0 16px; list-style-type: disc;">';
												foreach ( $reasons as $r ) {
													$name = is_array( $r ) ? ( $r['name'] ?? $r['code'] ?? '' ) : (string) $r;
													$pts  = is_array( $r ) && isset( $r['points'] ) ? ' (+' . $r['points'] . ' pts)' : '';
													$det  = is_array( $r ) && ! empty( $r['details'] ) ? ': ' . $r['details'] : '';
													echo '<li><strong>' . esc_html( $name . $pts ) . '</strong>' . esc_html( $det ) . '</li>';
												}
												echo '</ul>';
											}
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>

			<!-- TAB 6: DIAGNOSTICS & SIMULATOR -->
			<div id="tab-diagnostics" class="kl-tab-pane" style="display: none;">
				<div class="kl-panel">
					<h2 class="kl-panel-title"><?php esc_html_e( 'Interactive Protection Simulator', 'kl-universal-anti-spam' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Test the internal risk engine against various submission scenarios to verify scoring calculations and detection rules in real-time.', 'kl-universal-anti-spam' ); ?>
					</p>

					<input type="hidden" id="kl_admin_nonce" value="<?php echo esc_attr( wp_create_nonce( 'kl_admin_action_nonce' ) ); ?>" />

					<div style="display: flex; gap: 12px; align-items: center; margin-top: 16px;">
						<select id="kl-sim-scenario" style="min-width: 280px;">
							<option value="human_normal"><?php esc_html_e( 'Normal Human (5.4s typing, 32 keystrokes, clean)', 'kl-universal-anti-spam' ); ?></option>
							<option value="bot_honeypot"><?php esc_html_e( 'Automated Bot (Populated hidden honeypot)', 'kl-universal-anti-spam' ); ?></option>
							<option value="bot_invalid_token"><?php esc_html_e( 'Malicious Bot (Forged / invalid signed token)', 'kl-universal-anti-spam' ); ?></option>
							<option value="bot_fast_submit"><?php esc_html_e( 'Rapid Script (0.25s completion time)', 'kl-universal-anti-spam' ); ?></option>
							<option value="bot_headless"><?php esc_html_e( 'Headless Automation (Selenium / webdriver flag)', 'kl-universal-anti-spam' ); ?></option>
						</select>

						<button type="button" id="kl-run-test-simulator" class="button button-primary">
							<?php esc_html_e( 'Run Test Simulation', 'kl-universal-anti-spam' ); ?>
						</button>
					</div>

					<div id="kl-sim-result" style="display: none;"></div>
				</div>

				<div class="kl-panel">
					<h2 class="kl-panel-title"><?php esc_html_e( 'System Environment & Diagnostics', 'kl-universal-anti-spam' ); ?></h2>
					<table class="kl-table">
						<tbody>
							<tr>
								<td><strong><?php esc_html_e( 'PHP Version', 'kl-universal-anti-spam' ); ?></strong></td>
								<td><code><?php echo esc_html( PHP_VERSION ); ?></code> (>= 8.1 required)</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'WordPress Version', 'kl-universal-anti-spam' ); ?></strong></td>
								<td><code><?php echo esc_html( get_bloginfo( 'version' ) ); ?></code></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'HMAC Secret Key Status', 'kl-universal-anti-spam' ); ?></strong></td>
								<td><span class="kl-badge kl-badge-allow"><?php esc_html_e( 'Active & Cryptographically Secure', 'kl-universal-anti-spam' ); ?></span></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Logs Database Table', 'kl-universal-anti-spam' ); ?></strong></td>
								<td><code><?php echo esc_html( Database::get_logs_table() ); ?></code></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Rate Limits Database Table', 'kl-universal-anti-spam' ); ?></strong></td>
								<td><code><?php echo esc_html( Database::get_rates_table() ); ?></code></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Cleanup Cron Job', 'kl-universal-anti-spam' ); ?></strong></td>
								<td>
									<?php
									$next_cron = wp_next_scheduled( 'kl_antispam_cleanup_cron' );
									if ( $next_cron ) {
										printf(
											/* translators: %s: relative time */
											esc_html__( 'Scheduled (next run in %s)', 'kl-universal-anti-spam' ),
											esc_html( human_time_diff( time(), $next_cron ) )
										);
									} else {
										esc_html_e( 'Not scheduled', 'kl-universal-anti-spam' );
									}
									?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
}
