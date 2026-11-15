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
	 *
	 * Verifies table existence after dbDelta execution to guarantee destination
	 * tables are available before migration or runtime operations proceed.
	 *
	 * @return array{
	 *     success: bool,
	 *     created: array<int, string>,
	 *     missing: array<int, string>,
	 *     errors: array<int, string>
	 * }
	 */
	public static function create_tables(): array {
		global $wpdb;

		$result = array(
			'success' => true,
			'created' => array(),
			'missing' => array(),
			'errors'  => array(),
		);

		$logs_table   = self::get_logs_table();
		$rates_table  = self::get_rates_table();
		$tokens_table = self::get_tokens_table();

		$required_tables = array(
			$logs_table,
			$rates_table,
			$tokens_table,
		);

		try {
			$charset_collate = $wpdb->get_charset_collate();

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
				$wpdb->last_error = '';
				dbDelta( $sql_logs );
				dbDelta( $sql_rates );
				dbDelta( $sql_tokens );
			} else {
				$wpdb->last_error = '';
				$r1 = $wpdb->query( $sql_logs );
				$r2 = $wpdb->query( $sql_rates );
				$r3 = $wpdb->query( $sql_tokens );
				if ( false === $r1 || false === $r2 || false === $r3 || ! empty( $wpdb->last_error ) ) {
					$result['success']  = false;
					$result['errors'][] = 'Database query failed during table creation: ' . ( ! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Query returned false' );
				}
			}

			if ( ! empty( $wpdb->last_error ) && ! in_array( 'Database table creation error: ' . $wpdb->last_error, $result['errors'], true ) ) {
				$result['success']  = false;
				$result['errors'][] = 'Database table creation error: ' . $wpdb->last_error;
			}
		} catch ( \Throwable $e ) {
			$result['success']  = false;
			$result['errors'][] = 'Table creation exception: ' . $e->getMessage();
		}

		// Verify table existence after creation.
		// Do not assume that dbDelta() succeeded simply because it did not throw an exception.
		foreach ( $required_tables as $table ) {
			$wpdb->last_error = '';
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
			if ( false === $exists || ! empty( $wpdb->last_error ) ) {
				$result['success']   = false;
				$result['missing'][] = $table;
				$result['errors'][]  = sprintf(
					'Table verification query failed for %s: %s',
					$table,
					! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Query returned false'
				);
			} elseif ( $exists !== $table ) {
				$result['success']   = false;
				$result['missing'][] = $table;
				$result['errors'][]  = sprintf( 'Required destination table %s is missing after table creation.', $table );
			} else {
				$result['created'][] = $table;
			}
		}

		if ( ! empty( $result['errors'] ) || ! empty( $result['missing'] ) ) {
			$result['success'] = false;
		}

		return $result;
	}

	/**
	 * Safely migrates settings from legacy installations (BotFence or KL Anti-Spam).
	 *
	 * Idempotent: checks for prior migration and will never overwrite existing Vighnaghna settings.
	 * Preserves all legacy options without deleting user data.
	 *
	 * @return bool True if migration occurred or was already completed.
	 */
	/**
	 * Migrates settings and database records from legacy plugin versions.
	 *
	 * - Only sets vighnaghna_migrated_from_legacy when all required migrations succeed.
	 * - Does not set the flag when migration fails.
	 * - Returns an explicit migration result with success and error details.
	 * - Idempotent and safe to retry.
	 * - Preserves all legacy options and tables intact.
	 *
	 * @return array{
	 *     success: bool,
	 *     options_migrated: int,
	 *     records: array{logs: int, rate_limits: int},
	 *     errors: array<int, string>,
	 *     already_migrated?: bool
	 * }
	 */
	public static function migrate_from_legacy(): array {
		$migration_flag = get_option( 'vighnaghna_migrated_from_legacy' );
		if ( $migration_flag ) {
			return array(
				'success'          => true,
				'options_migrated' => 0,
				'records'          => array(
					'logs'        => 0,
					'rate_limits' => 0,
				),
				'errors'           => array(),
				'already_migrated' => true,
			);
		}

		$errors           = array();
		$options_migrated = 0;

		try {
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
						$stored_val = get_option( 'vighnaghna_' . $key );
						if ( $stored_val !== $legacy_val ) {
							$errors[] = sprintf(
								'Failed to persist migrated legacy option vighnaghna_%s: expected value did not save.',
								$key
							);
						} else {
							$options_migrated++;
						}
					}
				}
			}
		} catch ( \Throwable $e ) {
			$errors[] = 'Options migration error: ' . $e->getMessage();
		}

		// Migrate legacy database records (botfence_logs, botfence_rate_limits).
		$records_result = self::migrate_legacy_records();
		if ( ! empty( $records_result['errors'] ) ) {
			$errors = array_merge( $errors, $records_result['errors'] );
		}

		$success = empty( $errors ) && ! empty( $records_result['success'] );

		// Set the migration flag only after all required migrations succeed.
		// Do not set vighnaghna_migrated_from_legacy when migration fails.
		if ( $success ) {
			$saved     = update_option( 'vighnaghna_migrated_from_legacy', 1 );
			$persisted = ( 1 === (int) get_option( 'vighnaghna_migrated_from_legacy' ) );
			if ( ! $saved || ! $persisted ) {
				$success  = false;
				$errors[] = 'Failed to persist legacy migration completion flag (vighnaghna_migrated_from_legacy) in WordPress options.';
			}
		}

		return array(
			'success'          => $success,
			'options_migrated' => $options_migrated,
			'records'          => array(
				'logs'        => $records_result['logs'] ?? 0,
				'rate_limits' => $records_result['rate_limits'] ?? 0,
			),
			'errors'           => $errors,
		);
	}

	/**
	 * Migrates records from legacy tables (botfence_logs, botfence_rate_limits)
	 * into Vighnaghna tables.
	 *
	 * - Preserves record IDs where possible.
	 * - Prevents duplicate records.
	 * - Uses safe database queries using $wpdb.
	 * - Does not delete legacy tables during migration.
	 * - Idempotent and safe to retry.
	 * - Captures detailed error information if any record insertion fails.
	 *
	 * @return array{
	 *     success: bool,
	 *     logs: int,
	 *     rate_limits: int,
	 *     errors: array<int, string>
	 * } Counts of migrated rows and explicit error list.
	 */
	public static function migrate_legacy_records(): array {
		global $wpdb;

		$results = array(
			'success'     => true,
			'logs'        => 0,
			'rate_limits' => 0,
			'errors'      => array(),
		);

		try {
			$tables_result = self::create_tables();
			if ( empty( $tables_result['success'] ) ) {
				$results['success'] = false;
				$results['errors']  = array_merge( $results['errors'], $tables_result['errors'] );
				return $results;
			}

			$dest_logs  = self::get_logs_table();
			$dest_rates = self::get_rates_table();

			// 1. Migrate legacy logs.
			$legacy_logs_tables = array(
				$wpdb->prefix . 'botfence_logs',
				$wpdb->prefix . 'kl_antispam_logs',
			);

			foreach ( $legacy_logs_tables as $legacy_table ) {
				$wpdb->last_error = '';
				$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $legacy_table ) ) );
				if ( false === $exists || ! empty( $wpdb->last_error ) ) {
					$results['success']  = false;
					$results['errors'][] = sprintf(
						'Table existence query failed for %s: %s',
						$legacy_table,
						! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Query returned false'
					);
					continue;
				}

				if ( $exists === $legacy_table ) {
					$wpdb->last_error = '';
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$legacy_rows = $wpdb->get_results( "SELECT * FROM {$legacy_table}" );
					if ( false === $legacy_rows || null === $legacy_rows || ! empty( $wpdb->last_error ) ) {
						$results['success']  = false;
						$results['errors'][] = sprintf(
							'SELECT query failed for legacy table %s: %s',
							$legacy_table,
							! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Query returned false or null'
						);
						continue;
					}

					if ( is_array( $legacy_rows ) && ! empty( $legacy_rows ) ) {
						foreach ( $legacy_rows as $raw_row ) {
							$row    = (array) $raw_row;
							$row_id = isset( $row['id'] ) ? (int) $row['id'] : 0;
							if ( $row_id > 0 ) {
								$wpdb->last_error = '';
								// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								$id_exists = $wpdb->get_var(
									$wpdb->prepare( "SELECT id FROM {$dest_logs} WHERE id = %d", $row_id )
								);
								if ( false === $id_exists || ! empty( $wpdb->last_error ) ) {
									$results['success']  = false;
									$results['errors'][] = sprintf(
										'Destination duplicate-check query failed for log ID %d in %s: %s',
										$row_id,
										$dest_logs,
										! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Query returned false'
									);
									continue;
								}
								if ( null !== $id_exists && '' !== $id_exists ) {
									continue; // Prevent duplicates.
								}

								$wpdb->last_error = '';
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
								if ( false === $inserted || ! empty( $wpdb->last_error ) ) {
									$results['success']  = false;
									$results['errors'][] = sprintf(
										'Failed to insert migrated legacy log record ID %d: %s',
										$row_id,
										! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Database query returned false'
									);
								} elseif ( $inserted ) {
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
				$wpdb->last_error = '';
				$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $legacy_table ) ) );
				if ( false === $exists || ! empty( $wpdb->last_error ) ) {
					$results['success']  = false;
					$results['errors'][] = sprintf(
						'Table existence query failed for %s: %s',
						$legacy_table,
						! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Query returned false'
					);
					continue;
				}

				if ( $exists === $legacy_table ) {
					$wpdb->last_error = '';
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$legacy_rows = $wpdb->get_results( "SELECT * FROM {$legacy_table}" );
					if ( false === $legacy_rows || null === $legacy_rows || ! empty( $wpdb->last_error ) ) {
						$results['success']  = false;
						$results['errors'][] = sprintf(
							'SELECT query failed for legacy table %s: %s',
							$legacy_table,
							! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Query returned false or null'
						);
						continue;
					}

					if ( is_array( $legacy_rows ) && ! empty( $legacy_rows ) ) {
						foreach ( $legacy_rows as $raw_row ) {
							$row        = (array) $raw_row;
							$identifier = $row['identifier'] ?? '';
							$limit_type = $row['limit_type'] ?? 'ip';

							if ( empty( $identifier ) ) {
								continue;
							}

							$wpdb->last_error = '';
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$exists_in_dest = $wpdb->get_var(
								$wpdb->prepare(
									"SELECT id FROM {$dest_rates} WHERE identifier = %s AND limit_type = %s",
									$identifier,
									$limit_type
								)
							);
							if ( false === $exists_in_dest || ! empty( $wpdb->last_error ) ) {
								$results['success']  = false;
								$results['errors'][] = sprintf(
									'Destination duplicate-check query failed for rate limit (%s:%s) in %s: %s',
									$identifier,
									$limit_type,
									$dest_rates,
									! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Query returned false'
								);
								continue;
							}
							if ( null !== $exists_in_dest && '' !== $exists_in_dest ) {
								continue; // Prevent duplicates.
							}

							$row_id = isset( $row['id'] ) ? (int) $row['id'] : 0;
							if ( $row_id > 0 ) {
								$wpdb->last_error = '';
								// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								$id_collision = $wpdb->get_var(
									$wpdb->prepare( "SELECT id FROM {$dest_rates} WHERE id = %d", $row_id )
								);
								if ( false === $id_collision || ! empty( $wpdb->last_error ) ) {
									$results['success']  = false;
									$results['errors'][] = sprintf(
										'Destination ID collision check query failed for rate limit ID %d: %s',
										$row_id,
										! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Query returned false'
									);
									continue;
								}
								if ( null === $id_collision || '' === $id_collision ) {
									$wpdb->last_error = '';
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
									if ( false === $inserted || ! empty( $wpdb->last_error ) ) {
										$results['success']  = false;
										$results['errors'][] = sprintf(
											'Failed to insert migrated rate limit record ID %d (%s:%s): %s',
											$row_id,
											$identifier,
											$limit_type,
											! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Database query returned false'
										);
									} elseif ( $inserted ) {
										$results['rate_limits']++;
									}
									continue;
								}
							}

							// If ID collided or was 0, insert without specifying ID to auto-assign ID while preserving unique record data.
							$wpdb->last_error = '';
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
							if ( false === $inserted || ! empty( $wpdb->last_error ) ) {
								$results['success']  = false;
								$results['errors'][] = sprintf(
									'Failed to insert migrated rate limit record (%s:%s): %s',
									$identifier,
									$limit_type,
									! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Database query returned false'
								);
							} elseif ( $inserted ) {
								$results['rate_limits']++;
							}
						}
					}
				}
			}
		} catch ( \Throwable $e ) {
			$results['success']  = false;
			$results['errors'][] = 'Database exception during record migration: ' . $e->getMessage();
		}

		if ( ! empty( $results['errors'] ) ) {
			$results['success'] = false;
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
