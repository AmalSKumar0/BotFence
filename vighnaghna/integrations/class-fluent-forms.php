<?php
/**
 * Fluent Forms Adapter for Vighnaghna.
 *
 * @package Vighnaghna\Integrations
 */

namespace Vighnaghna\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Fluent_Forms
 */
class Fluent_Forms extends Abstract_Form_Adapter {

	/**
	 * Adapter ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'fluent_forms';
	}

	/**
	 * Adapter display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Fluent Forms';
	}

	/**
	 * Checks if Fluent Forms is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return defined( 'FLUENTFORM_VERSION' ) || defined( 'FLUENTFORM' ) || class_exists( 'FluentForm\App\Modules\Form\Form' );
	}

	/**
	 * Registers Fluent Forms integration hooks.
	 */
	public function register_hooks(): void {
		if ( ! $this->is_available() || ! $this->is_enabled() ) {
			return;
		}

		// Inject fields before the closing form element tag.
		add_action( 'fluentform/form_element_end', array( $this, 'render_form_fields' ), 20, 1 );

		// Validate submission before database insertion and email notifications.
		add_filter( 'fluentform/validate_submission', array( $this, 'validate_submission' ), 10, 3 );
	}

	/**
	 * Renders security fields inside the Fluent Form container.
	 *
	 * @param mixed $form Fluent form object.
	 */
	public function render_form_fields( $form = null ): void {
		$form_id = '';
		if ( is_object( $form ) && isset( $form->id ) ) {
			$form_id = (string) $form->id;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_fields( $form_id );
	}

	/**
	 * Validates Fluent Forms submission.
	 *
	 * @param array<string, mixed> $errors    Existing errors array.
	 * @param array<string, mixed> $form_data Form submitted data.
	 * @param mixed                $form      Fluent form object.
	 * @return array<string, mixed> Errors array.
	 */
	public function validate_submission( array $errors, array $form_data, $form = null ): array {
		$form_id = '';
		if ( is_object( $form ) && isset( $form->id ) ) {
			$form_id = (string) $form->id;
		} elseif ( is_array( $form ) && isset( $form['id'] ) ) {
			$form_id = (string) $form['id'];
		}

		// Combine form_data with $_POST in case token fields are in superglobal.
		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$data      = array_merge( $post_data, $form_data );

		$res = $this->verify( $data, $form_id );
		if ( ! $res['is_valid'] ) {
			$msg = ! empty( $res['error_message'] ) ? $res['error_message'] : __( 'Submission flagged by anti-spam verification.', 'vighnaghna' );
			$errors['vighnaghna']   = array( $msg );
			$errors['botfence']     = array( $msg );
			$errors['kl_anti_spam'] = array( $msg );
		}

		return $errors;
	}
}

// Backwards compatibility aliases.
if ( ! class_exists( '\BotFence\Integrations\Fluent_Forms', false ) ) {
	class_alias( '\Vighnaghna\Integrations\Fluent_Forms', '\BotFence\Integrations\Fluent_Forms' );
}
if ( ! class_exists( '\KL_Anti_Spam\Integrations\Fluent_Forms', false ) ) {
	class_alias( '\Vighnaghna\Integrations\Fluent_Forms', '\KL_Anti_Spam\Integrations\Fluent_Forms' );
}
