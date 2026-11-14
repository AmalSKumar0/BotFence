<?php
/**
 * Main Plugin Coordinator for Vighnaghna.
 *
 * @package Vighnaghna
 */

namespace Vighnaghna;

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

		// Register and conditionally enqueue frontend scripts & styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Register REST API endpoints for caching compatibility.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// AJAX fallback endpoint for token refresh.
		add_action( 'wp_ajax_vighnaghna_refresh_token', array( $this, 'ajax_refresh_token' ) );
		add_action( 'wp_ajax_nopriv_vighnaghna_refresh_token', array( $this, 'ajax_refresh_token' ) );
		add_action( 'wp_ajax_botfence_refresh_token', array( $this, 'ajax_refresh_token' ) );
		add_action( 'wp_ajax_nopriv_botfence_refresh_token', array( $this, 'ajax_refresh_token' ) );
		add_action( 'wp_ajax_kl_refresh_token', array( $this, 'ajax_refresh_token' ) );
		add_action( 'wp_ajax_nopriv_kl_refresh_token', array( $this, 'ajax_refresh_token' ) );

		// Scheduled cron cleanup.
		add_action( 'vighnaghna_cleanup_cron', array( $this, 'scheduled_cleanup' ) );
		add_action( 'botfence_cleanup_cron', array( $this, 'scheduled_cleanup' ) );
		add_action( 'kl_antispam_cleanup_cron', array( $this, 'scheduled_cleanup' ) );

		// Initialize admin interface if in dashboard.
		if ( $this->admin ) {
			$this->admin->init();
		}
	}

	/**
	 * Checks whether frontend scripts and styles should be enqueued for the current page.
	 *
	 * Prevents loading scripts on every page. Assets are only enqueued on pages where
	 * protected forms, comments, or form builder shortcodes/blocks exist.
	 *
	 * @return bool True if assets should be enqueued.
	 */
	public function should_enqueue_frontend_assets(): bool {
		// 1. Comments open on single post / page.
		if ( function_exists( 'is_singular' ) && is_singular() && function_exists( 'comments_open' ) && comments_open() ) {
			$comments_enabled = get_option( 'vighnaghna_enable_comments' );
			if ( false === $comments_enabled || null === $comments_enabled ) {
				$comments_enabled = get_option( 'botfence_enable_comments' );
			}
			if ( false === $comments_enabled || null === $comments_enabled ) {
				$comments_enabled = get_option( 'kl_antispam_enable_comments', 1 );
			}
			if ( (bool) $comments_enabled ) {
				return true;
			}
		}

		// 2. WooCommerce checkout, registration, or my-account pages.
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return true;
		}

		// 3. Inspect post content for known form builder shortcodes and blocks.
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && ! empty( $post->post_content ) ) {
				$content = $post->post_content;

				// Known shortcodes for supported form builders.
				$shortcodes = array(
					'contact-form-7',
					'wpforms',
					'fluentform',
					'gravityform',
					'formidable',
					'ninja_form',
					'elementor-template',
				);

				foreach ( $shortcodes as $tag ) {
					if ( function_exists( 'has_shortcode' ) && has_shortcode( $content, $tag ) ) {
						return true;
					}
				}

				// Known Gutenberg block identifiers.
				$blocks = array(
					'contact-form-7/form',
					'wpforms/form-selector',
					'fluentform/guten-block',
					'gravityforms/form',
					'formidable/simple-form',
					'ninja-forms/form',
				);

				foreach ( $blocks as $block ) {
					if ( function_exists( 'has_block' ) && has_block( $block, $content ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Registers and conditionally enqueues frontend scripts and CSS.
	 */
	public function enqueue_frontend_assets(): void {
		$is_enabled = get_option( 'vighnaghna_enabled' );
		if ( false === $is_enabled || null === $is_enabled ) {
			$is_enabled = get_option( 'botfence_enabled' );
		}
		if ( false === $is_enabled || null === $is_enabled ) {
			$is_enabled = get_option( 'kl_antispam_enabled', 1 );
		}
		if ( ! (bool) $is_enabled ) {
			return;
		}

		// Register styles and scripts so form adapters can safely call wp_enqueue_script().
		wp_register_style(
			'vighnaghna-css',
			VIGHNAGHNA_URL . 'assets/css/vighnaghna.css',
			array(),
			VIGHNAGHNA_VERSION
		);

		$script_asset = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'vighnaghna.js' : 'vighnaghna.min.js';
		if ( ! file_exists( VIGHNAGHNA_PATH . 'assets/js/' . $script_asset ) ) {
			$script_asset = 'vighnaghna.js';
		}

		wp_register_script(
			'vighnaghna-js',
			VIGHNAGHNA_URL . 'assets/js/' . $script_asset,
			array(),
			VIGHNAGHNA_VERSION,
			true
		);

		// Localize script data for caching compatibility and telemetry collection.
		$token_lifetime = (int) ( get_option( 'vighnaghna_token_lifetime' ) ?: get_option( 'botfence_token_lifetime' ) ?: get_option( 'kl_antispam_token_lifetime', Token_Manager::DEFAULT_LIFETIME ) );
		$min_time       = (float) ( get_option( 'vighnaghna_min_submission_time' ) ?: get_option( 'botfence_min_submission_time' ) ?: get_option( 'kl_antispam_min_submission_time', 2.5 ) );

		$script_config = array(
			'restUrl'       => esc_url_raw( rest_url( 'vighnaghna/v1/' ) ),
			'ajaxUrl'       => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'tokenLifetime' => $token_lifetime,
			'minTime'       => $min_time,
		);

		wp_localize_script( 'vighnaghna-js', 'vighnaghnaSettings', $script_config );
		wp_localize_script( 'vighnaghna-js', 'botfenceSettings', $script_config );
		wp_localize_script( 'vighnaghna-js', 'klAntiSpamSettings', $script_config );

		// Enqueue on pages where protected forms are detected.
		// Form adapters will also enqueue dynamically when rendering fields inside any form template.
		if ( $this->should_enqueue_frontend_assets() ) {
			wp_enqueue_style( 'vighnaghna-css' );
			wp_enqueue_script( 'vighnaghna-js' );
		}
	}

	/**
	 * Registers REST API endpoints.
	 */
	public function register_rest_routes(): void {
		$namespaces = array( 'vighnaghna/v1', 'botfence/v1', 'kl-anti-spam/v1' );
		foreach ( $namespaces as $ns ) {
			register_rest_route(
				$ns,
				'/token',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'rest_get_token' ),
					'permission_callback' => '__return_true', // Public token issuance for visitor forms.
				)
			);
		}
	}

	/**
	 * REST route callback for dynamic token issuance on cached pages.
	 *
	 * Includes rate-limiting protection to prevent DoS attacks against cryptographic token generation.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function rest_get_token( WP_REST_Request $request ): WP_REST_Response {
		$client_ip = $this->rate_limiter->get_client_ip();
		$ip_hash   = $this->rate_limiter->hash_ip( $client_ip );
		$now_dt    = gmdate( 'Y-m-d H:i:s' );
		$exp_dt    = gmdate( 'Y-m-d H:i:s', time() + 60 );
		$requests  = $this->rate_limiter->increment_counter( 'rest_token:' . $ip_hash, 'rest_token_min', $now_dt, $exp_dt );

		if ( $requests > 60 ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Rate limit exceeded for security token issuance.', 'vighnaghna' ) ),
				429
			);
		}

		$form_id    = sanitize_key( (string) $request->get_param( 'form_id' ) );
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
		$ip_hash   = $this->rate_limiter->hash_ip( $client_ip );
		$now_dt    = gmdate( 'Y-m-d H:i:s' );
		$exp_dt    = gmdate( 'Y-m-d H:i:s', time() + 60 );
		$requests  = $this->rate_limiter->increment_counter( 'ajax_token:' . $ip_hash, 'ajax_token_min', $now_dt, $exp_dt );

		if ( $requests > 60 ) {
			wp_send_json_error( array( 'message' => __( 'Rate limit exceeded for token refresh.', 'vighnaghna' ) ), 429 );
			return;
		}

		$form_id = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : 'cached_page'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

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
		$retention_days = (int) ( get_option( 'vighnaghna_log_retention_days' ) ?: get_option( 'botfence_log_retention_days' ) ?: get_option( 'kl_antispam_log_retention_days', 30 ) );
		Database::purge_old_logs( $retention_days );
		Database::purge_expired_rate_limits();
		Database::purge_expired_tokens();
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

// Backwards compatibility aliases.
if ( ! class_exists( '\BotFence\Plugin', false ) ) {
	class_alias( '\Vighnaghna\Plugin', '\BotFence\Plugin' );
}
if ( ! class_exists( '\KL_Anti_Spam\Plugin', false ) ) {
	class_alias( '\Vighnaghna\Plugin', '\KL_Anti_Spam\Plugin' );
}
