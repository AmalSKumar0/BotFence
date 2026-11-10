<?php
/**
 * PHPUnit Test Bootstrap and WordPress Test Doubles.
 *
 * Provides a comprehensive, production-grade WordPress simulation environment
 * covering options, transients, hooks, database layer, i18n, and form builders.
 *
 * @package KL_Anti_Spam\Tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/botfence/' );
}

if ( ! defined( 'BOTFENCE_VERSION' ) ) {
	define( 'BOTFENCE_VERSION', '1.0.2' );
}
if ( ! defined( 'BOTFENCE_FILE' ) ) {
	define( 'BOTFENCE_FILE', ABSPATH . 'botfence.php' );
}
if ( ! defined( 'BOTFENCE_PATH' ) ) {
	define( 'BOTFENCE_PATH', ABSPATH );
}
if ( ! defined( 'BOTFENCE_URL' ) ) {
	define( 'BOTFENCE_URL', 'http://example.com/wp-content/plugins/botfence/' );
}
if ( ! defined( 'BOTFENCE_BASENAME' ) ) {
	define( 'BOTFENCE_BASENAME', 'botfence/botfence.php' );
}

// Backwards-compatibility aliases
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

define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'SCRIPT_DEBUG', false );

// Global in-memory storage for test environment.
global $kl_test_options, $kl_test_transients, $kl_test_filters, $kl_test_actions, $wpdb;
$kl_test_options    = array();
$kl_test_transients = array();
$kl_test_filters    = array();
$kl_test_actions    = array();

/**
 * Options API Mocks.
 */
function get_option( $name, $default = false ) {
	global $kl_test_options;
	return $kl_test_options[ $name ] ?? $default;
}

function update_option( $name, $value, $autoload = null ) {
	global $kl_test_options;
	$kl_test_options[ $name ] = $value;
	return true;
}

function delete_option( $name ) {
	global $kl_test_options;
	unset( $kl_test_options[ $name ] );
	return true;
}

/**
 * Transients API Mocks.
 */
function get_transient( $name ) {
	global $kl_test_transients;
	if ( isset( $kl_test_transients[ $name ] ) ) {
		$item = $kl_test_transients[ $name ];
		if ( $item['expires'] > 0 && time() > $item['expires'] ) {
			unset( $kl_test_transients[ $name ] );
			return false;
		}
		return $item['value'];
	}
	return false;
}

function set_transient( $name, $value, $expiration = 0 ) {
	global $kl_test_transients;
	$expires = $expiration > 0 ? time() + $expiration : 0;
	$kl_test_transients[ $name ] = array(
		'value'   => $value,
		'expires' => $expires,
	);
	return true;
}

function delete_transient( $name ) {
	global $kl_test_transients;
	unset( $kl_test_transients[ $name ] );
	return true;
}

/**
 * Filter & Action Hooks Mocks.
 */
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $kl_test_filters;
	$kl_test_filters[ $hook ][] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
	return true;
}

function apply_filters( $hook, $value, ...$args ) {
	global $kl_test_filters;
	if ( empty( $kl_test_filters[ $hook ] ) ) {
		return $value;
	}

	$callbacks = $kl_test_filters[ $hook ];
	usort( $callbacks, fn( $a, $b ) => $a['priority'] <=> $b['priority'] );

	foreach ( $callbacks as $entry ) {
		$params = array_slice( array_merge( array( $value ), $args ), 0, $entry['accepted_args'] );
		$value  = call_user_func_array( $entry['callback'], $params );
	}

	return $value;
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $kl_test_actions;
	$kl_test_actions[ $hook ][] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
	return true;
}

function do_action( $hook, ...$args ) {
	global $kl_test_actions;
	if ( empty( $kl_test_actions[ $hook ] ) ) {
		return;
	}

	$callbacks = $kl_test_actions[ $hook ];
	usort( $callbacks, fn( $a, $b ) => $a['priority'] <=> $b['priority'] );

	foreach ( $callbacks as $entry ) {
		$params = array_slice( $args, 0, $entry['accepted_args'] );
		call_user_func_array( $entry['callback'], $params );
	}
}

function do_action_ref_array( $hook, array $args ) {
	global $kl_test_actions;
	if ( empty( $kl_test_actions[ $hook ] ) ) {
		return;
	}

	$callbacks = $kl_test_actions[ $hook ];
	usort( $callbacks, fn( $a, $b ) => $a['priority'] <=> $b['priority'] );

	foreach ( $callbacks as $entry ) {
		$params = array_slice( $args, 0, $entry['accepted_args'] );
		call_user_func_array( $entry['callback'], $params );
	}
}

