<?php
/**
 * Risk Scoring Engine for Vighnaghna.
 *
 * @package Vighnaghna
 */

namespace Vighnaghna;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Risk_Engine
 */
class Risk_Engine {

	/**
	 * Signal constants.
	 */
	public const SIGNAL_HONEYPOT            = 'honeypot_completed';
	public const SIGNAL_INVALID_TOKEN       = 'invalid_token';
	public const SIGNAL_EXPIRED_TOKEN       = 'expired_token';
	public const SIGNAL_FAST_SUBMISSION     = 'fast_submission';
	public const SIGNAL_RATE_LIMIT          = 'rate_limit_exceeded';
	public const SIGNAL_EXCESSIVE_URLS      = 'excessive_urls';
	public const SIGNAL_SUSPICIOUS_BEHAVIOR = 'suspicious_behavior';
	public const SIGNAL_REPEATED_MESSAGE    = 'repeated_message';
	public const SIGNAL_HEADLESS_BROWSER    = 'headless_browser';
	public const SIGNAL_UNTRUSTED_EVENT     = 'untrusted_event';
	public const SIGNAL_INSTANT_SUBMISSION  = 'instant_submission';
	public const SIGNAL_MISSING_TIMING      = 'missing_timing';
	public const SIGNAL_NEGATIVE_DURATION   = 'negative_duration';
	public const SIGNAL_FUTURE_TIMESTAMP    = 'future_timestamp';

	/**
	 * Default risk weights.
	 *
	 * @var array<string, int>
	 */
	private array $default_weights = array(
		self::SIGNAL_HONEYPOT            => 100,
		self::SIGNAL_INVALID_TOKEN       => 100,
		self::SIGNAL_EXPIRED_TOKEN       => 80,
		self::SIGNAL_FAST_SUBMISSION     => 30,
		self::SIGNAL_RATE_LIMIT          => 40,
		self::SIGNAL_EXCESSIVE_URLS      => 25,
		self::SIGNAL_SUSPICIOUS_BEHAVIOR => 20,
		self::SIGNAL_REPEATED_MESSAGE    => 35,
		self::SIGNAL_HEADLESS_BROWSER    => 50,
		self::SIGNAL_UNTRUSTED_EVENT     => 45,
	);

	/**
	 * Retrieves the configured weight for a risk signal.
	 *
	 * @param string $signal Signal identifier.
	 * @return int Configured weight points.
	 */
	public function get_weight( string $signal ): int {
		$option_map = array(
			self::SIGNAL_HONEYPOT            => 'weight_honeypot',
			self::SIGNAL_INVALID_TOKEN       => 'weight_invalid_token',
			self::SIGNAL_EXPIRED_TOKEN       => 'weight_expired_token',
			self::SIGNAL_FAST_SUBMISSION     => 'weight_fast_submit',
			self::SIGNAL_RATE_LIMIT          => 'weight_rate_limit',
			self::SIGNAL_EXCESSIVE_URLS      => 'weight_excessive_urls',
			self::SIGNAL_SUSPICIOUS_BEHAVIOR => 'weight_suspicious_telemetry',
			self::SIGNAL_REPEATED_MESSAGE    => 'weight_repeated_message',
			self::SIGNAL_HEADLESS_BROWSER    => 'weight_headless_browser',
			self::SIGNAL_UNTRUSTED_EVENT     => 'weight_untrusted_event',
		);

		if ( isset( $option_map[ $signal ] ) ) {
			$key = $option_map[ $signal ];
			$val = get_option( 'vighnaghna_' . $key );
			if ( false !== $val && null !== $val && is_numeric( $val ) ) {
				return (int) $val;
			}
			$legacy = get_option( 'botfence_' . $key );
			if ( false !== $legacy && null !== $legacy && is_numeric( $legacy ) ) {
				return (int) $legacy;
			}
			$kl = get_option( 'kl_antispam_' . $key );
			if ( false !== $kl && null !== $kl && is_numeric( $kl ) ) {
				return (int) $kl;
			}
		}

		return $this->default_weights[ $signal ] ?? 20;
	}

