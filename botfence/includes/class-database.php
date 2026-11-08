<?php
/**
 * Database management for BotFence.
 *
 * @package BotFence
 */

namespace BotFence;

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
		return $wpdb->prefix . 'botfence_logs';
	}

	/**
	 * Returns the rate limits table name with WordPress prefix.
	 *
	 * @return string
	 */
	public static function get_rates_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'botfence_rate_limits';
	}

	/**
	 * Returns the consumed single-use tokens table name with WordPress prefix.
	 *
	 * @return string
	 */
	public static function get_tokens_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'botfence_used_tokens';
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
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'BotFence Database initialization note: ' . $e->getMessage() );
			}
		}
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
		$rows_deleted = $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE expires_at < %s", $now ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return is_numeric( $rows_deleted ) ? (int) $rows_deleted : 0;
	}
}

// Backwards compatibility alias.
if ( ! class_exists( '\KL_Anti_Spam\Database', false ) ) {
	class_alias( '\BotFence\Database', '\KL_Anti_Spam\Database' );
}