function remove_filter( $hook, $callback, $priority = 10 ) {
	global $kl_test_filters;
	if ( isset( $kl_test_filters[ $hook ] ) ) {
		foreach ( $kl_test_filters[ $hook ] as $idx => $entry ) {
			if ( $entry['callback'] === $callback && $entry['priority'] === $priority ) {
				unset( $kl_test_filters[ $hook ][ $idx ] );
			}
		}
	}
	return true;
}

/**
 * Sanitization & Security Functions.
 */
function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function wp_strip_all_tags( $text, $remove_breaks = false ) {
	$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $text );
	$text = preg_replace( '/<[^>]*>/', '', (string) $text );
	if ( $remove_breaks ) {
		$text = preg_replace( '/[\r\n\t ]+/', ' ', (string) $text );
	}
	return trim( (string) $text );
}

function sanitize_text_field( $str ) {
	return wp_strip_all_tags( (string) $str );
}

function sanitize_textarea_field( $str ) {
	return wp_strip_all_tags( (string) $str );
}

function wp_unslash( $val ) {
	if ( is_array( $val ) ) {
		return array_map( 'wp_unslash', $val );
	}
	return stripslashes( (string) $val );
}

function esc_attr( $str ) {
	return htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' );
}

function esc_html( $str ) {
	return htmlspecialchars( (string) $str, ENT_NOQUOTES, 'UTF-8' );
}

function esc_textarea( $str ) {
	return htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $url ) {
	return filter_var( $url, FILTER_SANITIZE_URL );
}

function esc_url_raw( $url ) {
	return filter_var( $url, FILTER_SANITIZE_URL );
}

function wp_json_encode( $data ) {
	return json_encode( $data, JSON_UNESCAPED_SLASHES );
}

function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	if ( $special_chars ) {
		$chars .= '!@#$%^&*()';
	}
	return substr( str_shuffle( str_repeat( $chars, 5 ) ), 0, $length );
}

function wp_rand( $min = 0, $max = 0 ) {
	return random_int( $min, $max );
}

function wp_create_nonce( $action = -1 ) {
	return substr( hash_hmac( 'md5', (string) $action, 'test_nonce_salt' ), 0, 10 );
}

function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) {
	return true;
}

function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) {
	return true;
}

function current_user_can( $capability ) {
	global $kl_test_caps;
	return ! empty( $kl_test_caps[ $capability ] );
}

function is_admin() {
	return false;
}

function wp_next_scheduled( $hook ) {
	return false;
}

function wp_schedule_event( $timestamp, $recurrence, $hook ) {
	return true;
}

function wp_unschedule_event( $timestamp, $hook ) {
	return true;
}

function current_time( $type, $gmt = false ) {
	return gmdate( 'Y-m-d H:i:s' );
}

function wp_parse_args( $args, $defaults = array() ) {
	if ( is_object( $args ) ) {
		$r = get_object_vars( $args );
	} elseif ( is_array( $args ) ) {
		$r =& $args;
	} else {
		$r = array();
	}
	return array_merge( $defaults, $r );
}

/**
 * I18n Mocks.
 */
function __( $text, $domain = 'default' ) {
	return $text;
}

function _e( $text, $domain = 'default' ) {
	echo $text;
}

function esc_html__( $text, $domain = 'default' ) {
	return htmlspecialchars( $text, ENT_NOQUOTES, 'UTF-8' );
}

function esc_html_e( $text, $domain = 'default' ) {
	echo htmlspecialchars( $text, ENT_NOQUOTES, 'UTF-8' );
}

