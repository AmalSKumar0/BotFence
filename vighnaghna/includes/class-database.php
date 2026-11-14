<?php
/**
 * Database management and migration for Vighnaghna.
 *
 * @package Vighnaghna
 */

namespace Vighnaghna;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Database
 */
class Database {

	/**
	 * Returns the logs table name with WordPress prefix.
	 *
	 * @return string
	 */
	public static function get_logs_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'vighnaghna_logs';
	}

	/**
	 * Returns the rate limits table name with WordPress prefix.
	 *
	 * @return string
	 */
	public static function get_rates_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'vighnaghna_rate_limits';
	}

	/**
	 * Returns the consumed single-use tokens table name with WordPress prefix.
	 *
	 * @return string
	 */
	public static function get_tokens_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'vighnaghna_used_tokens';
	}

	/**
	 * Creates or updates the plugin database tables.
	 */
	public static function create_tables(): void {
		global $wpdb;

		try {
			$charset_collate = $wpdb->get_charset_collate();
			$logs_table      = self::get_logs_table();
			$rates_table     = self::get_rates_table();
			$tokens_table    = self::get_tokens_table();

			// Two spaces between PRIMARY KEY and definition required by dbDelta.
			$sql_logs = "CREATE TABLE {$logs_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				created_at datetime NOT NULL,
				form_type varchar(64) NOT NULL,
				form_id varchar(128) NOT NULL DEFAULT '',
				risk_score int(11) NOT NULL DEFAULT 0,
				action_taken varchar(32) NOT NULL DEFAULT 'allow',
				reasons longtext NOT NULL,
				ip_hash varchar(64) NOT NULL DEFAULT '',
				masked_ip varchar(64) NOT NULL DEFAULT '',
				user_agent varchar(255) NOT NULL DEFAULT '',
				submission_time float NOT NULL DEFAULT 0,
				is_spam tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY created_at (created_at),
				KEY form_type (form_type),
				KEY action_taken (action_taken),
				KEY is_spam (is_spam)
			) {$charset_collate};";

			$sql_rates = "CREATE TABLE {$rates_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				identifier varchar(128) NOT NULL,
				limit_type varchar(32) NOT NULL DEFAULT 'ip',
				count int(11) unsigned NOT NULL DEFAULT 1,
				first_seen datetime NOT NULL,
				expires_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY identifier_type (identifier, limit_type),
				KEY expires_at (expires_at)
			) {$charset_collate};";

			$sql_tokens = "CREATE TABLE {$tokens_table} (
				token_hash varchar(64) NOT NULL,
				form_id varchar(128) NOT NULL DEFAULT '',
				consumed_at datetime NOT NULL,
				expires_at datetime NOT NULL,
				PRIMARY KEY  (token_hash),
				KEY expires_at (expires_at)
			) {$charset_collate};";

			if ( ! function_exists( 'dbDelta' ) && defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			if ( function_exists( 'dbDelta' ) ) {
				dbDelta( $sql_logs );
				dbDelta( $sql_rates );
				dbDelta( $sql_tokens );
			}
		} catch ( \Throwable $e ) {
			// Fail gracefully if database initialization encounters permission constraints.
		}
	}

	/**
	 * Safely migrates settings from legacy installations (BotFence or KL Anti-Spam).
	 *
	 * Idempotent: checks for prior migration and will never overwrite existing Vighnaghna settings.
	 * Preserves all legacy options without deleting user data.
	 *
	 * @return bool True if migration occurred or was already completed.
	 */
	public static function migrate_from_legacy(): bool {
		$migration_flag = get_option( 'vighnaghna_migrated_from_legacy' );
		if ( $migration_flag ) {
			return true;
		}

		$keys = array(
			'secret_key',
			'enabled',
			'min_submission_time',
			'token_lifetime',
			'honeypot_enabled',
			'adaptive_challenge',
			'risk_threshold_challenge',
			'risk_threshold_block',
			'weight_honeypot',
			'weight_invalid_token',
			'weight_expired_token',
			'weight_fast_submit',
			'weight_rate_limit',
			'weight_excessive_urls',
			'weight_suspicious_telemetry',
			'weight_repeated_message',
			'weight_headless_browser',
			'weight_untrusted_event',
			'rate_limit_ip_per_minute',
			'rate_limit_ip_per_hour',
			'quarantine_duration',
			'log_retention_days',
			'enable_cf7',
			'enable_wpforms',
			'enable_fluent_forms',
			'enable_gravity_forms',
			'enable_formidable',
			'enable_ninja_forms',
			'enable_elementor',
			'enable_woocommerce',
			'enable_comments',
			'enable_custom_forms',
			'trust_reverse_proxies',
			'trusted_proxy_ips',
			'excluded_forms',
		);

		foreach ( $keys as $key ) {
			$vighnaghna_val = get_option( 'vighnaghna_' . $key );
			if ( false === $vighnaghna_val || null === $vighnaghna_val ) {
				$legacy_val = get_option( 'botfence_' . $key );
				if ( false === $legacy_val || null === $legacy_val ) {
					$legacy_val = get_option( 'kl_antispam_' . $key );
				}
				if ( false !== $legacy_val && null !== $legacy_val ) {
					update_option( 'vighnaghna_' . $key, $legacy_val );
				}
			}
		}

		// Migrate legacy database records (botfence_logs, botfence_rate_limits).
		self::migrate_legacy_records();

		update_option( 'vighnaghna_migrated_from_legacy', 1 );
		return true;
	}

	/**
	 * Migrates records from legacy tables (botfence_logs, botfence_rate_limits)
	 * into Vighnaghna tables.
	 *
	 * - Preserves record IDs where possible.
	 * - Prevents duplicate records.
	 * - Uses safe database queries using $wpdb.
	 * - Does not delete legacy tables during migration.
	 * - Idempotent.
	 *
	 * @return array Counts of migrated rows ['logs' => int, 'rate_limits' => int].
	 */
	public static function migrate_legacy_records(): array {
		global $wpdb;

		$results = array(
			'logs'        => 0,
			'rate_limits' => 0,
		);

		try {
			self::create_tables();

			$dest_logs  = self::get_logs_table();
			$dest_rates = self::get_rates_table();

			// 1. Migrate legacy logs.
			$legacy_logs_tables = array(
				$wpdb->prefix . 'botfence_logs',
				$wpdb->prefix . 'kl_antispam_logs',
			);

			foreach ( $legacy_logs_tables as $legacy_table ) {
				$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy_table ) );
				if ( $exists === $legacy_table ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$legacy_rows = $wpdb->get_results( "SELECT * FROM {$legacy_table}" );
					if ( ! empty( $legacy_rows ) && is_iterable( $legacy_rows ) ) {
						foreach ( $legacy_rows as $raw_row ) {
							$row    = (array) $raw_row;
							$row_id = isset( $row['id'] ) ? (int) $row['id'] : 0;
							if ( $row_id > 0 ) {
								// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								$id_exists = $wpdb->get_var(
									$wpdb->prepare( "SELECT id FROM {$dest_logs} WHERE id = %d", $row_id )
								);
								if ( $id_exists ) {
									continue; // Prevent duplicates.
								}

								// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								$inserted = $wpdb->query(
									$wpdb->prepare(
										"INSERT INTO {$dest_logs} (id, created_at, form_type, form_id, risk_score, action_taken, reasons, ip_hash, masked_ip, user_agent, submission_time, is_spam)
										VALUES (%d, %s, %s, %s, %d, %s, %s, %s, %s, %s, %f, %d)",
										$row_id,
										$row['created_at'] ?? gmdate( 'Y-m-d H:i:s' ),
										$row['form_type'] ?? 'unknown',
										$row['form_id'] ?? '',
										isset( $row['risk_score'] ) ? (int) $row['risk_score'] : 0,
										$row['action_taken'] ?? 'allow',
										$row['reasons'] ?? '',
										$row['ip_hash'] ?? '',
										$row['masked_ip'] ?? '',
										$row['user_agent'] ?? '',
										isset( $row['submission_time'] ) ? (float) $row['submission_time'] : 0.0,
										isset( $row['is_spam'] ) ? (int) $row['is_spam'] : 0
									)
								);
								if ( $inserted ) {
									$results['logs']++;
								}
							}
						}
					}
				}
			}

			// 2. Migrate legacy rate limits.
			$legacy_rate_tables = array(
				$wpdb->prefix . 'botfence_rate_limits',
				$wpdb->prefix . 'kl_antispam_rate_limits',
			);

			foreach ( $legacy_rate_tables as $legacy_table ) {
				$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy_table ) );
				if ( $exists === $legacy_table ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$legacy_rows = $wpdb->get_results( "SELECT * FROM {$legacy_table}" );
					if ( ! empty( $legacy_rows ) && is_iterable( $legacy_rows ) ) {
						foreach ( $legacy_rows as $raw_row ) {
							$row        = (array) $raw_row;
							$identifier = $row['identifier'] ?? '';
							$limit_type = $row['limit_type'] ?? 'ip';

							if ( empty( $identifier ) ) {
								continue;
							}

							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$exists_in_dest = $wpdb->get_var(
								$wpdb->prepare(
									"SELECT id FROM {$dest_rates} WHERE identifier = %s AND limit_type = %s",
									$identifier,
									$limit_type
								)
							);
							if ( $exists_in_dest ) {
								continue; // Prevent duplicates.
							}

							$row_id = isset( $row['id'] ) ? (int) $row['id'] : 0;
							if ( $row_id > 0 ) {
								// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								$id_collision = $wpdb->get_var(
									$wpdb->prepare( "SELECT id FROM {$dest_rates} WHERE id = %d", $row_id )
								);
								if ( ! $id_collision ) {
									// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
									$inserted = $wpdb->query(
										$wpdb->prepare(
											"INSERT INTO {$dest_rates} (id, identifier, limit_type, count, first_seen, expires_at)
											VALUES (%d, %s, %s, %d, %s, %s)",
											$row_id,
											$identifier,
											$limit_type,
											isset( $row['count'] ) ? (int) $row['count'] : 1,
											$row['first_seen'] ?? gmdate( 'Y-m-d H:i:s' ),
											$row['expires_at'] ?? gmdate( 'Y-m-d H:i:s', time() + 3600 )
										)
									);
									if ( $inserted ) {
										$results['rate_limits']++;
									}
									continue;
								}
							}

							// If ID collided or was 0, insert without specifying ID to auto-assign ID while preserving unique record data.
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$inserted = $wpdb->query(
								$wpdb->prepare(
									"INSERT INTO {$dest_rates} (identifier, limit_type, count, first_seen, expires_at)
									VALUES (%s, %s, %d, %s, %s)",
									$identifier,
									$limit_type,
									isset( $row['count'] ) ? (int) $row['count'] : 1,
									$row['first_seen'] ?? gmdate( 'Y-m-d H:i:s' ),
									$row['expires_at'] ?? gmdate( 'Y-m-d H:i:s', time() + 3600 )
								)
							);
							if ( $inserted ) {
								$results['rate_limits']++;
							}
						}
					}
				}
			}
		} catch ( \Throwable $e ) {
			// Fail gracefully if database constraints occur.
		}

		return $results;
	}

	/**
	 * Purges expired single-use tokens.
	 *
	 * @return int Number of rows deleted.
	 */
	public static function purge_expired_tokens(): int {
		global $wpdb;

		$table        = self::get_tokens_table();
		$now          = gmdate( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows_deleted = $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE expires_at < %s", $now ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return is_numeric( $rows_deleted ) ? (int) $rows_deleted : 0;
	}

	/**
	 * Purges log entries older than the retention threshold.
	 *
	 * @param int $retention_days Days to retain logs.
	 * @return int Number of rows deleted.
	 */
	public static function purge_old_logs( int $retention_days = 30 ): int {
		global $wpdb;

		if ( $retention_days <= 0 ) {
			$retention_days = 30;
		}

		$table        = self::get_logs_table();
		$cutoff       = gmdate( 'Y-m-d H:i:s', time() - ( $retention_days * DAY_IN_SECONDS ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows_deleted = $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return is_numeric( $rows_deleted ) ? (int) $rows_deleted : 0;
	}

	/**
	 * Purges expired rate limit records.
	 *
	 * @return int Number of rows deleted.
	 */
	public static function purge_expired_rate_limits(): int {
		global $wpdb;

		$table        = self::get_rates_table();
		$now          = gmdate( 'Y-m-d H:i:s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows_deleted = $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE expires_at < %s", $now ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return is_numeric( $rows_deleted ) ? (int) $rows_deleted : 0;
	}
}

// Backwards compatibility aliases.
if ( ! class_exists( '\BotFence\Database', false ) ) {
	class_alias( '\Vighnaghna\Database', '\BotFence\Database' );
}
if ( ! class_exists( '\KL_Anti_Spam\Database', false ) ) {
	class_alias( '\Vighnaghna\Database', '\KL_Anti_Spam\Database' );
}