	/**
	 * Retrieves configured risk thresholds.
	 *
	 * @return array{challenge: int, block: int}
	 */
	public function get_thresholds(): array {
		$challenge = get_option( 'vighnaghna_risk_threshold_challenge' );
		if ( false === $challenge || null === $challenge ) {
			$challenge = get_option( 'botfence_risk_threshold_challenge' );
		}
		if ( false === $challenge || null === $challenge ) {
			$challenge = get_option( 'kl_antispam_risk_threshold_challenge', 30 );
		}

		$block = get_option( 'vighnaghna_risk_threshold_block' );
		if ( false === $block || null === $block ) {
			$block = get_option( 'botfence_risk_threshold_block' );
		}
		if ( false === $block || null === $block ) {
			$block = get_option( 'kl_antispam_risk_threshold_block', 60 );
		}

		return array(
			'challenge' => max( 1, min( 99, (int) $challenge ) ),
			'block'     => max( 2, min( 100, (int) $block ) ),
		);
	}

	/**
	 * Evaluates submission signals and calculates total risk score.
	 *
	 * @param array<string, mixed> $context Evaluated parameters.
	 * @return array{
	 *     score: int,
	 *     action: string,
	 *     signals: array<int, array{code: string, name: string, points: int, details: string}>,
	 *     is_spam: bool
	 * }
	 */
	public function evaluate( array $context ): array {
		$signals     = array();
		$total_score = 0;

		// 1. Honeypot Signal.
		if ( ! empty( $context['honeypot_triggered'] ) ) {
			$points       = $this->get_weight( self::SIGNAL_HONEYPOT );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_HONEYPOT,
				'name'    => __( 'Honeypot field filled', 'vighnaghna' ),
				'points'  => $points,
				'details' => sprintf(
					/* translators: %s: field name */
					__( 'Hidden trap field "%s" was populated.', 'vighnaghna' ),
					sanitize_key( (string) ( $context['honeypot_field'] ?? '' ) )
				),
			);
		}

		// 2. Token Signal.
		$token_status  = (string) ( $context['token_status'] ?? 'valid' );
		$timing_status = (string) ( $context['timing_status'] ?? '' );

