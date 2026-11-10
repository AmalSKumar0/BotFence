<?php
/**
 * Rate Limiter and Abuse Prevention System for BotFence.
 *
 * @package BotFence
 */

namespace BotFence;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rate_Limiter
 */
class Rate_Limiter {

	/**
	 * Default per-minute submission limit per IP.
	 */
	public const DEFAULT_LIMIT_PER_MINUTE = 5;

	/**
	 * Default per-hour submission limit per IP.
	 */
	public const DEFAULT_LIMIT_PER_HOUR = 30;

	/**
	 * Default quarantine duration in seconds (15 minutes).
	 */
	public const DEFAULT_QUARANTINE_SECONDS = 900;

	/**
	 * Helper to get an option with fallback to legacy kl_antispam_ key.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	protected function get_conf( string $key, $default = false ) {
		$val = get_option( 'botfence_' . $key );
		if ( false !== $val && null !== $val ) {
			return $val;
		}
		return get_option( 'kl_antispam_' . $key, $default );
	}

	/**
	 * Extracts and sanitizes the visitor IP address.
	 *
	 * Securely handles reverse proxies only if explicitly trusted.
	 *
	 * @return string Validated IP address.
	 */
	public function get_client_ip(): string {
		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';

		if ( ! filter_var( $remote_addr, FILTER_VALIDATE_IP ) ) {
			$remote_addr = '127.0.0.1';
		}

		$trust_proxies = (bool) $this->get_conf( 'trust_reverse_proxies', 0 );
		if ( ! $trust_proxies ) {
			return $remote_addr;
		}

		// Verify whether REMOTE_ADDR is in trusted proxy list.
		if ( ! $this->is_trusted_proxy( $remote_addr ) ) {
			return $remote_addr;
		}

		// Cloudflare header.
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$cf_ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
			if ( filter_var( $cf_ip, FILTER_VALIDATE_IP ) ) {
				return $cf_ip;
			}
		}

