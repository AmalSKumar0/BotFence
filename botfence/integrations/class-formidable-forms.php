<?php
/**
 * Formidable Forms Adapter for BotFence.
 *
 * @package BotFence\Integrations
 */

namespace BotFence\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Formidable_Forms
 */
class Formidable_Forms extends Abstract_Form_Adapter {

	/**
	 * Adapter ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'formidable';
	}

	/**
	 * Adapter display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Formidable Forms';
	}

	/**
	 * Checks if Formidable Forms is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return class_exists( 'FrmHooksController' ) || class_exists( 'FrmEntry' );
	}

	/**
	 * Registers Formidable Forms integration hooks.
	 */
	public function register_hooks(): void {
		if ( ! $this->is_available() || ! $this->is_enabled() ) {
			return;
		}

		// Inject fields inside form HTML.
		add_action( 'frm_after_opening_tag', array( $this, 'render_form_fields' ), 20, 2 );

		// Validate submission before entry creation.
		add_filter( 'frm_validate_entry', array( $this, 'validate_entry' ), 10, 2 );
	}

	/**
	 * Injects security fields after opening form tag.
	 *
	 * @param mixed $params Form params.
	 * @param mixed $form   Form object.
	 */
	public function render_form_fields( $params = null, $form = null ): void {
		$form_id = '';
		if ( is_object( $form ) && isset( $form->id ) ) {
			$form_id = (string) $form->id;
		} elseif ( is_array( $form ) && isset( $form['id'] ) ) {
			$form_id = (string) $form['id'];
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_fields( $form_id );
	}

	/**
	 * Validates Formidable form entry.
	 *
	 * @param array<string, mixed> $errors Existing errors.
	 * @param array<string, mixed> $values Submitted values.
	 * @return array<string, mixed> Errors array.
	 */
	public function validate_entry( array $errors, array $values ): array {
		$form_id   = isset( $values['form_id'] ) ? (string) $values['form_id'] : '';
		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$data      = array_merge( $post_data, $values );

		$res = $this->verify( $data, $form_id );
		if ( ! $res['is_valid'] ) {
			$msg = ! empty( $res['error_message'] ) ? $res['error_message'] : __( 'Spam activity detected.', 'botfence' );
			$errors['botfence'] = $msg;
			$errors['kl_anti_spam'] = $msg;
		}

		return $errors;
	}
}

// Backwards compatibility alias.
if ( ! class_exists( '\KL_Anti_Spam\Integrations\Formidable_Forms', false ) ) {
	class_alias( '\BotFence\Integrations\Formidable_Forms', '\KL_Anti_Spam\Integrations\Formidable_Forms' );
}
