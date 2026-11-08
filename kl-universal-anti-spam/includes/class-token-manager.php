<?php
/**
 * Cryptographic Token Manager for KL Universal Anti-Spam.
 *
 * @package KL_Anti_Spam
 */

namespace KL_Anti_Spam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Token_Manager
 */
class Token_Manager {

	/**
	 * Secret key option name.
	 */
	public const SECRET_OPTION = 'kl_antispam_secret_key';

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
	 * Generates a short-lived cryptographically signed token.
	 *
	 * @param string $form_id   Identifier of the form.
	 * @param string $client_ip Visitor IP.
	 * @param int|null $custom_time Optional custom timestamp (for testing).
	 * @return array{token: string, timestamp: int, expires_at: int}
	 */
	public function generate_token( string $form_id = 'generic', string $client_ip = '', ?int $custom_time = null ): array {
		$secret    = $this->get_secret_key();
		$timestamp = null !== $custom_time ? $custom_time : time();
		$lifetime  = (int) get_option( 'kl_antispam_token_lifetime', self::DEFAULT_LIFETIME );
		if ( $lifetime <= 60 ) {
			$lifetime = self::DEFAULT_LIFETIME;
		}

		try {
			$nonce = bin2hex( random_bytes( 16 ) );
		} catch ( \Exception $e ) {
			$nonce = wp_generate_password( 32, false );
		}

		// Privacy-conscious client IP fingerprint for binding.
		$ip_fingerprint = ! empty( $client_ip ) ? substr( hash_hmac( 'sha256', $client_ip, $secret ), 0, 16 ) : '';

		$payload = array(
			'n' => $nonce,
			't' => $timestamp,
			'f' => sanitize_key( $form_id ),
			'i' => $ip_fingerprint,
		);

		$payload_json    = (string) wp_json_encode( $payload );
		$encoded_payload = rtrim( strtr( base64_encode( $payload_json ), '+/', '-_' ), '=' );
		$signature       = hash_hmac( 'sha256', $encoded_payload, $secret );
		$token_string    = $encoded_payload . '.' . $signature;

		return array(
			'token'      => $token_string,
			'timestamp'  => $timestamp,
			'expires_at' => $timestamp + $lifetime,
		);
	}

	/**
	 * Validates a cryptographically signed token.
	 *
	 * Checks structure, signature, expiration, and replay protection.
	 *
	 * @param mixed  $token           Token string to validate.
	 * @param string $form_id         Form identifier for validation.
	 * @param string $client_ip       Visitor IP address.
	 * @param int|null $current_time  Optional current timestamp (for testing).
	 * @return array{
	 *     is_valid: bool,
	 *     status: string,
	 *     timestamp: int,
	 *     age: float,
	 *     risk_points: int,
	 *     error_message: string
	 * }
	 */
	public function validate_token( $token, string $form_id = '', string $client_ip = '', ?int $current_time = null ): array {
		$now              = null !== $current_time ? $current_time : time();
		$weight_invalid   = (int) get_option( 'kl_antispam_weight_invalid_token', 100 );
		$weight_expired   = (int) get_option( 'kl_antispam_weight_expired_token', 80 );
		$lifetime         = (int) get_option( 'kl_antispam_token_lifetime', self::DEFAULT_LIFETIME );

		// 1. Missing Token.
		if ( empty( $token ) || ! is_string( $token ) ) {
			return array(
				'is_valid'      => false,
				'status'        => 'missing',
				'timestamp'     => 0,
				'age'           => 0.0,
				'risk_points'   => $weight_invalid,
				'error_message' => __( 'Security verification token is missing.', 'kl-universal-anti-spam' ),
			);
		}

		// 2. Token Structure Check (Payload . Signature).
		$parts = explode( '.', trim( $token ) );
		if ( count( $parts ) !== 2 ) {
			return array(
				'is_valid'      => false,
				'status'        => 'malformed',
				'timestamp'     => 0,
				'age'           => 0.0,
				'risk_points'   => $weight_invalid,
				'error_message' => __( 'Security verification token is malformed.', 'kl-universal-anti-spam' ),
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
				'age'           => 0.0,
				'risk_points'   => $weight_invalid,
				'error_message' => __( 'Security token signature verification failed.', 'kl-universal-anti-spam' ),
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
				'age'           => 0.0,
				'risk_points'   => $weight_invalid,
				'error_message' => __( 'Security token contents are corrupted.', 'kl-universal-anti-spam' ),
			);
		}

		$token_time = (int) $payload['t'];
		$age        = (float) ( $now - $token_time );

		// 5. Future Timestamp / Clock Skew Check (allow max 60s future drift).
		if ( $age < -60 ) {
			return array(
				'is_valid'      => false,
				'status'        => 'future_timestamp',
				'timestamp'     => $token_time,
				'age'           => $age,
				'risk_points'   => $weight_invalid,
				'error_message' => __( 'Security token timestamp is invalid.', 'kl-universal-anti-spam' ),
			);
		}

		// 6. Expiration Check.
		if ( $age > $lifetime ) {
			return array(
				'is_valid'      => false,
				'status'        => 'expired',
				'timestamp'     => $token_time,
				'age'           => $age,
				'risk_points'   => $weight_expired,
				'error_message' => __( 'Security token has expired. Please refresh the page.', 'kl-universal-anti-spam' ),
			);
		}

		// 7. Replay Protection.
		// Each nonce must be used at most once.
		$nonce_hash = md5( $payload['n'] . '_' . $payload['t'] );
		$transient_key = 'kl_used_tok_' . $nonce_hash;

		if ( get_transient( $transient_key ) ) {
			return array(
				'is_valid'      => false,
				'status'        => 'replayed',
				'timestamp'     => $token_time,
				'age'           => $age,
				'risk_points'   => $weight_invalid,
				'error_message' => __( 'Security token has already been submitted (replay detected).', 'kl-universal-anti-spam' ),
			);
		}

		// Mark token as consumed for its remaining lifetime plus 120s safety buffer.
		$ttl = max( 120, (int) ( $lifetime - $age + 120 ) );
		set_transient( $transient_key, 1, $ttl );

		return array(
			'is_valid'      => true,
			'status'        => 'valid',
			'timestamp'     => $token_time,
			'age'           => max( 0.0, $age ),
			'risk_points'   => 0,
			'error_message' => '',
		);
	}
}
