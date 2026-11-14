<?php
/**
 * Invisible Honeypot System for Vighnaghna.
 *
 * @package Vighnaghna
 */

namespace Vighnaghna;

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
		$secret    = (string) ( get_option( 'vighnaghna_secret_key' ) ?: get_option( 'botfence_secret_key' ) ?: get_option( 'kl_antispam_secret_key', 'vighnaghna_default_secret_salt' ) );
		$date_seed = gmdate( 'Y-m-d', time() + ( $day_offset * DAY_IN_SECONDS ) );
		$hash      = substr( hash_hmac( 'sha256', 'vighnaghna_hp_' . $date_seed, $secret ), 0, 10 );
		return 'vighnaghna_field_' . $hash;
	}

	/**
	 * Secondary static honeypot field name for fallback trap.
	 *
	 * @return string
	 */
	public function get_secondary_field_name(): string {
		return 'vighnaghna_url_website_hp';
	}

	/**
	 * Legacy BotFence dynamic field name for backwards compatibility.
	 *
	 * @param int $day_offset Offset in days.
	 * @return string
	 */
	public function get_botfence_dynamic_field_name( int $day_offset = 0 ): string {
		$secret    = (string) ( get_option( 'vighnaghna_secret_key' ) ?: get_option( 'botfence_secret_key' ) ?: get_option( 'kl_antispam_secret_key', 'botfence_default_secret_salt' ) );
		$date_seed = gmdate( 'Y-m-d', time() + ( $day_offset * DAY_IN_SECONDS ) );
		$hash      = substr( hash_hmac( 'sha256', 'botfence_hp_' . $date_seed, $secret ), 0, 10 );
		return 'botfence_field_' . $hash;
	}

	/**
	 * Legacy KL dynamic field name for backwards compatibility.
	 *
	 * @param int $day_offset Offset in days.
	 * @return string
	 */
	public function get_legacy_dynamic_field_name( int $day_offset = 0 ): string {
		$secret    = (string) ( get_option( 'vighnaghna_secret_key' ) ?: get_option( 'botfence_secret_key' ) ?: get_option( 'kl_antispam_secret_key', 'kl_default_secret_salt' ) );
		$date_seed = gmdate( 'Y-m-d', time() + ( $day_offset * DAY_IN_SECONDS ) );
		$hash      = substr( hash_hmac( 'sha256', 'kl_hp_' . $date_seed, $secret ), 0, 10 );
		return 'kl_field_' . $hash;
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
		$enabled = get_option( 'vighnaghna_honeypot_enabled' );
		if ( false === $enabled || null === $enabled ) {
			$enabled = get_option( 'botfence_honeypot_enabled' );
		}
		if ( false === $enabled || null === $enabled ) {
			$enabled = get_option( 'kl_antispam_honeypot_enabled', 1 );
		}
		if ( ! (bool) $enabled ) {
			return '';
		}

		$primary_name   = esc_attr( $this->get_dynamic_field_name( 0 ) );
		$secondary_name = esc_attr( $this->get_secondary_field_name() );
		$wrapper_id     = 'vighnaghna_hp_' . substr( md5( $form_id . '_' . $primary_name ), 0, 8 );

		// Accessible concealment styles that prevent user focus and screen reader confusion
		// while ensuring automated form fillers that inspect the DOM still populate them.
		$inline_style = 'position: absolute !important; left: -9999px !important; top: -9999px !important; '
			. 'width: 1px !important; height: 1px !important; overflow: hidden !important; '
			. 'opacity: 0 !important; pointer-events: none !important; z-index: -999 !important;';

		$html  = '<div id="' . esc_attr( $wrapper_id ) . '" class="vighnaghna-hp-wrap botfence-hp-wrap kl-hp-wrap" style="' . esc_attr( $inline_style ) . '" aria-hidden="true" tabindex="-1">';
		// Primary dynamic honeypot field.
		$html .= '<label for="' . $primary_name . '" aria-hidden="true" tabindex="-1">' . esc_html__( 'Please leave this field empty', 'vighnaghna' ) . '</label>';
		$html .= '<input type="text" name="' . $primary_name . '" id="' . $primary_name . '" value="" autocomplete="new-password" tabindex="-1" />';
		// Secondary decoy field with autofill-resistant protection.
		$html .= '<label for="' . $secondary_name . '" aria-hidden="true" tabindex="-1">' . esc_html__( 'Do not fill this field', 'vighnaghna' ) . '</label>';
		$html .= '<input type="text" name="' . $secondary_name . '" id="' . $secondary_name . '" value="" autocomplete="new-password" data-lpignore="true" data-form-type="other" tabindex="-1" />';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Checks if any honeypot field has been filled in the submission data.
	 *
	 * Distinguishes empty fields from automated bots and accidental browser autofill.
	 *
	 * @param array<string, mixed>      $submission_data POST data or submission array.
	 * @param array<string, mixed>|null $telemetry       Optional client telemetry data.
	 * @return array{triggered: bool, field: string, value: string, autofill_detected: bool}
	 */
	public function check_submission( array $submission_data, ?array $telemetry = null ): array {
		$enabled = get_option( 'vighnaghna_honeypot_enabled' );
		if ( false === $enabled || null === $enabled ) {
			$enabled = get_option( 'botfence_honeypot_enabled' );
		}
		if ( false === $enabled || null === $enabled ) {
			$enabled = get_option( 'kl_antispam_honeypot_enabled', 1 );
		}
		if ( ! (bool) $enabled ) {
			return array(
				'triggered'         => false,
				'field'             => '',
				'value'             => '',
				'autofill_detected' => false,
			);
		}

		// Primary dynamic honeypot fields.
		$primary_fields = array(
			$this->get_dynamic_field_name( 0 ),
			$this->get_dynamic_field_name( -1 ),
			$this->get_botfence_dynamic_field_name( 0 ),
			$this->get_botfence_dynamic_field_name( -1 ),
			$this->get_legacy_dynamic_field_name( 0 ),
			$this->get_legacy_dynamic_field_name( -1 ),
		);

		foreach ( $primary_fields as $field ) {
			if ( isset( $submission_data[ $field ] ) ) {
				$val = $submission_data[ $field ];
				if ( is_string( $val ) && '' !== trim( $val ) ) {
					// Primary dynamic trap filled -> definitive automated bot.
					return array(
						'triggered'         => true,
						'field'             => sanitize_key( $field ),
						'value'             => substr( sanitize_text_field( $val ), 0, 100 ),
						'autofill_detected' => false,
					);
				}
			}
		}

		// Secondary decoy fields.
		$decoy_fields = array(
			$this->get_secondary_field_name(),
			'botfence_url_website_hp',
			'kl_url_website_hp',
		);

		foreach ( $decoy_fields as $field ) {
			if ( isset( $submission_data[ $field ] ) ) {
				$val = $submission_data[ $field ];
				if ( is_string( $val ) && '' !== trim( $val ) ) {
					// Check if this submission has legitimate human telemetry indicating browser autofill.
					$elapsed_time = (float) ( $telemetry['elapsed_time'] ?? 0 );
					$mouse_moves  = (int) ( $telemetry['mouse_moves'] ?? -1 );
					$keystrokes   = (int) ( $telemetry['keystrokes'] ?? -1 );
					$is_trusted   = $telemetry['is_trusted'] ?? true;
					$autofill     = ! empty( $telemetry['autofill'] );
					$paste_count  = (int) ( $telemetry['paste_count'] ?? 0 );

					$is_human_autofill = (
						$autofill ||
						( $elapsed_time >= 2.0 && ( $mouse_moves > 5 || $keystrokes > 5 || $paste_count > 0 ) && false !== $is_trusted )
					);

					if ( $is_human_autofill ) {
						// Legitimate human submission where browser autofill populated the decoy field.
						return array(
							'triggered'         => false,
							'field'             => sanitize_key( $field ),
							'value'             => substr( sanitize_text_field( $val ), 0, 100 ),
							'autofill_detected' => true,
						);
					}

					// No human interaction telemetry -> automated bot filled decoy field.
					return array(
						'triggered'         => true,
						'field'             => sanitize_key( $field ),
						'value'             => substr( sanitize_text_field( $val ), 0, 100 ),
						'autofill_detected' => false,
					);
				}
			}
		}

		return array(
			'triggered'         => false,
			'field'             => '',
			'value'             => '',
			'autofill_detected' => false,
		);
	}
}

// Backwards compatibility aliases.
if ( ! class_exists( '\BotFence\Honeypot', false ) ) {
	class_alias( '\Vighnaghna\Honeypot', '\BotFence\Honeypot' );
}
if ( ! class_exists( '\KL_Anti_Spam\Honeypot', false ) ) {
	class_alias( '\Vighnaghna\Honeypot', '\KL_Anti_Spam\Honeypot' );
}
