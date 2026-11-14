<?php
/**
 * Plugin Name:       Vighnaghna
 * Plugin URI:        https://amalskumar.dev
 * Description:       Lightweight, invisible anti-spam protection for WordPress forms. No CAPTCHA, no external requests.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      8.1
 * Author:            Amal S Kumar
 * Author URI:        https://amalskumar.dev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vighnaghna
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Vighnaghna Plugin Constants.
if ( ! defined( 'VIGHNAGHNA_VERSION' ) ) {
	define( 'VIGHNAGHNA_VERSION', '1.0.0' );
}

if ( ! defined( 'VIGHNAGHNA_FILE' ) ) {
	define( 'VIGHNAGHNA_FILE', __FILE__ );
}

if ( ! defined( 'VIGHNAGHNA_PATH' ) ) {
	define( 'VIGHNAGHNA_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'VIGHNAGHNA_URL' ) ) {
	define( 'VIGHNAGHNA_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'VIGHNAGHNA_BASENAME' ) ) {
	define( 'VIGHNAGHNA_BASENAME', plugin_basename( __FILE__ ) );
}

// Backwards compatibility constants for legacy callers.
if ( ! defined( 'BOTFENCE_VERSION' ) ) {
	define( 'BOTFENCE_VERSION', VIGHNAGHNA_VERSION );
}
if ( ! defined( 'BOTFENCE_FILE' ) ) {
	define( 'BOTFENCE_FILE', VIGHNAGHNA_FILE );
}
if ( ! defined( 'BOTFENCE_PATH' ) ) {
	define( 'BOTFENCE_PATH', VIGHNAGHNA_PATH );
}
if ( ! defined( 'BOTFENCE_URL' ) ) {
	define( 'BOTFENCE_URL', VIGHNAGHNA_URL );
}
if ( ! defined( 'BOTFENCE_BASENAME' ) ) {
	define( 'BOTFENCE_BASENAME', VIGHNAGHNA_BASENAME );
}

if ( ! defined( 'KL_ANTISPAM_VERSION' ) ) {
	define( 'KL_ANTISPAM_VERSION', VIGHNAGHNA_VERSION );
}
if ( ! defined( 'KL_ANTISPAM_FILE' ) ) {
	define( 'KL_ANTISPAM_FILE', VIGHNAGHNA_FILE );
}
if ( ! defined( 'KL_ANTISPAM_PATH' ) ) {
	define( 'KL_ANTISPAM_PATH', VIGHNAGHNA_PATH );
}
if ( ! defined( 'KL_ANTISPAM_URL' ) ) {
	define( 'KL_ANTISPAM_URL', VIGHNAGHNA_URL );
}
if ( ! defined( 'KL_ANTISPAM_BASENAME' ) ) {
	define( 'KL_ANTISPAM_BASENAME', VIGHNAGHNA_BASENAME );
}

// Compatibility helper for PHP < 8.0.
if ( ! function_exists( 'str_starts_with' ) ) {
	function str_starts_with( string $haystack, string $needle ): bool {
		return '' === $needle || 0 === strncmp( $haystack, $needle, strlen( $needle ) );
	}
}

/**
 * Autoloader for Vighnaghna classes.
 *
 * @param string $class_name Class to load.
 */
