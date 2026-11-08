<?php
/**
 * Admin Dashboard and Settings Controller for BotFence.
 *
 * @package BotFence
 */

namespace BotFence;

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
		add_action( 'wp_ajax_botfence_simulate_test', array( $this, 'ajax_simulate_test' ) );
		add_action( 'wp_ajax_kl_simulate_test', array( $this, 'ajax_simulate_test' ) );
		add_action( 'wp_ajax_botfence_refresh_stats', array( $this, 'ajax_refresh_stats' ) );
	}

	/**
	 * Registers the top-level admin menu.
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'BotFence', 'botfence' ),
			__( 'BotFence', 'botfence' ),
			'manage_options',
			'botfence',
			array( $this, 'render_admin_page' ),
			'dashicons-shield',
			80
		);

		// Backwards-compatibility submenu redirect.
		add_submenu_page(
			null,
			__( 'KL Anti-Spam', 'botfence' ),
			__( 'KL Anti-Spam', 'botfence' ),
			'manage_options',
			'kl-universal-anti-spam',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueues CSS and JS for the plugin admin screen.
	 *
	 * @param string $hook Page hook suffix.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'botfence' ) && ! str_contains( $hook, 'kl-universal-anti-spam' ) ) {
			return;
		}

		$css_url = file_exists( BOTFENCE_PATH . 'assets/css/botfence-admin.css' ) ? BOTFENCE_URL . 'assets/css/botfence-admin.css' : BOTFENCE_URL . 'assets/css/kl-admin.css';
		$js_url  = file_exists( BOTFENCE_PATH . 'assets/admin/botfence-admin.js' ) ? BOTFENCE_URL . 'assets/admin/botfence-admin.js' : BOTFENCE_URL . 'assets/admin/kl-admin.js';

		wp_enqueue_style(
			'botfence-admin-css',
			$css_url,
			array(),
			BOTFENCE_VERSION
		);

		wp_enqueue_script(
			'botfence-admin-js',
			$js_url,
			array( 'jquery' ),
			BOTFENCE_VERSION,
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
		if ( ( isset( $_POST['botfence_save_settings'] ) || isset( $_POST['kl_save_settings'] ) )
			&& ( check_admin_referer( 'botfence_save_settings_action', 'botfence_save_settings_nonce' ) || check_admin_referer( 'kl_save_settings_action', 'kl_save_settings_nonce' ) )
		) {
			$this->save_settings();
			wp_safe_redirect( add_query_arg( array( 'page' => 'botfence', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// Handle Clear Logs.
		if ( ( isset( $_POST['botfence_clear_logs'] ) || isset( $_POST['kl_clear_logs'] ) )
			&& ( check_admin_referer( 'botfence_clear_logs_action', 'botfence_clear_logs_nonce' ) || check_admin_referer( 'kl_clear_logs_action', 'kl_clear_logs_nonce' ) )
		) {
			$this->engine->get_logger()->clear_logs();
			wp_safe_redirect( add_query_arg( array( 'page' => 'botfence', 'tab' => 'logs', 'logs_cleared' => '1' ), admin_url( 'admin.php#tab-logs' ) ) );
			exit;
		}

		// Handle CSV Export.
		if ( isset( $_GET['botfence_action'] ) && 'export_logs_csv' === $_GET['botfence_action'] && ( check_admin_referer( 'botfence_export_logs_action' ) || check_admin_referer( 'kl_export_logs_action' ) ) ) {
			$this->export_logs_csv();
			exit;
		}
		if ( isset( $_GET['kl_action'] ) && 'export_logs_csv' === $_GET['kl_action'] && check_admin_referer( 'kl_export_logs_action' ) ) {
			$this->export_logs_csv();
			exit;
		}
	}

	/**
	 * Sanitizes and saves settings to both current and legacy option names.
	 */
	protected function save_settings(): void {
		$update_pair = function( $suffix, $val ) {
			update_option( 'botfence_' . $suffix, $val );
			update_option( 'kl_antispam_' . $suffix, $val );
		};

		// Toggles & numbers.
		$enabled = ( isset( $_POST['botfence_enabled'] ) || isset( $_POST['kl_antispam_enabled'] ) ) ? 1 : 0;
		$update_pair( 'enabled', $enabled );

		$min_time = max( 0.5, floatval( $_POST['botfence_min_submission_time'] ?? $_POST['kl_antispam_min_submission_time'] ?? 2.5 ) );
		$update_pair( 'min_submission_time', $min_time );

		$lifetime = max( 60, intval( $_POST['botfence_token_lifetime'] ?? $_POST['kl_antispam_token_lifetime'] ?? 600 ) );
		$update_pair( 'token_lifetime', $lifetime );

		$hp_enabled = ( isset( $_POST['botfence_honeypot_enabled'] ) || isset( $_POST['kl_antispam_honeypot_enabled'] ) ) ? 1 : 0;
		$update_pair( 'honeypot_enabled', $hp_enabled );

		$adaptive = ( isset( $_POST['botfence_adaptive_challenge'] ) || isset( $_POST['kl_antispam_adaptive_challenge'] ) ) ? 1 : 0;
		$update_pair( 'adaptive_challenge', $adaptive );

		// Thresholds.
		$th_challenge = max( 1, min( 99, intval( $_POST['botfence_risk_threshold_challenge'] ?? $_POST['kl_antispam_risk_threshold_challenge'] ?? 30 ) ) );
		$update_pair( 'risk_threshold_challenge', $th_challenge );

		$th_block = max( 2, min( 100, intval( $_POST['botfence_risk_threshold_block'] ?? $_POST['kl_antispam_risk_threshold_block'] ?? 60 ) ) );
		$update_pair( 'risk_threshold_block', $th_block );

		// Weights.
		$weights = array(
			'weight_honeypot'             => 100,
			'weight_invalid_token'        => 100,
			'weight_expired_token'        => 80,
			'weight_fast_submit'          => 30,
			'weight_rate_limit'           => 40,
			'weight_excessive_urls'       => 25,
			'weight_suspicious_telemetry' => 20,
			'weight_repeated_message'     => 35,
		);

		foreach ( $weights as $w_key => $w_def ) {
			$val = max( 0, min( 100, intval( $_POST[ 'botfence_' . $w_key ] ?? $_POST[ 'kl_antispam_' . $w_key ] ?? $w_def ) ) );
			$update_pair( $w_key, $val );
		}

		// Rate Limits.
		$rate_min = max( 1, intval( $_POST['botfence_rate_limit_ip_per_minute'] ?? $_POST['kl_antispam_rate_limit_ip_per_minute'] ?? 5 ) );
		$update_pair( 'rate_limit_ip_per_minute', $rate_min );

		$rate_hr = max( 5, intval( $_POST['botfence_rate_limit_ip_per_hour'] ?? $_POST['kl_antispam_rate_limit_ip_per_hour'] ?? 30 ) );
		$update_pair( 'rate_limit_ip_per_hour', $rate_hr );

		$quarantine = max( 60, intval( $_POST['botfence_quarantine_duration'] ?? $_POST['kl_antispam_quarantine_duration'] ?? 900 ) );
		$update_pair( 'quarantine_duration', $quarantine );

		$retention = max( 1, intval( $_POST['botfence_log_retention_days'] ?? $_POST['kl_antispam_log_retention_days'] ?? 30 ) );
		$update_pair( 'log_retention_days', $retention );

		// Integrations.
		$adapters = $this->detector->get_adapters();
		foreach ( $adapters as $id => $adapter ) {
			$is_on = ( isset( $_POST[ 'botfence_enable_' . $id ] ) || isset( $_POST[ 'kl_antispam_enable_' . $id ] ) ) ? 1 : 0;
			$update_pair( 'enable_' . $id, $is_on );
		}

		// Reverse Proxies & Exclusions.
		$trust_proxy = ( isset( $_POST['botfence_trust_reverse_proxies'] ) || isset( $_POST['kl_antispam_trust_reverse_proxies'] ) ) ? 1 : 0;
		$update_pair( 'trust_reverse_proxies', $trust_proxy );

		$proxy_ips = sanitize_textarea_field( wp_unslash( $_POST['botfence_trusted_proxy_ips'] ?? $_POST['kl_antispam_trusted_proxy_ips'] ?? "127.0.0.1\n::1" ) );
		$update_pair( 'trusted_proxy_ips', $proxy_ips );

		$excluded = sanitize_textarea_field( wp_unslash( $_POST['botfence_excluded_forms'] ?? $_POST['kl_antispam_excluded_forms'] ?? '' ) );
		$update_pair( 'excluded_forms', $excluded );
	}

	/**
	 * Exports log records to a downloadable CSV file.
	 */
	protected function export_logs_csv(): void {
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		$filename = 'botfence-logs-' . gmdate( 'Y-m-d-His' ) . '.csv';

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
	 * AJAX endpoint: Returns refreshed dashboard statistics.
	 */
	public function ajax_refresh_stats(): void {
		$nonce_valid = check_ajax_referer( 'botfence_admin_action_nonce', 'nonce', false ) || check_ajax_referer( 'kl_admin_action_nonce', 'nonce', false );
		if ( ! $nonce_valid || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized request', 'botfence' ) ) );
		}

		$stats = $this->engine->get_logger()->get_stats();
		wp_send_json_success( $stats );
	}

	/**
	 * AJAX endpoint: Simulates test verification scenarios.
	 */
	public function ajax_simulate_test(): void {
		$nonce_valid = check_ajax_referer( 'botfence_admin_action_nonce', 'nonce', false ) || check_ajax_referer( 'kl_admin_action_nonce', 'nonce', false );
		if ( ! $nonce_valid ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		}

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
					'botfence_token'                    => $tok['token'],
					'kl_anti_spam_token'                => $tok['token'],
					$honeypot->get_dynamic_field_name( 0 ) => 'I am a spam bot',
					'message'                           => 'Buy cheap pills online',
				);
				break;

			case 'bot_invalid_token':
				$post_data = array(
					'botfence_token'     => 'invalid_tampered_token.123456',
					'kl_anti_spam_token' => 'invalid_tampered_token.123456',
					'message'            => 'Hello from automated script',
				);
				break;

			case 'bot_fast_submit':
				$tok       = $token_manager->generate_token( 'test_form' );
				$telemetry = base64_encode( (string) wp_json_encode( array( 'elapsed_time' => 0.25, 'mouse_moves' => 0, 'keystrokes' => 0, 'touches' => 0 ) ) );
				$post_data = array(
					'botfence_token'         => $tok['token'],
					'kl_anti_spam_token'     => $tok['token'],
					'botfence_telemetry'     => $telemetry,
					'kl_anti_spam_telemetry' => $telemetry,
					'message'                => 'Fast instant submit',
				);
				break;

			case 'bot_headless':
				$tok       = $token_manager->generate_token( 'test_form' );
				$telemetry = base64_encode( (string) wp_json_encode( array( 'elapsed_time' => 3.1, 'webdriver' => true, 'is_trusted' => false ) ) );
				$post_data = array(
					'botfence_token'         => $tok['token'],
					'kl_anti_spam_token'     => $tok['token'],
					'botfence_telemetry'     => $telemetry,
					'kl_anti_spam_telemetry' => $telemetry,
					'message'                => 'Automated test via Selenium',
				);
				break;

			case 'human_normal':
			default:
				$tok       = $token_manager->generate_token( 'test_form' );
				$telemetry = base64_encode( (string) wp_json_encode( array( 'elapsed_time' => 5.4, 'mouse_moves' => 18, 'keystrokes' => 32, 'touches' => 0, 'is_trusted' => true, 'webdriver' => false ) ) );
				$post_data = array(
					'botfence_token'         => $tok['token'],
					'kl_anti_spam_token'     => $tok['token'],
					'botfence_telemetry'     => $telemetry,
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
	 * Helper to get option with fallback.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	protected function get_conf( string $key, $default = false ) {
		$val = get_option( 'botfence_' . $key );
		if ( false !== $val && null !== $val ) {
			return $val;
		}
		return get_option( 'kl_antispam_' . $key, $default );
	}

	/**
	 * Renders the full admin page with tabs.
	 */
	public function render_admin_page(): void {
		$stats    = $this->engine->get_logger()->get_stats();
		$adapters = $this->detector->get_adapters();
		$logs     = $this->engine->get_logger()->get_logs( array( 'per_page' => 20 ) );

		?>
		<div class="wrap botfence-admin-wrap kl-admin-wrap">
			<div class="botfence-admin-header kl-admin-header">
				<div>
					<h1>
						<span class="dashicons dashicons-shield-alt" style="font-size: 26px; color: #2271b1; width: 26px; height: 26px;"></span>
						<?php esc_html_e( 'BotFence', 'botfence' ); ?>
						<span class="botfence-badge-version kl-badge-version">v<?php echo esc_html( BOTFENCE_VERSION ); ?></span>
					</h1>
					<p style="margin: 0; color: #646970; font-size: 13px;">
						<?php esc_html_e( 'Universal Anti-Spam & Automated CAPTCHA Engine built by Amal S Kumar', 'botfence' ); ?> &bull; <a href="https://amalskumar.dev" target="_blank" rel="noopener noreferrer" style="color: #2271b1; text-decoration: none;">amalskumar.dev</a>
					</p>
				</div>
				<div>
					<span class="botfence-badge kl-badge <?php echo $this->get_conf( 'enabled', 1 ) ? 'botfence-badge-active kl-badge-active' : 'botfence-badge-inactive kl-badge-inactive'; ?>">
						● <?php echo $this->get_conf( 'enabled', 1 ) ? esc_html__( 'Active & Protecting', 'botfence' ) : esc_html__( 'Disabled', 'botfence' ); ?>
					</span>
				</div>
			</div>

			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings updated successfully.', 'botfence' ); ?></p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['logs_cleared'] ) ) : ?>
				<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'BotFence logs have been cleared.', 'botfence' ); ?></p></div>
			<?php endif; ?>

			<!-- Navigation Tabs -->
			<nav class="botfence-nav-tabs kl-nav-tabs">
				<a href="#tab-dashboard" class="botfence-nav-tab kl-nav-tab active"><span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'botfence' ); ?></a>
				<a href="#tab-integrations" class="botfence-nav-tab kl-nav-tab"><span class="dashicons dashicons-forms"></span> <?php esc_html_e( 'Form Integrations', 'botfence' ); ?></a>
				<a href="#tab-risk" class="botfence-nav-tab kl-nav-tab"><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e( 'Risk & Security', 'botfence' ); ?></a>
				<a href="#tab-rates" class="botfence-nav-tab kl-nav-tab"><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Rate Limiting', 'botfence' ); ?></a>
				<a href="#tab-logs" class="botfence-nav-tab kl-nav-tab"><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Logs & Activity', 'botfence' ); ?></a>
				<a href="#tab-diagnostics" class="botfence-nav-tab kl-nav-tab"><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Diagnostics & Simulator', 'botfence' ); ?></a>
			</nav>

			<!-- TAB 1: DASHBOARD -->
			<div id="tab-dashboard" class="botfence-tab-pane kl-tab-pane">
				<!-- Hero Stats Banner: Spams Blocked Till Now -->
				<div class="botfence-hero-banner">
					<div class="botfence-hero-top">
						<div class="botfence-hero-status">
							<span class="botfence-live-dot"></span>
							<span class="botfence-live-text"><?php esc_html_e( 'Live Defense Engine Active', 'botfence' ); ?></span>
						</div>
						<div class="botfence-hero-actions">
							<button type="button" id="botfence-refresh-stats-btn" class="button button-secondary botfence-btn-refresh">
								<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Refresh Live Stats', 'botfence' ); ?>
							</button>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=botfence&botfence_action=export_logs_csv' ), 'botfence_export_logs_action' ) ); ?>" class="button button-secondary">
								<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export Audit CSV', 'botfence' ); ?>
							</a>
							<a href="#tab-diagnostics" class="button button-primary botfence-switch-tab-btn">
								<span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Run Threat Simulator', 'botfence' ); ?>
							</a>
						</div>
					</div>

					<div class="botfence-hero-main">
						<div class="botfence-hero-number-wrap">
							<div class="botfence-hero-number" id="botfence-stat-hero-blocked">
								<?php echo esc_html( number_format_i18n( $stats['total_blocked'] ) ); ?>
							</div>
							<div class="botfence-hero-label">
								<span class="dashicons dashicons-shield-alt"></span>
								<?php esc_html_e( 'Spams Blocked Till Now', 'botfence' ); ?>
							</div>
						</div>
						<div class="botfence-hero-desc">
							<?php esc_html_e( 'Total automated bots, credential stuffing, fake inquiries, malicious scripts, and honeypot traps intercepted and terminated before reaching your database or email inboxes.', 'botfence' ); ?>
						</div>
					</div>

					<!-- Hero Time-Windowed Quick Stats -->
					<div class="botfence-hero-quick-stats">
						<div class="botfence-hero-quick-stat">
							<span class="botfence-quick-val" id="botfence-stat-today-blocked"><?php echo esc_html( number_format_i18n( $stats['blocked_today'] ?? 0 ) ); ?></span>
							<span class="botfence-quick-lbl"><?php esc_html_e( 'Blocked Today', 'botfence' ); ?></span>
						</div>
						<div class="botfence-hero-quick-stat">
							<span class="botfence-quick-val" id="botfence-stat-7days-blocked"><?php echo esc_html( number_format_i18n( $stats['blocked_7_days'] ?? 0 ) ); ?></span>
							<span class="botfence-quick-lbl"><?php esc_html_e( 'Past 7 Days', 'botfence' ); ?></span>
						</div>
						<div class="botfence-hero-quick-stat">
							<span class="botfence-quick-val" id="botfence-stat-30days-blocked"><?php echo esc_html( number_format_i18n( $stats['blocked_30_days'] ?? 0 ) ); ?></span>
							<span class="botfence-quick-lbl"><?php esc_html_e( 'Past 30 Days', 'botfence' ); ?></span>
						</div>
						<div class="botfence-hero-quick-stat">
							<span class="botfence-quick-val" id="botfence-stat-rate"><?php echo esc_html( $stats['block_rate'] ); ?>%</span>
							<span class="botfence-quick-lbl"><?php esc_html_e( 'Interception Rate', 'botfence' ); ?></span>
						</div>
					</div>

					<div id="botfence-stats-status-notice" style="display: none;" class="botfence-live-notice"></div>
				</div>

				<!-- Secondary Metrics Grid -->
				<div class="botfence-metrics-grid kl-metrics-grid">
					<div class="botfence-metric-card kl-metric-card">
						<div class="botfence-metric-label kl-metric-label"><?php esc_html_e( 'Total Submissions Checked', 'botfence' ); ?></div>
						<div class="botfence-metric-value kl-metric-value" id="botfence-stat-total-checked"><?php echo esc_html( number_format_i18n( $stats['total_checked'] ) ); ?></div>
						<div class="botfence-metric-subtext"><?php esc_html_e( 'Across all protected website forms', 'botfence' ); ?></div>
					</div>
					<div class="botfence-metric-card kl-metric-card card-allowed">
						<div class="botfence-metric-label kl-metric-label"><?php esc_html_e( 'Legitimate Allowed', 'botfence' ); ?></div>
						<div class="botfence-metric-value kl-metric-value" id="botfence-stat-total-allowed"><?php echo esc_html( number_format_i18n( $stats['total_allowed'] ) ); ?></div>
						<div class="botfence-metric-subtext"><?php esc_html_e( 'Clean human submissions passed smoothly', 'botfence' ); ?></div>
					</div>
					<div class="botfence-metric-card kl-metric-card card-challenge">
						<div class="botfence-metric-label kl-metric-label"><?php esc_html_e( 'Adaptive Challenges Solved', 'botfence' ); ?></div>
						<div class="botfence-metric-value kl-metric-value" id="botfence-stat-total-challenged"><?php echo esc_html( number_format_i18n( $stats['total_challenged'] ) ); ?></div>
						<div class="botfence-metric-subtext"><?php esc_html_e( 'Medium-risk visitors verified via accessible puzzle', 'botfence' ); ?></div>
					</div>
					<div class="botfence-metric-card kl-metric-card card-rate">
						<div class="botfence-metric-label kl-metric-label"><?php esc_html_e( 'Protected Adapters', 'botfence' ); ?></div>
						<div class="botfence-metric-value kl-metric-value">
							<?php
							$active_adapters = count( array_filter( $adapters, fn( $a ) => $a['adapter']->is_detected() ) );
							echo esc_html( $active_adapters . ' / ' . count( $adapters ) );
							?>
						</div>
						<div class="botfence-metric-subtext"><?php esc_html_e( 'Form builders discovered & actively filtered', 'botfence' ); ?></div>
					</div>
				</div>

				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
					<!-- Form Breakdown -->
					<div class="botfence-panel kl-panel">
						<h2 class="botfence-panel-title kl-panel-title">
							<span><span class="dashicons dashicons-forms"></span> <?php esc_html_e( 'Spam Blocked by Form Builder', 'botfence' ); ?></span>
							<a href="#tab-integrations" class="botfence-switch-tab-btn" style="font-size: 12px; font-weight: normal; text-decoration: none;"><?php esc_html_e( 'Manage &rarr;', 'botfence' ); ?></a>
						</h2>
						<?php
						$form_names = array(
							'cf7'            => 'Contact Form 7',
							'wpforms'        => 'WPForms',
							'fluent_forms'   => 'Fluent Forms',
							'gravity_forms'  => 'Gravity Forms',
							'formidable'     => 'Formidable Forms',
							'ninja_forms'    => 'Ninja Forms',
							'elementor'      => 'Elementor Pro Forms',
							'woocommerce'    => 'WooCommerce (Checkout & Register)',
							'comments'       => 'WordPress Native Comments',
							'custom_forms'   => 'Custom HTML Forms',
						);
						$breakdown = $stats['form_breakdown'] ?? array();
						?>
						<?php if ( empty( $breakdown ) ) : ?>
							<div class="botfence-empty-state">
								<span class="dashicons dashicons-yes-alt" style="font-size: 36px; color: #10b981; width: 36px; height: 36px;"></span>
								<p><strong><?php esc_html_e( 'No form spam detected yet.', 'botfence' ); ?></strong></p>
								<p style="color: #646970; font-size: 12px; margin: 0;">
									<?php esc_html_e( 'BotFence is actively guarding all detected forms. Run the Threat Simulator to test response scenarios.', 'botfence' ); ?>
								</p>
							</div>
						<?php else : ?>
							<div class="botfence-breakdown-list" id="botfence-form-breakdown-container">
								<?php
								$max_form_tally = max( $breakdown );
								foreach ( $breakdown as $form_code => $count ) :
									$label = $form_names[ $form_code ] ?? ucfirst( str_replace( '_', ' ', $form_code ) );
									$pct   = $max_form_tally > 0 ? round( ( $count / $max_form_tally ) * 100 ) : 0;
									?>
									<div class="botfence-signal-item kl-signal-item">
										<div class="botfence-signal-label kl-signal-label">
											<span><strong><?php echo esc_html( $label ); ?></strong></span>
											<span class="botfence-badge kl-badge kl-badge-block"><?php echo esc_html( number_format_i18n( $count ) ); ?> <?php esc_html_e( 'blocked', 'botfence' ); ?></span>
										</div>
										<div class="botfence-progress-track kl-progress-track">
											<div class="botfence-progress-fill kl-progress-fill" style="width: <?php echo esc_attr( $pct ); ?>%;"></div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<!-- Top Spam Signals -->
					<div class="botfence-panel kl-panel">
						<h2 class="botfence-panel-title kl-panel-title">
							<span><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e( 'Most Common Spam Signals', 'botfence' ); ?></span>
							<a href="#tab-risk" class="botfence-switch-tab-btn" style="font-size: 12px; font-weight: normal; text-decoration: none;"><?php esc_html_e( 'Configure Weights &rarr;', 'botfence' ); ?></a>
						</h2>
						<?php if ( empty( $stats['top_signals'] ) ) : ?>
							<div class="botfence-empty-state">
								<span class="dashicons dashicons-shield" style="font-size: 36px; color: #2271b1; width: 36px; height: 36px;"></span>
								<p><strong><?php esc_html_e( 'No spam signals logged yet.', 'botfence' ); ?></strong></p>
								<p style="color: #646970; font-size: 12px; margin: 0;">
									<?php esc_html_e( 'Signals such as honeypot violations, invalid signatures, and rapid automated submissions will appear here.', 'botfence' ); ?>
								</p>
							</div>
						<?php else : ?>
							<div id="botfence-top-signals-container">
								<?php
								$max_tally = max( $stats['top_signals'] );
								foreach ( $stats['top_signals'] as $sig_name => $count ) :
									$pct = $max_tally > 0 ? round( ( $count / $max_tally ) * 100 ) : 0;
									?>
									<div class="botfence-signal-item kl-signal-item">
										<div class="botfence-signal-label kl-signal-label">
											<span><?php echo esc_html( $sig_name ); ?></span>
											<strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong>
										</div>
										<div class="botfence-progress-track kl-progress-track">
											<div class="botfence-progress-fill kl-progress-fill" style="width: <?php echo esc_attr( $pct ); ?>%;"></div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Recent Blocked Submissions Stream -->
				<div class="botfence-panel kl-panel">
					<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
						<h2 class="botfence-panel-title kl-panel-title" style="margin: 0;">
							<span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Recent Blocked Submissions', 'botfence' ); ?>
						</h2>
						<a href="#tab-logs" class="button button-secondary botfence-switch-tab-btn">
							<?php esc_html_e( 'View All Audit Logs', 'botfence' ); ?> &rarr;
						</a>
					</div>
					<?php if ( empty( $stats['recent_blocked'] ) ) : ?>
						<p style="color: #646970;"><?php esc_html_e( 'No recent blocked submissions in the database.', 'botfence' ); ?></p>
					<?php else : ?>
						<table class="botfence-table kl-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Intercepted', 'botfence' ); ?></th>
									<th><?php esc_html_e( 'Form Builder', 'botfence' ); ?></th>
									<th><?php esc_html_e( 'Risk Level', 'botfence' ); ?></th>
									<th><?php esc_html_e( 'Primary Trigger Signals', 'botfence' ); ?></th>
									<th><?php esc_html_e( 'Visitor Masked IP', 'botfence' ); ?></th>
								</tr>
							</thead>
							<tbody id="botfence-recent-blocked-tbody">
								<?php foreach ( $stats['recent_blocked'] as $item ) : ?>
									<?php
									$reasons_summary = array();
									$decoded_r = json_decode( (string) $item->reasons, true );
									if ( is_array( $decoded_r ) ) {
										foreach ( $decoded_r as $r ) {
											$reasons_summary[] = is_array( $r ) ? ( $r['name'] ?? $r['code'] ?? '' ) : (string) $r;
										}
									}
									$reasons_text = ! empty( $reasons_summary ) ? implode( ', ', array_slice( $reasons_summary, 0, 2 ) ) : __( 'Threat anomaly detected', 'botfence' );
									$fname = $form_names[ $item->form_type ] ?? ucfirst( str_replace( '_', ' ', $item->form_type ) );
									?>
									<tr>
										<td><strong><?php echo esc_html( human_time_diff( strtotime( $item->created_at ), time() ) . ' ' . __( 'ago', 'botfence' ) ); ?></strong></td>
										<td><code><?php echo esc_html( $fname ); ?></code></td>
										<td>
											<span class="botfence-badge kl-badge kl-badge-block">
												<?php echo esc_html( $item->risk_score ); ?>/100 <?php echo $item->risk_score >= 80 ? esc_html__( 'CRITICAL', 'botfence' ) : esc_html__( 'HIGH', 'botfence' ); ?>
											</span>
										</td>
										<td style="color: #475569; font-size: 12px;"><?php echo esc_html( $reasons_text ); ?></td>
										<td><code><?php echo esc_html( $item->masked_ip ); ?></code></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>

			<!-- TAB 2: INTEGRATIONS -->
			<div id="tab-integrations" class="botfence-tab-pane kl-tab-pane" style="display: none;">
				<form method="post" action="">
					<?php wp_nonce_field( 'botfence_save_settings_action', 'botfence_save_settings_nonce' ); ?>
					<div class="botfence-panel kl-panel">
						<h2 class="botfence-panel-title kl-panel-title"><?php esc_html_e( 'Universal Form Builder Protections', 'botfence' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'BotFence automatically detects active form plugins on your website. Toggle protection per form builder below.', 'botfence' ); ?>
						</p>

						<table class="botfence-table kl-table" style="margin-top: 18px;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Form Builder', 'botfence' ); ?></th>
									<th><?php esc_html_e( 'Environment Status', 'botfence' ); ?></th>
									<th><?php esc_html_e( 'Protection Status', 'botfence' ); ?></th>
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
												<span class="botfence-badge kl-badge kl-badge-allow"><?php esc_html_e( 'Detected & Available', 'botfence' ); ?></span>
											<?php else : ?>
												<span class="botfence-badge kl-badge kl-badge-inactive"><?php esc_html_e( 'Not Installed', 'botfence' ); ?></span>
											<?php endif; ?>
										</td>
										<td>
											<label class="botfence-switch kl-switch">
												<input type="checkbox" name="botfence_enable_<?php echo esc_attr( $id ); ?>" value="1" <?php checked( $adapter->is_enabled() ); ?> />
												<span class="botfence-slider kl-slider"></span>
											</label>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<div class="botfence-panel kl-panel">
						<h2 class="botfence-panel-title kl-panel-title"><?php esc_html_e( 'Excluded Forms / Whitelist', 'botfence' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'Exclude specific forms from anti-spam checks. Enter one form ID per line (e.g., 123 or cf7:123).', 'botfence' ); ?>
						</p>
						<textarea name="botfence_excluded_forms" rows="4" class="large-text code"><?php echo esc_textarea( $this->get_conf( 'excluded_forms', '' ) ); ?></textarea>
					</div>

					<p><input type="submit" name="botfence_save_settings" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Integration Settings', 'botfence' ); ?>" /></p>
				</form>
			</div>

			<!-- TAB 3: RISK & SECURITY -->
			<div id="tab-risk" class="botfence-tab-pane kl-tab-pane" style="display: none;">
				<form method="post" action="">
					<?php wp_nonce_field( 'botfence_save_settings_action', 'botfence_save_settings_nonce' ); ?>
					<div class="botfence-panel kl-panel">
						<h2 class="botfence-panel-title kl-panel-title"><?php esc_html_e( 'Risk Thresholds & Decision Logic', 'botfence' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Configure how the risk engine evaluates composite scores (0 to 100 points).', 'botfence' ); ?></p>

						<div class="botfence-form-grid kl-form-grid">
							<div>
								<label for="botfence_risk_threshold_challenge"><?php esc_html_e( 'Challenge Threshold', 'botfence' ); ?></label>
								<div class="botfence-desc kl-desc"><?php esc_html_e( 'Score at which secondary verification is required (default: 30).', 'botfence' ); ?></div>
							</div>
							<div>
								<input type="number" id="botfence_risk_threshold_challenge" name="botfence_risk_threshold_challenge" value="<?php echo esc_attr( $this->get_conf( 'risk_threshold_challenge', 30 ) ); ?>" min="1" max="99" class="small-text" /> points
							</div>

							<div>
								<label for="botfence_risk_threshold_block"><?php esc_html_e( 'Block Threshold', 'botfence' ); ?></label>
								<div class="botfence-desc kl-desc"><?php esc_html_e( 'Score at which submissions are immediately rejected (default: 60).', 'botfence' ); ?></div>
							</div>
							<div>
								<input type="number" id="botfence_risk_threshold_block" name="botfence_risk_threshold_block" value="<?php echo esc_attr( $this->get_conf( 'risk_threshold_block', 60 ) ); ?>" min="2" max="100" class="small-text" /> points
							</div>

							<div>
								<label for="botfence_min_submission_time"><?php esc_html_e( 'Minimum Submission Time', 'botfence' ); ?></label>
								<div class="botfence-desc kl-desc"><?php esc_html_e( 'Submissions completed faster than this time are flagged as bot activity (default: 2.5 seconds).', 'botfence' ); ?></div>
							</div>
							<div>
								<input type="number" step="0.1" id="botfence_min_submission_time" name="botfence_min_submission_time" value="<?php echo esc_attr( $this->get_conf( 'min_submission_time', 2.5 ) ); ?>" min="0.5" max="30" class="small-text" /> seconds
							</div>

							<div>
								<label for="botfence_token_lifetime"><?php esc_html_e( 'Token Lifetime', 'botfence' ); ?></label>
								<div class="botfence-desc kl-desc"><?php esc_html_e( 'Maximum validity window for cryptographic tokens (default: 600s / 10 minutes).', 'botfence' ); ?></div>
							</div>
							<div>
								<input type="number" id="botfence_token_lifetime" name="botfence_token_lifetime" value="<?php echo esc_attr( $this->get_conf( 'token_lifetime', 600 ) ); ?>" min="60" max="86400" class="regular-text" style="max-width: 120px;" /> seconds
							</div>
						</div>
					</div>

					<div class="botfence-panel kl-panel">
						<h2 class="botfence-panel-title kl-panel-title"><?php esc_html_e( 'Signal Risk Point Weights', 'botfence' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Customize how many risk points each anomaly contributes to the overall submission score.', 'botfence' ); ?></p>

						<table class="botfence-table kl-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Detection Signal', 'botfence' ); ?></th>
									<th style="width: 140px;"><?php esc_html_e( 'Risk Points', 'botfence' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><strong><?php esc_html_e( 'Honeypot Trap Completed', 'botfence' ); ?></strong></td>
									<td><input type="number" name="botfence_weight_honeypot" value="<?php echo esc_attr( $this->get_conf( 'weight_honeypot', 100 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Invalid or Tampered Signed Token', 'botfence' ); ?></strong></td>
									<td><input type="number" name="botfence_weight_invalid_token" value="<?php echo esc_attr( $this->get_conf( 'weight_invalid_token', 100 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Expired Token', 'botfence' ); ?></strong></td>
									<td><input type="number" name="botfence_weight_expired_token" value="<?php echo esc_attr( $this->get_conf( 'weight_expired_token', 80 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Rapid Submission (Under minimum time)', 'botfence' ); ?></strong></td>
									<td><input type="number" name="botfence_weight_fast_submit" value="<?php echo esc_attr( $this->get_conf( 'weight_fast_submit', 30 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Repeated Submissions (Rate limit warning)', 'botfence' ); ?></strong></td>
									<td><input type="number" name="botfence_weight_rate_limit" value="<?php echo esc_attr( $this->get_conf( 'weight_rate_limit', 40 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Excessive URLs in Message (>2 links)', 'botfence' ); ?></strong></td>
									<td><input type="number" name="botfence_weight_excessive_urls" value="<?php echo esc_attr( $this->get_conf( 'weight_excessive_urls', 25 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Suspicious Interaction Pattern (Zero telemetry with text)', 'botfence' ); ?></strong></td>
									<td><input type="number" name="botfence_weight_suspicious_telemetry" value="<?php echo esc_attr( $this->get_conf( 'weight_suspicious_telemetry', 20 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Repeated Identical Message Content', 'botfence' ); ?></strong></td>
									<td><input type="number" name="botfence_weight_repeated_message" value="<?php echo esc_attr( $this->get_conf( 'weight_repeated_message', 35 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
							</tbody>
						</table>
					</div>

					<p><input type="submit" name="botfence_save_settings" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Risk Configuration', 'botfence' ); ?>" /></p>
				</form>
			</div>

			<!-- TAB 4: RATE LIMITING -->
			<div id="tab-rates" class="botfence-tab-pane kl-tab-pane" style="display: none;">
				<form method="post" action="">
					<?php wp_nonce_field( 'botfence_save_settings_action', 'botfence_save_settings_nonce' ); ?>
					<div class="botfence-panel kl-panel">
						<h2 class="botfence-panel-title kl-panel-title"><?php esc_html_e( 'Submission Frequency & Rate Limits', 'botfence' ); ?></h2>
						<div class="botfence-form-grid kl-form-grid">
							<div>
								<label for="botfence_rate_limit_ip_per_minute"><?php esc_html_e( 'Submissions per Minute (per IP)', 'botfence' ); ?></label>
								<div class="botfence-desc kl-desc"><?php esc_html_e( 'Maximum submissions allowed from a single IP address per minute (default: 5).', 'botfence' ); ?></div>
							</div>
							<div>
								<input type="number" id="botfence_rate_limit_ip_per_minute" name="botfence_rate_limit_ip_per_minute" value="<?php echo esc_attr( $this->get_conf( 'rate_limit_ip_per_minute', 5 ) ); ?>" min="1" max="100" class="small-text" /> submissions
							</div>

							<div>
								<label for="botfence_rate_limit_ip_per_hour"><?php esc_html_e( 'Submissions per Hour (per IP)', 'botfence' ); ?></label>
								<div class="botfence-desc kl-desc"><?php esc_html_e( 'Maximum submissions allowed from a single IP address per hour (default: 30).', 'botfence' ); ?></div>
							</div>
							<div>
								<input type="number" id="botfence_rate_limit_ip_per_hour" name="botfence_rate_limit_ip_per_hour" value="<?php echo esc_attr( $this->get_conf( 'rate_limit_ip_per_hour', 30 ) ); ?>" min="5" max="500" class="small-text" /> submissions
							</div>

							<div>
								<label for="botfence_quarantine_duration"><?php esc_html_e( 'Temporary Quarantine Duration', 'botfence' ); ?></label>
								<div class="botfence-desc kl-desc"><?php esc_html_e( 'Temporary block duration for IPs exceeding rate limits (default: 900s / 15 minutes).', 'botfence' ); ?></div>
							</div>
							<div>
								<input type="number" id="botfence_quarantine_duration" name="botfence_quarantine_duration" value="<?php echo esc_attr( $this->get_conf( 'quarantine_duration', 900 ) ); ?>" min="60" max="86400" class="regular-text" style="max-width: 120px;" /> seconds
							</div>

							<div>
								<label for="botfence_log_retention_days"><?php esc_html_e( 'Log Retention Period', 'botfence' ); ?></label>
								<div class="botfence-desc kl-desc"><?php esc_html_e( 'Number of days to keep audit logs before automatic pruning (default: 30 days).', 'botfence' ); ?></div>
							</div>
							<div>
								<input type="number" id="botfence_log_retention_days" name="botfence_log_retention_days" value="<?php echo esc_attr( $this->get_conf( 'log_retention_days', 30 ) ); ?>" min="1" max="365" class="small-text" /> days
							</div>
						</div>
					</div>

					<div class="botfence-panel kl-panel">
						<h2 class="botfence-panel-title kl-panel-title"><?php esc_html_e( 'Reverse Proxy Configuration', 'botfence' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'For security, HTTP headers like X-Forwarded-For or CF-Connecting-IP are NEVER trusted unless the connection arrives from an explicitly trusted reverse proxy.', 'botfence' ); ?>
						</p>

						<div class="botfence-form-grid kl-form-grid">
							<div>
								<label for="botfence_trust_reverse_proxies"><?php esc_html_e( 'Trust Reverse Proxies', 'botfence' ); ?></label>
								<div class="botfence-desc kl-desc"><?php esc_html_e( 'Enable only if your site is behind Cloudflare, AWS ALB, Nginx, or a CDN.', 'botfence' ); ?></div>
							</div>
							<div>
								<label class="botfence-switch kl-switch">
									<input type="checkbox" id="botfence_trust_reverse_proxies" name="botfence_trust_reverse_proxies" value="1" <?php checked( $this->get_conf( 'trust_reverse_proxies', 0 ) ); ?> />
									<span class="botfence-slider kl-slider"></span>
								</label>
							</div>

							<div>
								<label for="botfence_trusted_proxy_ips"><?php esc_html_e( 'Trusted Proxy IPs / CIDRs', 'botfence' ); ?></label>
								<div class="botfence-desc kl-desc"><?php esc_html_e( 'One IP or CIDR per line (e.g., 127.0.0.1, 10.0.0.0/8).', 'botfence' ); ?></div>
							</div>
							<div>
								<textarea id="botfence_trusted_proxy_ips" name="botfence_trusted_proxy_ips" rows="4" class="large-text code"><?php echo esc_textarea( $this->get_conf( 'trusted_proxy_ips', "127.0.0.1\n::1" ) ); ?></textarea>
							</div>
						</div>
					</div>

					<p><input type="submit" name="botfence_save_settings" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Rate Limiting Settings', 'botfence' ); ?>" /></p>
				</form>
			</div>

			<!-- TAB 5: LOGS -->
			<div id="tab-logs" class="botfence-tab-pane kl-tab-pane" style="display: none;">
				<div class="botfence-panel kl-panel">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
						<h2 class="botfence-panel-title kl-panel-title" style="margin: 0;"><?php esc_html_e( 'Submission Audit Logs', 'botfence' ); ?></h2>
						<div style="display: flex; gap: 10px;">
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'botfence', 'botfence_action' => 'export_logs_csv' ), admin_url( 'admin.php' ) ), 'botfence_export_logs_action' ) ); ?>" class="button">
								<span class="dashicons dashicons-download" style="vertical-align: middle;"></span> <?php esc_html_e( 'Export to CSV', 'botfence' ); ?>
							</a>
							<form method="post" action="" style="display: inline;">
								<?php wp_nonce_field( 'botfence_clear_logs_action', 'botfence_clear_logs_nonce' ); ?>
								<input type="submit" id="botfence-clear-logs-btn" name="botfence_clear_logs" class="button button-link-delete" value="<?php esc_attr_e( 'Clear All Logs', 'botfence' ); ?>" />
							</form>
						</div>
					</div>

					<p class="description"><?php esc_html_e( 'All logs are privacy-conscious and anonymized. Full message bodies and personal details are never stored.', 'botfence' ); ?></p>

					<?php if ( empty( $logs['items'] ) ) : ?>
						<p style="padding: 20px 0; color: #646970; text-align: center;"><?php esc_html_e( 'No log entries found.', 'botfence' ); ?></p>
					<?php else : ?>
						<table class="botfence-table kl-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Date & Time', 'botfence' ); ?></th>
									<th><?php esc_html_e( 'Form', 'botfence' ); ?></th>
									<th><?php esc_html_e( 'Risk Score', 'botfence' ); ?></th>
									<th><?php esc_html_e( 'Action Taken', 'botfence' ); ?></th>
									<th><?php esc_html_e( 'Visitor IP', 'botfence' ); ?></th>
									<th><?php esc_html_e( 'Duration', 'botfence' ); ?></th>
									<th><?php esc_html_e( 'Details', 'botfence' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $logs['items'] as $entry ) : ?>
									<tr>
										<td><?php echo esc_html( $entry->created_at ); ?></td>
										<td><code><?php echo esc_html( $entry->form_type . ( $entry->form_id ? ' (' . $entry->form_id . ')' : '' ) ); ?></code></td>
										<td>
											<span class="botfence-badge kl-badge <?php echo $entry->risk_score >= 60 ? 'botfence-badge-block kl-badge-block' : ( $entry->risk_score >= 30 ? 'botfence-badge-challenge kl-badge-challenge' : 'botfence-badge-allow kl-badge-allow' ); ?>">
												<?php echo esc_html( $entry->risk_score ); ?>/100
											</span>
										</td>
										<td>
											<span class="botfence-badge kl-badge <?php echo 'block' === $entry->action_taken ? 'botfence-badge-block kl-badge-block' : ( 'challenge' === $entry->action_taken ? 'botfence-badge-challenge kl-badge-challenge' : 'botfence-badge-allow kl-badge-allow' ); ?>">
												<?php echo esc_html( strtoupper( $entry->action_taken ) ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $entry->masked_ip ); ?></td>
										<td><?php echo esc_html( sprintf( '%.2fs', $entry->submission_time ) ); ?></td>
										<td>
											<a href="#" class="botfence-toggle-details kl-toggle-details"><?php esc_html_e( 'View Signals', 'botfence' ); ?></a>
										</td>
									</tr>
									<tr class="botfence-details-row kl-details-row" style="display: none; background: #f6f7f7;">
										<td colspan="7" style="padding: 12px 20px;">
											<strong><?php esc_html_e( 'Triggered Signals:', 'botfence' ); ?></strong>
											<?php
											$reasons = json_decode( (string) $entry->reasons, true );
											if ( empty( $reasons ) ) {
												echo '<p style="margin: 4px 0; color: #008a20;">' . esc_html__( 'No risk signals triggered. Clean human submission.', 'botfence' ) . '</p>';
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
			<div id="tab-diagnostics" class="botfence-tab-pane kl-tab-pane" style="display: none;">
				<div class="botfence-panel kl-panel">
					<h2 class="botfence-panel-title kl-panel-title"><?php esc_html_e( 'Interactive Protection Simulator', 'botfence' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Test the internal risk engine against various submission scenarios to verify scoring calculations and detection rules in real-time.', 'botfence' ); ?>
					</p>

					<input type="hidden" id="botfence_admin_nonce" value="<?php echo esc_attr( wp_create_nonce( 'botfence_admin_action_nonce' ) ); ?>" />
					<input type="hidden" id="kl_admin_nonce" value="<?php echo esc_attr( wp_create_nonce( 'kl_admin_action_nonce' ) ); ?>" />

					<div style="display: flex; gap: 12px; align-items: center; margin-top: 16px;">
						<select id="botfence-sim-scenario" style="min-width: 280px;">
							<option value="human_normal"><?php esc_html_e( 'Normal Human (5.4s typing, 32 keystrokes, clean)', 'botfence' ); ?></option>
							<option value="bot_honeypot"><?php esc_html_e( 'Automated Bot (Populated hidden honeypot)', 'botfence' ); ?></option>
							<option value="bot_invalid_token"><?php esc_html_e( 'Malicious Bot (Forged / invalid signed token)', 'botfence' ); ?></option>
							<option value="bot_fast_submit"><?php esc_html_e( 'Rapid Script (0.25s completion time)', 'botfence' ); ?></option>
							<option value="bot_headless"><?php esc_html_e( 'Headless Automation (Selenium / webdriver flag)', 'botfence' ); ?></option>
						</select>

						<button type="button" id="botfence-run-test-simulator" class="button button-primary">
							<?php esc_html_e( 'Run Test Simulation', 'botfence' ); ?>
						</button>
					</div>

					<div id="botfence-sim-result" style="display: none;"></div>
				</div>

				<div class="botfence-panel kl-panel">
					<h2 class="botfence-panel-title kl-panel-title"><?php esc_html_e( 'System Environment & Diagnostics', 'botfence' ); ?></h2>
					<table class="botfence-table kl-table">
						<tbody>
							<tr>
								<td><strong><?php esc_html_e( 'Author & Developer', 'botfence' ); ?></strong></td>
								<td><strong>Amal S Kumar</strong> (<a href="https://amalskumar.dev" target="_blank" rel="noopener noreferrer">amalskumar.dev</a>)</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Plugin Version', 'botfence' ); ?></strong></td>
								<td><code>v<?php echo esc_html( BOTFENCE_VERSION ); ?></code></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'PHP Version', 'botfence' ); ?></strong></td>
								<td><code><?php echo esc_html( PHP_VERSION ); ?></code> (>= 8.1 required)</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'WordPress Version', 'botfence' ); ?></strong></td>
								<td><code><?php echo esc_html( get_bloginfo( 'version' ) ); ?></code></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'HMAC Secret Key Status', 'botfence' ); ?></strong></td>
								<td><span class="botfence-badge kl-badge kl-badge-allow"><?php esc_html_e( 'Active & Cryptographically Secure', 'botfence' ); ?></span></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Logs Database Table', 'botfence' ); ?></strong></td>
								<td><code><?php echo esc_html( Database::get_logs_table() ); ?></code></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Rate Limits Database Table', 'botfence' ); ?></strong></td>
								<td><code><?php echo esc_html( Database::get_rates_table() ); ?></code></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Cleanup Cron Job', 'botfence' ); ?></strong></td>
								<td>
									<?php
									$next_cron = wp_next_scheduled( 'botfence_cleanup_cron' ) ?: wp_next_scheduled( 'kl_antispam_cleanup_cron' );
									if ( $next_cron ) {
										printf(
											/* translators: %s: relative time */
											esc_html__( 'Scheduled (next run in %s)', 'botfence' ),
											esc_html( human_time_diff( time(), $next_cron ) )
										);
									} else {
										esc_html_e( 'Not scheduled', 'botfence' );
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

// Backwards compatibility alias.
if ( ! class_exists( '\KL_Anti_Spam\Admin', false ) ) {
	class_alias( '\BotFence\Admin', '\KL_Anti_Spam\Admin' );
}
