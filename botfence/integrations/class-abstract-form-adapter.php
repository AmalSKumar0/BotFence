<?php
/**
 * Abstract Form Adapter Base Class for BotFence.
 *
 * @package BotFence\Integrations
 */

namespace BotFence\Integrations;

use BotFence\Captcha_Engine;

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
		if ( null !== $engine ) {
			$this->engine = $engine;
		} elseif ( function_exists( 'botfence' ) ) {
			$this->engine = botfence()->get_captcha_engine();
		} elseif ( function_exists( 'kl_anti_spam' ) ) {
			$this->engine = kl_anti_spam()->get_captcha_engine();
		} else {
			$this->engine = new Captcha_Engine();
		}
	}

	/**
	 * Checks whether this adapter is enabled in settings.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$id         = $this->get_id();
		$opt_key    = 'botfence_enable_' . $id;
		$val        = get_option( $opt_key );
		if ( false !== $val && null !== $val ) {
			return (bool) $val;
		}
		return (bool) get_option( 'kl_antispam_enable_' . $id, 1 );
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

		// Hidden security fields (contains both BotFence and legacy field names for universal compatibility).
		$html  = "\n<!-- BotFence Security Fields -->\n";
		$html .= $honeypot;
		$html .= '<input type="hidden" name="botfence_token" class="botfence-token kl-anti-spam-token" value="' . $token . '" data-form-id="' . esc_attr( $form_id ) . '" />' . "\n";
		$html .= '<input type="hidden" name="kl_anti_spam_token" class="botfence-legacy-token" value="' . $token . '" />' . "\n";
		$html .= '<input type="hidden" name="botfence_telemetry" class="botfence-telemetry kl-anti-spam-telemetry" value="" />' . "\n";
		$html .= '<input type="hidden" name="kl_anti_spam_telemetry" class="botfence-legacy-telemetry" value="" />' . "\n";

		// Accessible fallback challenge (for non-JS or screen readers when adaptive challenge is active).
		$challenge = $this->engine->generate_adaptive_challenge( $form_id );
		$html     .= '<noscript>';
		$html     .= '<div class="botfence-fallback-challenge kl-fallback-challenge" style="margin: 10px 0; padding: 10px; border: 1px solid #ccd0d4; background: #f6f7f7;">';
		$html     .= '<label for="botfence_fallback_' . esc_attr( $form_id ) . '">' . esc_html( $challenge['question'] ) . '</label><br />';
		$html     .= '<input type="text" id="botfence_fallback_' . esc_attr( $form_id ) . '" name="botfence_challenge_answer" style="width: 100px;" required />' . "\n";
		$html     .= '<input type="hidden" name="botfence_challenge_token" value="' . esc_attr( $challenge['challenge_token'] ) . '" />' . "\n";
		$html     .= '<input type="hidden" name="kl_challenge_token" value="' . esc_attr( $challenge['challenge_token'] ) . '" />' . "\n";
		$html     .= '<input type="hidden" name="kl_challenge_answer" class="botfence-fallback-answer-sync" value="" />' . "\n";
		$html     .= '</div>';
		$html     .= '</noscript>';
		$html     .= "<!-- End BotFence Security Fields -->\n";

		return $html;
	}

	/**
	 * Per-request memoized verification results to prevent replay self-denial.
	 *
	 * @var array<string, array>
	 */
	protected static array $memoized_verifications = array();

	/**
	 * Clears memoized verifications (primarily used for test teardown).
	 */
	public static function clear_memoized_verifications(): void {
		self::$memoized_verifications = array();
	}

	/**
	 * Verifies submission data using the centralized CAPTCHA engine.
	 *
	 * Uses per-request memoization to avoid replay self-denial across multiple hooks.
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
		$cache_key = $this->get_id() . ':' . $form_id . ':' . md5( (string) wp_json_encode( $data ) );

		if ( isset( self::$memoized_verifications[ $cache_key ] ) ) {
			return self::$memoized_verifications[ $cache_key ];
		}

		$result = $this->engine->verify_submission( $data, $this->get_id(), $form_id );
		self::$memoized_verifications[ $cache_key ] = $result;

		return $result;
	}
}

// Backwards compatibility alias.
if ( ! class_exists( '\KL_Anti_Spam\Integrations\Abstract_Form_Adapter', false ) ) {
	class_alias( '\BotFence\Integrations\Abstract_Form_Adapter', '\KL_Anti_Spam\Integrations\Abstract_Form_Adapter' );
}
