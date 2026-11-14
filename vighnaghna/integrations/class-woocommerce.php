<?php
/**
 * WooCommerce Adapter for Vighnaghna.
 *
 * @package Vighnaghna\Integrations
 */

namespace Vighnaghna\Integrations;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WooCommerce
 */
class WooCommerce extends Abstract_Form_Adapter {

	/**
	 * Adapter ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'woocommerce';
	}

	/**
	 * Adapter display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'WooCommerce';
	}

	/**
	 * Checks if WooCommerce is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return defined( 'WC_VERSION' ) || class_exists( 'WooCommerce' );
	}

	/**
	 * Registers WooCommerce integration hooks.
	 */
	public function register_hooks(): void {
		if ( ! $this->is_available() || ! $this->is_enabled() ) {
			return;
		}

		// 1. Checkout Form.
		add_action( 'woocommerce_after_order_notes', array( $this, 'render_checkout_fields' ), 20 );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout' ), 10 );

		// 2. Account Registration Form.
		add_action( 'woocommerce_register_form', array( $this, 'render_registration_fields' ), 20 );
		add_action( 'woocommerce_register_post', array( $this, 'validate_registration' ), 10, 3 );

		// 3. Account Login Form.
		add_action( 'woocommerce_login_form', array( $this, 'render_login_fields' ), 20 );
		add_filter( 'authenticate', array( $this, 'validate_login' ), 20, 3 );
	}

	/**
	 * Renders security fields inside checkout form.
	 */
	public function render_checkout_fields(): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_fields( 'wc_checkout' );
	}

	/**
	 * Validates WooCommerce checkout submission.
	 */
	public function validate_checkout(): void {
		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$res       = $this->verify( $post_data, 'wc_checkout' );

		if ( ! $res['is_valid'] && function_exists( 'wc_add_notice' ) ) {
			$msg = ! empty( $res['error_message'] ) ? $res['error_message'] : __( 'Order submission flagged by anti-spam verification.', 'vighnaghna' );
			wc_add_notice( $msg, 'error' );
		}
	}

	/**
	 * Renders security fields inside account registration form.
	 */
	public function render_registration_fields(): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_fields( 'wc_register' );
	}

	/**
	 * Validates WooCommerce customer registration.
	 *
	 * @param string   $username          Customer username.
	 * @param string   $email             Customer email.
	 * @param WP_Error $validation_errors Validation errors collection.
	 */
	public function validate_registration( string $username, string $email, $validation_errors ): void {
		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$res       = $this->verify( $post_data, 'wc_register' );

		if ( ! $res['is_valid'] && is_wp_error( $validation_errors ) ) {
			$msg = ! empty( $res['error_message'] ) ? $res['error_message'] : __( 'Registration flagged by anti-spam verification.', 'vighnaghna' );
			$validation_errors->add( 'vighnaghna_spam_detected', $msg );
			$validation_errors->add( 'botfence_spam_detected', $msg );
			$validation_errors->add( 'kl_spam_detected', $msg );
		}
	}

	/**
	 * Renders security fields inside customer login form.
	 */
	public function render_login_fields(): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_fields( 'wc_login' );
	}

	/**
	 * Validates WooCommerce login submission.
	 *
	 * @param mixed  $user     Authenticated user object or WP_Error.
	 * @param string $username Username or email.
	 * @param string $password User password.
	 * @return mixed
	 */
	public function validate_login( $user, string $username = '', string $password = '' ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'POST' !== strtoupper( $request_method ) ) {
			return $user;
		}

		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$is_wc_login = isset( $post_data['woocommerce-login-nonce'] ) || isset( $post_data['login'] ) || isset( $post_data['wc_login'] );
		if ( ! $is_wc_login && empty( $post_data['vighnaghna_token'] ) && empty( $post_data['botfence_token'] ) && empty( $post_data['kl_anti_spam_token'] ) ) {
			return $user;
		}

		$res = $this->verify( $post_data, 'wc_login' );
		if ( ! $res['is_valid'] ) {
			$msg = ! empty( $res['error_message'] ) ? $res['error_message'] : __( 'Login flagged by anti-spam verification.', 'vighnaghna' );
			return new \WP_Error( 'vighnaghna_spam_detected', $msg );
		}

		return $user;
	}
}

// Backwards compatibility aliases.
if ( ! class_exists( '\BotFence\Integrations\WooCommerce', false ) ) {
	class_alias( '\Vighnaghna\Integrations\WooCommerce', '\BotFence\Integrations\WooCommerce' );
}
if ( ! class_exists( '\KL_Anti_Spam\Integrations\WooCommerce', false ) ) {
	class_alias( '\Vighnaghna\Integrations\WooCommerce', '\KL_Anti_Spam\Integrations\WooCommerce' );
}