function esc_attr__( $text, $domain = 'default' ) {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr_e( $text, $domain = 'default' ) {
	echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function checked( $checked, $current = true, $echo = true ) {
	$result = ( (string) $checked === (string) $current ) ? ' checked="checked"' : '';
	if ( $echo ) {
		echo $result;
	}
	return $result;
}

function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
	$nonce = wp_create_nonce( $action );
	$html  = '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $nonce ) . '" />';
	if ( $echo ) {
		echo $html;
	}
	return $html;
}

function number_format_i18n( $number ) {
	return number_format( (float) $number );
}

function human_time_diff( $from, $to = 0 ) {
	if ( empty( $to ) ) {
		$to = time();
	}
	$diff = (int) abs( $to - $from );
	if ( $diff < 60 ) {
		return $diff . ' secs';
	}
	return round( $diff / 60 ) . ' mins';
}

function get_bloginfo( $show = '' ) {
	if ( 'version' === $show ) {
		return '6.6.1';
	}
	return 'Test Site';
}

function rest_url( $path = '' ) {
	return 'http://example.com/wp-json/' . ltrim( $path, '/' );
}

function admin_url( $path = '' ) {
	return 'http://example.com/wp-admin/' . ltrim( $path, '/' );
}

function wp_nonce_url( $actionurl, $action = -1, $name = '_wpnonce' ) {
	return $actionurl . ( str_contains( $actionurl, '?' ) ? '&' : '?' ) . $name . '=testnonce';
}

function add_query_arg( $key, $val = '', $url = '' ) {
	if ( is_array( $key ) ) {
		$query = http_build_query( $key );
		return ( empty( $url ) ? '' : $url ) . ( str_contains( (string) $url, '?' ) ? '&' : '?' ) . $query;
	}
	return $url . ( str_contains( (string) $url, '?' ) ? '&' : '?' ) . $key . '=' . $val;
}

function wp_send_json_success( $data = null ) {
	echo json_encode( array( 'success' => true, 'data' => $data ) );
	exit;
}

function wp_send_json_error( $data = null ) {
	echo json_encode( array( 'success' => false, 'data' => $data ) );
	exit;
}

function wp_die( $message = '', $title = '', $args = array() ) {
	throw new \RuntimeException( (string) $message );
}

function dbDelta( $queries = '', $execute = true ) {
	return array();
}

/**
 * WP_Error Mock Class.
 */
class WP_Error {
	protected array $errors = array();
	protected array $error_data = array();

	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( ! empty( $code ) ) {
			$this->add( $code, $message, $data );
		}
	}

	public function add( $code, $message, $data = '' ) {
		$this->errors[ $code ][] = $message;
		if ( ! empty( $data ) ) {
			$this->error_data[ $code ] = $data;
		}
	}

	public function get_error_message( $code = '' ) {
		if ( empty( $code ) ) {
			$code = key( $this->errors );
		}
		return isset( $this->errors[ $code ][0] ) ? $this->errors[ $code ][0] : '';
	}

	public function get_error_code() {
		return key( $this->errors ) ?: '';
	}
}

function is_wp_error( $thing ) {
	return ( $thing instanceof WP_Error );
}

/**
 * WP_REST Mocks.
 */
class WP_REST_Server {
	public const READABLE = 'GET';
	public const CREATABLE = 'POST';
}

class WP_REST_Request {
	protected array $params = array();

	public function __construct( array $params = array() ) {
		$this->params = $params;
	}

	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}
}

class WP_REST_Response {
	public $data;
	public int $status;

	public function __construct( $data = null, int $status = 200 ) {
		$this->data   = $data;
		$this->status = $status;
	}
}

function register_rest_route( $namespace, $route, $args = array() ) {
	return true;
}

/**
 * Database Test Double (Mock WPDB).
 */
class Mock_WPDB {
	public string $prefix = 'wp_';
	public int $insert_id = 0;
	public array $logs_table = array();
	public array $rates_table = array();
	public array $used_tokens_table = array();
	public bool $suppress_errors = false;

	public function suppress_errors( bool $suppress = true ): bool {
		$prev = $this->suppress_errors;
		$this->suppress_errors = $suppress;
		return $prev;
	}

	public function get_charset_collate(): string {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';
	}

	public function esc_like( string $text ): string {
		return addcslashes( $text, '_%\\' );
	}