spl_autoload_register( function ( $class_name ) {
	$prefixes = array(
		'Vighnaghna\\'   => VIGHNAGHNA_PATH,
		'BotFence\\'     => VIGHNAGHNA_PATH,
		'KL_Anti_Spam\\' => VIGHNAGHNA_PATH,
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
if ( ! function_exists( 'vighnaghna_activate' ) ) {
	function vighnaghna_activate() {
		try {
			require_once VIGHNAGHNA_PATH . 'includes/class-database.php';
			\Vighnaghna\Database::create_tables();
			\Vighnaghna\Database::migrate_from_legacy();

			// Ensure unique secret key for HMAC token signing.
			$secret = get_option( 'vighnaghna_secret_key' );
			if ( ! $secret || ! is_string( $secret ) || strlen( $secret ) < 32 ) {
				$secret = get_option( 'botfence_secret_key' ) ?: get_option( 'kl_antispam_secret_key' );
				if ( ! $secret || ! is_string( $secret ) || strlen( $secret ) < 32 ) {
					try {
						$secret = bin2hex( random_bytes( 32 ) );
					} catch ( \Exception $e ) {
						$secret = wp_generate_password( 64, true, true );
					}
				}
				update_option( 'vighnaghna_secret_key', $secret, true );
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
				'weight_expired_token'        => 25,
				'weight_fast_submit'          => 30,
				'weight_rate_limit'           => 40,
				'weight_excessive_urls'       => 25,
				'weight_suspicious_telemetry' => 20,
				'weight_repeated_message'     => 35,
				'weight_headless_browser'     => 50,
				'weight_untrusted_event'      => 45,
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
				if ( false === get_option( 'vighnaghna_' . $key ) ) {
					// Check if legacy option exists before writing default.
					$legacy_val = get_option( 'botfence_' . $key );
					if ( false === $legacy_val || null === $legacy_val ) {
						$legacy_val = get_option( 'kl_antispam_' . $key );
					}
					$initial_val = ( false !== $legacy_val && null !== $legacy_val ) ? $legacy_val : $default_val;
					update_option( 'vighnaghna_' . $key, $initial_val );
				}
			}

			// Schedule cleanup cron if not scheduled.
			if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_event' ) ) {
				if ( ! wp_next_scheduled( 'vighnaghna_cleanup_cron' ) ) {
					wp_schedule_event( time() + 3600, 'hourly', 'vighnaghna_cleanup_cron' );
				}
			}
		} catch ( \Throwable $e ) {
			// Fail-safe activation.
		}
	}
}
register_activation_hook( __FILE__, 'vighnaghna_activate' );

/**
 * Plugin deactivation hook callback.
 */
if ( ! function_exists( 'vighnaghna_deactivate' ) ) {
	function vighnaghna_deactivate() {
		try {
			if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_unschedule_event' ) ) {
				$timestamp = wp_next_scheduled( 'vighnaghna_cleanup_cron' );
				if ( $timestamp ) {
					wp_unschedule_event( $timestamp, 'vighnaghna_cleanup_cron' );
				}
				// Clean up any legacy cron hooks if lingering.
				$bf_ts = wp_next_scheduled( 'botfence_cleanup_cron' );
				if ( $bf_ts ) {
					wp_unschedule_event( $bf_ts, 'botfence_cleanup_cron' );
				}
				$kl_ts = wp_next_scheduled( 'kl_antispam_cleanup_cron' );
				if ( $kl_ts ) {
					wp_unschedule_event( $kl_ts, 'kl_antispam_cleanup_cron' );
				}
			}
		} catch ( \Throwable $e ) {
			// Fail-safe.
		}
	}
}
register_deactivation_hook( __FILE__, 'vighnaghna_deactivate' );

/**
 * Returns the main Vighnaghna plugin instance.
 *
 * @return \Vighnaghna\Plugin
 */
if ( ! function_exists( 'vighnaghna' ) ) {
	function vighnaghna() {
		return \Vighnaghna\Plugin::get_instance();
	}
}

/**
 * Backwards compatibility helper for BotFence.
 *
 * @return \Vighnaghna\Plugin
 */
if ( ! function_exists( 'botfence' ) ) {
	function botfence() {
		return vighnaghna();
	}
}

/**
 * Backwards compatibility helper for KL Anti-Spam.
 *
 * @return \Vighnaghna\Plugin
 */
if ( ! function_exists( 'kl_anti_spam' ) ) {
	function kl_anti_spam() {
		return vighnaghna();
	}
}

// Bootstrap plugin when all plugins are loaded.
add_action( 'plugins_loaded', 'vighnaghna', 0 );
