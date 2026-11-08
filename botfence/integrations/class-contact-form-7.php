<?php
/**
 * Contact Form 7 Adapter for BotFence.
 *
 * @package BotFence\Integrations
 */

namespace BotFence\Integrations;

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

		// 1. Inject anti-spam fields into CF7 form HTML.
		add_filter( 'wpcf7_form_elements', array( $this, 'filter_form_elements' ), 20, 1 );

		// 2. Validate submission via official CF7 spam filter (priority 9 to run before default checks).
		add_filter( 'wpcf7_spam', array( $this, 'filter_spam' ), 9, 2 );

		// 3. Skip email delivery cleanly if flagged without aborting.
		add_filter( 'wpcf7_skip_mail', array( $this, 'filter_skip_mail' ), 10, 2 );

		// 4. Pre-send mail handler (ensures skip_mail is enforced; never sets $abort = true).
		add_action( 'wpcf7_before_send_mail', array( $this, 'action_before_send_mail' ), 10, 2 );

		// 5. Lifecycle completion hook.
		add_action( 'wpcf7_submit', array( $this, 'action_submit' ), 10, 2 );
	}

	/**
	 * Reliably resolves Contact Form ID across all CF7 lifecycle phases.
	 *
	 * @param mixed $submission_or_form Optional WPCF7_Submission or WPCF7_ContactForm instance.
	 * @return string Form identifier.
	 */
	public function get_cf7_form_id( $submission_or_form = null ): string {
		if ( ! empty( $_POST['_wpcf7'] ) ) {
			return sanitize_key( (string) $_POST['_wpcf7'] );
		}

		if ( is_object( $submission_or_form ) ) {
			if ( method_exists( $submission_or_form, 'id' ) ) {
				$fid = (string) $submission_or_form->id();
				if ( ! empty( $fid ) ) {
					return $fid;
				}
			}
			if ( method_exists( $submission_or_form, 'get_contact_form' ) ) {
				$cf = $submission_or_form->get_contact_form();
				if ( $cf && method_exists( $cf, 'id' ) ) {
					$fid = (string) $cf->id();
					if ( ! empty( $fid ) ) {
						return $fid;
					}
				}
			}
		}

		if ( class_exists( '\WPCF7_Submission' ) && method_exists( '\WPCF7_Submission', 'get_instance' ) ) {
			$sub = \WPCF7_Submission::get_instance();
			if ( $sub && method_exists( $sub, 'get_contact_form' ) ) {
				$cf = $sub->get_contact_form();
				if ( $cf && method_exists( $cf, 'id' ) ) {
					$fid = (string) $cf->id();
					if ( ! empty( $fid ) ) {
						return $fid;
					}
				}
			}
		}

		if ( class_exists( '\WPCF7_ContactForm' ) && method_exists( '\WPCF7_ContactForm', 'get_current' ) ) {
			$cf = \WPCF7_ContactForm::get_current();
			if ( $cf && method_exists( $cf, 'id' ) ) {
				$fid = (string) $cf->id();
				if ( ! empty( $fid ) ) {
					return $fid;
				}
			}
		}

		return '';
	}

	/**
	 * Injects anti-spam fields into CF7 forms.
	 *
	 * @param string $elements Form HTML.
	 * @return string Modified HTML.
	 */
	public function filter_form_elements( string $elements ): string {
		$form_id = $this->get_cf7_form_id();
		return $elements . $this->render_fields( $form_id );
	}

	/**
	 * Primary spam filter for Contact Form 7.
	 *
	 * Evaluates submission and delegates spam handling to CF7's native spam mechanism.
	 *
	 * @param bool  $spam       Current spam status.
	 * @param mixed $submission Optional WPCF7_Submission instance.
	 * @return bool True if flagged as spam.
	 */
	public function filter_spam( $spam, $submission = null ): bool {
		if ( $spam ) {
			return true;
		}

		$form_id   = $this->get_cf7_form_id( $submission );
		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$res       = $this->verify( $post_data, $form_id );

		return ! $res['is_valid'];
	}

	/**
	 * Skips mail delivery cleanly if flagged as spam without marking status as aborted.
	 *
	 * @param bool  $skip_mail    Current skip_mail status.
	 * @param mixed $contact_form WPCF7_ContactForm instance.
	 * @return bool True if mail should be skipped.
	 */
	public function filter_skip_mail( bool $skip_mail, $contact_form = null ): bool {
		if ( $skip_mail ) {
			return true;
		}

		$form_id   = $this->get_cf7_form_id( $contact_form );
		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$res       = $this->verify( $post_data, $form_id );

		return ! $res['is_valid'];
	}

	/**
	 * Pre-send mail handler.
	 *
	 * Ensures mail is skipped if flagged, but NEVER sets $abort = true to prevent
	 * Contact Form 7 from triggering the 'aborted' status error ("Sending mail has been aborted.").
	 *
	 * @param mixed $contact_form WPCF7_ContactForm instance.
	 * @param bool  $abort        Abort flag passed by reference (must remain false for spam).
	 */
	public function action_before_send_mail( $contact_form, &$abort = false ): void {
		$form_id   = $this->get_cf7_form_id( $contact_form );
		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$res       = $this->verify( $post_data, $form_id );

		if ( ! $res['is_valid'] ) {
			// Do NOT use $abort = true as a generic spam rejection mechanism.
			// Setting $abort = true triggers CF7's 'aborted' status ("Sending mail has been aborted.").
			// Instead, gracefully skip mail delivery via skip_mail property.
			if ( is_object( $contact_form ) && property_exists( $contact_form, 'skip_mail' ) ) {
				$contact_form->skip_mail = true;
			}
		}
	}

	/**
	 * Lifecycle completion action for CF7 submissions.
	 *
	 * @param mixed $contact_form WPCF7_ContactForm instance.
	 * @param array $result       Submission result array.
	 */
	public function action_submit( $contact_form, array $result = array() ): void {
		// Completed CF7 submission lifecycle.
	}
}

// Backwards compatibility alias.
if ( ! class_exists( '\KL_Anti_Spam\Integrations\Contact_Form_7', false ) ) {
	class_alias( '\BotFence\Integrations\Contact_Form_7', '\KL_Anti_Spam\Integrations\Contact_Form_7' );
}