	public function prepare( string $query, ...$args ): string {
		if ( isset( $args[0] ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		$idx = 0;
		return preg_replace_callback( '/(%s|%d|%f)/', function( $match ) use ( &$idx, $args ) {
			$val = $args[ $idx++ ] ?? '';
			if ( '%d' === $match[0] ) {
				return (string) (int) $val;
			}
			if ( '%f' === $match[0] ) {
				return (string) (float) $val;
			}
			return "'" . addslashes( (string) $val ) . "'";
		}, $query );
	}

	public function insert( string $table, array $data, ?array $format = null ): int {
		$this->insert_id++;
		$entry = (object) array_merge( array( 'id' => $this->insert_id ), $data );
		$this->logs_table[ $this->insert_id ] = $entry;
		return 1;
	}

	public function update( string $table, array $data, array $where, ?array $format = null, ?array $where_format = null ): int {
		$affected = 0;
		foreach ( $this->logs_table as $id => $row ) {
			$match = true;
			foreach ( $where as $col => $val ) {
				if ( (string) ( $row->$col ?? '' ) !== (string) $val ) {
					$match = false;
					break;
				}
			}
			if ( $match ) {
				foreach ( $data as $col => $val ) {
					$row->$col = $val;
				}
				$affected++;
			}
		}
		return $affected;
	}

	public function query( string $query ) {
		// Handle atomic token consumption into used_tokens table.
		if ( ( str_contains( $query, 'botfence_used_tokens' ) || str_contains( $query, 'kl_antispam_used_tokens' ) ) && str_contains( $query, 'INSERT INTO' ) ) {
			preg_match( "/VALUES \('([^']+)',\s*'([^']+)',\s*'([^']+)',\s*'([^']+)'\)/", $query, $m );
			if ( ! empty( $m ) ) {
				$token_hash  = $m[1];
				$form_id     = $m[2];
				$consumed_at = $m[3];
				$expires_at  = $m[4];

				if ( isset( $this->used_tokens_table[ $token_hash ] ) ) {
					// Duplicate token - simulate primary key constraint violation.
					return false;
				}

				$this->used_tokens_table[ $token_hash ] = (object) array(
					'token_hash'  => $token_hash,
					'form_id'     => $form_id,
					'consumed_at' => $consumed_at,
					'expires_at'  => $expires_at,
				);
				return 1;
			}
		}

		// Handle ON DUPLICATE KEY UPDATE for both BotFence and legacy tables.
		if ( ( str_contains( $query, 'botfence_rate_limits' ) || str_contains( $query, 'kl_antispam_rate_limits' ) ) && str_contains( $query, 'ON DUPLICATE KEY UPDATE' ) ) {
			preg_match( "/VALUES \('([^']+)',\s*'([^']+)',\s*1,\s*'([^']+)',\s*'([^']+)'\)/", $query, $m );
			if ( ! empty( $m ) ) {
				$ident = $m[1];
				$type  = $m[2];
				$key   = $ident . ':' . $type;
				if ( isset( $this->rates_table[ $key ] ) ) {
					$existing = $this->rates_table[ $key ];
					// If query contains atomic reset condition with expires_at check
					if ( str_contains( $query, 'IF(expires_at <=' ) || str_contains( $query, 'IF(expires_at <' ) ) {
						if ( strtotime( $existing->expires_at ) <= strtotime( $m[3] ) ) {
							$existing->count = 1;
							$existing->first_seen = $m[3];
							$existing->expires_at = $m[4];
						} else {
							$existing->count++;
						}
					} else {
						$this->rates_table[ $key ]->count++;
						$this->rates_table[ $key ]->expires_at = $m[4];
					}
				} else {
					$this->rates_table[ $key ] = (object) array(
						'identifier' => $ident,
						'limit_type' => $type,
						'count'      => 1,
						'first_seen' => $m[3],
						'expires_at' => $m[4],
					);
				}
				return 1;
			}
		}

		if ( str_contains( $query, 'TRUNCATE' ) ) {
			$this->logs_table = array();
			$this->used_tokens_table = array();
			return 1;
		}

		if ( str_contains( $query, 'DELETE FROM' ) && ( str_contains( $query, 'botfence_used_tokens' ) || str_contains( $query, 'kl_antispam_used_tokens' ) ) ) {
			return 1;
		}

		return 1;
	}

	public function get_var( string $query ) {
		if ( str_contains( $query, 'SHOW TABLES LIKE' ) ) {
			if ( str_contains( $query, 'used_tokens' ) ) {
				return 'wp_botfence_used_tokens';
			}
			if ( str_contains( $query, 'rate_limits' ) ) {
				return 'wp_botfence_rate_limits';
			}
			if ( str_contains( $query, 'wp_botfence_logs' ) ) {
				return 'wp_botfence_logs';
			}
			return 'wp_botfence_logs';
		}
		if ( str_contains( $query, 'COUNT(id)' ) ) {
			if ( str_contains( $query, "action_taken = 'block'" ) ) {
				return count( array_filter( $this->logs_table, fn( $e ) => $e->action_taken === 'block' ) );
			}
			if ( str_contains( $query, "action_taken = 'allow'" ) ) {
				return count( array_filter( $this->logs_table, fn( $e ) => $e->action_taken === 'allow' ) );
			}
			if ( str_contains( $query, "action_taken = 'challenge'" ) ) {
				return count( array_filter( $this->logs_table, fn( $e ) => $e->action_taken === 'challenge' ) );
			}
			return count( $this->logs_table );
		}
		if ( str_contains( $query, 'SELECT count FROM' ) ) {
			preg_match( "/identifier = '([^']+)' AND limit_type = '([^']+)'/", $query, $m );
			if ( ! empty( $m ) ) {
				$key = $m[1] . ':' . $m[2];
				return isset( $this->rates_table[ $key ] ) ? $this->rates_table[ $key ]->count : null;
			}
		}
		return null;
	}

	public function get_results( string $query ) {
		if ( str_contains( $query, 'GROUP BY form_type' ) ) {
			$tallies = array();
			foreach ( $this->logs_table as $row ) {
				if ( ( $row->action_taken ?? '' ) === 'block' ) {
					$ft = $row->form_type ?? 'unknown';
					$tallies[ $ft ] = ( $tallies[ $ft ] ?? 0 ) + 1;
				}
			}
			$out = array();
			foreach ( $tallies as $ft => $cnt ) {
				$out[] = (object) array( 'form_type' => $ft, 'total' => $cnt );
			}
			return $out;
		}

		if ( str_contains( $query, 'SELECT * FROM' ) ) {
			if ( str_contains( $query, "action_taken = 'block'" ) ) {
				return array_values( array_filter( $this->logs_table, fn( $e ) => ( $e->action_taken ?? '' ) === 'block' ) );
			}
			return array_values( $this->logs_table );
		}
		return array();
	}

	public function get_col( string $query ) {
		if ( str_contains( $query, 'SELECT reasons FROM' ) ) {
			$cols = array();
			foreach ( $this->logs_table as $row ) {
				if ( ! empty( $row->is_spam ) ) {
					$cols[] = $row->reasons;
				}
			}
			return $cols;
		}
		return array();
	}
}

$wpdb = new Mock_WPDB();

// Form builders mock hooks / functions.
if ( ! defined( 'WPCF7_VERSION' ) ) {
	define( 'WPCF7_VERSION', '5.9.3' );
}

if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
	class WPCF7_ContactForm {
		public static $current = null;
		public string $id = '1365';
		public bool $skip_mail = false;

		public function __construct( string $id = '1365' ) {
			$this->id = $id;
		}

		public function id(): string {
			return $this->id;
		}

		public static function get_current() {
			return self::$current;
		}
	}
}

if ( ! class_exists( 'WPCF7_Submission' ) ) {
	class WPCF7_Submission {
		public static $instance = null;
		public string $status = 'init';
		public ?WPCF7_ContactForm $contact_form = null;
		public array $posted_data = array();

		public function __construct( ?WPCF7_ContactForm $contact_form = null ) {
			$this->contact_form = $contact_form ?: new WPCF7_ContactForm();
		}

		public static function get_instance() {
			return self::$instance;
		}

		public function get_contact_form() {
			return $this->contact_form;
		}

		public function get_status(): string {
			return $this->status;
		}

		public function set_status( string $status ): void {
			$this->status = $status;
		}

		public function get_response(): string {
			switch ( $this->status ) {
				case 'aborted':
					return 'Sending mail has been aborted.';
				case 'spam':
					return 'There was an error trying to send your message. Please try again later.';
				case 'mail_sent':
					return 'Thank you for your message. It has been sent.';
				default:
					return '';
			}
		}
	}
}

function wc_add_notice( $message, $notice_type = 'success' ) {
	global $kl_test_wc_notices;
	$kl_test_wc_notices[] = array( 'msg' => $message, 'type' => $notice_type );
}

// Autoloader for both BotFence and KL_Anti_Spam namespaces.
spl_autoload_register( function ( $class_name ) {
	$prefixes = array(
		'BotFence\\'    => BOTFENCE_PATH,
		'KL_Anti_Spam\\' => KL_ANTISPAM_PATH,
	);

	foreach ( $prefixes as $prefix => $base_path ) {
		if ( str_starts_with( $class_name, $prefix ) ) {
			$relative = substr( $class_name, strlen( $prefix ) );

			if ( str_starts_with( $relative, 'Integrations\\' ) ) {
				$sub = substr( $relative, strlen( 'Integrations\\' ) );
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

			$class_file = strtolower( str_replace( '_', '-', $relative ) );
			$file = $base_path . 'includes/class-' . $class_file . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}
	}
} );
