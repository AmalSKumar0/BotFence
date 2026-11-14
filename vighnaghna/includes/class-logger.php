<?php
/**
 * Privacy-Conscious Logging System for Vighnaghna.
 *
 * @package Vighnaghna
 */

namespace Vighnaghna;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Logger
 */
class Logger {

	/**
	 * Records a form submission verification result to the database.
	 *
	 * Does NOT store sensitive personal data or form field contents.
	 *
	 * @param array<string, mixed> $entry Log entry attributes.
	 * @return int Inserted ID or 0 on failure.
	 */
	public function log( array $entry ): int {
		global $wpdb;

		$table = Database::get_logs_table();

		$data = array(
			'created_at'      => current_time( 'mysql', true ),
			'form_type'       => sanitize_text_field( (string) ( $entry['form_type'] ?? 'generic' ) ),
			'form_id'         => sanitize_text_field( (string) ( $entry['form_id'] ?? '' ) ),
			'risk_score'      => max( 0, min( 100, (int) ( $entry['risk_score'] ?? 0 ) ) ),
			'action_taken'    => sanitize_key( (string) ( $entry['action_taken'] ?? 'allow' ) ),
			'reasons'         => wp_json_encode( $entry['reasons'] ?? array() ),
			'ip_hash'         => sanitize_text_field( (string) ( $entry['ip_hash'] ?? '' ) ),
			'masked_ip'       => sanitize_text_field( (string) ( $entry['masked_ip'] ?? '' ) ),
			'user_agent'      => substr( sanitize_text_field( (string) ( $entry['user_agent'] ?? '' ) ), 0, 255 ),
			'submission_time' => (float) ( $entry['submission_time'] ?? 0 ),
			'is_spam'         => ! empty( $entry['is_spam'] ) ? 1 : 0,
		);

		$format = array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%d' );

		$res = $wpdb->insert( $table, $data, $format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $res ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Queries log entries with filtering and pagination.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array{items: array<int, object>, total: int, pages: int}
	 */
	public function get_logs( array $args = array() ): array {
		global $wpdb;

		$table    = Database::get_logs_table();
		$defaults = array(
			'per_page'  => 20,
			'page'      => 1,
			'action'    => '',
			'form_type' => '',
			'is_spam'   => null,
			'search'    => '',
			'orderby'   => 'created_at',
			'order'     => 'DESC',
		);

		$params = wp_parse_args( $args, $defaults );

		$where_clauses = array( '1=1' );
		$where_values  = array();

		if ( ! empty( $params['action'] ) ) {
			$where_clauses[] = 'action_taken = %s';
			$where_values[]  = sanitize_key( $params['action'] );
		}

		if ( ! empty( $params['form_type'] ) ) {
			$where_clauses[] = 'form_type = %s';
			$where_values[]  = sanitize_text_field( $params['form_type'] );
		}

		if ( null !== $params['is_spam'] && '' !== $params['is_spam'] ) {
			$where_clauses[] = 'is_spam = %d';
			$where_values[]  = (int) $params['is_spam'];
		}

		if ( ! empty( $params['search'] ) ) {
			$like            = '%' . $wpdb->esc_like( $params['search'] ) . '%';
			$where_clauses[] = '(form_id LIKE %s OR masked_ip LIKE %s OR reasons LIKE %s)';
			$where_values[]  = $like;
			$where_values[]  = $like;
			$where_values[]  = $like;
		}

		$where_sql = implode( ' AND ', $where_clauses );
		if ( ! empty( $where_values ) ) {
			$where_sql = $wpdb->prepare( $where_sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Count total records.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table} WHERE {$where_sql}" );

		// Pagination & Sorting.
		$per_page = max( 1, (int) $params['per_page'] );
		$page     = max( 1, (int) $params['page'] );
		$offset   = ( $page - 1 ) * $per_page;
		$order    = strtoupper( $params['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$orderby  = in_array( $params['orderby'], array( 'created_at', 'risk_score', 'form_type' ), true ) ? $params['orderby'] : 'created_at';

		$query = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
			$per_page,
			$offset
		);

		$items = $wpdb->get_results( $query );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
			'pages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Calculates aggregated security statistics for the admin dashboard.
	 *
	 * @return array<string, mixed>
	 */
	public function get_stats(): array {
		global $wpdb;

		$table = Database::get_logs_table();

		// Check if table exists to prevent errors during early initialization.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $table_exists ) {
			// Fallback check for legacy table names.
			$bf_table = $wpdb->prefix . 'botfence_logs';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$bf_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $bf_table ) );
			if ( $bf_exists ) {
				$table = $bf_table;
			} else {
				$legacy_table = $wpdb->prefix . 'kl_antispam_logs';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$legacy_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy_table ) );
				if ( $legacy_exists ) {
					$table = $legacy_table;
				} else {
					return array(
						'total_checked'     => 0,
						'total_blocked'     => 0,
						'blocked_today'     => 0,
						'blocked_7_days'    => 0,
						'blocked_30_days'   => 0,
						'total_allowed'     => 0,
						'allowed_today'     => 0,
						'total_challenged'  => 0,
						'block_rate'        => 0.0,
						'threats_mitigated' => 0,
						'top_signals'       => array(),
						'recent_blocked'    => array(),
						'form_breakdown'    => array(),
					);
				}
			}
		}

		$now_utc         = time();
		$start_of_today  = gmdate( 'Y-m-d 00:00:00', $now_utc );
		$seven_days_ago  = gmdate( 'Y-m-d H:i:s', $now_utc - ( 7 * DAY_IN_SECONDS ) );
		$thirty_days_ago = gmdate( 'Y-m-d H:i:s', $now_utc - ( 30 * DAY_IN_SECONDS ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_checked    = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );
		$total_blocked    = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table} WHERE action_taken = 'block'" );
		$total_allowed    = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table} WHERE action_taken = 'allow'" );
		$total_challenged = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table} WHERE action_taken = 'challenge'" );

		// Time-windowed statistics.
		$blocked_today = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE action_taken = 'block' AND created_at >= %s", $start_of_today )
		);
		$blocked_7_days = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE action_taken = 'block' AND created_at >= %s", $seven_days_ago )
		);
		$blocked_30_days = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE action_taken = 'block' AND created_at >= %s", $thirty_days_ago )
		);
		$allowed_today = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE action_taken = 'allow' AND created_at >= %s", $start_of_today )
		);

		$block_rate        = $total_checked > 0 ? round( ( $total_blocked / $total_checked ) * 100, 1 ) : 0.0;
		$threats_mitigated = $total_blocked + $total_challenged;

		// Fetch recent blocked submissions.
		$recent_blocked = $wpdb->get_results(
			"SELECT * FROM {$table} WHERE action_taken = 'block' ORDER BY created_at DESC LIMIT 8"
		);

		// Form builder breakdown of blocked spam.
		$form_breakdown = array();
		$form_rows      = $wpdb->get_results(
			"SELECT form_type, COUNT(id) as total FROM {$table} WHERE action_taken = 'block' GROUP BY form_type ORDER BY total DESC LIMIT 10"
		);
		if ( is_array( $form_rows ) ) {
			foreach ( $form_rows as $frow ) {
				$ft = ! empty( $frow->form_type ) ? (string) $frow->form_type : 'unknown';
				$form_breakdown[ $ft ] = (int) $frow->total;
			}
		}

		// Tally top signals from the last 150 spam events.
		$signal_tallies = array();
		$reasons_rows   = $wpdb->get_col(
			"SELECT reasons FROM {$table} WHERE is_spam = 1 ORDER BY created_at DESC LIMIT 150"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( is_array( $reasons_rows ) ) {
			foreach ( $reasons_rows as $row_json ) {
				$signals = json_decode( (string) $row_json, true );
				if ( is_array( $signals ) ) {
					foreach ( $signals as $sig ) {
						$name = is_array( $sig ) ? ( $sig['name'] ?? $sig['code'] ?? 'Unknown' ) : 'Signal';
						$signal_tallies[ $name ] = ( $signal_tallies[ $name ] ?? 0 ) + 1;
					}
				}
			}
		}

		arsort( $signal_tallies );

		return array(
			'total_checked'     => $total_checked,
			'total_blocked'     => $total_blocked,
			'blocked_today'     => $blocked_today,
			'blocked_7_days'    => $blocked_7_days,
			'blocked_30_days'   => $blocked_30_days,
			'total_allowed'     => $total_allowed,
			'allowed_today'     => $allowed_today,
			'total_challenged'  => $total_challenged,
			'block_rate'        => $block_rate,
			'threats_mitigated' => $threats_mitigated,
			'top_signals'       => array_slice( $signal_tallies, 0, 6, true ),
			'recent_blocked'    => is_array( $recent_blocked ) ? $recent_blocked : array(),
			'form_breakdown'    => $form_breakdown,
		);
	}

	/**
	 * Marks a log entry as a false positive (reviewed legitimate).
	 *
	 * @param int $log_id Log record ID.
	 * @return bool True on success.
	 */
	public function mark_false_positive( int $log_id ): bool {
		global $wpdb;

		if ( $log_id <= 0 ) {
			return false;
		}

		$table   = Database::get_logs_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$table,
			array(
				'is_spam'      => 0,
				'action_taken' => 'allow',
			),
			array( 'id' => $log_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Empties all records from the logs table.
	 *
	 * @return int Number of rows deleted.
	 */
	public function clear_logs(): int {
		global $wpdb;
		$table        = Database::get_logs_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows_deleted = $wpdb->query( "TRUNCATE TABLE {$table}" );
		return is_numeric( $rows_deleted ) ? (int) $rows_deleted : 0;
	}
}

// Backwards compatibility aliases.
if ( ! class_exists( '\BotFence\Logger', false ) ) {
	class_alias( '\Vighnaghna\Logger', '\BotFence\Logger' );
}
if ( ! class_exists( '\KL_Anti_Spam\Logger', false ) ) {
	class_alias( '\Vighnaghna\Logger', '\KL_Anti_Spam\Logger' );
}
