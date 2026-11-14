<?php
/**
 * Master Anti-Spam & Security Engine for Vighnaghna.
 *
 * @package Vighnaghna
 */

namespace Vighnaghna;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Captcha_Engine
 */
class Captcha_Engine {

	/**
	 * Token Manager instance.
	 *
	 * @var Token_Manager
	 */
	protected Token_Manager $token_manager;

	/**
	 * Honeypot instance.
	 *
	 * @var Honeypot
	 */
	protected Honeypot $honeypot;

	/**
	 * Rate Limiter instance.
	 *
	 * @var Rate_Limiter
	 */
	protected Rate_Limiter $rate_limiter;

	/**
	 * Risk Engine instance.
	 *
	 * @var Risk_Engine
	 */
	protected Risk_Engine $risk_engine;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	protected Logger $logger;

	/**
	 * Complete diagnostic lifecycle trace for the most recent verification.
	 *
	 * @var array<string, mixed>|null
	 */
	protected ?array $last_verification_trace = null;

	/**
	 * Constructor.
	 *
	 * @param Token_Manager|null $token_manager Token manager.
	 * @param Honeypot|null      $honeypot      Honeypot.
	 * @param Rate_Limiter|null  $rate_limiter  Rate limiter.
	 * @param Risk_Engine|null   $risk_engine   Risk engine.
	 * @param Logger|null        $logger        Logger.
	 */
	public function __construct(
		?Token_Manager $token_manager = null,
		?Honeypot $honeypot = null,
		?Rate_Limiter $rate_limiter = null,
		?Risk_Engine $risk_engine = null,
		?Logger $logger = null
	) {
		$this->token_manager = $token_manager ?? new Token_Manager();
		$this->honeypot      = $honeypot ?? new Honeypot();
		$this->rate_limiter  = $rate_limiter ?? new Rate_Limiter();
		$this->risk_engine   = $risk_engine ?? new Risk_Engine();
		$this->logger        = $logger ?? new Logger();
	}

