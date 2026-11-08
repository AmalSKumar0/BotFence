<?php
/**
 * Master CAPTCHA & Security Engine for BotFence.
 *
 * @package BotFence
 */

namespace BotFence;

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
		$is_enabled = get_option( 'botfence_enabled' );
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

		// 1. Honeypot Check.
		$hp_result = $this->honeypot->check_submission( $submission_data );

		// 2. Token Check.
		$possible_token_keys = array( 'botfence_token', 'botfence_anti_spam_token', 'kl_anti_spam_token', '_kl_as_token', '_botfence_token' );
		$raw_token           = '';
		foreach ( $possible_token_keys as $k ) {
			if ( ! empty( $submission_data[ $k ] ) ) {
				$raw_token = (string) $submission_data[ $k ];
				break;
			}
		}

		$token_res = $this->token_manager->validate_token( $raw_token, $form_id, $client_ip );

		// 3. Telemetry Extraction.
		$possible_telemetry_keys = array( 'botfence_telemetry', 'botfence_anti_spam_telemetry', 'kl_anti_spam_telemetry', '_kl_as_telemetry', '_botfence_telemetry' );
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

		$elapsed_time = isset( $telemetry['elapsed_time'] ) ? (float) $telemetry['elapsed_time'] : (float) ( $submission_data['botfence_elapsed_time'] ?? $submission_data['kl_elapsed_time'] ?? 0 );

		// 4. Content Analysis (Excessive URLs & Duplicate Message).
		$text_fields = $this->extract_text_content( $submission_data );
		$url_count   = $this->count_urls( $text_fields );

		// 5. Rate Limiting Check.
		$rate_res = $this->rate_limiter->check_submission( $form_id, $text_fields, $client_ip );

		// 6. Check for Adaptive / Fallback Challenge Solution.
		$challenge_solved = false;
		$challenge_token  = (string) ( $submission_data['botfence_challenge_token'] ?? $submission_data['kl_challenge_token'] ?? '' );
		$challenge_answer = (string) ( $submission_data['botfence_challenge_answer'] ?? $submission_data['kl_challenge_answer'] ?? '' );
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

		// If adaptive challenge was successfully solved, allow if score was in medium risk range!
		if ( $challenge_solved && 'challenge' === $evaluation['action'] ) {
			$evaluation['action']  = 'allow';
			$evaluation['is_spam'] = false;
		}

		// Generate user-friendly error message if blocked or challenged.
		$error_message = '';
		if ( 'block' === $evaluation['action'] ) {
			$error_message = __( 'Your submission was flagged as potential spam. If you believe this is an error, please try again or contact the site administrator.', 'botfence' );
		} elseif ( 'challenge' === $evaluation['action'] ) {
			$error_message = __( 'Additional human verification is required before your submission can be accepted.', 'botfence' );
		}

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
	 * Checks if a form is in the exclusion list.
	 *
	 * @param string $form_type Form builder slug.
	 * @param string $form_id   Form ID.
	 * @return bool True if excluded.
	 */
	public function is_form_excluded( string $form_type, string $form_id ): bool {
		$excluded_raw = (string) ( get_option( 'botfence_excluded_forms' ) ?: get_option( 'kl_antispam_excluded_forms', '' ) );
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
			'botfence_token',
			'botfence_anti_spam_token',
			'kl_anti_spam_token',
			'_kl_as_token',
			'botfence_telemetry',
			'botfence_anti_spam_telemetry',
			'kl_anti_spam_telemetry',
			'_kl_as_telemetry',
			'botfence_challenge_token',
			'botfence_challenge_answer',
			'kl_challenge_token',
			'kl_challenge_answer',
		);

		foreach ( $data as $k => $v ) {
			if ( in_array( $k, $skip_keys, true ) || str_starts_with( (string) $k, 'botfence_hp_' ) || str_starts_with( (string) $k, 'kl_hp_' ) ) {
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

		$secret     = (string) ( get_option( 'botfence_secret_key' ) ?: get_option( 'kl_antispam_secret_key', 'botfence_secret' ) );
		$expires_at = time() + 900; // 15 minutes validity
		$nonce      = wp_generate_password( 12, false );

		$payload   = $form_id . '|' . $answer . '|' . $expires_at . '|' . $nonce;
		$signature = hash_hmac( 'sha256', $payload, $secret );
		$token     = base64_encode( $payload ) . '.' . $signature;

		return array(
			'question'        => sprintf(
				/* translators: 1: first number, 2: second number */
				__( 'Please solve this simple verification: What is %1$d + %2$d?', 'botfence' ),
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

		list( $encoded_payload, $signature ) = $parts;
		$secret  = (string) ( get_option( 'botfence_secret_key' ) ?: get_option( 'kl_antispam_secret_key', 'botfence_secret' ) );
		$payload = base64_decode( $encoded_payload );

		$expected_sig = hash_hmac( 'sha256', $payload, $secret );
		if ( ! hash_equals( $expected_sig, $signature ) ) {
			return false;
		}

		$data = explode( '|', $payload );
		if ( count( $data ) !== 4 ) {
			return false;
		}

		list( $token_form_id, $expected_answer, $expires_at, $nonce ) = $data;

		if ( time() > (int) $expires_at ) {
			return false;
		}

		// Replay protection for challenge token.
		$transient_key = 'botfence_ch_used_' . md5( $nonce );
		$kl_key        = 'kl_ch_used_' . md5( $nonce );
		if ( get_transient( $transient_key ) || get_transient( $kl_key ) ) {
			return false;
		}
		set_transient( $transient_key, 1, 900 );
		set_transient( $kl_key, 1, 900 );

		return trim( $answer ) === trim( $expected_answer );
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

// Backwards compatibility alias.
if ( ! class_exists( '\KL_Anti_Spam\Captcha_Engine', false ) ) {
	class_alias( '\BotFence\Captcha_Engine', '\KL_Anti_Spam\Captcha_Engine' );
}
