<?php
/**
 * Gravity Forms Adapter for BotFence.
 *
 * @package BotFence\Integrations
 */

namespace BotFence\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Gravity_Forms
 */
class Gravity_Forms extends Abstract_Form_Adapter {

	/**
	 * Adapter ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'gravity_forms';
	}

	/**
	 * Adapter display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Gravity Forms';
	}

	/**
	 * Checks if Gravity Forms is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return class_exists( 'GFForms' ) || class_exists( 'GFCommon' );
	}

	/**
	 * Registers Gravity Forms integration hooks.
	 */
	public function register_hooks(): void {
		if ( ! $this->is_available() || ! $this->is_enabled() ) {
			return;
		}

		// Inject fields inside Gravity Form markup.
		add_filter( 'gform_get_form_filter', array( $this, 'filter_form_html' ), 20, 2 );

		// Validate submission during gform_validation lifecycle.
		add_filter( 'gform_validation', array( $this, 'validate_submission' ), 10, 1 );

		// Mark entry as spam in Gravity Forms entry database if applicable.
		add_filter( 'gform_entry_is_spam', array( $this, 'filter_entry_is_spam' ), 10, 3 );
	}

	/**
	 * Injects anti-spam fields into Gravity Forms HTML.
	 *
	 * @param string               $form_string Form HTML.
	 * @param array<string, mixed> $form        Form array.
	 * @return string Modified HTML.
	 */
	public function filter_form_html( string $form_string, array $form ): string {
		$form_id = isset( $form['id'] ) ? (string) $form['id'] : '';
		$fields  = $this->render_fields( $form_id );

		if ( str_contains( $form_string, '</form>' ) ) {
			return str_replace( '</form>', $fields . '</form>', $form_string );
		}

		return $form_string . $fields;
	}

	/**
	 * Validates submission and halts form processing if flagged.
	 *
	 * @param array<string, mixed> $validation_result GF validation result structure.
	 * @return array<string, mixed> Modified validation result.
	 */
	public function validate_submission( array $validation_result ): array {
		$form    = $validation_result['form'] ?? array();
		$form_id = isset( $form['id'] ) ? (string) $form['id'] : '';

		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$res       = $this->verify( $post_data, $form_id );

		if ( ! $res['is_valid'] ) {
			$validation_result['is_valid'] = false;
			$msg = ! empty( $res['error_message'] ) ? $res['error_message'] : __( 'There was a problem with your submission (anti-spam validation).', 'botfence' );

			// Set form-level validation message.
			$validation_result['form']['validation_message'] = '<div class="validation_error">' . esc_html( $msg ) . '</div>';
		}

		return $validation_result;
	}

	/**
	 * Marks Gravity Forms entry as spam if detected by engine.
	 *
	 * @param bool                 $is_spam Current spam flag.
	 * @param array<string, mixed> $form    Form object.
	 * @param array<string, mixed> $entry   Entry object.
	 * @return bool
	 */
	public function filter_entry_is_spam( bool $is_spam, array $form, array $entry ): bool {
		if ( $is_spam ) {
			return true;
		}

		$form_id   = isset( $form['id'] ) ? (string) $form['id'] : '';
		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$res       = $this->verify( $post_data, $form_id );

		return ! $res['is_valid'];
	}
}

// Backwards compatibility alias.
if ( ! class_exists( '\KL_Anti_Spam\Integrations\Gravity_Forms', false ) ) {
	class_alias( '\BotFence\Integrations\Gravity_Forms', '\KL_Anti_Spam\Integrations\Gravity_Forms' );
}