	/**
	 * Verifies a form submission across all detection layers.
	 *
	 * @param array<string, mixed> $submission_data POST data or submission payload.
	 * @param string               $form_type       Form builder type (cf7, wpforms, etc.).
	 * @param string               $form_id         Form ID or name.
	 * @return array{
	 *     is_valid: bool,
	 *     action: string,
	 *     score: int,
	 *     error_message: string,
	 *     signals: array<int, array{code: string, name: string, points: int, details: string}>
	 * }
	 */
	public function verify_submission( array $submission_data, string $form_type = 'generic', string $form_id = '' ): array {
		// 0. Check if global protection is disabled or form is explicitly excluded.
		$is_enabled = get_option( 'vighnaghna_enabled' );
		if ( false === $is_enabled || null === $is_enabled ) {
			$is_enabled = get_option( 'botfence_enabled' );
		}
		if ( false === $is_enabled || null === $is_enabled ) {
			$is_enabled = get_option( 'kl_antispam_enabled', 1 );
		}

		if ( ! (bool) $is_enabled || $this->is_form_excluded( $form_type, $form_id ) ) {
			return array(
				'is_valid'      => true,
				'action'        => 'allow',
				'score'         => 0,
				'error_message' => '',
				'signals'       => array(),
			);
		}

		$client_ip = $this->rate_limiter->get_client_ip();
		$ip_hash   = $this->rate_limiter->hash_ip( $client_ip );
		$masked_ip = $this->rate_limiter->mask_ip( $client_ip );

		// 1. Telemetry Extraction.
		$possible_telemetry_keys = array(
			'vighnaghna_telemetry',
			'_vighnaghna_telemetry',
			'botfence_telemetry',
			'botfence_anti_spam_telemetry',
			'kl_anti_spam_telemetry',
			'_kl_as_telemetry',
			'_botfence_telemetry',
		);
		$telemetry_raw           = null;
		foreach ( $possible_telemetry_keys as $tk ) {
			if ( isset( $submission_data[ $tk ] ) ) {
				$telemetry_raw = $submission_data[ $tk ];
				break;
			}
		}

		$telemetry = array();
		if ( is_string( $telemetry_raw ) && ! empty( $telemetry_raw ) ) {
			$decoded   = json_decode( base64_decode( $telemetry_raw ), true );
			$telemetry = is_array( $decoded ) ? $decoded : array();
		} elseif ( is_array( $telemetry_raw ) ) {
			$telemetry = $telemetry_raw;
		}

		$elapsed_time = isset( $telemetry['elapsed_time'] ) ? (float) $telemetry['elapsed_time'] : (float) ( $submission_data['vighnaghna_elapsed_time'] ?? $submission_data['botfence_elapsed_time'] ?? $submission_data['kl_elapsed_time'] ?? 0 );

		// 2. Honeypot Check (with telemetry-informed autofill discrimination).
		$hp_result = $this->honeypot->check_submission( $submission_data, $telemetry );

		// 3. Token Check.
		$possible_token_keys = array(
			'vighnaghna_token',
			'_vighnaghna_token',
			'botfence_token',
			'botfence_anti_spam_token',
			'kl_anti_spam_token',
			'_kl_as_token',
			'_botfence_token',
		);
		$raw_token           = '';
		foreach ( $possible_token_keys as $k ) {
			if ( ! empty( $submission_data[ $k ] ) ) {
				$raw_token = (string) $submission_data[ $k ];
				break;
			}
		}

		$token_res = $this->token_manager->validate_token( $raw_token, $form_id, $client_ip );

		// 4. Content Analysis (Excessive URLs & Duplicate Message).
		$text_fields = $this->extract_text_content( $submission_data );
		$url_count   = $this->count_urls( $text_fields );

		// 5. Rate Limiting Check.
		$rate_res = $this->rate_limiter->check_submission( $form_id, $text_fields, $client_ip );

		// 6. Check for Adaptive / Fallback Challenge Solution.
		$challenge_solved = false;
		$challenge_token  = (string) ( $submission_data['vighnaghna_challenge_token'] ?? $submission_data['botfence_challenge_token'] ?? $submission_data['kl_challenge_token'] ?? '' );
		$challenge_answer = (string) ( $submission_data['vighnaghna_challenge_answer'] ?? $submission_data['botfence_challenge_answer'] ?? $submission_data['kl_challenge_answer'] ?? '' );
		if ( ! empty( $challenge_token ) && ! empty( $challenge_answer ) ) {
			if ( $this->verify_adaptive_challenge( $challenge_token, $challenge_answer, $form_id ) ) {
				$challenge_solved = true;
			}
		}

		// 7. Assemble context for Risk Engine.
		$context = array(
			'honeypot_triggered' => $hp_result['triggered'],
			'honeypot_field'     => $hp_result['field'],
			'token_status'       => $token_res['status'],
			'token_age'          => $token_res['age'],
			'elapsed_time'       => $elapsed_time,
			'rate_exceeded'      => $rate_res['rate_exceeded'],
			'is_quarantined'     => $rate_res['is_quarantined'],
			'rate_reason'        => $rate_res['reason'],
			'repeated_message'   => $rate_res['repeated_message'],
			'url_count'          => $url_count,
			'telemetry'          => $telemetry,
			'text_length'        => strlen( $text_fields ),
		);

		// 8. Calculate Risk Score.
		$evaluation = $this->risk_engine->evaluate( $context );

		// If adaptive challenge was successfully solved, allow if score was in challenge risk range.
		if ( $challenge_solved && 'challenge' === $evaluation['action'] ) {
			$evaluation['action']  = 'allow';
			$evaluation['is_spam'] = false;
		}

		// Distinct error messaging for expired tokens vs. malicious bot spam.
		$error_message = '';
		if ( 'expired' === $token_res['status'] && ! $hp_result['triggered'] ) {
			$error_message = __( 'Your form session has expired. Please refresh the page and try again.', 'vighnaghna' );
		} elseif ( 'block' === $evaluation['action'] ) {
			$error_message = __( 'Your submission was flagged as potential spam. If you believe this is an error, please try again or contact the site administrator.', 'vighnaghna' );
		} elseif ( 'challenge' === $evaluation['action'] ) {
			$error_message = __( 'Additional human verification is required before your submission can be accepted.', 'vighnaghna' );
		}

		// Record complete submission lifecycle trace for diagnostic logging without logging PII.
		$this->last_verification_trace = array(
			'form_id'                  => $form_id,
			'form_type'                => $form_type,
			'token_status'             => $token_res['status'],
			'token_age'                => $token_res['age'],
			'is_token_expired'         => 'expired' === $token_res['status'],
			'is_token_replayed'        => 'replayed' === $token_res['status'],
			'honeypot_triggered'       => $hp_result['triggered'],
			'honeypot_autofill'        => ! empty( $hp_result['autofill_detected'] ),
			'telemetry_validated'      => ! empty( $telemetry ),
			'elapsed_time'             => $elapsed_time,
			'risk_score'               => $evaluation['score'],
			'challenge_solved'         => $challenge_solved,
			'rate_limit_exceeded'      => $rate_res['rate_exceeded'],
			'is_quarantined'           => $rate_res['is_quarantined'],
			'security_decision'        => $evaluation['action'],
			'is_valid'                 => ( 'allow' === $evaluation['action'] ),
			'other_plugin_aborted'     => false,
		);

		// 9. Log the verification attempt.
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$this->logger->log(
			array(
				'form_type'       => $form_type,
				'form_id'         => $form_id,
				'risk_score'      => $evaluation['score'],
				'action_taken'    => $evaluation['action'],
				'reasons'         => $evaluation['signals'],
				'ip_hash'         => $ip_hash,
				'masked_ip'       => $masked_ip,
				'user_agent'      => $user_agent,
				'submission_time' => $elapsed_time,
				'is_spam'         => $evaluation['is_spam'],
			)
		);

		return array(
			'is_valid'      => ( 'allow' === $evaluation['action'] ),
			'action'        => $evaluation['action'],
			'score'         => $evaluation['score'],
			'error_message' => $error_message,
			'signals'       => $evaluation['signals'],
		);
	}

