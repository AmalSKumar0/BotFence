<?php
/**
 * Cryptographic Token Manager for Vighnaghna.
 *
 * @package Vighnaghna
 */

namespace Vighnaghna;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Token_Manager
 */
class Token_Manager {

	/**
	 * Primary secret key option name.
	 */
	public const SECRET_OPTION = 'vighnaghna_secret_key';

	/**
	 * Default token lifetime in seconds (10 minutes).
	 */
	public const DEFAULT_LIFETIME = 600;

	/**
	 * Cached secret key.
	 *
	 * @var string|null
	 */
	private ?string $secret_key = null;

	/**
	 * Retrieves the secret key for HMAC signing.
	 *
	 * @return string
	 */
	public function get_secret_key(): string {
		if ( null !== $this->secret_key && '' !== $this->secret_key ) {
			return $this->secret_key;
		}

		$key = get_option( self::SECRET_OPTION );
		if ( ! $key || ! is_string( $key ) || strlen( $key ) < 32 ) {
			$key = get_option( 'botfence_secret_key' );
		}
		if ( ! $key || ! is_string( $key ) || strlen( $key ) < 32 ) {
			$key = get_option( 'kl_antispam_secret_key' );
		}

		if ( ! $key || ! is_string( $key ) || strlen( $key ) < 32 ) {
			try {
				$key = bin2hex( random_bytes( 32 ) );
			} catch ( \Exception $e ) {
				$key = wp_generate_password( 64, true, true );
			}
			update_option( self::SECRET_OPTION, $key, true );
		}

		$this->secret_key = $key;
		return $this->secret_key;
	}

	/**
	 * Sets an in-memory secret key (primarily for unit testing).
	 *
	 * @param string $key Secret key.
	 */
	public function set_secret_key( string $key ): void {
		$this->secret_key = $key;
	}

	/**
	 * Generates a short-lived cryptographically signed token with millisecond precision.
	 *
	 * @param string          $form_id     Identifier of the form.
	 * @param string          $client_ip   Visitor IP.
	 * @param int|float|null  $custom_time Optional custom timestamp in seconds or milliseconds (for testing).
	 * @return array{token: string, timestamp: int, timestamp_ms: int, expires_at: int}
	 */
	public function generate_token( string $form_id = 'generic', string $client_ip = '', $custom_time = null ): array {
		$secret = $this->get_secret_key();

		if ( null !== $custom_time ) {
			if ( $custom_time < 10000000000 ) {
				$timestamp_ms = (int) round( (float) $custom_time * 1000 );
				$timestamp_s  = (int) round( (float) $custom_time );
			} else {
				$timestamp_ms = (int) round( (float) $custom_time );
				$timestamp_s  = (int) round( (float) $custom_time / 1000 );
			}
		} else {
			$micro        = microtime( true );
			$timestamp_ms = (int) round( $micro * 1000 );
			$timestamp_s  = (int) round( $micro );
		}

		$lifetime = (int) ( get_option( 'vighnaghna_token_lifetime' ) ?: get_option( 'botfence_token_lifetime' ) ?: get_option( 'kl_antispam_token_lifetime', self::DEFAULT_LIFETIME ) );
		if ( $lifetime <= 60 ) {
			$lifetime = self::DEFAULT_LIFETIME;
		}

		try {
			$nonce = bin2hex( random_bytes( 16 ) );
		} catch ( \Exception $e ) {
			$nonce = wp_generate_password( 32, false );
		}

		// Privacy-conscious client IP fingerprint for binding inspection.
		$ip_fingerprint = ! empty( $client_ip ) ? substr( hash_hmac( 'sha256', $client_ip, $secret ), 0, 16 ) : '';

		$payload = array(
			'n' => $nonce,
			't' => $timestamp_ms,
			'f' => sanitize_key( $form_id ),
			'i' => $ip_fingerprint,
		);

		$payload_json    = (string) wp_json_encode( $payload );
		$encoded_payload = rtrim( strtr( base64_encode( $payload_json ), '+/', '-_' ), '=' );
		$signature       = hash_hmac( 'sha256', $encoded_payload, $secret );
		$token_string    = $encoded_payload . '.' . $signature;

		return array(
			'token'        => $token_string,
			'timestamp'    => $timestamp_s,
			'timestamp_ms' => $timestamp_ms,
			'expires_at'   => $timestamp_s + $lifetime,
		);
	}

