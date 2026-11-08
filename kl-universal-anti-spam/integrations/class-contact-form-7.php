<?php
/**
 * Contact Form 7 Adapter for KL Universal Anti-Spam.
 *
 * @package KL_Anti_Spam\Integrations
 */

namespace KL_Anti_Spam\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Contact_Form_7
 */
class Contact_Form_7 extends Abstract_Form_Adapter {

	/**
	 * Adapter ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'cf7';
	}

	/**
	 * Adapter display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Contact Form 7';
	}

	/**
	 * Checks if Contact Form 7 is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7' );
	}

	/**
	 * Registers CF7 integration hooks.
	 */
	public function register_hooks(): void {
		if ( ! $this->is_available() || ! $this->is_enabled() ) {
			return;
		}

		// Inject anti-spam fields into CF7 form HTML.
		add_filter( 'wpcf7_form_elements', array( $this, 'filter_form_elements' ), 20, 1 );

		// Validate submission before mail delivery or storage.
		add_filter( 'wpcf7_spam', array( $this, 'filter_spam' ), 10, 2 );

		// Prevent email dispatch if flagged as spam.
		add_action( 'wpcf7_before_send_mail', array( $this, 'action_before_send_mail' ), 10, 2 );
	}

	/**
	 * Injects anti-spam fields into CF7 forms.
	 *
	 * @param string $elements Form HTML.
	 * @return string Modified HTML.
	 */
	public function filter_form_elements( string $elements ): string {
		$form_id = '';
		if ( class_exists( '\WPCF7_ContactForm' ) && method_exists( '\WPCF7_ContactForm', 'get_current' ) ) {
			$current = \WPCF7_ContactForm::get_current();
			if ( $current && method_exists( $current, 'id' ) ) {
				$form_id = (string) $current->id();
			}
		}

		return $elements . $this->render_fields( $form_id );
	}

	/**
	 * Primary spam filter for Contact Form 7.
	 *
	 * @param bool  $spam       Current spam status.
	 * @param mixed $submission WPCF7_Submission instance.
	 * @return bool True if flagged as spam.
	 */
	public function filter_spam( $spam, $submission = null ): bool {
		if ( $spam ) {
			return true;
		}

		$form_id = '';
		if ( is_object( $submission ) && method_exists( $submission, 'get_contact_form' ) ) {
			$contact_form = $submission->get_contact_form();
			if ( $contact_form && method_exists( $contact_form, 'id' ) ) {
				$form_id = (string) $contact_form->id();
			}
		}

		// Use $_POST data.
		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$res       = $this->verify( $post_data, $form_id );

		return ! $res['is_valid'];
	}

	/**
	 * Aborts mail dispatch if flagged as spam.
	 *
	 * @param mixed $contact_form WPCF7_ContactForm instance.
	 * @param bool  $abort        Abort flag passed by reference.
	 */
	public function action_before_send_mail( $contact_form, &$abort = false ): void {
		if ( is_object( $contact_form ) && method_exists( $contact_form, 'id' ) ) {
			$form_id   = (string) $contact_form->id();
			$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$res       = $this->verify( $post_data, $form_id );

			if ( ! $res['is_valid'] ) {
				$abort = true;
				if ( property_exists( $contact_form, 'skip_mail' ) ) {
					$contact_form->skip_mail = true;
				}
			}
		}
	}
}
