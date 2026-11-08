<?php
/**
 * Abstract Form Adapter Base Class for KL Universal Anti-Spam.
 *
 * @package KL_Anti_Spam\Integrations
 */

namespace KL_Anti_Spam\Integrations;

use KL_Anti_Spam\Captcha_Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Abstract_Form_Adapter
 */
abstract class Abstract_Form_Adapter implements Form_Adapter_Interface {

	/**
	 * CAPTCHA Engine instance.
	 *
	 * @var Captcha_Engine
	 */
	protected Captcha_Engine $engine;

	/**
	 * Constructor.
	 *
	 * @param Captcha_Engine|null $engine CAPTCHA Engine instance.
	 */
	public function __construct( ?Captcha_Engine $engine = null ) {
		$this->engine = $engine ?? ( function_exists( 'kl_anti_spam' ) ? kl_anti_spam()->get_captcha_engine() : new Captcha_Engine() );
	}

	/**
	 * Checks whether this adapter is enabled in settings.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$option_key = 'kl_antispam_enable_' . $this->get_id();
		return (bool) get_option( $option_key, 1 );
	}

	/**
	 * Generates hidden anti-spam fields (honeypot, cryptographic token, telemetry placeholder).
	 *
	 * @param string $form_id Form identifier.
	 * @return string HTML output.
	 */
	public function render_fields( string $form_id = '' ): string {
		$token_data = $this->engine->get_token_manager()->generate_token( $form_id );
		$token      = esc_attr( $token_data['token'] );
		$honeypot   = $this->engine->get_honeypot()->render_fields( $form_id );

		// Hidden security fields.
		$html  = "\n<!-- KL Universal Anti-Spam Fields -->\n";
		$html .= $honeypot;
		$html .= '<input type="hidden" name="kl_anti_spam_token" class="kl-anti-spam-token" value="' . $token . '" data-form-id="' . esc_attr( $form_id ) . '" />' . "\n";
		$html .= '<input type="hidden" name="kl_anti_spam_telemetry" class="kl-anti-spam-telemetry" value="" />' . "\n";

		// Accessible fallback challenge (for non-JS or screen readers when adaptive challenge is active).
		$challenge = $this->engine->generate_adaptive_challenge( $form_id );
		$html     .= '<noscript>';
		$html     .= '<div class="kl-fallback-challenge" style="margin: 10px 0; padding: 10px; border: 1px solid #ccd0d4; background: #f6f7f7;">';
		$html     .= '<label for="kl_fallback_' . esc_attr( $form_id ) . '">' . esc_html( $challenge['question'] ) . '</label><br />';
		$html     .= '<input type="text" id="kl_fallback_' . esc_attr( $form_id ) . '" name="kl_challenge_answer" style="width: 100px;" required />' . "\n";
		$html     .= '<input type="hidden" name="kl_challenge_token" value="' . esc_attr( $challenge['challenge_token'] ) . '" />' . "\n";
		$html     .= '</div>';
		$html     .= '</noscript>';
		$html     .= "<!-- End KL Universal Anti-Spam Fields -->\n";

		return $html;
	}

	/**
	 * Verifies submission data using the centralized CAPTCHA engine.
	 *
	 * @param array<string, mixed> $data    Submission data.
	 * @param string               $form_id Form identifier.
	 * @return array{
	 *     is_valid: bool,
	 *     action: string,
	 *     score: int,
	 *     error_message: string,
	 *     signals: array<int, array{code: string, name: string, points: int, details: string}>
	 * }
	 */
	public function verify( array $data, string $form_id = '' ): array {
		return $this->engine->verify_submission( $data, $this->get_id(), $form_id );
	}
}