		if ( 'expired' === $token_status ) {
			// Expired tokens are treated separately from malicious tampering.
			$points       = $this->get_weight( self::SIGNAL_EXPIRED_TOKEN );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_EXPIRED_TOKEN,
				'name'    => __( 'Expired security token', 'vighnaghna' ),
				'points'  => $points,
				'details' => sprintf(
					/* translators: %.1f: token age in seconds */
					__( 'Token submitted after expiration (age: %.1f seconds).', 'vighnaghna' ),
					(float) ( $context['token_age'] ?? 0 )
				),
			);
		} elseif ( in_array( $token_status, array( 'missing', 'malformed', 'tampered', 'invalid_payload', 'replayed', 'form_mismatch', 'future_timestamp', 'negative_duration' ), true ) ) {
			$points       = $this->get_weight( self::SIGNAL_INVALID_TOKEN );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_INVALID_TOKEN,
				'name'    => __( 'Invalid or replayed token', 'vighnaghna' ),
				'points'  => $points,
				'details' => sprintf(
					/* translators: %s: token status code */
					__( 'Token validation failed with status: %s.', 'vighnaghna' ),
					sanitize_key( $token_status )
				),
			);
		}

		// 3. Timing and Submission Velocity Analysis.
		$has_duration = false;
		$duration_ms  = null;

		if ( array_key_exists( 'duration_ms', $context ) && null !== $context['duration_ms'] ) {
			$duration_ms  = (int) round( (float) $context['duration_ms'] );
			$has_duration = true;
		} elseif ( array_key_exists( 'verified_duration_ms', $context ) && null !== $context['verified_duration_ms'] ) {
			$duration_ms  = (int) round( (float) $context['verified_duration_ms'] );
			$has_duration = true;
		} elseif ( array_key_exists( 'elapsed_time', $context ) && null !== $context['elapsed_time'] ) {
			$duration_ms  = (int) round( (float) $context['elapsed_time'] * 1000 );
			$has_duration = true;
		}

		$thresholds   = $this->get_thresholds();
		$block_thresh = $thresholds['block'];

		// Case A: Negative duration -> Reject.
		if ( ( $has_duration && $duration_ms < 0 ) || 'negative_duration' === $timing_status || 'negative_duration' === $token_status ) {
			$points       = 100;
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_NEGATIVE_DURATION,
				'name'    => __( 'Negative submission duration', 'vighnaghna' ),
				'points'  => $points,
				'details' => sprintf(
					/* translators: %d: measured duration in milliseconds */
					__( 'Recorded negative elapsed time (%d ms). Timestamp is corrupted or in the future.', 'vighnaghna' ),
					(int) $duration_ms
				),
			);
		}
		// Case B: Future timestamp -> Reject.
		elseif ( 'future_timestamp' === $timing_status || 'future_timestamp' === $token_status ) {
			$points       = 100;
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_FUTURE_TIMESTAMP,
				'name'    => __( 'Future security timestamp', 'vighnaghna' ),
				'points'  => $points,
				'details' => __( 'Security verification timestamp is in the future.', 'vighnaghna' ),
			);
		}
		// Case C: Tampered / malformed token or timing data -> Already evaluated in token signal.
		elseif ( in_array( $timing_status, array( 'tampered', 'malformed', 'invalid_payload', 'replayed' ), true ) || in_array( $token_status, array( 'tampered', 'malformed', 'invalid_payload', 'replayed' ), true ) ) {
			// Evaluated in token signal above.
		}
		// Case D: Expired token -> Already evaluated in token signal.
		elseif ( 'expired' === $token_status || 'expired' === $timing_status ) {
			// Evaluated in token signal above.
		}
		// Case E: Missing timing data -> High risk; do not treat as valid.
		elseif ( ! $has_duration || 'missing' === $timing_status || 'missing' === $token_status ) {
			// If missing token was already flagged with 100 pts above, avoid redundant score bloat.
			if ( 'missing' !== $token_status ) {
				$points       = max( 80, $block_thresh );
				$total_score += $points;
				$signals[]    = array(
					'code'    => self::SIGNAL_MISSING_TIMING,
					'name'    => __( 'Missing timing data', 'vighnaghna' ),
					'points'  => $points,
					'details' => __( 'Form submission did not include verifiable timing data or timestamp.', 'vighnaghna' ),
				);
			}
		}
		// Case F: Verified duration = 0 ms -> Block.
		elseif ( $duration_ms === 0 ) {
			$points       = 100;
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_INSTANT_SUBMISSION,
				'name'    => __( 'Zero-time submission (instant bot)', 'vighnaghna' ),
				'points'  => $points,
				'details' => __( 'Submission duration was measured at 0 ms (impossible for human interaction).', 'vighnaghna' ),
			);
		}
		// Case G: Verified duration between 1–999 ms -> Block.
		elseif ( $duration_ms >= 1 && $duration_ms <= 999 ) {
			$points       = 100;
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_INSTANT_SUBMISSION,
				'name'    => __( 'Sub-second submission', 'vighnaghna' ),
				'points'  => $points,
				'details' => sprintf(
					/* translators: %d: duration in milliseconds */
					__( 'Completed in %d ms (sub-second submission; automated velocity).', 'vighnaghna' ),
					$duration_ms
				),
			);
		}
		// Case H: Verified duration = 1,000–1,999 ms -> High risk; block at the configured threshold.
		elseif ( $duration_ms >= 1000 && $duration_ms <= 1999 ) {
			$points       = max( 60, $block_thresh );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_FAST_SUBMISSION,
				'name'    => __( 'High-risk submission velocity', 'vighnaghna' ),
				'points'  => $points,
				'details' => sprintf(
					/* translators: %d: duration in milliseconds */
					__( 'Completed in %d ms (high risk; under 2 seconds threshold).', 'vighnaghna' ),
					$duration_ms
				),
			);
		}
		// Case I: Valid duration above 2 seconds (>= 2,000 ms) -> Continue normal risk evaluation.
		else {
			$elapsed_seconds = (float) ( $duration_ms / 1000.0 );
			$min_time_limit  = (float) ( get_option( 'vighnaghna_min_submission_time' ) ?: get_option( 'botfence_min_submission_time' ) ?: get_option( 'kl_antispam_min_submission_time', 2.5 ) );
			if ( $elapsed_seconds < $min_time_limit ) {
				$points       = $this->get_weight( self::SIGNAL_FAST_SUBMISSION );
				$total_score += $points;
				$signals[]    = array(
					'code'    => self::SIGNAL_FAST_SUBMISSION,
					'name'    => __( 'Unrealistically fast submission', 'vighnaghna' ),
					'points'  => $points,
					'details' => sprintf(
						/* translators: 1: elapsed seconds, 2: minimum required seconds */
						__( 'Completed in %1$.2f seconds (minimum human threshold: %2$.2f seconds).', 'vighnaghna' ),
						$elapsed_seconds,
						$min_time_limit
					),
				);
			}
		}

		// 4. Rate Limiting Signal.
		if ( ! empty( $context['rate_exceeded'] ) || ! empty( $context['is_quarantined'] ) ) {
			$points       = $this->get_weight( self::SIGNAL_RATE_LIMIT );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_RATE_LIMIT,
				'name'    => __( 'Rate limit exceeded', 'vighnaghna' ),
				'points'  => $points,
				'details' => (string) ( $context['rate_reason'] ?? __( 'Exceeded allowed submission frequency.', 'vighnaghna' ) ),
			);
		}

		// 5. Repeated Message Signal.
		if ( ! empty( $context['repeated_message'] ) ) {
			$points       = $this->get_weight( self::SIGNAL_REPEATED_MESSAGE );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_REPEATED_MESSAGE,
				'name'    => __( 'Repeated identical message', 'vighnaghna' ),
				'points'  => $points,
				'details' => __( 'Identical form contents submitted repeatedly across recent requests.', 'vighnaghna' ),
			);
		}

		// 6. Excessive URLs in content.
		$url_count = (int) ( $context['url_count'] ?? 0 );
		if ( $url_count > 2 ) {
			$points       = $this->get_weight( self::SIGNAL_EXCESSIVE_URLS );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_EXCESSIVE_URLS,
				'name'    => __( 'Excessive URLs in form content', 'vighnaghna' ),
				'points'  => $points,
				'details' => sprintf(
					/* translators: %d: count of links */
					__( 'Found %d links/URLs in submitted text fields.', 'vighnaghna' ),
					$url_count
				),
			);
		}

		// 7. Behaviour & Telemetry Signals.
		$telemetry = is_array( $context['telemetry'] ?? null ) ? $context['telemetry'] : array();

		// Headless browser check.
		if ( ! empty( $telemetry['webdriver'] ) ) {
			$points       = $this->get_weight( self::SIGNAL_HEADLESS_BROWSER );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_HEADLESS_BROWSER,
				'name'    => __( 'Automated browser detected', 'vighnaghna' ),
				'points'  => $points,
				'details' => __( 'Client environment revealed navigator.webdriver flag.', 'vighnaghna' ),
			);
		}

		// Untrusted synthetic event.
		if ( isset( $telemetry['is_trusted'] ) && false === $telemetry['is_trusted'] ) {
			$points       = $this->get_weight( self::SIGNAL_UNTRUSTED_EVENT );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_UNTRUSTED_EVENT,
				'name'    => __( 'Synthetic submit event', 'vighnaghna' ),
				'points'  => $points,
				'details' => __( 'Form submission was triggered programmatically by script (event.isTrusted is false).', 'vighnaghna' ),
			);
		}

		// Zero human interaction with substantial text length.
		$mouse_moves = (int) ( $telemetry['mouse_moves'] ?? -1 );
		$keystrokes  = (int) ( $telemetry['keystrokes'] ?? -1 );
		$touches     = (int) ( $telemetry['touches'] ?? -1 );
		$focus_count = (int) ( $telemetry['focus_count'] ?? -1 );
		$paste_count = (int) ( $telemetry['paste_count'] ?? 0 );
		$autofill    = ! empty( $telemetry['autofill'] );
		$text_length = (int) ( $context['text_length'] ?? 0 );

		$is_assistive_or_paste = ( $paste_count > 0 || $autofill || $focus_count > 0 );

		if ( ! $is_assistive_or_paste && $mouse_moves === 0 && $keystrokes === 0 && $touches === 0 && $text_length > 30 ) {
			$points       = $this->get_weight( self::SIGNAL_SUSPICIOUS_BEHAVIOR );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_SUSPICIOUS_BEHAVIOR,
				'name'    => __( 'Suspicious zero-interaction submission', 'vighnaghna' ),
				'points'  => $points,
				'details' => __( 'Form contains substantial text but recorded zero keyboard, mouse, or touch events.', 'vighnaghna' ),
			);
		}

		// Clamp final score between 0 and 100.
		$final_score = min( 100, max( 0, $total_score ) );
		$thresholds  = $this->get_thresholds();

		if ( $final_score >= $thresholds['block'] ) {
			$action  = 'block';
			$is_spam = true;
		} elseif ( $final_score >= $thresholds['challenge'] ) {
			$action  = 'challenge';
			$is_spam = true;
		} else {
			$action  = 'allow';
			$is_spam = false;
		}

		return array(
			'score'   => $final_score,
			'action'  => $action,
			'signals' => $signals,
			'is_spam' => $is_spam,
		);
	}
}

// Backwards compatibility aliases.
if ( ! class_exists( '\BotFence\Risk_Engine', false ) ) {
	class_alias( '\Vighnaghna\Risk_Engine', '\BotFence\Risk_Engine' );
}
if ( ! class_exists( '\KL_Anti_Spam\Risk_Engine', false ) ) {
	class_alias( '\Vighnaghna\Risk_Engine', '\KL_Anti_Spam\Risk_Engine' );
}