	/**
	 * Returns the diagnostic lifecycle trace for the most recent verification.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_last_verification_trace(): ?array {
		return $this->last_verification_trace;
	}

	/**
	 * Checks if a form is in the exclusion list.
	 *
	 * @param string $form_type Form builder slug.
	 * @param string $form_id   Form ID.
	 * @return bool True if excluded.
	 */
	public function is_form_excluded( string $form_type, string $form_id ): bool {
		$excluded_raw = (string) ( get_option( 'vighnaghna_excluded_forms' ) ?: get_option( 'botfence_excluded_forms' ) ?: get_option( 'kl_antispam_excluded_forms', '' ) );
		if ( empty( trim( $excluded_raw ) ) ) {
			return false;
		}

		$lines = array_filter( array_map( 'trim', explode( "\n", $excluded_raw ) ) );
		foreach ( $lines as $line ) {
			if ( $line === $form_id || $line === ( $form_type . ':' . $form_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extracts all text values from the submission payload for content scanning.
	 *
	 * @param array<string, mixed> $data Submission data.
	 * @return string Concatenated text content.
	 */
	public function extract_text_content( array $data ): string {
		$text_parts = array();
		$skip_keys  = array(
			'_wpnonce',
			'action',
			'vighnaghna_token',
			'_vighnaghna_token',
			'botfence_token',
			'botfence_anti_spam_token',
			'kl_anti_spam_token',
			'_kl_as_token',
			'vighnaghna_telemetry',
			'_vighnaghna_telemetry',
			'botfence_telemetry',
			'botfence_anti_spam_telemetry',
			'kl_anti_spam_telemetry',
			'_kl_as_telemetry',
			'vighnaghna_challenge_token',
			'vighnaghna_challenge_answer',
			'botfence_challenge_token',
			'botfence_challenge_answer',
			'kl_challenge_token',
			'kl_challenge_answer',
			'_wpcf7',
			'_wpcf7_version',
			'_wpcf7_locale',
			'_wpcf7_unit_tag',
			'_wpcf7_container_post',
			'_wpcf7_posted_data_hash',
			'wpa_initiator',
			'wpa_field',
			'wpa_uuid',
		);

		foreach ( $data as $k => $v ) {
			$key_str = (string) $k;
			if (
				in_array( $key_str, $skip_keys, true ) ||
				str_starts_with( $key_str, 'vighnaghna_' ) ||
				str_starts_with( $key_str, 'botfence_' ) ||
				str_starts_with( $key_str, 'kl_' ) ||
				str_starts_with( $key_str, 'wpa_' ) ||
				str_starts_with( $key_str, 'akismet_' ) ||
				str_starts_with( $key_str, '_wpcf7' )
			) {
				continue;
			}
			if ( is_string( $v ) ) {
				$text_parts[] = $v;
			} elseif ( is_array( $v ) ) {
				$text_parts[] = $this->extract_text_content( $v );
			}
		}

		return implode( ' ', $text_parts );
	}

	/**
	 * Counts URLs found within text content.
	 *
	 * @param string $text Text to scan.
	 * @return int Number of URLs found.
	 */
	public function count_urls( string $text ): int {
		if ( empty( $text ) ) {
			return 0;
		}

		$pattern = '/\b(?:https?:\/\/|www\.)[^\s<>"\'\)]+/i';
		$matches = array();
		preg_match_all( $pattern, $text, $matches );

		return isset( $matches[0] ) ? count( $matches[0] ) : 0;
	}

	/**
	 * Generates an accessible adaptive / fallback verification challenge.
	 *
	 * @param string $form_id Form identifier.
	 * @return array{
	 *     question: string,
	 *     challenge_token: string,
	 *     expires_at: int
	 * }
	 */
	public function generate_adaptive_challenge( string $form_id = '' ): array {
		$num1   = wp_rand( 2, 9 );
		$num2   = wp_rand( 1, 9 );
		$answer = (string) ( $num1 + $num2 );

		$secret     = (string) ( get_option( 'vighnaghna_secret_key' ) ?: get_option( 'botfence_secret_key' ) ?: get_option( 'kl_antispam_secret_key', 'vighnaghna_secret' ) );
		$expires_at = time() + 900; // 15 minutes validity
		$nonce      = wp_generate_password( 16, false );

		// Cryptographic payload contains NO plaintext answer.
		// The answer is sealed in the HMAC signature.
		$payload     = $form_id . '|' . $expires_at . '|' . $nonce;
		$answer_hash = hash_hmac( 'sha256', $form_id . '|' . $answer . '|' . $expires_at . '|' . $nonce, $secret );
		$token       = base64_encode( $payload ) . '.' . $answer_hash;

		return array(
			'question'        => sprintf(
				/* translators: 1: first number, 2: second number */
				__( 'Please solve this simple verification: What is %1$d + %2$d?', 'vighnaghna' ),
				$num1,
				$num2
			),
			'challenge_token' => $token,
			'expires_at'      => $expires_at,
		);
	}

	/**
	 * Verifies an adaptive challenge solution.
	 *
	 * @param string $token   Challenge token.
	 * @param string $answer  User provided answer.
	 * @param string $form_id Form identifier.
	 * @return bool True if solution is correct and token valid.
	 */
	public function verify_adaptive_challenge( string $token, string $answer, string $form_id = '' ): bool {
		$parts = explode( '.', trim( $token ) );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		list( $encoded_payload, $provided_hash ) = $parts;
		$secret  = (string) ( get_option( 'vighnaghna_secret_key' ) ?: get_option( 'botfence_secret_key' ) ?: get_option( 'kl_antispam_secret_key', 'vighnaghna_secret' ) );
		$payload = base64_decode( $encoded_payload );

		$data = explode( '|', $payload );
		if ( count( $data ) !== 3 ) {
			return false;
		}

		list( $token_form_id, $expires_at, $nonce ) = $data;

		// Form binding validation.
		if ( ! empty( $form_id ) && ! empty( $token_form_id ) && $token_form_id !== $form_id && ! in_array( $token_form_id, array( 'generic', 'cached_page' ), true ) ) {
			return false;
		}

		if ( time() > (int) $expires_at ) {
			return false;
		}

		// Atomic single-use consumption of challenge nonce.
		$nonce_hash = hash( 'sha256', 'challenge_' . $nonce );
		$now_dt     = gmdate( 'Y-m-d H:i:s' );
		$exp_dt     = gmdate( 'Y-m-d H:i:s', (int) $expires_at + 120 );
		$consumed   = $this->token_manager->consume_token_atomically( $nonce_hash, $token_form_id, $now_dt, $exp_dt );
		if ( ! $consumed ) {
			return false;
		}

		// Recompute sealed answer HMAC.
		$clean_answer  = trim( (string) $answer );
		$expected_hash = hash_hmac( 'sha256', $token_form_id . '|' . $clean_answer . '|' . $expires_at . '|' . $nonce, $secret );

		return hash_equals( $expected_hash, $provided_hash );
	}

	/**
	 * Getter for token manager.
	 *
	 * @return Token_Manager
	 */
	public function get_token_manager(): Token_Manager {
		return $this->token_manager;
	}

	/**
	 * Getter for honeypot.
	 *
	 * @return Honeypot
	 */
	public function get_honeypot(): Honeypot {
		return $this->honeypot;
	}

	/**
	 * Getter for rate limiter.
	 *
	 * @return Rate_Limiter
	 */
	public function get_rate_limiter(): Rate_Limiter {
		return $this->rate_limiter;
	}

	/**
	 * Getter for risk engine.
	 *
	 * @return Risk_Engine
	 */
	public function get_risk_engine(): Risk_Engine {
		return $this->risk_engine;
	}

	/**
	 * Getter for logger.
	 *
	 * @return Logger
	 */
	public function get_logger(): Logger {
		return $this->logger;
	}
}

// Backwards compatibility aliases.
if ( ! class_exists( '\BotFence\Captcha_Engine', false ) ) {
	class_alias( '\Vighnaghna\Captcha_Engine', '\BotFence\Captcha_Engine' );
}
if ( ! class_exists( '\KL_Anti_Spam\Captcha_Engine', false ) ) {
	class_alias( '\Vighnaghna\Captcha_Engine', '\KL_Anti_Spam\Captcha_Engine' );
}
