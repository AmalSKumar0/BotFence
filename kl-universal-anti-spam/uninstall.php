<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package KL_Anti_Spam
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables.
$table_logs = $wpdb->prefix . 'kl_antispam_logs';
$table_rates = $wpdb->prefix . 'kl_antispam_rate_limits';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$table_logs}" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$table_rates}" );

// Delete registered options.
$options = array(
	'kl_antispam_secret_key',
	'kl_antispam_enabled',
	'kl_antispam_min_submission_time',
	'kl_antispam_token_lifetime',
	'kl_antispam_honeypot_enabled',
	'kl_antispam_adaptive_challenge',
	'kl_antispam_risk_threshold_challenge',
	'kl_antispam_risk_threshold_block',
	'kl_antispam_weight_honeypot',
	'kl_antispam_weight_invalid_token',
	'kl_antispam_weight_expired_token',
	'kl_antispam_weight_fast_submit',
	'kl_antispam_weight_rate_limit',
	'kl_antispam_weight_excessive_urls',
	'kl_antispam_weight_suspicious_telemetry',
	'kl_antispam_weight_repeated_message',
	'kl_antispam_rate_limit_ip_per_minute',
	'kl_antispam_rate_limit_ip_per_hour',
	'kl_antispam_quarantine_duration',
	'kl_antispam_log_retention_days',
	'kl_antispam_enable_cf7',
	'kl_antispam_enable_wpforms',
	'kl_antispam_enable_fluent_forms',
	'kl_antispam_enable_gravity_forms',
	'kl_antispam_enable_formidable',
	'kl_antispam_enable_ninja_forms',
	'kl_antispam_enable_elementor',
	'kl_antispam_enable_woocommerce',
	'kl_antispam_enable_comments',
	'kl_antispam_enable_custom_forms',
	'kl_antispam_trust_reverse_proxies',
	'kl_antispam_trusted_proxy_ips',
	'kl_antispam_excluded_forms',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Clear any scheduled cleanup cron.
$timestamp = wp_next_scheduled( 'kl_antispam_cleanup_cron' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'kl_antispam_cleanup_cron' );
}
