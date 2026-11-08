<?php
/**
 * Ninja Forms Adapter for KL Universal Anti-Spam.
 *
 * @package KL_Anti_Spam\Integrations
 */

namespace KL_Anti_Spam\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ninja_Forms
 */
class Ninja_Forms extends Abstract_Form_Adapter {

	/**
	 * Adapter ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'ninja_forms';
	}

	/**
	 * Adapter display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Ninja Forms';
	}

	/**
	 * Checks if Ninja Forms is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return class_exists( 'Ninja_Forms' ) || function_exists( 'Ninja_Forms' );
	}

	/**
	 * Registers Ninja Forms integration hooks.
	 */
	public function register_hooks(): void {
		if ( ! $this->is_available() || ! $this->is_enabled() ) {
			return;
		}

		// Inject fields in rendered form output.
		add_action( 'ninja_forms_display_after_form_fields', array( $this, 'render_form_fields' ), 20, 1 );

		// Validate submission before actions/emails run.
		add_filter( 'ninja_forms_submit_data', array( $this, 'validate_submission' ), 10, 1 );
	}

	/**
	 * Renders hidden anti-spam fields in Ninja Forms markup.
	 *
	 * @param mixed $form_id Form ID.
	 */
	public function render_form_fields( $form_id = '' ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_fields( (string) $form_id );
	}

	/**
	 * Validates submission data and injects form error if flagged.
	 *
	 * @param array<string, mixed> $form_data NF submit data.
	 * @return array<string, mixed> Modified submit data.
	 */
	public function validate_submission( array $form_data ): array {
		$form_id = isset( $form_data['id'] ) ? (string) $form_data['id'] : '';

		// Gather field values into flat array.
		$submission_values = array();
		if ( ! empty( $form_data['fields'] ) && is_array( $form_data['fields'] ) ) {
			foreach ( $form_data['fields'] as $field ) {
				if ( isset( $field['id'], $field['value'] ) ) {
					$submission_values[ 'field_' . $field['id'] ] = $field['value'];
				}
			}
		}

		// Also merge $_POST data (where tokens and honeypots are submitted).
		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$data      = array_merge( $post_data, $submission_values );

		$res = $this->verify( $data, $form_id );
		if ( ! $res['is_valid'] ) {
			$msg = ! empty( $res['error_message'] ) ? $res['error_message'] : __( 'Anti-spam security verification failed.', 'kl-universal-anti-spam' );
			if ( ! isset( $form_data['errors']['forms'] ) || ! is_array( $form_data['errors']['forms'] ) ) {
				$form_data['errors']['forms'] = array();
			}
			$form_data['errors']['forms']['kl_anti_spam'] = $msg;
		}

		return $form_data;
	}
}
