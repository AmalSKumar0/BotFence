<?php
/**
 * Fired when Vighnaghna is uninstalled.
 *
 * @package Vighnaghna
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Handles cleanup of Vighnaghna options, custom database tables, and scheduled crons.
 */
function vighnaghna_uninstall(): void {
	global $wpdb;

	// Drop custom tables belonging to Vighnaghna.
	$tables = array(
		$wpdb->prefix . 'vighnaghna_logs',
		$wpdb->prefix . 'vighnaghna_rate_limits',
		$wpdb->prefix . 'vighnaghna_used_tokens',
	);

	foreach ( $tables as $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	// Delete registered plugin options.
	$base_keys = array(
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
		'migrated_from_legacy',
	);

	foreach ( $base_keys as $key ) {
		delete_option( 'vighnaghna_' . $key );
	}

	// Clear scheduled cleanup cron.
	$timestamp = wp_next_scheduled( 'vighnaghna_cleanup_cron' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'vighnaghna_cleanup_cron' );
	}
}

vighnaghna_uninstall();