		// X-Forwarded-For header.
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$ip_list   = explode( ',', $forwarded );
			foreach ( $ip_list as $raw_ip ) {
				$candidate = trim( $raw_ip );
				if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
					return $candidate;
				}
			}
		}

		// X-Real-IP header.
		if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$real_ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
			if ( filter_var( $real_ip, FILTER_VALIDATE_IP ) ) {
				return $real_ip;
			}
		}

		return $remote_addr;
	}

	/**
	 * Checks if an IP is configured as a trusted reverse proxy.
	 *
	 * @param string $ip IP to check.
	 * @return bool True if trusted.
	 */
	public function is_trusted_proxy( string $ip ): bool {
		$trusted_raw = (string) $this->get_conf( 'trusted_proxy_ips', "127.0.0.1\n::1" );
		$trusted_ips = array_filter( array_map( 'trim', explode( "\n", $trusted_raw ) ) );

		foreach ( $trusted_ips as $trusted ) {
			if ( empty( $trusted ) ) {
				continue;
			}

			// Subnet check CIDR (e.g. 10.0.0.0/8).
			if ( str_contains( $trusted, '/' ) ) {
				if ( $this->ip_in_range( $ip, $trusted ) ) {
					return true;
				}
			} elseif ( $ip === $trusted ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if an IPv4 IP is within a CIDR subnet range.
	 *
	 * @param string $ip    IP address.
	 * @param string $range CIDR range.
	 * @return bool
	 */
	private function ip_in_range( string $ip, string $range ): bool {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return false;
		}

		$parts = explode( '/', $range );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		list( $subnet, $bits ) = $parts;
		$ip_long     = ip2long( $ip );
		$subnet_long = ip2long( $subnet );
		$mask        = -1 << ( 32 - (int) $bits );
		$subnet_long &= $mask;

		return ( $ip_long & $mask ) === $subnet_long;
	}

	/**
	 * Returns privacy-preserving hash of an IP address.
	 *
	 * @param string $ip IP address.
	 * @return string SHA-256 hash.
	 */
	public function hash_ip( string $ip ): string {
		$secret = (string) $this->get_conf( 'secret_key', 'botfence_salt' );
		return hash_hmac( 'sha256', $ip, $secret );
	}

	/**
	 * Returns masked IP for admin display (GDPR compliant).
	 *
	 * @param string $ip IP address.
	 * @return string Masked IP.
	 */
	public function mask_ip( string $ip ): string {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$parts = explode( '.', $ip );
			if ( count( $parts ) === 4 ) {
				return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.***';
			}
		} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$parts = explode( ':', $ip );
			if ( count( $parts ) >= 3 ) {
				return $parts[0] . ':' . $parts[1] . ':****:****';
			}
		}
		return '***.***.***.***';
	}

	/**
	 * Checks and increments rate limit counters for a submission.
	 *
	 * @param string $form_id Form identifier.
	 * @param string $content Optional message text content for duplicate detection.
	 * @param string|null $client_ip Optional custom IP (for testing).
	 * @return array{
	 *     allowed: bool,
	 *     is_quarantined: bool,
	 *     rate_exceeded: bool,
	 *     repeated_message: bool,
	 *     risk_points: int,
	 *     reason: string
	 * }
	 */
	public function check_submission( string $form_id = '', string $content = '', ?string $client_ip = null ): array {
		global $wpdb;

		$ip          = null !== $client_ip ? $client_ip : $this->get_client_ip();
		$ip_hash     = $this->hash_ip( $ip );
		$rates_table = Database::get_rates_table();
		$now_time    = time();
		$now_dt      = gmdate( 'Y-m-d H:i:s', $now_time );

		$weight_rate    = (int) $this->get_conf( 'weight_rate_limit', 40 );
		$weight_message = (int) $this->get_conf( 'weight_repeated_message', 35 );
		$limit_minute   = (int) $this->get_conf( 'rate_limit_ip_per_minute', self::DEFAULT_LIMIT_PER_MINUTE );
		$limit_hour     = (int) $this->get_conf( 'rate_limit_ip_per_hour', self::DEFAULT_LIMIT_PER_HOUR );
		$quarantine_dur = (int) $this->get_conf( 'quarantine_duration', self::DEFAULT_QUARANTINE_SECONDS );

		// 1. Check if IP is currently quarantined.
		$quarantine_ident = 'quarantine:' . $ip_hash;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$is_quarantined   = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT count FROM {$rates_table} WHERE identifier = %s AND limit_type = 'quarantine' AND expires_at > %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$quarantine_ident,
				$now_dt
			)
		);

		if ( $is_quarantined ) {
			return array(
				'allowed'          => false,
				'is_quarantined'   => true,
				'rate_exceeded'    => true,
				'repeated_message' => false,
				'risk_points'      => 100, // Instant block while in quarantine.
				'reason'           => __( 'IP address is temporarily blocked due to previous excessive submissions.', 'botfence' ),
			);
		}

		$accumulated_risk = 0;
		$reasons          = array();
		$rate_exceeded    = false;

		// 2. Increment & Check Per-Minute IP rate limit.
		$minute_ident   = 'ip_m:' . $ip_hash;
		$minute_expires = gmdate( 'Y-m-d H:i:s', $now_time + 60 );
		$minute_count   = $this->increment_counter( $minute_ident, 'ip_minute', $now_dt, $minute_expires );

		if ( $minute_count > $limit_minute ) {
			$rate_exceeded     = true;
			$accumulated_risk += $weight_rate;
			$reasons[]         = sprintf(
				/* translators: %d: submissions per minute limit */
				__( 'Exceeded rate limit of %d submissions per minute.', 'botfence' ),
				$limit_minute
			);

			// Place in temporary quarantine if excessive (>= 2x minute limit).
			if ( $minute_count >= ( $limit_minute * 2 ) && $quarantine_dur > 0 ) {
				$quarantine_expires = gmdate( 'Y-m-d H:i:s', $now_time + $quarantine_dur );
				$this->increment_counter( $quarantine_ident, 'quarantine', $now_dt, $quarantine_expires );
			}
		}

		// 3. Increment & Check Per-Hour IP rate limit.
		$hour_ident   = 'ip_h:' . $ip_hash;
		$hour_expires = gmdate( 'Y-m-d H:i:s', $now_time + 3600 );
		$hour_count   = $this->increment_counter( $hour_ident, 'ip_hour', $now_dt, $hour_expires );

		if ( $hour_count > $limit_hour ) {
			$rate_exceeded     = true;
			$accumulated_risk += $weight_rate;
			$reasons[]         = sprintf(
				/* translators: %d: submissions per hour limit */
				__( 'Exceeded hourly rate limit of %d submissions.', 'botfence' ),
				$limit_hour
			);
		}

		// 4. Per-Form rate limit.
		if ( ! empty( $form_id ) ) {
			$form_ident   = 'form:' . sanitize_key( $form_id ) . ':' . $ip_hash;
			$form_limit   = (int) $this->get_conf( 'rate_limit_form_per_minute', 10 );
			$form_expires = gmdate( 'Y-m-d H:i:s', $now_time + 60 );
			$form_count   = $this->increment_counter( $form_ident, 'form_minute', $now_dt, $form_expires );

			if ( $form_count > $form_limit ) {
				$rate_exceeded     = true;
				$accumulated_risk += $weight_rate;
				$reasons[]         = sprintf(
					/* translators: 1: form ID, 2: rate limit */
					__( 'Exceeded limit of %2$d submissions per minute for form "%1$s".', 'botfence' ),
					sanitize_key( $form_id ),
					$form_limit
				);
			}
		}

		// 5. Per-Session rate limit (if session identifier present).
		$session_token = '';
		if ( ! empty( $_COOKIE['botfence_session'] ) && is_string( $_COOKIE['botfence_session'] ) ) {
			$session_token = sanitize_key( $_COOKIE['botfence_session'] );
		} elseif ( function_exists( 'wp_get_session_token' ) ) {
			$raw_token = (string) wp_get_session_token();
			if ( ! empty( $raw_token ) ) {
				$session_token = hash( 'sha256', $raw_token );
			}
		}

		if ( ! empty( $session_token ) ) {
			$sess_ident   = 'sess:' . substr( $session_token, 0, 32 );
			$sess_limit   = (int) $this->get_conf( 'rate_limit_session_per_minute', self::DEFAULT_LIMIT_PER_MINUTE );
			$sess_expires = gmdate( 'Y-m-d H:i:s', $now_time + 60 );
			$sess_count   = $this->increment_counter( $sess_ident, 'session_minute', $now_dt, $sess_expires );

			if ( $sess_count > $sess_limit ) {
				$rate_exceeded     = true;
				$accumulated_risk += $weight_rate;
				$reasons[]         = sprintf(
					/* translators: %d: limit */
					__( 'Exceeded session rate limit of %d submissions per minute.', 'botfence' ),
					$sess_limit
				);
			}
		}

		// 6. Repeated identical message detection.
		$repeated_message = false;
		if ( ! empty( $content ) && strlen( trim( $content ) ) > 10 ) {
			$content_clean = preg_replace( '/\s+/', ' ', strtolower( trim( $content ) ) );
			$content_hash  = 'msg:' . hash( 'sha256', (string) $content_clean );
			$msg_expires   = gmdate( 'Y-m-d H:i:s', $now_time + 600 ); // 10 minute window

			$msg_count = $this->increment_counter( $content_hash, 'message_hash', $now_dt, $msg_expires );
			if ( $msg_count > 1 ) {
				$repeated_message  = true;
				$accumulated_risk += $weight_message;
				$reasons[]         = __( 'Repeated identical submission content detected.', 'botfence' );
			}
		}

		return array(
			'allowed'          => ( $accumulated_risk < 60 ),
			'is_quarantined'   => false,
			'rate_exceeded'    => $rate_exceeded,
			'repeated_message' => $repeated_message,
			'risk_points'      => $accumulated_risk,
			'reason'           => implode( ' ', $reasons ),
		);
	}

	/**
	 * Atomically increments a counter record in the database.
	 *
	 * Resets count and expiration window if previous window has expired.
	 *
	 * @param string $identifier Record key.
	 * @param string $limit_type Counter category.
	 * @param string $first_seen Datetime string.
	 * @param string $expires_at Datetime string.
	 * @return int New count.
	 */
	public function increment_counter( string $identifier, string $limit_type, string $first_seen, string $expires_at ): int {
		global $wpdb;

		$table = Database::get_rates_table();

		// Atomic insert or increment with window reset if expired.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (identifier, limit_type, count, first_seen, expires_at)
				VALUES (%s, %s, 1, %s, %s)
				ON DUPLICATE KEY UPDATE
					count = IF(expires_at <= VALUES(first_seen), 1, count + 1),
					first_seen = IF(expires_at <= VALUES(first_seen), VALUES(first_seen), first_seen),
					expires_at = IF(expires_at <= VALUES(first_seen), VALUES(expires_at), expires_at)",
				$identifier,
				$limit_type,
				$first_seen,
				$expires_at
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$current_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT count FROM {$table} WHERE identifier = %s AND limit_type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$identifier,
				$limit_type
			)
		);

		return is_numeric( $current_count ) ? (int) $current_count : 1;
	}
}

// Backwards compatibility alias.
if ( ! class_exists( '\KL_Anti_Spam\Rate_Limiter', false ) ) {
	class_alias( '\BotFence\Rate_Limiter', '\KL_Anti_Spam\Rate_Limiter' );
}
