<?php
/**
 * Plugin Name:       BotFence
 * Plugin URI:        https://amalskumar.dev
 * Description:       Universal invisible anti-spam and automated CAPTCHA engine for WordPress. Built by Amal S Kumar. Protects all forms with behaviour analysis, cryptographic tokens, dynamic honeypots, rate limiting, and risk scoring.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      8.1
 * Author:            Amal S Kumar
 * Author URI:        https://amalskumar.dev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       botfence
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// BotFence Plugin constants.
if ( ! defined( 'BOTFENCE_VERSION' ) ) {
	define( 'BOTFENCE_VERSION', '1.0.0' );
}

if ( ! defined( 'BOTFENCE_FILE' ) ) {
	define( 'BOTFENCE_FILE', __FILE__ );
}

if ( ! defined( 'BOTFENCE_PATH' ) ) {
	define( 'BOTFENCE_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'BOTFENCE_URL' ) ) {
	define( 'BOTFENCE_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'BOTFENCE_BASENAME' ) ) {
	define( 'BOTFENCE_BASENAME', plugin_basename( __FILE__ ) );
}

// Backwards compatibility constants.
if ( ! defined( 'KL_ANTISPAM_VERSION' ) ) {
	define( 'KL_ANTISPAM_VERSION', BOTFENCE_VERSION );
}
if ( ! defined( 'KL_ANTISPAM_FILE' ) ) {
	define( 'KL_ANTISPAM_FILE', BOTFENCE_FILE );
}
if ( ! defined( 'KL_ANTISPAM_PATH' ) ) {
	define( 'KL_ANTISPAM_PATH', BOTFENCE_PATH );
}
if ( ! defined( 'KL_ANTISPAM_URL' ) ) {
	define( 'KL_ANTISPAM_URL', BOTFENCE_URL );
}
if ( ! defined( 'KL_ANTISPAM_BASENAME' ) ) {
	define( 'KL_ANTISPAM_BASENAME', BOTFENCE_BASENAME );
}

/**
 * Autoloader for BotFence classes.
 *
 * @param string $class_name Class to load.
 */
spl_autoload_register( function ( $class_name ) {
	$prefixes = array(
		'BotFence\\'    => BOTFENCE_PATH,
		'KL_Anti_Spam\\' => BOTFENCE_PATH,
	);

	foreach ( $prefixes as $prefix => $base_path ) {
		if ( str_starts_with( $class_name, $prefix ) ) {
			$relative = substr( $class_name, strlen( $prefix ) );

			if ( str_starts_with( $relative, 'Integrations\\' ) ) {
				$sub       = substr( $relative, strlen( 'Integrations\\' ) );
				$sub_lower = strtolower( str_replace( '_', '-', $sub ) );

				if ( $sub === 'Form_Adapter_Interface' ) {
					$file = $base_path . 'integrations/interface-form-adapter.php';
				} elseif ( $sub === 'Abstract_Form_Adapter' ) {
					$file = $base_path . 'integrations/class-abstract-form-adapter.php';
				} else {
					$file = $base_path . 'integrations/class-' . $sub_lower . '.php';
				}

				if ( file_exists( $file ) ) {
					require_once $file;
					return;
				}
			}

			// Handle includes classes.
			$class_file = strtolower( str_replace( '_', '-', $relative ) );
			$file       = $base_path . 'includes/class-' . $class_file . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}
	}
} );

/**
 * Plugin activation hook callback.
 */
function botfence_activate() {
	require_once BOTFENCE_PATH . 'includes/class-database.php';
	\BotFence\Database::create_tables();

	// Ensure unique secret key for HMAC token signing.
	if ( ! get_option( 'botfence_secret_key' ) && ! get_option( 'kl_antispam_secret_key' ) ) {
		$secret = bin2hex( random_bytes( 32 ) );
		update_option( 'botfence_secret_key', $secret, true );
		update_option( 'kl_antispam_secret_key', $secret, true );
	}

	// Set default options if not already present.
	$defaults = array(
		'enabled'                     => 1,
		'min_submission_time'         => 2.5,
		'token_lifetime'              => 600, // 10 minutes
		'honeypot_enabled'            => 1,
		'adaptive_challenge'          => 1,
		'risk_threshold_challenge'    => 30,
		'risk_threshold_block'        => 60,
		// Risk weights
		'weight_honeypot'             => 100,
		'weight_invalid_token'        => 100,
		'weight_expired_token'        => 80,
		'weight_fast_submit'          => 30,
		'weight_rate_limit'           => 40,
		'weight_excessive_urls'       => 25,
		'weight_suspicious_telemetry' => 20,
		'weight_repeated_message'     => 35,
		// Rate limits
		'rate_limit_ip_per_minute'    => 5,
		'rate_limit_ip_per_hour'      => 30,
		'quarantine_duration'         => 900, // 15 minutes
		'log_retention_days'          => 30,
		// Integrations enabled flags
		'enable_cf7'                  => 1,
		'enable_wpforms'              => 1,
		'enable_fluent_forms'         => 1,
		'enable_gravity_forms'        => 1,
		'enable_formidable'           => 1,
		'enable_ninja_forms'          => 1,
		'enable_elementor'            => 1,
		'enable_woocommerce'          => 1,
		'enable_comments'             => 1,
		'enable_custom_forms'         => 1,
		// Reverse proxy settings
		'trust_reverse_proxies'       => 0,
		'trusted_proxy_ips'           => "127.0.0.1\n::1",
		'excluded_forms'              => '',
	);

	foreach ( $defaults as $key => $default_val ) {
		if ( false === get_option( 'botfence_' . $key ) ) {
			update_option( 'botfence_' . $key, $default_val );
		}
		if ( false === get_option( 'kl_antispam_' . $key ) ) {
			update_option( 'kl_antispam_' . $key, $default_val );
		}
	}

	// Schedule cleanup cron if not scheduled.
	if ( ! wp_next_scheduled( 'botfence_cleanup_cron' ) ) {
		wp_schedule_event( time() + 3600, 'hourly', 'botfence_cleanup_cron' );
	}
}
register_activation_hook( __FILE__, 'botfence_activate' );

/**
 * Plugin deactivation hook callback.
 */
function botfence_deactivate() {
	$timestamp = wp_next_scheduled( 'botfence_cleanup_cron' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'botfence_cleanup_cron' );
	}
	$kl_ts = wp_next_scheduled( 'kl_antispam_cleanup_cron' );
	if ( $kl_ts ) {
		wp_unschedule_event( $kl_ts, 'kl_antispam_cleanup_cron' );
	}
}
register_deactivation_hook( __FILE__, 'botfence_deactivate' );

/**
 * Returns the main BotFence plugin instance.
 *
 * @return \BotFence\Plugin
 */
function botfence() {
	return \BotFence\Plugin::get_instance();
}

/**
 * Backwards compatibility helper.
 */
function kl_anti_spam() {
	return botfence();
}

// Bootstrap plugin when all plugins are loaded.
add_action( 'plugins_loaded', 'botfence', 0 );
