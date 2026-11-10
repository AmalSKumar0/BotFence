<?php
/**
 * Fired when BotFence is uninstalled.
 *
 * @package BotFence
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Handles cleanup of BotFence options, custom database tables, and scheduled crons.
 */
function botfence_uninstall(): void {
	global $wpdb;

	// Drop custom tables.
	$tables = array(
		$wpdb->prefix . 'botfence_logs',
		$wpdb->prefix . 'botfence_rate_limits',
		$wpdb->prefix . 'kl_antispam_logs',
		$wpdb->prefix . 'kl_antispam_rate_limits',
	);

	foreach ( $tables as $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	// Delete registered options.
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

	foreach ( $base_keys as $key ) {
		delete_option( 'botfence_' . $key );
		delete_option( 'kl_antispam_' . $key );
	}

	// Clear any scheduled cleanup cron.
	foreach ( array( 'botfence_cleanup_cron', 'kl_antispam_cleanup_cron' ) as $cron_hook ) {
		$timestamp = wp_next_scheduled( $cron_hook );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $cron_hook );
		}
	}
}

botfence_uninstall();
