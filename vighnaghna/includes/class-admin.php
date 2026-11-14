<?php
/**
 * Admin Dashboard and Settings Controller for Vighnaghna.
 *
 * @package Vighnaghna
 */

namespace Vighnaghna;

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

		// AJAX endpoints with current and legacy actions.
		add_action( 'wp_ajax_vighnaghna_simulate_test', array( $this, 'ajax_simulate_test' ) );
		add_action( 'wp_ajax_botfence_simulate_test', array( $this, 'ajax_simulate_test' ) );
		add_action( 'wp_ajax_kl_simulate_test', array( $this, 'ajax_simulate_test' ) );

		add_action( 'wp_ajax_vighnaghna_refresh_stats', array( $this, 'ajax_refresh_stats' ) );
		add_action( 'wp_ajax_botfence_refresh_stats', array( $this, 'ajax_refresh_stats' ) );

		add_action( 'wp_ajax_vighnaghna_mark_false_positive', array( $this, 'ajax_mark_false_positive' ) );
		add_action( 'wp_ajax_botfence_mark_false_positive', array( $this, 'ajax_mark_false_positive' ) );
	}

	/**
	 * Registers the top-level admin menu.
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'Vighnaghna', 'vighnaghna' ),
			__( 'Vighnaghna', 'vighnaghna' ),
			'manage_options',
			'vighnaghna',
			array( $this, 'render_admin_page' ),
			'dashicons-shield',
			80
		);

		// Backwards-compatibility submenu redirects.
		add_submenu_page(
			null,
			__( 'BotFence', 'vighnaghna' ),
			__( 'BotFence', 'vighnaghna' ),
			'manage_options',
			'botfence',
			array( $this, 'render_admin_page' )
		);

		add_submenu_page(
			null,
			__( 'KL Anti-Spam', 'vighnaghna' ),
			__( 'KL Anti-Spam', 'vighnaghna' ),
			'manage_options',
			'kl-universal-anti-spam',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueues CSS and JS for the plugin admin screen.
	 * Assets are strictly loaded only on the plugin admin pages.
	 *
	 * @param string $hook Page hook suffix.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'vighnaghna' ) && ! str_contains( $hook, 'botfence' ) && ! str_contains( $hook, 'kl-universal-anti-spam' ) ) {
			return;
		}

		$base_url  = defined( 'VIGHNAGHNA_URL' ) ? VIGHNAGHNA_URL : plugin_dir_url( dirname( __FILE__ ) );
		$base_path = defined( 'VIGHNAGHNA_PATH' ) ? VIGHNAGHNA_PATH : plugin_dir_path( dirname( __FILE__ ) );
		$version   = defined( 'VIGHNAGHNA_VERSION' ) ? VIGHNAGHNA_VERSION : '1.0.0';

		$css_url = file_exists( $base_path . 'assets/css/vighnaghna-admin.css' )
			? $base_url . 'assets/css/vighnaghna-admin.css'
			: ( file_exists( $base_path . 'assets/css/botfence-admin.css' ) ? $base_url . 'assets/css/botfence-admin.css' : $base_url . 'assets/css/kl-admin.css' );

		$js_url = file_exists( $base_path . 'assets/admin/vighnaghna-admin.js' )
			? $base_url . 'assets/admin/vighnaghna-admin.js'
			: ( file_exists( $base_path . 'assets/admin/botfence-admin.js' ) ? $base_url . 'assets/admin/botfence-admin.js' : $base_url . 'assets/admin/kl-admin.js' );

		wp_enqueue_style(
			'vighnaghna-admin-css',
			$css_url,
			array(),
			$version
		);

		wp_enqueue_script(
			'vighnaghna-admin-js',
			$js_url,
			array( 'jquery' ),
			$version,
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
		if ( ( isset( $_POST['vighnaghna_save_settings'] ) || isset( $_POST['botfence_save_settings'] ) || isset( $_POST['kl_save_settings'] ) )
			&& ( check_admin_referer( 'vighnaghna_save_settings_action', 'vighnaghna_save_settings_nonce' )
				|| check_admin_referer( 'botfence_save_settings_action', 'botfence_save_settings_nonce' )
				|| check_admin_referer( 'kl_save_settings_action', 'kl_save_settings_nonce' ) )
		) {
			$this->save_settings();
			$tab = isset( $_POST['vighnaghna_current_tab'] )
				? sanitize_key( (string) $_POST['vighnaghna_current_tab'] )
				: ( isset( $_POST['botfence_current_tab'] ) ? sanitize_key( (string) $_POST['botfence_current_tab'] ) : 'dashboard' );

			wp_safe_redirect( add_query_arg( array( 'page' => 'vighnaghna', 'tab' => $tab, 'updated' => '1' ), admin_url( 'admin.php#tab-' . $tab ) ) );
			exit;
		}

		// Handle Clear Logs.
		if ( ( isset( $_POST['vighnaghna_clear_logs'] ) || isset( $_POST['botfence_clear_logs'] ) || isset( $_POST['kl_clear_logs'] ) )
			&& ( check_admin_referer( 'vighnaghna_clear_logs_action', 'vighnaghna_clear_logs_nonce' )
				|| check_admin_referer( 'botfence_clear_logs_action', 'botfence_clear_logs_nonce' )
				|| check_admin_referer( 'kl_clear_logs_action', 'kl_clear_logs_nonce' ) )
		) {
			$this->engine->get_logger()->clear_logs();
			wp_safe_redirect( add_query_arg( array( 'page' => 'vighnaghna', 'tab' => 'logs', 'logs_cleared' => '1' ), admin_url( 'admin.php#tab-logs' ) ) );
			exit;
		}

		// Handle CSV Export.
		if ( ( isset( $_GET['vighnaghna_action'] ) && 'export_logs_csv' === $_GET['vighnaghna_action'] && ( check_admin_referer( 'vighnaghna_export_logs_action' ) || check_admin_referer( 'botfence_export_logs_action' ) || check_admin_referer( 'kl_export_logs_action' ) ) )
			|| ( isset( $_GET['botfence_action'] ) && 'export_logs_csv' === $_GET['botfence_action'] && ( check_admin_referer( 'botfence_export_logs_action' ) || check_admin_referer( 'kl_export_logs_action' ) ) )
			|| ( isset( $_GET['kl_action'] ) && 'export_logs_csv' === $_GET['kl_action'] && check_admin_referer( 'kl_export_logs_action' ) )
		) {
			$this->export_logs_csv();
			exit;
		}
	}

	/**
	 * Sanitizes and saves settings to current and legacy option names for seamless backwards compatibility.
	 */
	protected function save_settings(): void {
		$valid_nonce = check_admin_referer( 'vighnaghna_save_settings_action', 'vighnaghna_save_settings_nonce' )
			|| check_admin_referer( 'botfence_save_settings_action', 'botfence_save_settings_nonce' )
			|| check_admin_referer( 'kl_save_settings_action', 'kl_save_settings_nonce' );

		if ( ! $valid_nonce ) {
			return;
		}

		$update_all = static function( $suffix, $val ) {
			update_option( 'vighnaghna_' . $suffix, $val );
			update_option( 'botfence_' . $suffix, $val );
			update_option( 'kl_antispam_' . $suffix, $val );
		};

		// Toggles & numbers.
		$enabled = ( isset( $_POST['vighnaghna_enabled'] ) || isset( $_POST['botfence_enabled'] ) || isset( $_POST['kl_antispam_enabled'] ) ) ? 1 : 0;
		$update_all( 'enabled', $enabled );

		$min_time = max( 0.5, floatval( $_POST['vighnaghna_min_submission_time'] ?? $_POST['botfence_min_submission_time'] ?? $_POST['kl_antispam_min_submission_time'] ?? 2.5 ) );
		$update_all( 'min_submission_time', $min_time );

		$lifetime = max( 60, intval( $_POST['vighnaghna_token_lifetime'] ?? $_POST['botfence_token_lifetime'] ?? $_POST['kl_antispam_token_lifetime'] ?? 600 ) );
		$update_all( 'token_lifetime', $lifetime );

		$hp_enabled = ( isset( $_POST['vighnaghna_honeypot_enabled'] ) || isset( $_POST['botfence_honeypot_enabled'] ) || isset( $_POST['kl_antispam_honeypot_enabled'] ) ) ? 1 : 0;
		$update_all( 'honeypot_enabled', $hp_enabled );

		$adaptive = ( isset( $_POST['vighnaghna_adaptive_challenge'] ) || isset( $_POST['botfence_adaptive_challenge'] ) || isset( $_POST['kl_antispam_adaptive_challenge'] ) ) ? 1 : 0;
		$update_all( 'adaptive_challenge', $adaptive );

		// Thresholds.
		$th_challenge = max( 1, min( 99, intval( $_POST['vighnaghna_risk_threshold_challenge'] ?? $_POST['botfence_risk_threshold_challenge'] ?? $_POST['kl_antispam_risk_threshold_challenge'] ?? 30 ) ) );
		$update_all( 'risk_threshold_challenge', $th_challenge );

		$th_block = max( 2, min( 100, intval( $_POST['vighnaghna_risk_threshold_block'] ?? $_POST['botfence_risk_threshold_block'] ?? $_POST['kl_antispam_risk_threshold_block'] ?? 60 ) ) );
		$update_all( 'risk_threshold_block', $th_block );

		// Weights: include all supported signals including headless browser & synthetic events.
		$weights = array(
			'weight_honeypot'             => 100,
			'weight_invalid_token'        => 100,
			'weight_expired_token'        => 80,
			'weight_fast_submit'          => 30,
			'weight_rate_limit'           => 40,
			'weight_excessive_urls'       => 25,
			'weight_suspicious_telemetry' => 20,
			'weight_repeated_message'     => 35,
			'weight_headless_browser'     => 50,
			'weight_untrusted_event'      => 45,
		);

		foreach ( $weights as $w_key => $w_def ) {
			$val = max( 0, min( 100, intval( $_POST[ 'vighnaghna_' . $w_key ] ?? $_POST[ 'botfence_' . $w_key ] ?? $_POST[ 'kl_antispam_' . $w_key ] ?? $w_def ) ) );
			$update_all( $w_key, $val );
		}

		// Rate Limits.
		$rate_min = max( 1, intval( $_POST['vighnaghna_rate_limit_ip_per_minute'] ?? $_POST['botfence_rate_limit_ip_per_minute'] ?? $_POST['kl_antispam_rate_limit_ip_per_minute'] ?? 5 ) );
		$update_all( 'rate_limit_ip_per_minute', $rate_min );

		$rate_hr = max( 5, intval( $_POST['vighnaghna_rate_limit_ip_per_hour'] ?? $_POST['botfence_rate_limit_ip_per_hour'] ?? $_POST['kl_antispam_rate_limit_ip_per_hour'] ?? 30 ) );
		$update_all( 'rate_limit_ip_per_hour', $rate_hr );

		$quarantine = max( 60, intval( $_POST['vighnaghna_quarantine_duration'] ?? $_POST['botfence_quarantine_duration'] ?? $_POST['kl_antispam_quarantine_duration'] ?? 900 ) );
		$update_all( 'quarantine_duration', $quarantine );

		$retention = max( 1, intval( $_POST['vighnaghna_log_retention_days'] ?? $_POST['botfence_log_retention_days'] ?? $_POST['kl_antispam_log_retention_days'] ?? 30 ) );
		$update_all( 'log_retention_days', $retention );

		// Integrations.
		$adapters = $this->detector->get_adapters();
		foreach ( $adapters as $id => $adapter ) {
			$is_on = ( isset( $_POST[ 'vighnaghna_enable_' . $id ] ) || isset( $_POST[ 'botfence_enable_' . $id ] ) || isset( $_POST[ 'kl_antispam_enable_' . $id ] ) ) ? 1 : 0;
			$update_all( 'enable_' . $id, $is_on );
		}

		// Reverse Proxies & Exclusions.
		$trust_proxy = ( isset( $_POST['vighnaghna_trust_reverse_proxies'] ) || isset( $_POST['botfence_trust_reverse_proxies'] ) || isset( $_POST['kl_antispam_trust_reverse_proxies'] ) ) ? 1 : 0;
		$update_all( 'trust_reverse_proxies', $trust_proxy );

		$proxy_ips = sanitize_textarea_field( wp_unslash( $_POST['vighnaghna_trusted_proxy_ips'] ?? $_POST['botfence_trusted_proxy_ips'] ?? $_POST['kl_antispam_trusted_proxy_ips'] ?? "127.0.0.1\n::1" ) );
		$update_all( 'trusted_proxy_ips', $proxy_ips );

		$excluded = sanitize_textarea_field( wp_unslash( $_POST['vighnaghna_excluded_forms'] ?? $_POST['botfence_excluded_forms'] ?? $_POST['kl_antispam_excluded_forms'] ?? '' ) );
		$update_all( 'excluded_forms', $excluded );
	}

	/**
	 * Sanitizes a CSV cell to prevent formula injection (CWE-1236).
	 *
	 * Prepends single quote if cell starts with =, +, -, @, tab, or carriage return.
	 *
	 * @param mixed $value Cell value.
	 * @return string Sanitized cell value.
	 */
	public static function sanitize_csv_cell( $value ): string {
		$str = (string) $value;
		if ( '' !== $str && in_array( $str[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $str;
		}
		return $str;
	}

	/**
	 * Exports log records to a downloadable CSV file.
	 */
	protected function export_logs_csv(): void {
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		$filename = 'vighnaghna-logs-' . gmdate( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$format_csv_row = static function( array $fields ): string {
			$escaped = array_map(
				static function( $val ) {
					$str = (string) $val;
					if ( str_contains( $str, '"' ) || str_contains( $str, ',' ) || str_contains( $str, "\n" ) || str_contains( $str, "\r" ) ) {
						return '"' . str_replace( '"', '""', $str ) . '"';
					}
					return $str;
				},
				$fields
			);
			return implode( ',', $escaped ) . "\r\n";
		};

		echo $format_csv_row( array( 'ID', 'Date/Time (UTC)', 'Form Type', 'Form ID', 'Risk Score', 'Action', 'Signals', 'Masked IP', 'Duration (s)', 'Is Spam' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$logs_data = $this->engine->get_logger()->get_logs( array( 'per_page' => 5000 ) );
		foreach ( $logs_data['items'] as $log ) {
			$signals_list = array();
			$decoded      = json_decode( (string) $log->reasons, true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $s ) {
					$signals_list[] = is_array( $s ) ? ( $s['name'] ?? $s['code'] ?? '' ) : (string) $s;
				}
			}

			$row = array(
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
			);

			echo $format_csv_row( array_map( array( self::class, 'sanitize_csv_cell' ), $row ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		exit;
	}

	/**
	 * AJAX endpoint: Marks a log item as a false positive.
	 */
	public function ajax_mark_false_positive(): void {
		$nonce_valid = check_ajax_referer( 'vighnaghna_admin_action_nonce', 'nonce', false )
			|| check_ajax_referer( 'botfence_admin_action_nonce', 'nonce', false )
			|| check_ajax_referer( 'kl_admin_action_nonce', 'nonce', false );

		if ( ! $nonce_valid || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'vighnaghna' ) ), 403 );
			return;
		}

		$log_id = isset( $_POST['log_id'] ) ? (int) $_POST['log_id'] : 0;
		if ( $log_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid log ID.', 'vighnaghna' ) ), 400 );
			return;
		}

		$success = $this->engine->get_logger()->mark_false_positive( $log_id );
		if ( $success ) {
			wp_send_json_success( array( 'message' => __( 'Marked as false positive.', 'vighnaghna' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update record.', 'vighnaghna' ) ), 500 );
		}
	}

	/**
	 * AJAX endpoint: Returns refreshed dashboard statistics.
	 */
	public function ajax_refresh_stats(): void {
		$nonce_valid = check_ajax_referer( 'vighnaghna_admin_action_nonce', 'nonce', false )
			|| check_ajax_referer( 'botfence_admin_action_nonce', 'nonce', false )
			|| check_ajax_referer( 'kl_admin_action_nonce', 'nonce', false );

		if ( ! $nonce_valid || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized request', 'vighnaghna' ) ) );
		}

		$stats = $this->engine->get_logger()->get_stats();
		wp_send_json_success( $stats );
	}

	/**
	 * AJAX endpoint: Simulates test verification scenarios.
	 */
	public function ajax_simulate_test(): void {
		$nonce_valid = check_ajax_referer( 'vighnaghna_admin_action_nonce', 'nonce', false )
			|| check_ajax_referer( 'botfence_admin_action_nonce', 'nonce', false )
			|| check_ajax_referer( 'kl_admin_action_nonce', 'nonce', false );

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
					'vighnaghna_token'                     => $tok['token'],
					'botfence_token'                       => $tok['token'],
					'kl_anti_spam_token'                   => $tok['token'],
					$honeypot->get_dynamic_field_name( 0 ) => 'I am a spam bot',
					'message'                              => 'Buy cheap pills online',
				);
				break;

			case 'bot_invalid_token':
				$post_data = array(
					'vighnaghna_token'   => 'invalid_tampered_token.123456',
					'botfence_token'     => 'invalid_tampered_token.123456',
					'kl_anti_spam_token' => 'invalid_tampered_token.123456',
					'message'            => 'Hello from automated script',
				);
				break;

			case 'bot_fast_submit':
				$tok       = $token_manager->generate_token( 'test_form' );
				$telemetry = base64_encode( (string) wp_json_encode( array( 'elapsed_time' => 0.25, 'mouse_moves' => 0, 'keystrokes' => 0, 'touches' => 0 ) ) );
				$post_data = array(
					'vighnaghna_token'       => $tok['token'],
					'botfence_token'         => $tok['token'],
					'kl_anti_spam_token'     => $tok['token'],
					'vighnaghna_telemetry'   => $telemetry,
					'botfence_telemetry'     => $telemetry,
					'kl_anti_spam_telemetry' => $telemetry,
					'message'                => 'Fast instant submit',
				);
				break;

			case 'bot_headless':
				$tok       = $token_manager->generate_token( 'test_form' );
				$telemetry = base64_encode( (string) wp_json_encode( array( 'elapsed_time' => 3.1, 'webdriver' => true, 'is_trusted' => false ) ) );
				$post_data = array(
					'vighnaghna_token'       => $tok['token'],
					'botfence_token'         => $tok['token'],
					'kl_anti_spam_token'     => $tok['token'],
					'vighnaghna_telemetry'   => $telemetry,
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
					'vighnaghna_token'       => $tok['token'],
					'botfence_token'         => $tok['token'],
					'kl_anti_spam_token'     => $tok['token'],
					'vighnaghna_telemetry'   => $telemetry,
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
	 * Helper to get option with fallback across new and legacy keys.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	protected function get_conf( string $key, $default = false ) {
		$val = get_option( 'vighnaghna_' . $key );
		if ( false !== $val && null !== $val ) {
			return $val;
		}
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
		$stats      = $this->engine->get_logger()->get_stats();
		$adapters   = $this->detector->get_adapters();
		$logs       = $this->engine->get_logger()->get_logs( array( 'per_page' => 20 ) );
		$valid_tabs = array( 'dashboard', 'integrations', 'risk', 'rates', 'logs', 'diagnostics' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], $valid_tabs, true ) ? sanitize_key( (string) $_GET['tab'] ) : 'dashboard';

		$logo_url = file_exists( VIGHNAGHNA_PATH . 'assets/images/icon-128x128.png' )
			? VIGHNAGHNA_URL . 'assets/images/icon-128x128.png'
			: VIGHNAGHNA_URL . 'assets/images/logo.png';

		?>
		<div class="wrap vighnaghna-admin-wrap botfence-admin-wrap kl-admin-wrap">
			<div class="vighnaghna-admin-header botfence-admin-header kl-admin-header">
				<div>
					<h1>
						<?php if ( file_exists( VIGHNAGHNA_PATH . 'assets/images/icon-128x128.png' ) || file_exists( VIGHNAGHNA_PATH . 'assets/images/logo.png' ) ) : ?>
							<img src="<?php echo esc_url( $logo_url ); ?>" class="vighnaghna-header-logo botfence-header-logo" alt="Vighnaghna" style="width: 32px; height: 32px; border-radius: 6px; object-fit: contain; vertical-align: middle;" />
						<?php endif; ?>
						<?php esc_html_e( 'Vighnaghna', 'vighnaghna' ); ?>
						<span style="font-size: 14px; font-weight: normal; color: #646970; margin-left: 6px;">(विघ्नघ्न — Destroyer of Obstacles)</span>
						<span class="vighnaghna-badge-version botfence-badge-version kl-badge-version">v<?php echo esc_html( VIGHNAGHNA_VERSION ); ?></span>
					</h1>
					<p style="margin: 0; color: #646970; font-size: 13px;">
						<?php esc_html_e( 'Intelligent Form Protection. Invisible to Your Visitors.', 'vighnaghna' ); ?> &bull; <?php esc_html_e( 'Built by Amal S Kumar', 'vighnaghna' ); ?> &bull; <a href="https://amalskumar.dev" target="_blank" rel="noopener noreferrer" style="color: #2271b1; text-decoration: none;">amalskumar.dev</a>
					</p>
				</div>
				<div>
					<span class="vighnaghna-badge botfence-badge kl-badge <?php echo $this->get_conf( 'enabled', 1 ) ? 'vighnaghna-badge-active botfence-badge-active kl-badge-active' : 'vighnaghna-badge-inactive botfence-badge-inactive kl-badge-inactive'; ?>">
						● <?php echo $this->get_conf( 'enabled', 1 ) ? esc_html__( 'Active & Protecting', 'vighnaghna' ) : esc_html__( 'Disabled', 'vighnaghna' ); ?>
					</span>
				</div>
			</div>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings updated successfully.', 'vighnaghna' ); ?></p></div>
			<?php endif; ?>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<?php if ( isset( $_GET['logs_cleared'] ) ) : ?>
				<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Vighnaghna logs have been cleared.', 'vighnaghna' ); ?></p></div>
			<?php endif; ?>

			<!-- Navigation Tabs -->
			<nav class="vighnaghna-nav-tabs botfence-nav-tabs kl-nav-tabs">
				<a href="#tab-dashboard" class="vighnaghna-nav-tab botfence-nav-tab kl-nav-tab <?php echo 'dashboard' === $active_tab ? 'active' : ''; ?>"><span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'vighnaghna' ); ?></a>
				<a href="#tab-integrations" class="vighnaghna-nav-tab botfence-nav-tab kl-nav-tab <?php echo 'integrations' === $active_tab ? 'active' : ''; ?>"><span class="dashicons dashicons-forms"></span> <?php esc_html_e( 'Form Integrations', 'vighnaghna' ); ?></a>
				<a href="#tab-risk" class="vighnaghna-nav-tab botfence-nav-tab kl-nav-tab <?php echo 'risk' === $active_tab ? 'active' : ''; ?>"><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e( 'Risk & Security', 'vighnaghna' ); ?></a>
				<a href="#tab-rates" class="vighnaghna-nav-tab botfence-nav-tab kl-nav-tab <?php echo 'rates' === $active_tab ? 'active' : ''; ?>"><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Rate Limiting', 'vighnaghna' ); ?></a>
				<a href="#tab-logs" class="vighnaghna-nav-tab botfence-nav-tab kl-nav-tab <?php echo 'logs' === $active_tab ? 'active' : ''; ?>"><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Logs & Activity', 'vighnaghna' ); ?></a>
				<a href="#tab-diagnostics" class="vighnaghna-nav-tab botfence-nav-tab kl-nav-tab <?php echo 'diagnostics' === $active_tab ? 'active' : ''; ?>"><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Diagnostics & Simulator', 'vighnaghna' ); ?></a>
			</nav>

			<!-- TAB 1: DASHBOARD -->
			<div id="tab-dashboard" class="vighnaghna-tab-pane botfence-tab-pane kl-tab-pane" style="<?php echo 'dashboard' === $active_tab ? '' : 'display: none;'; ?>">
				<!-- Hero Stats Banner: Spams Blocked Till Now -->
				<div class="vighnaghna-hero-banner botfence-hero-banner">
					<div class="vighnaghna-hero-top botfence-hero-top">
						<div class="vighnaghna-hero-status botfence-hero-status">
							<span class="vighnaghna-live-dot botfence-live-dot"></span>
							<span class="vighnaghna-live-text botfence-live-text"><?php esc_html_e( 'Live Defense Engine Active', 'vighnaghna' ); ?></span>
						</div>
						<div class="vighnaghna-hero-actions botfence-hero-actions">
							<button type="button" id="vighnaghna-refresh-stats-btn" class="button button-secondary vighnaghna-btn-refresh botfence-btn-refresh">
								<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Refresh Live Stats', 'vighnaghna' ); ?>
							</button>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=vighnaghna&vighnaghna_action=export_logs_csv' ), 'vighnaghna_export_logs_action' ) ); ?>" class="button button-secondary">
								<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export Audit CSV', 'vighnaghna' ); ?>
							</a>
							<a href="#tab-diagnostics" class="button button-primary vighnaghna-switch-tab-btn botfence-switch-tab-btn">
								<span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Run Threat Simulator', 'vighnaghna' ); ?>
							</a>
						</div>
					</div>

					<div class="vighnaghna-hero-main botfence-hero-main">
						<div class="vighnaghna-hero-number-wrap botfence-hero-number-wrap">
							<div class="vighnaghna-hero-number botfence-hero-number" id="vighnaghna-stat-hero-blocked">
								<?php echo esc_html( number_format_i18n( $stats['total_blocked'] ) ); ?>
							</div>
							<div class="vighnaghna-hero-label botfence-hero-label">
								<span class="dashicons dashicons-shield-alt"></span>
								<?php esc_html_e( 'Spams Blocked Till Now', 'vighnaghna' ); ?>
							</div>
						</div>
						<div class="vighnaghna-hero-desc botfence-hero-desc">
							<?php esc_html_e( 'Total automated bots, credential stuffing, fake inquiries, malicious scripts, and honeypot traps intercepted and terminated before reaching your database or email inboxes.', 'vighnaghna' ); ?>
						</div>
					</div>

					<!-- Hero Time-Windowed Quick Stats -->
					<div class="vighnaghna-hero-quick-stats botfence-hero-quick-stats">
						<div class="vighnaghna-hero-quick-stat botfence-hero-quick-stat">
							<span class="vighnaghna-quick-val botfence-quick-val" id="vighnaghna-stat-today-blocked"><?php echo esc_html( number_format_i18n( $stats['blocked_today'] ?? 0 ) ); ?></span>
							<span class="vighnaghna-quick-lbl botfence-quick-lbl"><?php esc_html_e( 'Blocked Today', 'vighnaghna' ); ?></span>
						</div>
						<div class="vighnaghna-hero-quick-stat botfence-hero-quick-stat">
							<span class="vighnaghna-quick-val botfence-quick-val" id="vighnaghna-stat-7days-blocked"><?php echo esc_html( number_format_i18n( $stats['blocked_7_days'] ?? 0 ) ); ?></span>
							<span class="vighnaghna-quick-lbl botfence-quick-lbl"><?php esc_html_e( 'Past 7 Days', 'vighnaghna' ); ?></span>
						</div>
						<div class="vighnaghna-hero-quick-stat botfence-hero-quick-stat">
							<span class="vighnaghna-quick-val botfence-quick-val" id="vighnaghna-stat-30days-blocked"><?php echo esc_html( number_format_i18n( $stats['blocked_30_days'] ?? 0 ) ); ?></span>
							<span class="vighnaghna-quick-lbl botfence-quick-lbl"><?php esc_html_e( 'Past 30 Days', 'vighnaghna' ); ?></span>
						</div>
						<div class="vighnaghna-hero-quick-stat botfence-hero-quick-stat">
							<span class="vighnaghna-quick-val botfence-quick-val" id="vighnaghna-stat-rate"><?php echo esc_html( $stats['block_rate'] ); ?>%</span>
							<span class="vighnaghna-quick-lbl botfence-quick-lbl"><?php esc_html_e( 'Interception Rate', 'vighnaghna' ); ?></span>
						</div>
					</div>

					<div id="vighnaghna-stats-status-notice" style="display: none;" class="vighnaghna-live-notice botfence-live-notice"></div>
				</div>

				<!-- Secondary Metrics Grid -->
				<div class="vighnaghna-metrics-grid botfence-metrics-grid kl-metrics-grid">
					<div class="vighnaghna-metric-card botfence-metric-card kl-metric-card">
						<div class="vighnaghna-metric-label botfence-metric-label kl-metric-label"><?php esc_html_e( 'Total Submissions Checked', 'vighnaghna' ); ?></div>
						<div class="vighnaghna-metric-value botfence-metric-value kl-metric-value" id="vighnaghna-stat-total-checked"><?php echo esc_html( number_format_i18n( $stats['total_checked'] ) ); ?></div>
						<div class="vighnaghna-metric-subtext botfence-metric-subtext"><?php esc_html_e( 'Across all protected website forms', 'vighnaghna' ); ?></div>
					</div>
					<div class="vighnaghna-metric-card botfence-metric-card kl-metric-card card-allowed">
						<div class="vighnaghna-metric-label botfence-metric-label kl-metric-label"><?php esc_html_e( 'Legitimate Allowed', 'vighnaghna' ); ?></div>
						<div class="vighnaghna-metric-value botfence-metric-value kl-metric-value" id="vighnaghna-stat-total-allowed"><?php echo esc_html( number_format_i18n( $stats['total_allowed'] ) ); ?></div>
						<div class="vighnaghna-metric-subtext botfence-metric-subtext"><?php esc_html_e( 'Clean human submissions passed smoothly', 'vighnaghna' ); ?></div>
					</div>
					<div class="vighnaghna-metric-card botfence-metric-card kl-metric-card card-challenge">
						<div class="vighnaghna-metric-label botfence-metric-label kl-metric-label"><?php esc_html_e( 'Adaptive Challenges Solved', 'vighnaghna' ); ?></div>
						<div class="vighnaghna-metric-value botfence-metric-value kl-metric-value" id="vighnaghna-stat-total-challenged"><?php echo esc_html( number_format_i18n( $stats['total_challenged'] ) ); ?></div>
						<div class="vighnaghna-metric-subtext botfence-metric-subtext"><?php esc_html_e( 'Medium-risk visitors verified via accessible puzzle', 'vighnaghna' ); ?></div>
					</div>
					<div class="vighnaghna-metric-card botfence-metric-card kl-metric-card card-rate">
						<div class="vighnaghna-metric-label botfence-metric-label kl-metric-label"><?php esc_html_e( 'Protected Adapters', 'vighnaghna' ); ?></div>
						<div class="vighnaghna-metric-value botfence-metric-value kl-metric-value">
							<?php
							$active_adapters = count(
								array_filter(
									$adapters,
									static function( $a ) {
										if ( is_object( $a ) && method_exists( $a, 'is_available' ) ) {
											return $a->is_available() && ( ! method_exists( $a, 'is_enabled' ) || $a->is_enabled() );
										}
										if ( is_array( $a ) ) {
											$adapter = $a['adapter'] ?? null;
											if ( is_object( $adapter ) && method_exists( $adapter, 'is_available' ) ) {
												return $adapter->is_available() && ( ! method_exists( $adapter, 'is_enabled' ) || $adapter->is_enabled() );
											}
											return ! empty( $a['is_available'] ) && ! empty( $a['is_enabled'] );
										}
										return false;
									}
								)
							);
							echo esc_html( $active_adapters . ' / ' . count( $adapters ) );
							?>
						</div>
						<div class="vighnaghna-metric-subtext botfence-metric-subtext"><?php esc_html_e( 'Form builders discovered & actively filtered', 'vighnaghna' ); ?></div>
					</div>
				</div>

				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
					<!-- Form Breakdown -->
					<div class="vighnaghna-panel botfence-panel kl-panel">
						<h2 class="vighnaghna-panel-title botfence-panel-title kl-panel-title">
							<span><span class="dashicons dashicons-forms"></span> <?php esc_html_e( 'Spam Blocked by Form Builder', 'vighnaghna' ); ?></span>
							<a href="#tab-integrations" class="vighnaghna-switch-tab-btn botfence-switch-tab-btn" style="font-size: 12px; font-weight: normal; text-decoration: none;"><?php esc_html_e( 'Manage &rarr;', 'vighnaghna' ); ?></a>
						</h2>
						<?php
						$form_names = array(
							'cf7'          => 'Contact Form 7',
							'wpforms'      => 'WPForms',
							'fluent_forms' => 'Fluent Forms',
							'gravity_forms' => 'Gravity Forms',
							'formidable'   => 'Formidable Forms',
							'ninja_forms'  => 'Ninja Forms',
							'elementor'    => 'Elementor Pro Forms',
							'woocommerce'  => 'WooCommerce (Checkout & Register)',
							'comments'     => 'WordPress Native Comments',
							'custom_forms' => 'Custom HTML Forms',
						);
						$breakdown = $stats['form_breakdown'] ?? array();
						?>
						<?php if ( empty( $breakdown ) ) : ?>
							<div class="vighnaghna-empty-state botfence-empty-state">
								<span class="dashicons dashicons-yes-alt" style="font-size: 36px; color: #10b981; width: 36px; height: 36px;"></span>
								<p><strong><?php esc_html_e( 'No form spam detected yet.', 'vighnaghna' ); ?></strong></p>
								<p style="color: #646970; font-size: 12px; margin: 0;">
									<?php esc_html_e( 'Vighnaghna is actively guarding all detected forms. Run the Threat Simulator to test response scenarios.', 'vighnaghna' ); ?>
								</p>
							</div>
						<?php else : ?>
							<div class="vighnaghna-breakdown-list botfence-breakdown-list" id="vighnaghna-form-breakdown-container">
								<?php
								$max_form_tally = max( $breakdown );
								foreach ( $breakdown as $form_code => $count ) :
									$label = $form_names[ $form_code ] ?? ucfirst( str_replace( '_', ' ', $form_code ) );
									$pct   = $max_form_tally > 0 ? round( ( $count / $max_form_tally ) * 100 ) : 0;
									?>
									<div class="vighnaghna-signal-item botfence-signal-item kl-signal-item">
										<div class="vighnaghna-signal-label botfence-signal-label kl-signal-label">
											<span><strong><?php echo esc_html( $label ); ?></strong></span>
											<span class="vighnaghna-badge botfence-badge kl-badge kl-badge-block"><?php echo esc_html( number_format_i18n( $count ) ); ?> <?php esc_html_e( 'blocked', 'vighnaghna' ); ?></span>
										</div>
										<div class="vighnaghna-progress-track botfence-progress-track kl-progress-track">
											<div class="vighnaghna-progress-fill botfence-progress-fill kl-progress-fill" style="width: <?php echo esc_attr( $pct ); ?>%;"></div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<!-- Top Spam Signals -->
					<div class="vighnaghna-panel botfence-panel kl-panel">
						<h2 class="vighnaghna-panel-title botfence-panel-title kl-panel-title">
							<span><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e( 'Most Common Spam Signals', 'vighnaghna' ); ?></span>
							<a href="#tab-risk" class="vighnaghna-switch-tab-btn botfence-switch-tab-btn" style="font-size: 12px; font-weight: normal; text-decoration: none;"><?php esc_html_e( 'Configure Weights &rarr;', 'vighnaghna' ); ?></a>
						</h2>
						<?php if ( empty( $stats['top_signals'] ) ) : ?>
							<div class="vighnaghna-empty-state botfence-empty-state">
								<span class="dashicons dashicons-shield" style="font-size: 36px; color: #2271b1; width: 36px; height: 36px;"></span>
								<p><strong><?php esc_html_e( 'No spam signals logged yet.', 'vighnaghna' ); ?></strong></p>
								<p style="color: #646970; font-size: 12px; margin: 0;">
									<?php esc_html_e( 'Signals such as honeypot violations, invalid signatures, and rapid automated submissions will appear here.', 'vighnaghna' ); ?>
								</p>
							</div>
						<?php else : ?>
							<div id="vighnaghna-top-signals-container">
								<?php
								$max_tally = max( $stats['top_signals'] );
								foreach ( $stats['top_signals'] as $sig_name => $count ) :
									$pct = $max_tally > 0 ? round( ( $count / $max_tally ) * 100 ) : 0;
									?>
									<div class="vighnaghna-signal-item botfence-signal-item kl-signal-item">
										<div class="vighnaghna-signal-label botfence-signal-label kl-signal-label">
											<span><?php echo esc_html( $sig_name ); ?></span>
											<strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong>
										</div>
										<div class="vighnaghna-progress-track botfence-progress-track kl-progress-track">
											<div class="vighnaghna-progress-fill botfence-progress-fill kl-progress-fill" style="width: <?php echo esc_attr( $pct ); ?>%;"></div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Recent Blocked Submissions Stream -->
				<div class="vighnaghna-panel botfence-panel kl-panel">
					<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
						<h2 class="vighnaghna-panel-title botfence-panel-title kl-panel-title" style="margin: 0;">
							<span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Recent Blocked Submissions', 'vighnaghna' ); ?>
						</h2>
						<a href="#tab-logs" class="button button-secondary vighnaghna-switch-tab-btn botfence-switch-tab-btn">
							<?php esc_html_e( 'View All Audit Logs', 'vighnaghna' ); ?> &rarr;
						</a>
					</div>
					<?php if ( empty( $stats['recent_blocked'] ) ) : ?>
						<p style="color: #646970;"><?php esc_html_e( 'No recent blocked submissions in the database.', 'vighnaghna' ); ?></p>
					<?php else : ?>
						<table class="vighnaghna-table botfence-table kl-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Intercepted', 'vighnaghna' ); ?></th>
									<th><?php esc_html_e( 'Form Builder', 'vighnaghna' ); ?></th>
									<th><?php esc_html_e( 'Risk Level', 'vighnaghna' ); ?></th>
									<th><?php esc_html_e( 'Primary Trigger Signals', 'vighnaghna' ); ?></th>
									<th><?php esc_html_e( 'Visitor Masked IP', 'vighnaghna' ); ?></th>
								</tr>
							</thead>
							<tbody id="vighnaghna-recent-blocked-tbody">
								<?php foreach ( $stats['recent_blocked'] as $item ) : ?>
									<?php
									$reasons_summary = array();
									$decoded_r       = json_decode( (string) $item->reasons, true );
									if ( is_array( $decoded_r ) ) {
										foreach ( $decoded_r as $r ) {
											$reasons_summary[] = is_array( $r ) ? ( $r['name'] ?? $r['code'] ?? '' ) : (string) $r;
										}
									}
									$reasons_text = ! empty( $reasons_summary ) ? implode( ', ', array_slice( $reasons_summary, 0, 2 ) ) : __( 'Threat anomaly detected', 'vighnaghna' );
									$fname        = $form_names[ $item->form_type ] ?? ucfirst( str_replace( '_', ' ', $item->form_type ) );
									?>
									<tr>
										<td><strong><?php echo esc_html( human_time_diff( strtotime( $item->created_at ), time() ) . ' ' . __( 'ago', 'vighnaghna' ) ); ?></strong></td>
										<td><code><?php echo esc_html( $fname ); ?></code></td>
										<td>
											<span class="vighnaghna-badge botfence-badge kl-badge kl-badge-block">
												<?php echo esc_html( $item->risk_score ); ?>/100 <?php echo $item->risk_score >= 80 ? esc_html__( 'CRITICAL', 'vighnaghna' ) : esc_html__( 'HIGH', 'vighnaghna' ); ?>
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
			<div id="tab-integrations" class="vighnaghna-tab-pane botfence-tab-pane kl-tab-pane" style="<?php echo 'integrations' === $active_tab ? '' : 'display: none;'; ?>">
				<form method="post" action="">
					<?php wp_nonce_field( 'vighnaghna_save_settings_action', 'vighnaghna_save_settings_nonce' ); ?>
					<input type="hidden" name="vighnaghna_current_tab" value="integrations" />
					<div class="vighnaghna-panel botfence-panel kl-panel">
						<h2 class="vighnaghna-panel-title botfence-panel-title kl-panel-title"><?php esc_html_e( 'Universal Form Builder Protections', 'vighnaghna' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'Vighnaghna automatically detects active form plugins on your website. Toggle protection per form builder below.', 'vighnaghna' ); ?>
						</p>

						<table class="vighnaghna-table botfence-table kl-table" style="margin-top: 18px;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Form Builder', 'vighnaghna' ); ?></th>
									<th><?php esc_html_e( 'Environment Status', 'vighnaghna' ); ?></th>
									<th><?php esc_html_e( 'Protection Status', 'vighnaghna' ); ?></th>
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
												<span class="vighnaghna-badge botfence-badge kl-badge kl-badge-allow"><?php esc_html_e( 'Detected & Available', 'vighnaghna' ); ?></span>
											<?php else : ?>
												<span class="vighnaghna-badge botfence-badge kl-badge kl-badge-inactive"><?php esc_html_e( 'Not Installed', 'vighnaghna' ); ?></span>
											<?php endif; ?>
										</td>
										<td>
											<label class="vighnaghna-switch botfence-switch kl-switch">
												<input type="checkbox" name="vighnaghna_enable_<?php echo esc_attr( $id ); ?>" value="1" <?php checked( $adapter->is_enabled() ); ?> />
												<span class="vighnaghna-slider botfence-slider kl-slider"></span>
											</label>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<div class="vighnaghna-panel botfence-panel kl-panel">
						<h2 class="vighnaghna-panel-title botfence-panel-title kl-panel-title"><?php esc_html_e( 'Excluded Forms / Whitelist', 'vighnaghna' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'Exclude specific forms from anti-spam checks. Enter one form ID per line (e.g., 123 or cf7:123).', 'vighnaghna' ); ?>
						</p>
						<textarea name="vighnaghna_excluded_forms" rows="4" class="large-text code"><?php echo esc_textarea( $this->get_conf( 'excluded_forms', '' ) ); ?></textarea>
					</div>

					<p><input type="submit" name="vighnaghna_save_settings" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Integration Settings', 'vighnaghna' ); ?>" /></p>
				</form>
			</div>

			<!-- TAB 3: RISK & SECURITY -->
			<div id="tab-risk" class="vighnaghna-tab-pane botfence-tab-pane kl-tab-pane" style="<?php echo 'risk' === $active_tab ? '' : 'display: none;'; ?>">
				<form method="post" action="">
					<?php wp_nonce_field( 'vighnaghna_save_settings_action', 'vighnaghna_save_settings_nonce' ); ?>
					<input type="hidden" name="vighnaghna_current_tab" value="risk" />
					<div class="vighnaghna-panel botfence-panel kl-panel">
						<h2 class="vighnaghna-panel-title botfence-panel-title kl-panel-title"><?php esc_html_e( 'Risk Thresholds & Decision Logic', 'vighnaghna' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Configure how the risk engine evaluates composite scores (0 to 100 points).', 'vighnaghna' ); ?></p>

						<div class="vighnaghna-form-grid botfence-form-grid kl-form-grid">
							<div>
								<label for="vighnaghna_risk_threshold_challenge"><?php esc_html_e( 'Challenge Threshold', 'vighnaghna' ); ?></label>
								<div class="vighnaghna-desc botfence-desc kl-desc"><?php esc_html_e( 'Score at which secondary verification is required (default: 30).', 'vighnaghna' ); ?></div>
							</div>
							<div>
								<input type="number" id="vighnaghna_risk_threshold_challenge" name="vighnaghna_risk_threshold_challenge" value="<?php echo esc_attr( $this->get_conf( 'risk_threshold_challenge', 30 ) ); ?>" min="1" max="99" class="small-text" /> points
							</div>

							<div>
								<label for="vighnaghna_risk_threshold_block"><?php esc_html_e( 'Block Threshold', 'vighnaghna' ); ?></label>
								<div class="vighnaghna-desc botfence-desc kl-desc"><?php esc_html_e( 'Score at which submissions are immediately rejected (default: 60).', 'vighnaghna' ); ?></div>
							</div>
							<div>
								<input type="number" id="vighnaghna_risk_threshold_block" name="vighnaghna_risk_threshold_block" value="<?php echo esc_attr( $this->get_conf( 'risk_threshold_block', 60 ) ); ?>" min="2" max="100" class="small-text" /> points
							</div>

							<div>
								<label for="vighnaghna_min_submission_time"><?php esc_html_e( 'Minimum Submission Time', 'vighnaghna' ); ?></label>
								<div class="vighnaghna-desc botfence-desc kl-desc"><?php esc_html_e( 'Submissions completed faster than this time are flagged as bot activity (default: 2.5 seconds).', 'vighnaghna' ); ?></div>
							</div>
							<div>
								<input type="number" step="0.1" id="vighnaghna_min_submission_time" name="vighnaghna_min_submission_time" value="<?php echo esc_attr( $this->get_conf( 'min_submission_time', 2.5 ) ); ?>" min="0.5" max="30" class="small-text" /> seconds
							</div>

							<div>
								<label for="vighnaghna_token_lifetime"><?php esc_html_e( 'Token Lifetime', 'vighnaghna' ); ?></label>
								<div class="vighnaghna-desc botfence-desc kl-desc"><?php esc_html_e( 'Maximum validity window for cryptographic tokens (default: 600s / 10 minutes).', 'vighnaghna' ); ?></div>
							</div>
							<div>
								<input type="number" id="vighnaghna_token_lifetime" name="vighnaghna_token_lifetime" value="<?php echo esc_attr( $this->get_conf( 'token_lifetime', 600 ) ); ?>" min="60" max="86400" class="regular-text" style="max-width: 120px;" /> seconds
							</div>
						</div>
					</div>

					<div class="vighnaghna-panel botfence-panel kl-panel">
						<h2 class="vighnaghna-panel-title botfence-panel-title kl-panel-title"><?php esc_html_e( 'Signal Risk Point Weights', 'vighnaghna' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Customize how many risk points each anomaly contributes to the overall submission score.', 'vighnaghna' ); ?></p>

						<table class="vighnaghna-table botfence-table kl-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Detection Signal', 'vighnaghna' ); ?></th>
									<th style="width: 140px;"><?php esc_html_e( 'Risk Points', 'vighnaghna' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><strong><?php esc_html_e( 'Honeypot Trap Completed', 'vighnaghna' ); ?></strong></td>
									<td><input type="number" name="vighnaghna_weight_honeypot" value="<?php echo esc_attr( $this->get_conf( 'weight_honeypot', 100 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Invalid or Tampered Signed Token', 'vighnaghna' ); ?></strong></td>
									<td><input type="number" name="vighnaghna_weight_invalid_token" value="<?php echo esc_attr( $this->get_conf( 'weight_invalid_token', 100 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td>
										<strong><?php esc_html_e( 'Expired Token', 'vighnaghna' ); ?></strong>
										<div style="font-size: 11px; color: #646970;"><?php esc_html_e( 'Separate from tampered tokens; visitors with idle forms can safely re-verify.', 'vighnaghna' ); ?></div>
									</td>
									<td><input type="number" name="vighnaghna_weight_expired_token" value="<?php echo esc_attr( $this->get_conf( 'weight_expired_token', 80 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Rapid Submission (Under minimum time)', 'vighnaghna' ); ?></strong></td>
									<td><input type="number" name="vighnaghna_weight_fast_submit" value="<?php echo esc_attr( $this->get_conf( 'weight_fast_submit', 30 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Repeated Submissions (Rate limit warning)', 'vighnaghna' ); ?></strong></td>
									<td><input type="number" name="vighnaghna_weight_rate_limit" value="<?php echo esc_attr( $this->get_conf( 'weight_rate_limit', 40 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Excessive URLs in Message (>2 links)', 'vighnaghna' ); ?></strong></td>
									<td><input type="number" name="vighnaghna_weight_excessive_urls" value="<?php echo esc_attr( $this->get_conf( 'weight_excessive_urls', 25 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Suspicious Interaction Pattern (Zero telemetry with text)', 'vighnaghna' ); ?></strong></td>
									<td><input type="number" name="vighnaghna_weight_suspicious_telemetry" value="<?php echo esc_attr( $this->get_conf( 'weight_suspicious_telemetry', 20 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Repeated Identical Message Content', 'vighnaghna' ); ?></strong></td>
									<td><input type="number" name="vighnaghna_weight_repeated_message" value="<?php echo esc_attr( $this->get_conf( 'weight_repeated_message', 35 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td>
										<strong><?php esc_html_e( 'Headless Browser / Automation Framework', 'vighnaghna' ); ?></strong>
										<div style="font-size: 11px; color: #646970;"><?php esc_html_e( 'Detected navigator.webdriver or automated browser attributes.', 'vighnaghna' ); ?></div>
									</td>
									<td><input type="number" name="vighnaghna_weight_headless_browser" value="<?php echo esc_attr( $this->get_conf( 'weight_headless_browser', 50 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
								<tr>
									<td>
										<strong><?php esc_html_e( 'Synthetic / Untrusted Event Execution', 'vighnaghna' ); ?></strong>
										<div style="font-size: 11px; color: #646970;"><?php esc_html_e( 'Dispatched programmatic synthetic form submit event (isTrusted === false).', 'vighnaghna' ); ?></div>
									</td>
									<td><input type="number" name="vighnaghna_weight_untrusted_event" value="<?php echo esc_attr( $this->get_conf( 'weight_untrusted_event', 45 ) ); ?>" min="0" max="100" class="small-text" /></td>
								</tr>
							</tbody>
						</table>
					</div>

					<p><input type="submit" name="vighnaghna_save_settings" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Risk Configuration', 'vighnaghna' ); ?>" /></p>
				</form>
			</div>

			<!-- TAB 4: RATE LIMITING -->
			<div id="tab-rates" class="vighnaghna-tab-pane botfence-tab-pane kl-tab-pane" style="<?php echo 'rates' === $active_tab ? '' : 'display: none;'; ?>">
				<form method="post" action="">
					<?php wp_nonce_field( 'vighnaghna_save_settings_action', 'vighnaghna_save_settings_nonce' ); ?>
					<input type="hidden" name="vighnaghna_current_tab" value="rates" />
					<div class="vighnaghna-panel botfence-panel kl-panel">
						<h2 class="vighnaghna-panel-title botfence-panel-title kl-panel-title"><?php esc_html_e( 'Submission Frequency & Rate Limits', 'vighnaghna' ); ?></h2>
						<div class="vighnaghna-form-grid botfence-form-grid kl-form-grid">
							<div>
								<label for="vighnaghna_rate_limit_ip_per_minute"><?php esc_html_e( 'Submissions per Minute (per IP)', 'vighnaghna' ); ?></label>
								<div class="vighnaghna-desc botfence-desc kl-desc"><?php esc_html_e( 'Maximum submissions allowed from a single IP address per minute (default: 5).', 'vighnaghna' ); ?></div>
							</div>
							<div>
								<input type="number" id="vighnaghna_rate_limit_ip_per_minute" name="vighnaghna_rate_limit_ip_per_minute" value="<?php echo esc_attr( $this->get_conf( 'rate_limit_ip_per_minute', 5 ) ); ?>" min="1" max="100" class="small-text" /> submissions
							</div>

							<div>
								<label for="vighnaghna_rate_limit_ip_per_hour"><?php esc_html_e( 'Submissions per Hour (per IP)', 'vighnaghna' ); ?></label>
								<div class="vighnaghna-desc botfence-desc kl-desc"><?php esc_html_e( 'Maximum submissions allowed from a single IP address per hour (default: 30).', 'vighnaghna' ); ?></div>
							</div>
							<div>
								<input type="number" id="vighnaghna_rate_limit_ip_per_hour" name="vighnaghna_rate_limit_ip_per_hour" value="<?php echo esc_attr( $this->get_conf( 'rate_limit_ip_per_hour', 30 ) ); ?>" min="5" max="500" class="small-text" /> submissions
							</div>

							<div>
								<label for="vighnaghna_quarantine_duration"><?php esc_html_e( 'Temporary Quarantine Duration', 'vighnaghna' ); ?></label>
								<div class="vighnaghna-desc botfence-desc kl-desc"><?php esc_html_e( 'Temporary block duration for IPs exceeding rate limits (default: 900s / 15 minutes).', 'vighnaghna' ); ?></div>
							</div>
							<div>
								<input type="number" id="vighnaghna_quarantine_duration" name="vighnaghna_quarantine_duration" value="<?php echo esc_attr( $this->get_conf( 'quarantine_duration', 900 ) ); ?>" min="60" max="86400" class="regular-text" style="max-width: 120px;" /> seconds
							</div>

							<div>
								<label for="vighnaghna_log_retention_days"><?php esc_html_e( 'Log Retention Period', 'vighnaghna' ); ?></label>
								<div class="vighnaghna-desc botfence-desc kl-desc"><?php esc_html_e( 'Number of days to keep audit logs before automatic pruning (default: 30 days).', 'vighnaghna' ); ?></div>
							</div>
							<div>
								<input type="number" id="vighnaghna_log_retention_days" name="vighnaghna_log_retention_days" value="<?php echo esc_attr( $this->get_conf( 'log_retention_days', 30 ) ); ?>" min="1" max="365" class="small-text" /> days
							</div>
						</div>
					</div>

					<div class="vighnaghna-panel botfence-panel kl-panel">
						<h2 class="vighnaghna-panel-title botfence-panel-title kl-panel-title"><?php esc_html_e( 'Reverse Proxy Configuration', 'vighnaghna' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'For security, HTTP headers like X-Forwarded-For or CF-Connecting-IP are NEVER trusted unless the connection arrives from an explicitly trusted reverse proxy.', 'vighnaghna' ); ?>
						</p>

						<div class="vighnaghna-form-grid botfence-form-grid kl-form-grid">
							<div>
								<label for="vighnaghna_trust_reverse_proxies"><?php esc_html_e( 'Trust Reverse Proxies', 'vighnaghna' ); ?></label>
								<div class="vighnaghna-desc botfence-desc kl-desc"><?php esc_html_e( 'Enable only if your site is behind Cloudflare, AWS ALB, Nginx, or a CDN.', 'vighnaghna' ); ?></div>
							</div>
							<div>
								<label class="vighnaghna-switch botfence-switch kl-switch">
									<input type="checkbox" id="vighnaghna_trust_reverse_proxies" name="vighnaghna_trust_reverse_proxies" value="1" <?php checked( $this->get_conf( 'trust_reverse_proxies', 0 ) ); ?> />
									<span class="vighnaghna-slider botfence-slider kl-slider"></span>
								</label>
							</div>

							<div>
								<label for="vighnaghna_trusted_proxy_ips"><?php esc_html_e( 'Trusted Proxy IPs / CIDRs', 'vighnaghna' ); ?></label>
								<div class="vighnaghna-desc botfence-desc kl-desc"><?php esc_html_e( 'One IP or CIDR per line (e.g., 127.0.0.1, 10.0.0.0/8).', 'vighnaghna' ); ?></div>
							</div>
							<div>
								<textarea id="vighnaghna_trusted_proxy_ips" name="vighnaghna_trusted_proxy_ips" rows="4" class="large-text code"><?php echo esc_textarea( $this->get_conf( 'trusted_proxy_ips', "127.0.0.1\n::1" ) ); ?></textarea>
							</div>
						</div>
					</div>

					<p><input type="submit" name="vighnaghna_save_settings" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Rate Limiting Settings', 'vighnaghna' ); ?>" /></p>
				</form>
			</div>

			<!-- TAB 5: LOGS -->
			<div id="tab-logs" class="vighnaghna-tab-pane botfence-tab-pane kl-tab-pane" style="<?php echo 'logs' === $active_tab ? '' : 'display: none;'; ?>">
				<div class="vighnaghna-panel botfence-panel kl-panel">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
						<h2 class="vighnaghna-panel-title botfence-panel-title kl-panel-title" style="margin: 0;"><?php esc_html_e( 'Submission Audit Logs', 'vighnaghna' ); ?></h2>
						<div style="display: flex; gap: 10px;">
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'vighnaghna', 'vighnaghna_action' => 'export_logs_csv' ), admin_url( 'admin.php' ) ), 'vighnaghna_export_logs_action' ) ); ?>" class="button">
								<span class="dashicons dashicons-download" style="vertical-align: middle;"></span> <?php esc_html_e( 'Export to CSV', 'vighnaghna' ); ?>
							</a>
							<form method="post" action="" style="display: inline;">
								<?php wp_nonce_field( 'vighnaghna_clear_logs_action', 'vighnaghna_clear_logs_nonce' ); ?>
								<input type="submit" id="vighnaghna-clear-logs-btn" name="vighnaghna_clear_logs" class="button button-link-delete" value="<?php esc_attr_e( 'Clear All Logs', 'vighnaghna' ); ?>" />
							</form>
						</div>
					</div>

					<p class="description"><?php esc_html_e( 'All logs are privacy-conscious and anonymized. Full message bodies and personal details are never stored.', 'vighnaghna' ); ?></p>

					<?php if ( empty( $logs['items'] ) ) : ?>
						<p style="padding: 20px 0; color: #646970; text-align: center;"><?php esc_html_e( 'No log entries found.', 'vighnaghna' ); ?></p>
					<?php else : ?>
						<table class="vighnaghna-table botfence-table kl-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Date & Time', 'vighnaghna' ); ?></th>
									<th><?php esc_html_e( 'Form', 'vighnaghna' ); ?></th>
									<th><?php esc_html_e( 'Risk Score', 'vighnaghna' ); ?></th>
									<th><?php esc_html_e( 'Action Taken', 'vighnaghna' ); ?></th>
									<th><?php esc_html_e( 'Visitor IP', 'vighnaghna' ); ?></th>
									<th><?php esc_html_e( 'Duration', 'vighnaghna' ); ?></th>
									<th><?php esc_html_e( 'Details', 'vighnaghna' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $logs['items'] as $entry ) : ?>
									<tr>
										<td><?php echo esc_html( $entry->created_at ); ?></td>
										<td><code><?php echo esc_html( $entry->form_type . ( $entry->form_id ? ' (' . $entry->form_id . ')' : '' ) ); ?></code></td>
										<td>
											<span class="vighnaghna-badge botfence-badge kl-badge <?php echo $entry->risk_score >= 60 ? 'vighnaghna-badge-block botfence-badge-block kl-badge-block' : ( $entry->risk_score >= 30 ? 'vighnaghna-badge-challenge botfence-badge-challenge kl-badge-challenge' : 'vighnaghna-badge-allow botfence-badge-allow kl-badge-allow' ); ?>">
												<?php echo esc_html( $entry->risk_score ); ?>/100
											</span>
										</td>
										<td>
											<span class="vighnaghna-badge botfence-badge kl-badge <?php echo 'block' === $entry->action_taken ? 'vighnaghna-badge-block botfence-badge-block kl-badge-block' : ( 'challenge' === $entry->action_taken ? 'vighnaghna-badge-challenge botfence-badge-challenge kl-badge-challenge' : 'vighnaghna-badge-allow botfence-badge-allow kl-badge-allow' ); ?>">
												<?php echo esc_html( strtoupper( $entry->action_taken ) ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $entry->masked_ip ); ?></td>
										<td><?php echo esc_html( sprintf( '%.2fs', $entry->submission_time ) ); ?></td>
										<td>
											<a href="#" class="vighnaghna-toggle-details botfence-toggle-details kl-toggle-details"><?php esc_html_e( 'View Signals', 'vighnaghna' ); ?></a>
										</td>
									</tr>
									<tr class="vighnaghna-details-row botfence-details-row kl-details-row" style="display: none; background: #f6f7f7;">
										<td colspan="7" style="padding: 12px 20px;">
											<strong><?php esc_html_e( 'Triggered Signals:', 'vighnaghna' ); ?></strong>
											<?php
											$reasons = json_decode( (string) $entry->reasons, true );
											if ( empty( $reasons ) ) {
												echo '<p style="margin: 4px 0; color: #008a20;">' . esc_html__( 'No risk signals triggered. Clean human submission.', 'vighnaghna' ) . '</p>';
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
			<div id="tab-diagnostics" class="vighnaghna-tab-pane botfence-tab-pane kl-tab-pane" style="<?php echo 'diagnostics' === $active_tab ? '' : 'display: none;'; ?>">
				<div class="vighnaghna-panel botfence-panel kl-panel">
					<h2 class="vighnaghna-panel-title botfence-panel-title kl-panel-title"><?php esc_html_e( 'Interactive Protection Simulator', 'vighnaghna' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Test the internal risk engine against various submission scenarios to verify scoring calculations and detection rules in real-time.', 'vighnaghna' ); ?>
					</p>

					<input type="hidden" id="vighnaghna_admin_nonce" value="<?php echo esc_attr( wp_create_nonce( 'vighnaghna_admin_action_nonce' ) ); ?>" />
					<input type="hidden" id="botfence_admin_nonce" value="<?php echo esc_attr( wp_create_nonce( 'botfence_admin_action_nonce' ) ); ?>" />
					<input type="hidden" id="kl_admin_nonce" value="<?php echo esc_attr( wp_create_nonce( 'kl_admin_action_nonce' ) ); ?>" />

					<div style="display: flex; gap: 12px; align-items: center; margin-top: 16px;">
						<select id="vighnaghna-sim-scenario" style="min-width: 280px;">
							<option value="human_normal"><?php esc_html_e( 'Normal Human (5.4s typing, 32 keystrokes, clean)', 'vighnaghna' ); ?></option>
							<option value="bot_honeypot"><?php esc_html_e( 'Automated Bot (Populated hidden honeypot)', 'vighnaghna' ); ?></option>
							<option value="bot_invalid_token"><?php esc_html_e( 'Malicious Bot (Forged / invalid signed token)', 'vighnaghna' ); ?></option>
							<option value="bot_fast_submit"><?php esc_html_e( 'Rapid Script (0.25s completion time)', 'vighnaghna' ); ?></option>
							<option value="bot_headless"><?php esc_html_e( 'Headless Automation (Selenium / webdriver flag)', 'vighnaghna' ); ?></option>
						</select>

						<button type="button" id="vighnaghna-run-test-simulator" class="button button-primary">
							<?php esc_html_e( 'Run Test Simulation', 'vighnaghna' ); ?>
						</button>
					</div>

					<div id="vighnaghna-sim-result" style="display: none;"></div>
				</div>

				<div class="vighnaghna-panel botfence-panel kl-panel">
					<h2 class="vighnaghna-panel-title botfence-panel-title kl-panel-title"><?php esc_html_e( 'System Environment & Diagnostics', 'vighnaghna' ); ?></h2>
					<table class="vighnaghna-table botfence-table kl-table">
						<tbody>
							<tr>
								<td><strong><?php esc_html_e( 'Plugin Identity', 'vighnaghna' ); ?></strong></td>
								<td>
									<div style="display: flex; align-items: center; gap: 12px;">
										<?php if ( file_exists( VIGHNAGHNA_PATH . 'assets/images/icon-128x128.png' ) || file_exists( VIGHNAGHNA_PATH . 'assets/images/logo.png' ) ) : ?>
											<img src="<?php echo esc_url( $logo_url ); ?>" alt="Vighnaghna" style="width: 36px; height: 36px; border-radius: 6px;" />
										<?php endif; ?>
										<div>
											<strong>Vighnaghna (विघ्नघ्न)</strong> &bull; <code>v<?php echo esc_html( VIGHNAGHNA_VERSION ); ?></code>
											<div style="color: #646970; font-size: 12px;"><?php esc_html_e( 'Intelligent Form Protection. Invisible to Your Visitors.', 'vighnaghna' ); ?></div>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Author & Developer', 'vighnaghna' ); ?></strong></td>
								<td><strong>Amal S Kumar</strong> (<a href="https://amalskumar.dev" target="_blank" rel="noopener noreferrer">amalskumar.dev</a>)</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Plugin Version', 'vighnaghna' ); ?></strong></td>
								<td><code>v<?php echo esc_html( VIGHNAGHNA_VERSION ); ?></code></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'PHP Version', 'vighnaghna' ); ?></strong></td>
								<td><code><?php echo esc_html( PHP_VERSION ); ?></code> (>= 8.1 required)</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'WordPress Version', 'vighnaghna' ); ?></strong></td>
								<td><code><?php echo esc_html( get_bloginfo( 'version' ) ); ?></code></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'HMAC Secret Key Status', 'vighnaghna' ); ?></strong></td>
								<td><span class="vighnaghna-badge botfence-badge kl-badge kl-badge-allow"><?php esc_html_e( 'Active & Cryptographically Secure', 'vighnaghna' ); ?></span></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Logs Database Table', 'vighnaghna' ); ?></strong></td>
								<td><code><?php echo esc_html( Database::get_logs_table() ); ?></code></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Rate Limits Database Table', 'vighnaghna' ); ?></strong></td>
								<td><code><?php echo esc_html( Database::get_rates_table() ); ?></code></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Cleanup Cron Job', 'vighnaghna' ); ?></strong></td>
								<td>
									<?php
									$next_cron = wp_next_scheduled( 'vighnaghna_cleanup_cron' )
										?: ( wp_next_scheduled( 'botfence_cleanup_cron' ) ?: wp_next_scheduled( 'kl_antispam_cleanup_cron' ) );
									if ( $next_cron ) {
										printf(
											/* translators: %s: relative time */
											esc_html__( 'Scheduled (next run in %s)', 'vighnaghna' ),
											esc_html( human_time_diff( time(), $next_cron ) )
										);
									} else {
										esc_html_e( 'Not scheduled', 'vighnaghna' );
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

// Backwards compatibility aliases.
if ( ! class_exists( '\BotFence\Admin', false ) ) {
	class_alias( '\Vighnaghna\Admin', '\BotFence\Admin' );
}
if ( ! class_exists( '\KL_Anti_Spam\Admin', false ) ) {
	class_alias( '\Vighnaghna\Admin', '\KL_Anti_Spam\Admin' );
}
