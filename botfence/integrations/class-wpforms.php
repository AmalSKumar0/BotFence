<?php
/**
 * WPForms Adapter for BotFence.
 *
 * @package BotFence\Integrations
 */

namespace BotFence\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPForms
 */
class WPForms extends Abstract_Form_Adapter {

	/**
	 * Adapter ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'wpforms';
	}

	/**
	 * Adapter display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'WPForms';
	}

	/**
	 * Checks if WPForms is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return defined( 'WPFORMS_VERSION' ) || class_exists( 'WPForms' );
	}

	/**
	 * Registers WPForms integration hooks.
	 */
	public function register_hooks(): void {
		if ( ! $this->is_available() || ! $this->is_enabled() ) {
			return;
		}

		// Inject security fields into WPForms frontend output.
		add_filter( 'wpforms_frontend_output', array( $this, 'filter_frontend_output' ), 20, 2 );

		// Validate submission before processing or notification emails.
		add_filter( 'wpforms_process_initial_errors', array( $this, 'validate_submission' ), 10, 2 );
	}

	/**
	 * Injects anti-spam fields into WPForms markup.
	 *
	 * @param string               $output    Form HTML output.
	 * @param array<string, mixed> $form_data Form settings data.
	 * @return string Modified HTML.
	 */
	public function filter_frontend_output( string $output, array $form_data ): string {
		$form_id = isset( $form_data['id'] ) ? (string) $form_data['id'] : '';
		$fields  = $this->render_fields( $form_id );

		// Append fields right before closing </form> tag.
		if ( str_contains( $output, '</form>' ) ) {
			return str_replace( '</form>', $fields . '</form>', $output );
		}

		return $output . $fields;
	}

	/**
	 * Validates WPForms submissions.
	 *
	 * @param array<string, mixed> $errors    Existing errors array.
	 * @param array<string, mixed> $form_data Form settings data.
	 * @return array<string, mixed> Errors array.
	 */
	public function validate_submission( array $errors, array $form_data ): array {
		$form_id   = isset( $form_data['id'] ) ? (string) $form_data['id'] : '';
		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$res = $this->verify( $post_data, $form_id );
		if ( ! $res['is_valid'] ) {
			$msg = ! empty( $res['error_message'] ) ? $res['error_message'] : __( 'Anti-spam verification failed.', 'botfence' );
			$errors[ $form_id ]['header'] = $msg;
		}

		return $errors;
	}
}

// Backwards compatibility alias.
if ( ! class_exists( '\KL_Anti_Spam\Integrations\WPForms', false ) ) {
	class_alias( '\BotFence\Integrations\WPForms', '\KL_Anti_Spam\Integrations\WPForms' );
}
