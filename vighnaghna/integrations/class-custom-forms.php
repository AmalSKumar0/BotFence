<?php
/**
 * Custom HTML Forms Adapter & Developer API for Vighnaghna.
 *
 * @package Vighnaghna\Integrations
 */

namespace Vighnaghna\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Custom_Forms
 */
class Custom_Forms extends Abstract_Form_Adapter {

	/**
	 * Adapter ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'custom_forms';
	}

	/**
	 * Adapter display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Custom HTML Forms & API';
	}

	/**
	 * Always available.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return true;
	}

	/**
	 * Registers custom form filters and developer hooks.
	 */
	public function register_hooks(): void {
		if ( ! $this->is_available() || ! $this->is_enabled() ) {
			return;
		}

		// Inject security fields into standalone custom forms in post content.
		add_filter( 'the_content', array( $this, 'filter_content_forms' ), 99 );

		// Vighnaghna Developer action & filter hooks.
		add_filter( 'vighnaghna_verify_submission', array( $this, 'hook_verify_submission' ), 10, 3 );
		add_action( 'vighnaghna_verify_or_die', array( $this, 'hook_verify_or_die' ), 10, 2 );
		add_filter( 'vighnaghna_render_fields', array( $this, 'hook_render_fields' ), 10, 2 );

		// Backwards-compatibility aliases.
		add_filter( 'botfence_verify_submission', array( $this, 'hook_verify_submission' ), 10, 3 );
		add_action( 'botfence_verify_or_die', array( $this, 'hook_verify_or_die' ), 10, 2 );
		add_filter( 'botfence_render_fields', array( $this, 'hook_render_fields' ), 10, 2 );

		add_filter( 'kl_anti_spam_verify_submission', array( $this, 'hook_verify_submission' ), 10, 3 );
		add_action( 'kl_anti_spam_verify_or_die', array( $this, 'hook_verify_or_die' ), 10, 2 );
		add_filter( 'kl_anti_spam_render_fields', array( $this, 'hook_render_fields' ), 10, 2 );
	}

	/**
	 * Automatically injects fields into custom POST forms in post content.
	 *
	 * @param string $content Post HTML content.
	 * @return string Modified content.
	 */
	public function filter_content_forms( string $content ): string {
		// Only run if content contains a form tag with POST method.
		if ( ! str_contains( $content, '<form' ) || ! preg_match( '/<form[^>]+method=["\']post["\']/i', $content ) ) {
			return $content;
		}

		// Skip forms already protected by specific adapters.
		$skip_markers = array( 'wpcf7-form', 'wpforms-form', 'fluentform', 'gform_wrapper', 'frm_forms', 'ninja-forms-form', 'elementor-form' );
		foreach ( $skip_markers as $marker ) {
			if ( str_contains( $content, $marker ) ) {
				return $content;
			}
		}

		// Avoid double-injecting if fields are already present.
		if ( str_contains( $content, 'vighnaghna_token' ) || str_contains( $content, 'botfence_token' ) || str_contains( $content, 'kl_anti_spam_token' ) ) {
			return $content;
		}

		$fields = $this->render_fields( 'custom_content' );
		return preg_replace( '/(<\/form>)/i', $fields . '$1', $content, 1 );
	}

	/**
	 * Developer filter: vighnaghna_verify_submission / botfence_verify_submission.
	 *
	 * Usage:
	 * $is_valid = apply_filters( 'vighnaghna_verify_submission', true, $_POST, 'my_custom_form' );
	 *
	 * @param bool                      $default_val Default boolean.
	 * @param array<string, mixed>|null $data        Submission data (defaults to $_POST).
	 * @param string                    $form_id     Form identifier.
	 * @return bool True if valid, false if spam.
	 */
	public function hook_verify_submission( bool $default_val, ?array $data = null, string $form_id = 'custom' ): bool {
		$submission_data = null !== $data ? $data : ( ! empty( $_POST ) ? wp_unslash( $_POST ) : array() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$res             = $this->verify( $submission_data, $form_id );

		return (bool) $res['is_valid'];
	}

	/**
	 * Developer action: vighnaghna_verify_or_die / botfence_verify_or_die.
	 *
	 * Usage:
	 * do_action( 'vighnaghna_verify_or_die', 'my_custom_form' );
	 *
	 * @param string                    $form_id Form identifier.
	 * @param array<string, mixed>|null $data    Submission data.
	 */
	public function hook_verify_or_die( string $form_id = 'custom', ?array $data = null ): void {
		$submission_data = null !== $data ? $data : ( ! empty( $_POST ) ? wp_unslash( $_POST ) : array() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$res             = $this->verify( $submission_data, $form_id );

		if ( ! $res['is_valid'] ) {
			$msg = ! empty( $res['error_message'] ) ? $res['error_message'] : __( 'Security verification failed.', 'vighnaghna' );
			wp_die( esc_html( $msg ), esc_html__( 'Spam Blocked', 'vighnaghna' ), array( 'response' => 403 ) );
		}
	}

	/**
	 * Developer filter: vighnaghna_render_fields / botfence_render_fields.
	 *
	 * Usage:
	 * echo apply_filters( 'vighnaghna_render_fields', '', 'my_custom_form' );
	 *
	 * @param string $html    Existing HTML.
	 * @param string $form_id Form identifier.
	 * @return string HTML output.
	 */
	public function hook_render_fields( string $html, string $form_id = 'custom' ): string {
		return $html . $this->render_fields( $form_id );
	}
}

// Backwards compatibility aliases.
if ( ! class_exists( '\BotFence\Integrations\Custom_Forms', false ) ) {
	class_alias( '\Vighnaghna\Integrations\Custom_Forms', '\BotFence\Integrations\Custom_Forms' );
}
if ( ! class_exists( '\KL_Anti_Spam\Integrations\Custom_Forms', false ) ) {
	class_alias( '\Vighnaghna\Integrations\Custom_Forms', '\KL_Anti_Spam\Integrations\Custom_Forms' );
}
