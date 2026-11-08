<?php
/**
 * Risk Scoring Engine for BotFence.
 *
 * @package BotFence
 */

namespace BotFence;

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
		);

		if ( isset( $option_map[ $signal ] ) ) {
			$key = $option_map[ $signal ];
			$val = get_option( 'botfence_' . $key );
			if ( false !== $val && null !== $val && is_numeric( $val ) ) {
				return (int) $val;
			}
			$legacy = get_option( 'kl_antispam_' . $key );
			if ( false !== $legacy && null !== $legacy && is_numeric( $legacy ) ) {
				return (int) $legacy;
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
		$challenge = get_option( 'botfence_risk_threshold_challenge' );
		if ( false === $challenge || null === $challenge ) {
			$challenge = get_option( 'kl_antispam_risk_threshold_challenge', 30 );
		}

		$block = get_option( 'botfence_risk_threshold_block' );
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
				'name'    => __( 'Honeypot field filled', 'botfence' ),
				'points'  => $points,
				'details' => sprintf(
					/* translators: %s: field name */
					__( 'Hidden trap field "%s" was populated.', 'botfence' ),
					sanitize_key( (string) ( $context['honeypot_field'] ?? '' ) )
				),
			);
		}

		// 2. Token Signal.
		$token_status = (string) ( $context['token_status'] ?? 'valid' );
		if ( 'expired' === $token_status ) {
			$points       = $this->get_weight( self::SIGNAL_EXPIRED_TOKEN );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_EXPIRED_TOKEN,
				'name'    => __( 'Expired security token', 'botfence' ),
				'points'  => $points,
				'details' => sprintf(
					/* translators: %.1f: token age in seconds */
					__( 'Token submitted after expiration (age: %.1f seconds).', 'botfence' ),
					(float) ( $context['token_age'] ?? 0 )
				),
			);
		} elseif ( in_array( $token_status, array( 'missing', 'malformed', 'tampered', 'invalid_payload', 'replayed', 'form_mismatch', 'future_timestamp' ), true ) ) {
			$points       = $this->get_weight( self::SIGNAL_INVALID_TOKEN );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_INVALID_TOKEN,
				'name'    => __( 'Invalid or replayed token', 'botfence' ),
				'points'  => $points,
				'details' => sprintf(
					/* translators: %s: token status code */
					__( 'Token validation failed with status: %s.', 'botfence' ),
					sanitize_key( $token_status )
				),
			);
		}

		// 3. Fast Submission Analysis.
		$elapsed_time   = (float) ( $context['elapsed_time'] ?? 0 );
		$min_time_limit = (float) ( get_option( 'botfence_min_submission_time' ) ?: get_option( 'kl_antispam_min_submission_time', 2.5 ) );
		if ( $elapsed_time > 0 && $elapsed_time < $min_time_limit ) {
			$points       = $this->get_weight( self::SIGNAL_FAST_SUBMISSION );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_FAST_SUBMISSION,
				'name'    => __( 'Unrealistically fast submission', 'botfence' ),
				'points'  => $points,
				'details' => sprintf(
					/* translators: 1: submission elapsed seconds, 2: minimum required seconds */
					__( 'Completed in %1$.2f seconds (minimum human threshold: %2$.2f seconds).', 'botfence' ),
					$elapsed_time,
					$min_time_limit
				),
			);
		}

		// 4. Rate Limiting Signal.
		if ( ! empty( $context['rate_exceeded'] ) || ! empty( $context['is_quarantined'] ) ) {
			$points       = $this->get_weight( self::SIGNAL_RATE_LIMIT );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_RATE_LIMIT,
				'name'    => __( 'Rate limit exceeded', 'botfence' ),
				'points'  => $points,
				'details' => (string) ( $context['rate_reason'] ?? __( 'Exceeded allowed submission frequency.', 'botfence' ) ),
			);
		}

		// 5. Repeated Message Signal.
		if ( ! empty( $context['repeated_message'] ) ) {
			$points       = $this->get_weight( self::SIGNAL_REPEATED_MESSAGE );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_REPEATED_MESSAGE,
				'name'    => __( 'Repeated identical message', 'botfence' ),
				'points'  => $points,
				'details' => __( 'Identical form contents submitted repeatedly across recent requests.', 'botfence' ),
			);
		}

		// 6. Excessive URLs in content.
		$url_count = (int) ( $context['url_count'] ?? 0 );
		if ( $url_count > 2 ) {
			$points       = $this->get_weight( self::SIGNAL_EXCESSIVE_URLS );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_EXCESSIVE_URLS,
				'name'    => __( 'Excessive URLs in form content', 'botfence' ),
				'points'  => $points,
				'details' => sprintf(
					/* translators: %d: count of links */
					__( 'Found %d links/URLs in submitted text fields.', 'botfence' ),
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
				'name'    => __( 'Automated browser detected', 'botfence' ),
				'points'  => $points,
				'details' => __( 'Client environment revealed navigator.webdriver flag.', 'botfence' ),
			);
		}

		// Untrusted synthetic event.
		if ( isset( $telemetry['is_trusted'] ) && false === $telemetry['is_trusted'] ) {
			$points       = $this->get_weight( self::SIGNAL_UNTRUSTED_EVENT );
			$total_score += $points;
			$signals[]    = array(
				'code'    => self::SIGNAL_UNTRUSTED_EVENT,
				'name'    => __( 'Synthetic submit event', 'botfence' ),
				'points'  => $points,
				'details' => __( 'Form submission was triggered programmatically by script (event.isTrusted is false).', 'botfence' ),
			);
		}

		// Zero human interaction with long text.
		// Protect legitimate users using password managers, autofill, paste, or assistive navigation.
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
				'name'    => __( 'Suspicious zero-interaction submission', 'botfence' ),
				'points'  => $points,
				'details' => __( 'Form contains substantial text but recorded zero keyboard, mouse, or touch events.', 'botfence' ),
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

// Backwards compatibility alias.
if ( ! class_exists( '\KL_Anti_Spam\Risk_Engine', false ) ) {
	class_alias( '\BotFence\Risk_Engine', '\KL_Anti_Spam\Risk_Engine' );
}