	/**
	 * Validates a cryptographically signed token.
	 *
	 * Checks structure, signature, expiration, form binding, timing duration, and replay protection.
	 * Employs a safe policy for proxies, mobile networks, and shifting IP addresses.
	 *
	 * @param mixed          $token        Token string to validate.
	 * @param string         $form_id      Form identifier for validation.
	 * @param string         $client_ip    Visitor IP address.
	 * @param int|float|null $current_time Optional current timestamp in seconds or milliseconds (for testing).
	 * @return array{
	 *     is_valid: bool,
	 *     status: string,
	 *     timestamp: int,
	 *     timestamp_ms: ?int,
	 *     age: float,
	 *     duration_ms: ?int,
	 *     timing_status: string,
	 *     risk_points: int,
	 *     error_message: string,
	 *     ip_match: bool
	 * }
	 */
	public function validate_token( $token, string $form_id = '', string $client_ip = '', $current_time = null ): array {
		$weight_invalid = (int) ( get_option( 'vighnaghna_weight_invalid_token' ) ?: get_option( 'botfence_weight_invalid_token' ) ?: get_option( 'kl_antispam_weight_invalid_token', 100 ) );
		$weight_expired = (int) ( get_option( 'vighnaghna_weight_expired_token' ) ?: get_option( 'botfence_weight_expired_token' ) ?: get_option( 'kl_antispam_weight_expired_token', 80 ) );
		$lifetime       = (int) ( get_option( 'vighnaghna_token_lifetime' ) ?: get_option( 'botfence_token_lifetime' ) ?: get_option( 'kl_antispam_token_lifetime', self::DEFAULT_LIFETIME ) );

		// 1. Missing Token.
		if ( empty( $token ) || ! is_string( $token ) ) {
			return array(
				'is_valid'      => false,
				'status'        => 'missing',
				'timestamp'     => 0,
				'timestamp_ms'  => null,
				'age'           => 0.0,
				'duration_ms'   => null,
				'timing_status' => 'missing',
				'risk_points'   => $weight_invalid,
				'error_message' => __( 'Security verification token is missing.', 'vighnaghna' ),
				'ip_match'      => false,
			);
		}

		// 2. Token Structure Check (Payload . Signature).
		$parts = explode( '.', trim( $token ) );
		if ( count( $parts ) !== 2 ) {
			return array(
				'is_valid'      => false,
				'status'        => 'malformed',
				'timestamp'     => 0,
				'timestamp_ms'  => null,
				'age'           => 0.0,
				'duration_ms'   => null,
				'timing_status' => 'malformed',
				'risk_points'   => $weight_invalid,
				'error_message' => __( 'Security verification token is malformed.', 'vighnaghna' ),
				'ip_match'      => false,
			);
		}

		list( $encoded_payload, $signature ) = $parts;
		$secret = $this->get_secret_key();

		// 3. Cryptographic Signature Verification.
		$expected_signature = hash_hmac( 'sha256', $encoded_payload, $secret );
		if ( ! hash_equals( $expected_signature, $signature ) ) {
			return array(
				'is_valid'      => false,
				'status'        => 'tampered',
				'timestamp'     => 0,
				'timestamp_ms'  => null,
				'age'           => 0.0,
				'duration_ms'   => null,
				'timing_status' => 'tampered',
				'risk_points'   => $weight_invalid,
				'error_message' => __( 'Security token signature verification failed.', 'vighnaghna' ),
				'ip_match'      => false,
			);
		}

		// 4. Decode Payload.
		$decoded_json = base64_decode( strtr( $encoded_payload, '-_', '+/' ) );
		$payload      = json_decode( $decoded_json, true );

		if ( ! is_array( $payload ) || ! isset( $payload['n'], $payload['t'] ) || ! is_numeric( $payload['t'] ) ) {
			return array(
				'is_valid'      => false,
				'status'        => 'invalid_payload',
				'timestamp'     => 0,
				'timestamp_ms'  => null,
				'age'           => 0.0,
				'duration_ms'   => null,
				'timing_status' => 'invalid_payload',
				'risk_points'   => $weight_invalid,
				'error_message' => __( 'Security token contents are corrupted.', 'vighnaghna' ),
				'ip_match'      => false,
			);
		}

		$raw_t = (float) $payload['t'];
		if ( $raw_t < 10000000000 ) {
			$token_time_ms = (int) round( $raw_t * 1000 );
			$token_time_s  = (int) round( $raw_t );
		} else {
			$token_time_ms = (int) round( $raw_t );
			$token_time_s  = (int) round( $raw_t / 1000 );
		}

		if ( null !== $current_time ) {
			if ( $current_time < 10000000000 ) {
				$now_ms = (int) round( (float) $current_time * 1000 );
				$now_s  = (int) round( (float) $current_time );
			} else {
				$now_ms = (int) round( (float) $current_time );
				$now_s  = (int) round( (float) $current_time / 1000 );
			}
		} else {
			$micro  = microtime( true );
			$now_ms = (int) round( $micro * 1000 );
			$now_s  = (int) round( $micro );
		}

		$duration_ms = $now_ms - $token_time_ms;
		$age         = (float) ( $duration_ms / 1000.0 );

		// 5. Future Timestamp / Clock Skew Check (allow max 60s future drift for signature validity).
		if ( $duration_ms < -60000 ) {
			return array(
				'is_valid'      => false,
				'status'        => 'future_timestamp',
				'timestamp'     => $token_time_s,
				'timestamp_ms'  => $token_time_ms,
				'age'           => $age,
				'duration_ms'   => $duration_ms,
				'timing_status' => 'future_timestamp',
				'risk_points'   => $weight_invalid,
				'error_message' => __( 'Security token timestamp is invalid (in the future).', 'vighnaghna' ),
				'ip_match'      => false,
			);
		}

		// 6. Expiration Check (treated separately from malicious tampering).
		if ( $age > $lifetime ) {
			return array(
				'is_valid'      => false,
				'status'        => 'expired',
				'timestamp'     => $token_time_s,
				'timestamp_ms'  => $token_time_ms,
				'age'           => $age,
				'duration_ms'   => $duration_ms,
				'timing_status' => 'expired',
				'risk_points'   => $weight_expired,
				'error_message' => __( 'Security verification token has expired. Please refresh the page and try again.', 'vighnaghna' ),
				'ip_match'      => true,
			);
		}

		// 7. Form-Specific Binding Validation.
		$token_form_id = sanitize_key( (string) ( $payload['f'] ?? '' ) );
		if ( ! empty( $form_id ) && ! empty( $token_form_id ) ) {
			$expected_form_id = sanitize_key( $form_id );
			if ( $token_form_id !== $expected_form_id && ! in_array( $token_form_id, array( 'generic', 'cached_page' ), true ) ) {
				return array(
					'is_valid'      => false,
					'status'        => 'form_mismatch',
					'timestamp'     => $token_time_s,
					'timestamp_ms'  => $token_time_ms,
					'age'           => $age,
					'duration_ms'   => $duration_ms,
					'timing_status' => 'form_mismatch',
					'risk_points'   => $weight_invalid,
					'error_message' => __( 'Security verification token is not valid for this form.', 'vighnaghna' ),
					'ip_match'      => false,
				);
			}
		}

		// 8. Safe IP Fingerprint Comparison (non-blocking policy for mobile networks and proxies).
		$ip_match = true;
		$token_ip = (string) ( $payload['i'] ?? '' );
		if ( ! empty( $token_ip ) && ! empty( $client_ip ) ) {
			$expected_ip_fingerprint = substr( hash_hmac( 'sha256', $client_ip, $secret ), 0, 16 );
			$ip_match                = hash_equals( $expected_ip_fingerprint, $token_ip );
			// Note: Mobile devices (CGNAT, cellular handover) and proxies routinely switch client IP.
			// To avoid false positives on legitimate users, IP mismatch is recorded as telemetry
			// but does NOT strictly reject an otherwise authentic, signed, non-expired token.
		}

		// 9. Atomic Single-Use Token Consumption (Replay Prevention).
		$nonce_hash = hash( 'sha256', $payload['n'] . '_' . $payload['t'] );
		$now_dt     = gmdate( 'Y-m-d H:i:s', $now_s );
		$exp_dt     = gmdate( 'Y-m-d H:i:s', $now_s + $lifetime + 120 );

		$consumed = $this->consume_token_atomically( $nonce_hash, $token_form_id, $now_dt, $exp_dt );
		if ( ! $consumed ) {
			return array(
				'is_valid'      => false,
				'status'        => 'replayed',
				'timestamp'     => $token_time_s,
				'timestamp_ms'  => $token_time_ms,
				'age'           => $age,
				'duration_ms'   => $duration_ms,
				'timing_status' => 'replayed',
				'risk_points'   => $weight_invalid,
				'error_message' => __( 'Security token has already been submitted (replay detected).', 'vighnaghna' ),
				'ip_match'      => $ip_match,
			);
		}

		return array(
			'is_valid'      => true,
			'status'        => 'valid',
			'timestamp'     => $token_time_s,
			'timestamp_ms'  => $token_time_ms,
			'age'           => $age,
			'duration_ms'   => $duration_ms,
			'timing_status' => $duration_ms < 0 ? 'negative_duration' : 'valid',
			'risk_points'   => 0,
			'error_message' => '',
			'ip_match'      => $ip_match,
		);
	}

