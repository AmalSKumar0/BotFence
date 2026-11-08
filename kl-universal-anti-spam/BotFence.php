<?php
/**
 * Plugin Name:       BotFence
 * Plugin URI:        https://karlomosoft.com/plugins/botfence
 * Description:       Universal invisible anti-spam and automated CAPTCHA engine for WordPress. Protects all forms with behaviour analysis, cryptographic tokens, dynamic honeypots, rate limiting, and risk scoring.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      8.1
 * Author:            KL Security Team
 * Author URI:        https://karlomosoft.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kl-universal-anti-spam
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
if ( ! defined( 'KL_ANTISPAM_VERSION' ) ) {
	define( 'KL_ANTISPAM_VERSION', '1.0.0' );
}

if ( ! defined( 'KL_ANTISPAM_FILE' ) ) {
	define( 'KL_ANTISPAM_FILE', __FILE__ );
}

if ( ! defined( 'KL_ANTISPAM_PATH' ) ) {
	define( 'KL_ANTISPAM_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'KL_ANTISPAM_URL' ) ) {
	define( 'KL_ANTISPAM_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'KL_ANTISPAM_BASENAME' ) ) {
	define( 'KL_ANTISPAM_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Autoloader for KL Universal Anti-Spam classes.
 *
 * @param string $class_name Class to load.
 */
spl_autoload_register( function ( $class_name ) {
	// Only autoload classes in our namespace.
	$prefix = 'KL_Anti_Spam\\';
	if ( str_starts_with( $class_name, $prefix ) ) {
		$relative = substr( $class_name, strlen( $prefix ) );

		if ( str_starts_with( $relative, 'Integrations\\' ) ) {
			$sub = substr( $relative, strlen( 'Integrations\\' ) );
			$sub_lower = strtolower( str_replace( '_', '-', $sub ) );

			// Check interface vs class file name convention.
			if ( $sub === 'Form_Adapter_Interface' ) {
				$file = KL_ANTISPAM_PATH . 'integrations/interface-form-adapter.php';
			} elseif ( $sub === 'Abstract_Form_Adapter' ) {
				$file = KL_ANTISPAM_PATH . 'integrations/class-abstract-form-adapter.php';
			} else {
				$file = KL_ANTISPAM_PATH . 'integrations/class-' . $sub_lower . '.php';
			}

			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Handle includes classes.
		$class_file = strtolower( str_replace( '_', '-', $relative ) );
		$file = KL_ANTISPAM_PATH . 'includes/class-' . $class_file . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
			return;
		}
	}
} );

/**
 * Plugin activation hook callback.
 */
function kl_anti_spam_activate() {
	require_once KL_ANTISPAM_PATH . 'includes/class-database.php';
	\KL_Anti_Spam\Database::create_tables();

	// Ensure unique secret key for HMAC token signing.
	if ( ! get_option( 'kl_antispam_secret_key' ) ) {
		$secret = bin2hex( random_bytes( 32 ) );
		update_option( 'kl_antispam_secret_key', $secret, true );
	}

	// Set default options if not already present.
	$defaults = array(
		'enabled'                  => 1,
		'min_submission_time'      => 2.5,
		'token_lifetime'           => 600, // 10 minutes
		'honeypot_enabled'         => 1,
		'adaptive_challenge'       => 1,
		'risk_threshold_challenge' => 30,
		'risk_threshold_block'     => 60,
		// Risk weights
		'weight_honeypot'          => 100,
		'weight_invalid_token'     => 100,
		'weight_expired_token'     => 80,
		'weight_fast_submit'       => 30,
		'weight_rate_limit'        => 40,
		'weight_excessive_urls'    => 25,
		'weight_suspicious_telemetry' => 20,
		'weight_repeated_message'  => 35,
		// Rate limits
		'rate_limit_ip_per_minute' => 5,
		'rate_limit_ip_per_hour'   => 30,
		'quarantine_duration'      => 900, // 15 minutes
		'log_retention_days'       => 30,
		// Integrations enabled flags
		'enable_cf7'               => 1,
		'enable_wpforms'           => 1,
		'enable_fluent_forms'      => 1,
		'enable_gravity_forms'     => 1,
		'enable_formidable'        => 1,
		'enable_ninja_forms'       => 1,
		'enable_elementor'         => 1,
		'enable_woocommerce'       => 1,
		'enable_comments'          => 1,
		'enable_custom_forms'      => 1,
		// Reverse proxy settings
		'trust_reverse_proxies'    => 0,
		'trusted_proxy_ips'        => "127.0.0.1\n::1",
		'excluded_forms'           => '',
	);

	foreach ( $defaults as $key => $default_val ) {
		if ( false === get_option( 'kl_antispam_' . $key ) ) {
			update_option( 'kl_antispam_' . $key, $default_val );
		}
	}

	// Schedule cleanup cron if not scheduled.
	if ( ! wp_next_scheduled( 'kl_antispam_cleanup_cron' ) ) {
		wp_schedule_event( time() + 3600, 'hourly', 'kl_antispam_cleanup_cron' );
	}
}
register_activation_hook( __FILE__, 'kl_anti_spam_activate' );

/**
 * Plugin deactivation hook callback.
 */
function kl_anti_spam_deactivate() {
	$timestamp = wp_next_scheduled( 'kl_antispam_cleanup_cron' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'kl_antispam_cleanup_cron' );
	}
}
register_deactivation_hook( __FILE__, 'kl_anti_spam_deactivate' );

/**
 * Returns the main plugin instance.
 *
 * @return \KL_Anti_Spam\Plugin
 */
function kl_anti_spam() {
	return \KL_Anti_Spam\Plugin::get_instance();
}

// Bootstrap plugin when all plugins are loaded.
add_action( 'plugins_loaded', 'kl_anti_spam', 0 );
