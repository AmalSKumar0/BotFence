<?php
/**
 * Elementor Pro Forms Adapter for KL Universal Anti-Spam.
 *
 * @package KL_Anti_Spam\Integrations
 */

namespace KL_Anti_Spam\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Elementor_Forms
 */
class Elementor_Forms extends Abstract_Form_Adapter {

	/**
	 * Adapter ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'elementor';
	}

	/**
	 * Adapter display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Elementor Forms';
	}

	/**
	 * Checks if Elementor Pro Forms is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return defined( 'ELEMENTOR_PRO_VERSION' ) || class_exists( '\ElementorPro\Plugin' );
	}

	/**
	 * Registers Elementor Forms integration hooks.
	 */
	public function register_hooks(): void {
		if ( ! $this->is_available() || ! $this->is_enabled() ) {
			return;
		}

		// Inject fields into Elementor form widget output.
		add_filter( 'elementor/widget/render_content', array( $this, 'filter_widget_content' ), 20, 2 );

		// Validate submission before form actions or emails execute.
		add_action( 'elementor_pro/forms/validation', array( $this, 'validate_submission' ), 10, 2 );
	}

	/**
	 * Injects anti-spam fields into Elementor form HTML.
	 *
	 * @param string $content Widget rendered HTML.
	 * @param mixed  $widget  Elementor widget instance.
	 * @return string Modified HTML.
	 */
	public function filter_widget_content( string $content, $widget ): string {
		if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
			return $content;
		}

		if ( 'form' !== $widget->get_name() ) {
			return $content;
		}

		$form_id = method_exists( $widget, 'get_id' ) ? (string) $widget->get_id() : '';
		$fields  = $this->render_fields( $form_id );

		if ( str_contains( $content, '</form>' ) ) {
			return str_replace( '</form>', $fields . '</form>', $content );
		}

		return $content . $fields;
	}

	/**
	 * Validates Elementor form submission via Ajax Handler.
	 *
	 * @param mixed $record       Elementor Form_Record instance.
	 * @param mixed $ajax_handler Elementor Ajax_Handler instance.
	 */
	public function validate_submission( $record, $ajax_handler ): void {
		$form_id = '';
		if ( is_object( $record ) && method_exists( $record, 'get_form_settings' ) ) {
			$form_id = (string) ( $record->get_form_settings( 'id' ) ?? $record->get_form_settings( 'form_name' ) ?? '' );
		}

		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$res       = $this->verify( $post_data, $form_id );

		if ( ! $res['is_valid'] && is_object( $ajax_handler ) && method_exists( $ajax_handler, 'add_error_message' ) ) {
			$msg = ! empty( $res['error_message'] ) ? $res['error_message'] : __( 'Spam verification check failed.', 'kl-universal-anti-spam' );
			$ajax_handler->add_error_message( $msg );
		}
	}
}