	/**
	 * Atomically records token consumption to eliminate TOCTOU race conditions.
	 *
	 * @param string $nonce_hash  Unique token hash.
	 * @param string $form_id     Form identifier.
	 * @param string $consumed_at Datetime string.
	 * @param string $expires_at  Datetime string.
	 * @return bool True if successfully consumed, false if already consumed (duplicate).
	 */
	public function consume_token_atomically( string $nonce_hash, string $form_id, string $consumed_at, string $expires_at ): bool {
		global $wpdb;

		$table = Database::get_tokens_table();

		// Check if table exists in current database.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		if ( $table_exists === $table ) {
			$wpdb->suppress_errors( true );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$inserted = $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$table} (token_hash, form_id, consumed_at, expires_at) VALUES (%s, %s, %s, %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$nonce_hash,
					$form_id,
					$consumed_at,
					$expires_at
				)
			);
			$wpdb->suppress_errors( false );

			if ( false === $inserted || 0 === $inserted ) {
				return false;
			}
			return true;
		}

		// Fallback to transient-based consumption if database table is not yet provisioned.
		$transient_key = 'vighnaghna_used_tok_' . md5( $nonce_hash );
		if ( get_transient( $transient_key ) ) {
			return false;
		}
		set_transient( $transient_key, 1, 900 );
		return true;
	}
}

// Backwards compatibility aliases.
if ( ! class_exists( '\BotFence\Token_Manager', false ) ) {
	class_alias( '\Vighnaghna\Token_Manager', '\BotFence\Token_Manager' );
}
if ( ! class_exists( '\KL_Anti_Spam\Token_Manager', false ) ) {
	class_alias( '\Vighnaghna\Token_Manager', '\KL_Anti_Spam\Token_Manager' );
}
