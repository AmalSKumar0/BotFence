<?php
/**
 * Main Plugin Coordinator for KL Universal Anti-Spam.
 *
 * @package KL_Anti_Spam
 */

namespace KL_Anti_Spam;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	protected static ?Plugin $instance = null;

	/**
	 * Subsystem instances.
	 */
	protected Token_Manager $token_manager;
	protected Honeypot $honeypot;
	protected Rate_Limiter $rate_limiter;
	protected Risk_Engine $risk_engine;
	protected Logger $logger;
	protected Captcha_Engine $captcha_engine;
	protected Form_Detector $form_detector;
	protected ?Admin $admin = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->setup_dependencies();
		$this->init_hooks();
	}

	/**
	 * Initializes all subsystem instances.
	 */
	protected function setup_dependencies(): void {
		$this->token_manager  = new Token_Manager();
		$this->honeypot       = new Honeypot();
		$this->rate_limiter   = new Rate_Limiter();
		$this->risk_engine    = new Risk_Engine();
		$this->logger         = new Logger();
		$this->captcha_engine = new Captcha_Engine(
			$this->token_manager,
			$this->honeypot,
			$this->rate_limiter,
			$this->risk_engine,
			$this->logger
		);
		$this->form_detector  = new Form_Detector( $this->captcha_engine );

		if ( is_admin() ) {
			$this->admin = new Admin( $this->captcha_engine, $this->form_detector );
		}
	}

	/**
	 * Registers WordPress core hooks.
	 */
	protected function init_hooks(): void {
		// Initialize form adapters.
		$this->form_detector->init_adapters();

		// Enqueue frontend scripts & styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Register REST API endpoints for caching compatibility.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// AJAX fallback endpoint for token refresh.
		add_action( 'wp_ajax_kl_refresh_token', array( $this, 'ajax_refresh_token' ) );
		add_action( 'wp_ajax_nopriv_kl_refresh_token', array( $this, 'ajax_refresh_token' ) );

		// Scheduled cron cleanup.
		add_action( 'kl_antispam_cleanup_cron', array( $this, 'scheduled_cleanup' ) );

		// Initialize admin interface if in dashboard.
		if ( $this->admin ) {
			$this->admin->init();
		}
	}

	/**
	 * Enqueues frontend scripts and CSS.
	 */
	public function enqueue_frontend_assets(): void {
		if ( ! (bool) get_option( 'kl_antispam_enabled', 1 ) ) {
			return;
		}

		wp_enqueue_style(
			'kl-anti-spam-css',
			KL_ANTISPAM_URL . 'assets/css/kl-anti-spam.css',
			array(),
			KL_ANTISPAM_VERSION
		);

		$script_asset = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'kl-anti-spam.js' : 'kl-anti-spam.min.js';
		wp_enqueue_script(
			'kl-anti-spam-js',
			KL_ANTISPAM_URL . 'assets/js/' . $script_asset,
			array(),
			KL_ANTISPAM_VERSION,
			true
		);

		// Localize script data for caching compatibility and telemetry collection.
		$script_config = array(
			'restUrl'       => esc_url_raw( rest_url( 'kl-anti-spam/v1/' ) ),
			'ajaxUrl'       => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'tokenLifetime' => (int) get_option( 'kl_antispam_token_lifetime', Token_Manager::DEFAULT_LIFETIME ),
			'minTime'       => (float) get_option( 'kl_antispam_min_submission_time', 2.5 ),
		);

		wp_localize_script( 'kl-anti-spam-js', 'klAntiSpamSettings', $script_config );
	}

	/**
	 * Registers REST API endpoints.
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'kl-anti-spam/v1',
			'/token',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_token' ),
				'permission_callback' => '__return_true', // Public token issuance for visitor forms.
			)
		);
	}

	/**
	 * REST route callback for dynamic token issuance on cached pages.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function rest_get_token( WP_REST_Request $request ): WP_REST_Response {
		$client_ip = $this->rate_limiter->get_client_ip();
		$form_id   = sanitize_key( (string) $request->get_param( 'form_id' ) );
		$token_data = $this->token_manager->generate_token( ! empty( $form_id ) ? $form_id : 'cached_page', $client_ip );

		return new WP_REST_Response(
			array(
				'token'      => $token_data['token'],
				'expires_at' => $token_data['expires_at'],
			),
			200
		);
	}

	/**
	 * AJAX endpoint callback for token refresh fallback.
	 */
	public function ajax_refresh_token(): void {
		$client_ip = $this->rate_limiter->get_client_ip();
		$form_id   = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : 'cached_page'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$token_data = $this->token_manager->generate_token( $form_id, $client_ip );

		wp_send_json_success(
			array(
				'token'      => $token_data['token'],
				'expires_at' => $token_data['expires_at'],
			)
		);
	}

	/**
	 * Scheduled cron cleanup for expired rate limits and aged logs.
	 */
	public function scheduled_cleanup(): void {
		$retention_days = (int) get_option( 'kl_antispam_log_retention_days', 30 );
		Database::purge_old_logs( $retention_days );
		Database::purge_expired_rate_limits();
	}

	/**
	 * Getter for CAPTCHA Engine.
	 *
	 * @return Captcha_Engine
	 */
	public function get_captcha_engine(): Captcha_Engine {
		return $this->captcha_engine;
	}

	/**
	 * Getter for Form Detector.
	 *
	 * @return Form_Detector
	 */
	public function get_form_detector(): Form_Detector {
		return $this->form_detector;
	}
}
