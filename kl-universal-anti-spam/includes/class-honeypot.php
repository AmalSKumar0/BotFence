<?php
/**
 * Invisible Honeypot System for KL Universal Anti-Spam.
 *
 * @package KL_Anti_Spam
 */

namespace KL_Anti_Spam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Honeypot
 */
class Honeypot {

	/**
	 * Generates a dynamic honeypot field name based on a date seed and site secret.
	 *
	 * @param int $day_offset Offset in days (0 for today, -1 for yesterday).
	 * @return string Dynamic field name.
	 */
	public function get_dynamic_field_name( int $day_offset = 0 ): string {
		$secret    = (string) get_option( 'kl_antispam_secret_key', 'kl_default_secret_salt' );
		$date_seed = gmdate( 'Y-m-d', time() + ( $day_offset * DAY_IN_SECONDS ) );
		$hash      = substr( hash_hmac( 'sha256', 'kl_hp_' . $date_seed, $secret ), 0, 10 );
		return 'kl_field_' . $hash;
	}

	/**
	 * Secondary static honeypot field name for fallback trap.
	 *
	 * @return string
	 */
	public function get_secondary_field_name(): string {
		return 'kl_url_website_hp';
	}

	/**
	 * Renders HTML markup for the honeypot fields.
	 *
	 * Uses accessibility-safe concealment techniques (off-screen, zero dimensions,
	 * aria-hidden, tabindex=-1) that are invisible to real users but attractive to bots.
	 *
	 * @param string $form_id Form identifier.
	 * @return string HTML markup.
	 */
	public function render_fields( string $form_id = '' ): string {
		if ( ! (bool) get_option( 'kl_antispam_honeypot_enabled', 1 ) ) {
			return '';
		}

		$primary_name   = esc_attr( $this->get_dynamic_field_name( 0 ) );
		$secondary_name = esc_attr( $this->get_secondary_field_name() );
		$wrapper_id     = 'kl_hp_' . substr( md5( $form_id . '_' . $primary_name ), 0, 8 );

		// Accessible concealment styles that prevent user focus and screen reader confusion
		// while ensuring automated form fillers that inspect the DOM still populate them.
		$inline_style = 'position: absolute !important; left: -9999px !important; top: -9999px !important; '
			. 'width: 1px !important; height: 1px !important; overflow: hidden !important; '
			. 'opacity: 0 !important; pointer-events: none !important; z-index: -999 !important;';

		$html  = '<div id="' . esc_attr( $wrapper_id ) . '" class="kl-hp-wrap" style="' . esc_attr( $inline_style ) . '" aria-hidden="true" tabindex="-1">';
		// Primary dynamic honeypot field.
		$html .= '<label for="' . $primary_name . '" aria-hidden="true" tabindex="-1">' . esc_html__( 'Please leave this field empty', 'kl-universal-anti-spam' ) . '</label>';
		$html .= '<input type="text" name="' . $primary_name . '" id="' . $primary_name . '" value="" autocomplete="new-password" tabindex="-1" />';
		// Secondary innocent-looking honeypot field (often targeted by URL spammers).
		$html .= '<label for="' . $secondary_name . '" aria-hidden="true" tabindex="-1">' . esc_html__( 'Website URL', 'kl-universal-anti-spam' ) . '</label>';
		$html .= '<input type="text" name="' . $secondary_name . '" id="' . $secondary_name . '" value="" autocomplete="off" tabindex="-1" />';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Checks if any honeypot field has been filled in the submission data.
	 *
	 * @param array<string, mixed> $submission_data POST data or submission array.
	 * @return array{triggered: bool, field: string, value: string}
	 */
	public function check_submission( array $submission_data ): array {
		if ( ! (bool) get_option( 'kl_antispam_honeypot_enabled', 1 ) ) {
			return array(
				'triggered' => false,
				'field'     => '',
				'value'     => '',
			);
		}

		// Fields to check: today's dynamic name, yesterday's dynamic name (for caching skew), and secondary name.
		$fields_to_check = array(
			$this->get_dynamic_field_name( 0 ),
			$this->get_dynamic_field_name( -1 ),
			$this->get_secondary_field_name(),
		);

		foreach ( $fields_to_check as $field ) {
			if ( isset( $submission_data[ $field ] ) ) {
				$val = $submission_data[ $field ];
				if ( is_string( $val ) && '' !== trim( $val ) ) {
					return array(
						'triggered' => true,
						'field'     => sanitize_key( $field ),
						'value'     => substr( sanitize_text_field( $val ), 0, 100 ),
					);
				}
			}
		}

		return array(
			'triggered' => false,
			'field'     => '',
			'value'     => '',
		);
	}
}
