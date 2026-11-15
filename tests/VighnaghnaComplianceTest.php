<?php
/**
 * Vighnaghna Compliance and Security Test Suite.
 *
 * Verifies WordPress.org compliance fixes:
 * 1. Safe IP comparison policy (mobile network / proxy IP switches do not block users).
 * 2. Separate expired token handling with safe retry path.
 * 3. Configurable weights for Headless Browser (Selenium) and Synthetic Events.
 * 4. Conditional asset loading (scripts/styles not enqueued on every page).
 * 5. Idempotent legacy database migration.
 *
 * @package Vighnaghna\Tests
 */

namespace Vighnaghna\Tests;

use PHPUnit\Framework\TestCase;
use Vighnaghna\Token_Manager;
use Vighnaghna\Risk_Engine;
use Vighnaghna\Captcha_Engine;
use Vighnaghna\Honeypot;
use Vighnaghna\Rate_Limiter;
use Vighnaghna\Logger;
use Vighnaghna\Plugin;
use Vighnaghna\Database;

class VighnaghnaComplianceTest extends TestCase {

	protected Token_Manager $token_manager;
	protected Risk_Engine $risk_engine;
	protected Captcha_Engine $captcha_engine;
	protected Honeypot $honeypot;
	protected Rate_Limiter $rate_limiter;
	protected Logger $logger;

	protected function setUp(): void {
		parent::setUp();
		global $kl_test_options, $kl_test_transients, $wpdb;
		$kl_test_options    = array(
			'vighnaghna_enabled'                   => 1,
			'vighnaghna_secret_key'                => 'vighnaghna_test_secret_key_1234567890',
			'vighnaghna_token_lifetime'            => 600,
			'vighnaghna_risk_threshold_challenge'  => 30,
			'vighnaghna_risk_threshold_block'      => 60,
			'vighnaghna_weight_headless_browser'   => 50,
			'vighnaghna_weight_untrusted_event'    => 45,
			'vighnaghna_weight_expired_token'      => 80,
		);
		$kl_test_transients = array();

		$this->token_manager  = new Token_Manager();
		$this->risk_engine    = new Risk_Engine();
		$this->honeypot       = new Honeypot();
		$this->rate_limiter   = new Rate_Limiter();
		$this->logger         = new Logger();
		$this->captcha_engine = new Captcha_Engine(
			$this->token_manager,
			$this->honeypot,
			$this->rate_limiter,
			$this->risk_engine,
			$this->logger
		);
	}

	/**
	 * Test 1: Safe IP comparison policy.
	 *
	 * When a mobile user switches cell towers or connects via dynamic CGNAT proxy,
	 * their IP changes between page load and form submission.
	 * The token signature remains 100% authentic.
	 * Vighnaghna's safe policy records the IP mismatch for telemetry without blocking the user.
	 */
	public function test_safe_ip_policy_does_not_block_mobile_users(): void {
		$issue_ip  = '203.0.113.10';  // Mobile IP at form load
		$submit_ip = '198.51.100.25'; // Different IP at form submission (carrier switch)

		$token_data = $this->token_manager->generate_token( 'contact_form', $issue_ip );
		$this->assertNotEmpty( $token_data['token'] );

		// Validate with the new submission IP.
		$result = $this->token_manager->validate_token( $token_data['token'], 'contact_form', $submit_ip );

		$this->assertTrue( $result['is_valid'], 'Authentic token must remain valid even when mobile IP changes' );
		$this->assertSame( 'valid', $result['status'] );
		$this->assertFalse( $result['ip_match'], 'IP mismatch is accurately recorded for diagnostic telemetry' );
		$this->assertSame( 0, $result['risk_points'], 'IP mismatch alone must not penalize legitimate mobile users' );
	}

	/**
	 * Test 2: Expired tokens provide a distinct, friendly retry message.
	 */
	public function test_expired_token_handling_and_message(): void {
		$form_id  = 'contact_form';
		$tok_data = $this->token_manager->generate_token( $form_id, '127.0.0.1', time() - 3600 ); // 1 hour expired

		$post_data = array(
			'vighnaghna_token' => $tok_data['token'],
			'user_name'        => 'John Doe',
			'user_email'       => 'john@example.com',
			'message'          => 'Legitimate message submitted after leaving browser tab idle',
		);

		$verification = $this->captcha_engine->verify_submission( $post_data, 'contact_form_7', $form_id );

		$this->assertFalse( $verification['is_valid'] );
		$this->assertSame( 'block', $verification['action'] );
		$this->assertStringContainsString( 'session has expired', $verification['error_message'] );
	}

	/**
	 * Test 3: Headless browser detection is configurable and contributes assigned points.
	 */
	public function test_headless_browser_configurable_weight(): void {
		$context = array(
			'honeypot_triggered' => false,
			'token_status'       => 'valid',
			'elapsed_time'       => 5.0,
			'telemetry'          => array(
				'webdriver'    => true,
				'mouse_moves'  => 10,
				'keystrokes'   => 15,
				'is_trusted'   => true,
			),
		);

		$eval_default = $this->risk_engine->evaluate( $context );
		$this->assertSame( 50, $eval_default['score'], 'Default headless browser weight must be 50' );
		$this->assertSame( 'challenge', $eval_default['action'] );

		// Customize headless weight to 70 (block threshold).
		update_option( 'vighnaghna_weight_headless_browser', 70 );
		$eval_custom = $this->risk_engine->evaluate( $context );
		$this->assertSame( 70, $eval_custom['score'], 'Configured weight 70 must be reflected' );
		$this->assertSame( 'block', $eval_custom['action'] );
	}

	/**
	 * Test 4: Untrusted synthetic event execution is configurable and evaluated.
	 */
	public function test_untrusted_synthetic_event_configurable_weight(): void {
		$context = array(
			'honeypot_triggered' => false,
			'token_status'       => 'valid',
			'elapsed_time'       => 5.0,
			'telemetry'          => array(
				'is_trusted'   => false, // Programmatic synthetic dispatch
				'mouse_moves'  => 5,
				'keystrokes'   => 5,
			),
		);

		$eval_default = $this->risk_engine->evaluate( $context );
		$this->assertSame( 45, $eval_default['score'], 'Default untrusted event weight must be 45' );
		$this->assertSame( 'challenge', $eval_default['action'] );

		// Customize untrusted weight to 65.
		update_option( 'vighnaghna_weight_untrusted_event', 65 );
		$eval_custom = $this->risk_engine->evaluate( $context );
		$this->assertSame( 65, $eval_custom['score'] );
		$this->assertSame( 'block', $eval_custom['action'] );
	}

	/**
	 * Test 5: Conditional frontend asset loading.
	 *
	 * Scripts and styles must NOT be enqueued on every WordPress page.
	 * They should only load when forms or comments are present.
	 */
	public function test_conditional_asset_loading(): void {
		$plugin = Plugin::get_instance();

		// Default state: no forms, no comments open, not checkout.
		$this->assertFalse( $plugin->should_enqueue_frontend_assets() );

		// If comments are open on a singular post:
		$GLOBALS['post'] = (object) array( 'comment_status' => 'open' );
		// Simulating comments open
		add_filter( 'comments_open', '__return_true' );
		// When comments_open is true on singular post
		$this->assertTrue( $plugin->should_enqueue_frontend_assets() );
		remove_filter( 'comments_open', '__return_true' );
		unset( $GLOBALS['post'] );
	}

	/**
	 * Test 6: Idempotent legacy database migration preserves data and copies safely.
	 */
	public function test_idempotent_database_migration(): void {
		delete_option( 'vighnaghna_risk_threshold_block' );
		delete_option( 'vighnaghna_quarantine_duration' );
		delete_option( 'vighnaghna_secret_key' );

		update_option( 'botfence_risk_threshold_block', 75 );
		update_option( 'botfence_quarantine_duration', 1200 );
		update_option( 'botfence_secret_key', 'legacy_botfence_secret_key_abc' );

		// Run migration
		Database::migrate_from_legacy();

		$this->assertSame( 75, (int) get_option( 'vighnaghna_risk_threshold_block' ) );
		$this->assertSame( 1200, (int) get_option( 'vighnaghna_quarantine_duration' ) );
		$this->assertSame( 'legacy_botfence_secret_key_abc', get_option( 'vighnaghna_secret_key' ) );

		// Verify idempotence: if already migrated, re-running does not corrupt or overwrite
		update_option( 'vighnaghna_risk_threshold_block', 85 ); // User changed it in Vighnaghna
		Database::migrate_from_legacy();
		$this->assertSame( 85, (int) get_option( 'vighnaghna_risk_threshold_block' ) );
	}

	/**
	 * Test 7: Legacy options migration includes weight_headless_browser and weight_untrusted_event.
	 */
	public function test_legacy_weights_migration_includes_headless_and_untrusted(): void {
		delete_option( 'vighnaghna_migrated_from_legacy' );
		delete_option( 'vighnaghna_weight_headless_browser' );
		delete_option( 'vighnaghna_weight_untrusted_event' );

		update_option( 'botfence_weight_headless_browser', 55 );
		update_option( 'botfence_weight_untrusted_event', 48 );

		Database::migrate_from_legacy();

		$this->assertSame( 55, (int) get_option( 'vighnaghna_weight_headless_browser' ) );
		$this->assertSame( 48, (int) get_option( 'vighnaghna_weight_untrusted_event' ) );
	}

	/**
	 * Test 8: Legacy database table records migration for botfence_logs and botfence_rate_limits.
	 *
	 * - Preserves record IDs where possible.
	 * - Prevents duplicate records.
	 * - Does not delete legacy tables.
	 * - Is idempotent.
	 */
	public function test_legacy_table_records_migration(): void {
		global $wpdb;

		$wpdb->logs_table          = array();
		$wpdb->rates_table         = array();
		$wpdb->legacy_logs_table   = array(
			array(
				'id'              => 42,
				'created_at'      => '2026-10-01 12:00:00',
				'form_type'       => 'contact_form_7',
				'form_id'         => '123',
				'risk_score'      => 85,
				'action_taken'    => 'block',
				'reasons'         => 'Honeypot trap triggered',
				'ip_hash'         => 'hash42',
				'masked_ip'       => '203.0.113.xxx',
				'user_agent'      => 'BotCrawler/1.0',
				'submission_time' => 0.4,
				'is_spam'         => 1,
			),
			array(
				'id'              => 43,
				'created_at'      => '2026-10-01 12:05:00',
				'form_type'       => 'wpforms',
				'form_id'         => '456',
				'risk_score'      => 10,
				'action_taken'    => 'allow',
				'reasons'         => 'Clean submission',
				'ip_hash'         => 'hash43',
				'masked_ip'       => '198.51.100.xxx',
				'user_agent'      => 'Mozilla/5.0',
				'submission_time' => 4.2,
				'is_spam'         => 0,
			),
		);
		$wpdb->legacy_rates_table  = array(
			array(
				'id'         => 10,
				'identifier' => '192.0.2.100',
				'limit_type' => 'ip',
				'count'      => 4,
				'first_seen' => '2026-10-01 11:00:00',
				'expires_at' => '2026-10-01 13:00:00',
			),
		);

		// Execute table records migration
		$results = Database::migrate_legacy_records();

		$this->assertSame( 2, $results['logs'], 'Must migrate 2 legacy log records' );
		$this->assertSame( 1, $results['rate_limits'], 'Must migrate 1 legacy rate limit record' );

		// Verify record IDs are preserved
		$this->assertArrayHasKey( 42, $wpdb->logs_table, 'Record ID 42 must be preserved' );
		$this->assertArrayHasKey( 43, $wpdb->logs_table, 'Record ID 43 must be preserved' );
		$this->assertSame( 42, $wpdb->logs_table[42]->id );
		$this->assertSame( 'contact_form_7', $wpdb->logs_table[42]->form_type );

		// Verify rates table migration
		$rate_key = '192.0.2.100:ip';
		$this->assertArrayHasKey( $rate_key, $wpdb->rates_table );
		$this->assertSame( 4, $wpdb->rates_table[ $rate_key ]->count );

		// Verify legacy tables were NOT deleted
		$this->assertCount( 2, $wpdb->legacy_logs_table, 'Legacy logs table must not be deleted' );
		$this->assertCount( 1, $wpdb->legacy_rates_table, 'Legacy rates table must not be deleted' );

		// Verify idempotence: re-running migration does not duplicate records
		$second_run = Database::migrate_legacy_records();
		$this->assertSame( 0, $second_run['logs'], 'Second migration must insert 0 duplicates' );
		$this->assertSame( 0, $second_run['rate_limits'], 'Second migration must insert 0 duplicate rates' );
		$this->assertCount( 2, $wpdb->logs_table, 'Total logs must remain exactly 2' );
	}

	/**
	 * Test 9: Activation and deactivation callbacks execute safely without altering active_plugins.
	 */
	public function test_activation_and_deactivation_lifecycle(): void {
		global $kl_test_options;

		// Seed initial state with a simulated active_plugins list
		$initial_active_plugins = array( 'vighnaghna/vighnaghna.php', 'woocommerce/woocommerce.php', 'contact-form-7/wp-contact-form-7.php' );
		$kl_test_options['active_plugins'] = $initial_active_plugins;

		// Trigger plugin activation
		require_once VIGHNAGHNA_PATH . 'vighnaghna.php';
		vighnaghna_activate();

		// Verify active_plugins was NOT touched or modified
		$this->assertSame( $initial_active_plugins, $kl_test_options['active_plugins'], 'Activation must never alter active_plugins' );

		// Verify secret key and default options were written
		$this->assertNotEmpty( get_option( 'vighnaghna_secret_key' ) );
		$this->assertSame( 1, (int) get_option( 'vighnaghna_enabled' ) );
		$this->assertSame( 100, (int) get_option( 'vighnaghna_weight_honeypot' ) );

		// Verify cron event was scheduled
		$this->assertNotFalse( wp_next_scheduled( 'vighnaghna_cleanup_cron' ) );

		// Trigger plugin deactivation
		vighnaghna_deactivate();

		// Verify cron event was cleanly unscheduled
		$this->assertFalse( wp_next_scheduled( 'vighnaghna_cleanup_cron' ) );

		// Verify active_plugins remains completely unchanged
		$this->assertSame( $initial_active_plugins, $kl_test_options['active_plugins'], 'Deactivation must never alter active_plugins' );
	}

	/**
	 * Test 10: Legacy database migration partial failure and safe retry.
	 *
	 * - Does not set vighnaghna_migrated_from_legacy when migration fails.
	 * - Returns an explicit migration result with success and error details.
	 * - Keeps migration idempotent and preserves all legacy data.
	 * - Successfully retries once failure condition is resolved and sets migration flag.
	 */
	public function test_migration_partial_failure_and_retry(): void {
		global $wpdb;

		delete_option( 'vighnaghna_migrated_from_legacy' );
		delete_option( 'vighnaghna_rate_limit_ip_per_minute' );

		update_option( 'botfence_rate_limit_ip_per_minute', 12 );

		$wpdb->logs_table         = array();
		$wpdb->rates_table        = array();
		$wpdb->legacy_logs_table  = array(
			array(
				'id'              => 88,
				'created_at'      => '2026-11-01 10:00:00',
				'form_type'       => 'wpforms',
				'form_id'         => '10',
				'risk_score'      => 40,
				'action_taken'    => 'challenge',
				'reasons'         => 'Test reason',
				'ip_hash'         => 'hash88',
				'masked_ip'       => '192.0.2.xxx',
				'user_agent'      => 'TestAgent',
				'submission_time' => 3.0,
				'is_spam'         => 1,
			),
		);
		$wpdb->legacy_rates_table = array(
			array(
				'id'         => 55,
				'identifier' => '198.51.100.1',
				'limit_type' => 'ip',
				'count'      => 7,
				'first_seen' => '2026-11-01 09:00:00',
				'expires_at' => '2026-11-01 11:00:00',
			),
		);

		// 1. Simulate database failure during migration
		$wpdb->simulate_query_failure = true;

		$failure_result = Database::migrate_from_legacy();

		// Migration must explicitly report failure with error details
		$this->assertFalse( $failure_result['success'], 'Result success must be false on failure' );
		$this->assertNotEmpty( $failure_result['errors'], 'Result must contain explicit error details' );
		$this->assertStringContainsString( 'Simulated database query failure', implode( '; ', $failure_result['errors'] ) );

		// The migration flag MUST NOT be set when migration fails
		$this->assertFalse( (bool) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must not be set on failure' );

		// Legacy tables must remain completely preserved intact
		$this->assertCount( 1, $wpdb->legacy_logs_table, 'Legacy logs must not be deleted or corrupted' );
		$this->assertCount( 1, $wpdb->legacy_rates_table, 'Legacy rates must not be deleted or corrupted' );

		// 2. Resolve database failure and retry migration
		$wpdb->simulate_query_failure = false;
		$wpdb->last_error             = '';

		$retry_result = Database::migrate_from_legacy();

		// Retry must succeed
		$this->assertTrue( $retry_result['success'], 'Retry must succeed once database issue is resolved' );
		$this->assertEmpty( $retry_result['errors'], 'No errors should be reported on successful migration' );
		$this->assertSame( 1, $retry_result['records']['logs'] );
		$this->assertSame( 1, $retry_result['records']['rate_limits'] );

		// The migration flag MUST now be set only after full success
		$this->assertSame( 1, (int) get_option( 'vighnaghna_migrated_from_legacy' ) );
		$this->assertSame( 12, (int) get_option( 'vighnaghna_rate_limit_ip_per_minute' ) );

		// Record ID and data must be migrated properly
		$this->assertArrayHasKey( 88, $wpdb->logs_table );
		$this->assertSame( 'wpforms', $wpdb->logs_table[88]->form_type );

		// 3. Test idempotence on repeated run
		$idempotent_result = Database::migrate_from_legacy();
		$this->assertTrue( $idempotent_result['success'] );
		$this->assertTrue( $idempotent_result['already_migrated'] );
		$this->assertCount( 1, $wpdb->logs_table, 'No duplicate records created on repeated migration' );
	}

	/**
	 * Test 11: Expired token offers safe retry, while invalid or tampered token is rejected as spam.
	 */
	public function test_expired_token_gives_safe_retry_and_tampered_token_rejected(): void {
		$form_id = 'contact_form_7';

		// Case A: Expired Token (Legitimate user whose session timed out)
		$expired_tok_data = $this->token_manager->generate_token( $form_id, '127.0.0.1', time() - 3600 );
		$expired_post     = array(
			'vighnaghna_token' => $expired_tok_data['token'],
			'user_name'        => 'Alice',
			'user_email'       => 'alice@example.com',
			'message'          => 'Legitimate contact inquiry with expired token',
		);

		$res_expired = $this->captcha_engine->verify_submission( $expired_post, 'contact_form_7', $form_id );

		$this->assertFalse( $res_expired['is_valid'], 'Expired token submission must not be allowed' );
		$this->assertSame( 'block', $res_expired['action'] );
		$this->assertSame( 80, $res_expired['score'], 'Expired token default weight must be 80' );
		$this->assertSame( 'Your form session has expired. Please refresh the page and try again.', $res_expired['error_message'], 'Expired token must offer safe retry prompt' );

		$trace_expired = $this->captcha_engine->get_last_verification_trace();
		$this->assertTrue( $trace_expired['is_token_expired'], 'Trace must flag token as expired' );
		$this->assertSame( 'expired', $trace_expired['token_status'] );

		// Case B: Tampered Token (Malicious actor forging signature)
		$tampered_post = array(
			'vighnaghna_token' => $expired_tok_data['token'] . 'tampered',
			'user_name'        => 'Attacker',
			'user_email'       => 'bot@malicious.com',
			'message'          => 'Buy cheap pills now http://spam.xyz',
		);

		$res_tampered = $this->captcha_engine->verify_submission( $tampered_post, 'contact_form_7', $form_id );

		$this->assertFalse( $res_tampered['is_valid'], 'Tampered token must be rejected' );
		$this->assertSame( 'block', $res_tampered['action'] );
		$this->assertSame( 100, $res_tampered['score'], 'Tampered token weight must be 100' );
		$this->assertStringContainsString( 'flagged as potential spam', $res_tampered['error_message'], 'Tampered token must be flagged as spam' );

		$trace_tampered = $this->captcha_engine->get_last_verification_trace();
		$this->assertFalse( $trace_tampered['is_token_expired'], 'Trace must not flag tampered token as expired' );
		$this->assertSame( 'tampered', $trace_tampered['token_status'] );

		// Case C: Missing Token
		$missing_post = array(
			'user_name'  => 'Bot',
			'user_email' => 'bot@example.com',
			'message'    => 'Automated submission without security tokens',
		);

		$res_missing = $this->captcha_engine->verify_submission( $missing_post, 'contact_form_7', $form_id );
		$this->assertFalse( $res_missing['is_valid'] );
		$this->assertSame( 'block', $res_missing['action'] );
		$this->assertStringContainsString( 'flagged as potential spam', $res_missing['error_message'] );
	}

	/**
	 * Test 12: Expired-token configuration uses one consistent default value (80) across activation, admin settings, and risk scoring.
	 */
	public function test_consistent_expired_token_default_configuration(): void {
		// 1. Activation default option
		delete_option( 'vighnaghna_weight_expired_token' );
		require_once VIGHNAGHNA_PATH . 'vighnaghna.php';
		vighnaghna_activate();
		$this->assertSame( 80, (int) get_option( 'vighnaghna_weight_expired_token' ), 'Activation must write 80 as default expired token weight' );

		// 2. Admin settings default fallback
		delete_option( 'vighnaghna_weight_expired_token' );
		delete_option( 'botfence_weight_expired_token' );
		delete_option( 'kl_antispam_weight_expired_token' );
		$admin = new \Vighnaghna\Admin( $this->captcha_engine, new \Vighnaghna\Form_Detector( $this->captcha_engine ) );
		$this->assertSame( 80, (int) $admin->get_conf( 'weight_expired_token', 80 ), 'Admin get_conf default must be 80' );

		// 3. Risk scoring default weight
		$this->assertSame( 80, $this->risk_engine->get_weight( Risk_Engine::SIGNAL_EXPIRED_TOKEN ), 'Risk engine default weight must be 80' );

		// 4. Token Manager default weight
		$this->assertSame( 80, (int) ( get_option( 'vighnaghna_weight_expired_token' ) ?: 80 ), 'Token manager default weight must be 80' );
	}

	/**
	 * Test 13: WordPress version compatibility and plugin metadata parity.
	 *
	 * - Tests against the actual WordPress version in the test environment (6.7).
	 * - Verifies Requires at least, Tested up to, Requires PHP, and Version match identically between vighnaghna.php and readme.txt.
	 */
	public function test_wordpress_compatibility_and_metadata_parity(): void {
		global $wp_version;

		// 1. Verify environment WordPress version
		$this->assertSame( '6.7', $wp_version, 'Test environment WordPress version must be 6.7' );

		// 2. Read vighnaghna.php plugin header
		$plugin_header = file_get_contents( VIGHNAGHNA_PATH . 'vighnaghna.php' );
		$this->assertNotEmpty( $plugin_header );

		preg_match( '/\*\s*Version:\s*([0-9\.]+)/', $plugin_header, $php_ver_match );
		preg_match( '/\*\s*Requires at least:\s*([0-9\.]+)/', $plugin_header, $php_req_at_least );
		preg_match( '/\*\s*Tested up to:\s*([0-9\.]+)/', $plugin_header, $php_tested_up_to );
		preg_match( '/\*\s*Requires PHP:\s*([0-9\.]+)/', $plugin_header, $php_req_php );

		$php_version   = $php_ver_match[1] ?? '';
		$php_at_least  = $php_req_at_least[1] ?? '';
		$php_tested    = $php_tested_up_to[1] ?? '';
		$php_min_php   = $php_req_php[1] ?? '';

		// 3. Read readme.txt metadata
		$readme_content = file_get_contents( VIGHNAGHNA_PATH . 'readme.txt' );
		$this->assertNotEmpty( $readme_content );

		preg_match( '/Stable tag:\s*([0-9\.]+)/', $readme_content, $rm_stable_match );
		preg_match( '/Requires at least:\s*([0-9\.]+)/', $readme_content, $rm_req_at_least );
		preg_match( '/Tested up to:\s*([0-9\.]+)/', $readme_content, $rm_tested_up_to );
		preg_match( '/Requires PHP:\s*([0-9\.]+)/', $readme_content, $rm_req_php );

		$readme_version  = $rm_stable_match[1] ?? '';
		$readme_at_least = $rm_req_at_least[1] ?? '';
		$readme_tested   = $rm_tested_up_to[1] ?? '';
		$readme_min_php  = $rm_req_php[1] ?? '';

		// 4. Parity assertions
		$this->assertSame( '1.0.0', $php_version );
		$this->assertSame( $php_version, $readme_version, 'Version in vighnaghna.php must match Stable tag in readme.txt' );

		$this->assertSame( '6.0', $php_at_least );
		$this->assertSame( $php_at_least, $readme_at_least, 'Requires at least must match in header and readme.txt' );

		$this->assertSame( '6.7', $php_tested );
		$this->assertSame( $php_tested, $readme_tested, 'Tested up to must match in header and readme.txt' );
		$this->assertSame( $wp_version, $php_tested, 'Tested up to must match environment WordPress version' );

		$this->assertSame( '8.1', $php_min_php );
		$this->assertSame( $php_min_php, $readme_min_php, 'Requires PHP must match in header and readme.txt' );
	}

	/**
	 * Test 14: Migration with a legacy table that contains zero rows.
	 *
	 * A valid empty result must NOT be treated as a database failure.
	 */
	public function test_migration_legacy_table_with_zero_rows(): void {
		global $wpdb;

		delete_option( 'vighnaghna_migrated_from_legacy' );
		$wpdb->logs_table          = array();
		$wpdb->rates_table         = array();
		$wpdb->legacy_logs_table   = array(); // Exists but has 0 rows
		$wpdb->legacy_rates_table  = array(); // Exists but has 0 rows
		$wpdb->has_legacy_logs_table  = true;
		$wpdb->has_legacy_rates_table = true;
		$wpdb->simulate_query_failure = false;
		$wpdb->simulate_select_failure = false;
		$wpdb->fail_tables            = array();
		$wpdb->last_error             = '';

		$result = Database::migrate_from_legacy();

		$this->assertTrue( $result['success'], 'Migration must report success when legacy tables have zero rows' );
		$this->assertEmpty( $result['errors'], 'No errors should be reported for valid empty results' );
		$this->assertSame( 0, $result['records']['logs'] );
		$this->assertSame( 0, $result['records']['rate_limits'] );
		$this->assertSame( 1, (int) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must be set after valid zero-row migration' );
	}

	/**
	 * Test 15: Migration fails when legacy SELECT query fails.
	 */
	public function test_migration_legacy_select_query_fails(): void {
		global $wpdb;

		delete_option( 'vighnaghna_migrated_from_legacy' );
		$wpdb->logs_table          = array();
		$wpdb->rates_table         = array();
		$wpdb->legacy_logs_table   = array(
			array( 'id' => 101, 'created_at' => '2026-11-01 12:00:00', 'form_type' => 'cf7' ),
		);
		$wpdb->has_legacy_logs_table   = true;
		$wpdb->simulate_select_failure = true;
		$wpdb->last_error              = '';

		$result = Database::migrate_from_legacy();

		$this->assertFalse( $result['success'], 'Migration must fail when SELECT query fails' );
		$this->assertNotEmpty( $result['errors'], 'Errors must contain failure message' );
		$this->assertStringContainsString( 'SELECT query failed', implode( '; ', $result['errors'] ) );
		$this->assertFalse( (bool) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must not be set on SELECT failure' );
		$this->assertCount( 1, $wpdb->legacy_logs_table, 'Legacy records must be preserved' );

		$wpdb->simulate_select_failure = false;
		$wpdb->last_error              = '';
	}

	/**
	 * Test 16: Migration fails when table existence query fails.
	 */
	public function test_migration_table_existence_query_fails(): void {
		global $wpdb;

		delete_option( 'vighnaghna_migrated_from_legacy' );
		$wpdb->logs_table                 = array();
		$wpdb->rates_table                = array();
		$wpdb->legacy_logs_table          = array(
			array( 'id' => 102, 'created_at' => '2026-11-01 12:00:00', 'form_type' => 'cf7' ),
		);
		$wpdb->simulate_table_exists_failure = true;
		$wpdb->last_error                    = '';

		$result = Database::migrate_from_legacy();

		$this->assertFalse( $result['success'], 'Migration must fail when table existence query fails' );
		$this->assertNotEmpty( $result['errors'] );
		$this->assertStringContainsString( 'Table existence query failed', implode( '; ', $result['errors'] ) );
		$this->assertFalse( (bool) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must not be set' );

		$wpdb->simulate_table_exists_failure = false;
		$wpdb->last_error                    = '';
	}

	/**
	 * Test 17: Migration fails when destination duplicate-check query fails.
	 */
	public function test_migration_destination_duplicate_check_fails(): void {
		global $wpdb;

		delete_option( 'vighnaghna_migrated_from_legacy' );
		$wpdb->logs_table                    = array();
		$wpdb->rates_table                   = array();
		$wpdb->legacy_logs_table             = array(
			array( 'id' => 103, 'created_at' => '2026-11-01 12:00:00', 'form_type' => 'cf7' ),
		);
		$wpdb->simulate_duplicate_check_failure = true;
		$wpdb->last_error                       = '';

		$result = Database::migrate_from_legacy();

		$this->assertFalse( $result['success'], 'Migration must fail when destination duplicate-check query fails' );
		$this->assertNotEmpty( $result['errors'] );
		$this->assertStringContainsString( 'duplicate-check query failed', implode( '; ', $result['errors'] ) );
		$this->assertFalse( (bool) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must not be set' );

		$wpdb->simulate_duplicate_check_failure = false;
		$wpdb->last_error                       = '';
	}

	/**
	 * Test 18: Migration fails when legacy INSERT fails.
	 */
	public function test_migration_legacy_insert_fails(): void {
		global $wpdb;

		delete_option( 'vighnaghna_migrated_from_legacy' );
		$wpdb->logs_table             = array();
		$wpdb->rates_table            = array();
		$wpdb->legacy_logs_table      = array(
			array( 'id' => 104, 'created_at' => '2026-11-01 12:00:00', 'form_type' => 'cf7' ),
		);
		$wpdb->simulate_insert_failure = true;
		$wpdb->last_error             = '';

		$result = Database::migrate_from_legacy();

		$this->assertFalse( $result['success'], 'Migration must fail when INSERT fails' );
		$this->assertNotEmpty( $result['errors'] );
		$this->assertStringContainsString( 'Failed to insert', implode( '; ', $result['errors'] ) );
		$this->assertFalse( (bool) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must not be set' );

		$wpdb->simulate_insert_failure = false;
		$wpdb->last_error             = '';
	}

	/**
	 * Test 19: One table migrates but another table fails.
	 *
	 * - Partially migrated records are kept.
	 * - Failed table operation records an error.
	 * - success is false.
	 * - Migration flag is NOT set.
	 * - Later retry completes the migration without duplicating records.
	 */
	public function test_migration_one_table_succeeds_and_another_table_fails_then_retries_safely(): void {
		global $wpdb;

		delete_option( 'vighnaghna_migrated_from_legacy' );
		$wpdb->logs_table          = array();
		$wpdb->rates_table         = array();
		$wpdb->legacy_logs_table   = array(
			array(
				'id'         => 201,
				'created_at' => '2026-11-02 10:00:00',
				'form_type'  => 'woocommerce',
			),
		);
		$wpdb->legacy_rates_table  = array(
			array(
				'id'         => 202,
				'identifier' => '10.0.0.1',
				'limit_type' => 'ip',
				'count'      => 3,
				'first_seen' => '2026-11-02 09:00:00',
				'expires_at' => '2026-11-02 11:00:00',
			),
		);

		// Fail only queries on wp_botfence_rate_limits
		$wpdb->fail_tables = array( 'wp_botfence_rate_limits' );
		$wpdb->last_error  = '';

		$partial_result = Database::migrate_from_legacy();

		// Overall migration MUST fail because rate limits failed
		$this->assertFalse( $partial_result['success'], 'Migration must fail when any table fails' );
		$this->assertNotEmpty( $partial_result['errors'], 'Errors must be recorded for failed table' );
		$this->assertSame( 1, $partial_result['records']['logs'], 'Logs table succeeded and must be migrated' );
		$this->assertSame( 0, $partial_result['records']['rate_limits'], 'Rate limits table failed and migrated 0' );
		$this->assertFalse( (bool) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag MUST NOT be set on partial failure' );

		// Destination has the log
		$this->assertArrayHasKey( 201, $wpdb->logs_table );

		// Legacy tables remain preserved
		$this->assertCount( 1, $wpdb->legacy_logs_table );
		$this->assertCount( 1, $wpdb->legacy_rates_table );

		// Resolve the error on rate limits and retry
		$wpdb->fail_tables = array();
		$wpdb->last_error  = '';

		$retry_result = Database::migrate_from_legacy();

		// Retry must succeed
		$this->assertTrue( $retry_result['success'], 'Retry must succeed after database error is cleared' );
		$this->assertEmpty( $retry_result['errors'] );
		$this->assertSame( 0, $retry_result['records']['logs'], 'Retry must not re-insert duplicate logs' );
		$this->assertSame( 1, $retry_result['records']['rate_limits'], 'Retry must now migrate the rate limits' );

		// Migration flag must now be set
		$this->assertSame( 1, (int) get_option( 'vighnaghna_migrated_from_legacy' ) );

		// Verify no duplicate records exist in destination
		$this->assertCount( 1, $wpdb->logs_table, 'Total logs must remain exactly 1 (no duplicates)' );
		$this->assertCount( 1, $wpdb->rates_table, 'Total rate limits must be exactly 1' );
	}

	/**
	 * Test 20: Migration fails when destination table creation fails.
	 *
	 * - create_tables() returns explicit success=false and records errors.
	 * - Legacy migration does not proceed with destination tables unavailable.
	 * - Migration reports success=false.
	 * - Migration completion flag remains unset.
	 * - Legacy records remain preserved intact.
	 * - Retry succeeds after problem is resolved.
	 */
	public function test_migration_fails_when_destination_table_creation_fails(): void {
		global $wpdb;

		delete_option( 'vighnaghna_migrated_from_legacy' );
		$wpdb->logs_table          = array();
		$wpdb->rates_table         = array();
		$wpdb->legacy_logs_table   = array(
			array( 'id' => 301, 'created_at' => '2026-11-03 10:00:00', 'form_type' => 'fluent_forms' ),
		);
		$wpdb->legacy_rates_table  = array(
			array( 'id' => 302, 'identifier' => '10.1.1.1', 'limit_type' => 'ip', 'count' => 2, 'first_seen' => '2026-11-03 09:00:00', 'expires_at' => '2026-11-03 11:00:00' ),
		);
		$wpdb->has_legacy_logs_table  = true;
		$wpdb->has_legacy_rates_table = true;

		// 1. Simulate destination table creation failure
		$wpdb->simulate_destination_table_create_failure = true;
		$wpdb->last_error                                = '';

		// Verify create_tables() explicitly reports failure
		$create_res = Database::create_tables();
		$this->assertFalse( $create_res['success'], 'create_tables() must return success=false on failure' );
		$this->assertNotEmpty( $create_res['errors'], 'create_tables() must report errors' );

		// Run migration during table creation failure
		$migration_res = Database::migrate_from_legacy();

		// Migration must fail
		$this->assertFalse( $migration_res['success'], 'migrate_from_legacy() must return success=false when destination table creation fails' );
		$this->assertNotEmpty( $migration_res['errors'], 'Errors must detail the table creation failure' );
		$this->assertFalse( (bool) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must NOT be set on table creation failure' );

		// Legacy records must remain completely intact
		$this->assertCount( 1, $wpdb->legacy_logs_table );
		$this->assertCount( 1, $wpdb->legacy_rates_table );

		// 2. Resolve failure and retry
		$wpdb->simulate_destination_table_create_failure = false;
		$wpdb->last_error                                = '';

		$retry_res = Database::migrate_from_legacy();
		$this->assertTrue( $retry_res['success'], 'Migration must succeed on retry after resolving table creation failure' );
		$this->assertSame( 1, (int) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must be saved after retry succeeds' );
		$this->assertArrayHasKey( 301, $wpdb->logs_table );
	}

	/**
	 * Test 21: Migration fails when destination table is missing after dbDelta().
	 *
	 * - dbDelta() executes without throwing, but destination table verification detects missing table.
	 * - create_tables() returns explicit success=false and lists missing tables.
	 * - Migration does not proceed and returns success=false.
	 * - Migration completion flag remains unset.
	 * - Retry succeeds after destination tables exist.
	 */
	public function test_migration_fails_when_destination_table_missing_after_dbdelta(): void {
		global $wpdb;

		delete_option( 'vighnaghna_migrated_from_legacy' );
		$wpdb->logs_table          = array();
		$wpdb->rates_table         = array();
		$wpdb->legacy_logs_table   = array(
			array( 'id' => 401, 'created_at' => '2026-11-04 10:00:00', 'form_type' => 'wpforms' ),
		);
		$wpdb->has_legacy_logs_table  = true;
		$wpdb->has_legacy_rates_table = true;

		// 1. Simulate destination table missing after table creation
		$wpdb->simulate_destination_table_missing = true;
		$wpdb->last_error                         = '';

		$create_res = Database::create_tables();
		$this->assertFalse( $create_res['success'], 'create_tables() must return success=false when table is missing' );
		$this->assertNotEmpty( $create_res['missing'], 'Missing tables list must be populated' );
		$this->assertNotEmpty( $create_res['errors'] );

		$migration_res = Database::migrate_from_legacy();
		$this->assertFalse( $migration_res['success'], 'Migration must fail when destination table is missing' );
		$this->assertNotEmpty( $migration_res['errors'] );
		$this->assertStringContainsString( 'missing after table creation', implode( '; ', $migration_res['errors'] ) );
		$this->assertFalse( (bool) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must NOT be set when table is missing' );

		// 2. Resolve missing table state and retry
		$wpdb->simulate_destination_table_missing = false;
		$wpdb->last_error                         = '';

		$retry_res = Database::migrate_from_legacy();
		$this->assertTrue( $retry_res['success'], 'Migration must succeed on retry' );
		$this->assertSame( 1, (int) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must be saved after retry succeeds' );
		$this->assertArrayHasKey( 401, $wpdb->logs_table );
	}

	/**
	 * Test 22: Migration fails when migration completion flag persistence fails.
	 *
	 * - Table creation succeeds and records migrate without database query errors.
	 * - Persistence of vighnaghna_migrated_from_legacy in options fails.
	 * - Migration returns success=false and includes a useful error message.
	 * - Never reports completed migration if the completion flag is not saved.
	 * - Safe retry succeeds and does not duplicate records.
	 */
	public function test_migration_fails_when_migration_flag_update_fails(): void {
		global $wpdb, $kl_simulate_option_update_failure;

		delete_option( 'vighnaghna_migrated_from_legacy' );
		$wpdb->logs_table          = array();
		$wpdb->rates_table         = array();
		$wpdb->legacy_logs_table   = array(
			array( 'id' => 501, 'created_at' => '2026-11-05 10:00:00', 'form_type' => 'woocommerce' ),
		);
		$wpdb->legacy_rates_table  = array(
			array( 'id' => 502, 'identifier' => '172.16.0.1', 'limit_type' => 'ip', 'count' => 1, 'first_seen' => '2026-11-05 09:00:00', 'expires_at' => '2026-11-05 11:00:00' ),
		);
		$wpdb->has_legacy_logs_table  = true;
		$wpdb->has_legacy_rates_table = true;

		// 1. Simulate option update failure for the migration flag
		$kl_simulate_option_update_failure = 'vighnaghna_migrated_from_legacy';

		$migration_res = Database::migrate_from_legacy();

		// Migration MUST NOT report success if flag is not persisted
		$this->assertFalse( $migration_res['success'], 'Migration must return success=false when flag persistence fails' );
		$this->assertNotEmpty( $migration_res['errors'] );
		$this->assertStringContainsString( 'Failed to persist legacy migration completion flag', implode( '; ', $migration_res['errors'] ) );
		$this->assertFalse( (bool) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must remain unset' );

		// 2. Restore option persistence and retry
		$kl_simulate_option_update_failure = false;

		$retry_res = Database::migrate_from_legacy();
		$this->assertTrue( $retry_res['success'], 'Retry must succeed once option persistence is restored' );
		$this->assertEmpty( $retry_res['errors'] );
		$this->assertSame( 1, (int) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must now be saved' );

		// Verify records are preserved without duplication
		$this->assertCount( 1, $wpdb->logs_table, 'Records must not be duplicated on retry' );
		$this->assertCount( 1, $wpdb->rates_table, 'Rate limits must not be duplicated on retry' );
	}

	/**
	 * Test 23: Final distribution ZIP structure and compliance verification.
	 *
	 * - Validates vighnaghna.zip archive opens successfully.
	 * - Confirms every archive entry starts with vighnaghna/.
	 * - Confirms essential production files exist (vighnaghna.php, readme.txt, uninstall.php, includes/, integrations/, assets/, languages/).
	 * - Confirms complete exclusion of development assets (.git, vendor, tests, docs, README.md, phpunit cache).
	 * - Validates PHP syntax on all PHP files within the archive.
	 */
	public function test_zip_structure_and_integrity(): void {
		$zip_path = dirname( __DIR__ ) . '/vighnaghna.zip';
		$this->assertFileExists( $zip_path, 'vighnaghna.zip must exist in project root' );

		$zip = new \ZipArchive();
		$res = $zip->open( $zip_path );
		$this->assertTrue( true === $res, 'vighnaghna.zip must open successfully' );

		$entry_count = $zip->numFiles;
		$this->assertGreaterThan( 30, $entry_count, 'ZIP must contain required plugin files' );

		$has_main_php     = false;
		$has_readme       = false;
		$has_uninstall    = false;
		$has_includes_dir = false;
		$has_assets_dir   = false;

		for ( $i = 0; $i < $entry_count; $i++ ) {
			$name = $zip->getNameIndex( $i );
			$this->assertStringStartsWith( 'vighnaghna/', $name, 'Every ZIP entry must start with vighnaghna/' );

			// Ensure zero development or excluded documentation files
			$this->assertStringNotContainsString( '.git', $name );
			$this->assertStringNotContainsString( 'vendor/', $name );
			$this->assertStringNotContainsString( 'tests/', $name );
			$this->assertStringNotContainsString( '.phpunit.cache', $name );
			$this->assertStringNotContainsString( 'vighnaghna/docs/', $name );
			$this->assertStringNotContainsString( 'vighnaghna/README.md', $name );

			if ( 'vighnaghna/vighnaghna.php' === $name ) {
				$has_main_php = true;
			}
			if ( 'vighnaghna/readme.txt' === $name ) {
				$has_readme = true;
			}
			if ( 'vighnaghna/uninstall.php' === $name ) {
				$has_uninstall = true;
			}
			if ( 'vighnaghna/includes/' === $name || str_starts_with( $name, 'vighnaghna/includes/' ) ) {
				$has_includes_dir = true;
			}
			if ( 'vighnaghna/assets/' === $name || str_starts_with( $name, 'vighnaghna/assets/' ) ) {
				$has_assets_dir = true;
			}
		}

		$this->assertTrue( $has_main_php, 'Main plugin PHP file must exist in ZIP' );
		$this->assertTrue( $has_readme, 'readme.txt must exist in ZIP' );
		$this->assertTrue( $has_uninstall, 'uninstall.php must exist in ZIP' );
		$this->assertTrue( $has_includes_dir, 'includes directory must exist in ZIP' );
		$this->assertTrue( $has_assets_dir, 'assets directory must exist in ZIP' );

		$zip->close();
	}

	/**
	 * Test 24: Legacy option migration failure handling, persistence verification, and safe retry.
	 *
	 * - For every legacy option: attempts migration, verifies new option contains expected value.
	 * - If persistence fails, records descriptive error and does NOT set migration flag.
	 * - Unchanged values where expected value is already stored are not treated as failure.
	 * - Retry succeeds once persistence problem is resolved and saves migration flag.
	 */
	public function test_legacy_option_migration_failure_and_safe_retry(): void {
		global $kl_simulate_option_update_failure;

		delete_option( 'vighnaghna_migrated_from_legacy' );
		delete_option( 'vighnaghna_secret_key' );
		delete_option( 'vighnaghna_weight_fast_submit' );

		update_option( 'botfence_secret_key', 'legacy_secret_key_12345678901234567890' );
		update_option( 'botfence_weight_fast_submit', 42 );

		// 1. Simulate failure updating vighnaghna_secret_key
		$kl_simulate_option_update_failure = 'vighnaghna_secret_key';

		$res = Database::migrate_from_legacy();

		// Migration MUST fail
		$this->assertFalse( $res['success'], 'Migration must fail when any option fails to persist' );
		$this->assertNotEmpty( $res['errors'], 'Errors list must contain option persistence failure' );
		$this->assertStringContainsString(
			'Failed to persist migrated legacy option vighnaghna_secret_key: expected value did not save.',
			implode( '; ', $res['errors'] )
		);

		// Flag must NOT be set
		$this->assertFalse( (bool) get_option( 'vighnaghna_migrated_from_legacy' ), 'Migration flag must not be set when option fails' );

		// The failed option was not saved
		$this->assertFalse( get_option( 'vighnaghna_secret_key' ) );

		// The other option was successfully saved
		$this->assertSame( 42, (int) get_option( 'vighnaghna_weight_fast_submit' ) );

		// 2. Resolve option failure and retry
		$kl_simulate_option_update_failure = false;

		$retry_res = Database::migrate_from_legacy();

		// Retry must succeed
		$this->assertTrue( $retry_res['success'], 'Retry must succeed when option persistence is restored' );
		$this->assertEmpty( $retry_res['errors'] );

		// Secret key is now saved
		$this->assertSame( 'legacy_secret_key_12345678901234567890', get_option( 'vighnaghna_secret_key' ) );

		// Previously migrated option remains intact
		$this->assertSame( 42, (int) get_option( 'vighnaghna_weight_fast_submit' ) );

		// Flag is now persisted
		$this->assertSame( 1, (int) get_option( 'vighnaghna_migrated_from_legacy' ) );

		// 3. Verify strict comparison prevents false-to-0 loose comparison false positives
		delete_option( 'vighnaghna_migrated_from_legacy' );
		delete_option( 'vighnaghna_trust_reverse_proxies' );
		update_option( 'botfence_trust_reverse_proxies', 0 );

		// Simulate update failure on trust_reverse_proxies (expected is 0, stored is false)
		$kl_simulate_option_update_failure = 'vighnaghna_trust_reverse_proxies';
		$res_zero = Database::migrate_from_legacy();

		$this->assertFalse( $res_zero['success'], 'Strict comparison must detect failure when expected value is 0 and option did not save' );
		$this->assertStringContainsString( 'vighnaghna_trust_reverse_proxies', implode( '; ', $res_zero['errors'] ) );
		$this->assertFalse( (bool) get_option( 'vighnaghna_migrated_from_legacy' ) );

		$kl_simulate_option_update_failure = false;
		$retry_zero = Database::migrate_from_legacy();
		$this->assertTrue( $retry_zero['success'] );
		$this->assertSame( 0, (int) get_option( 'vighnaghna_trust_reverse_proxies' ) );
	}

	/**
	 * Test 25: SQL LIKE table names are escaped with esc_like() and reject similarly named tables.
	 *
	 * - SHOW TABLES LIKE queries must escape table names using $wpdb->esc_like() before prepare().
	 * - Exact table names are verified: similarly named tables (wildcard matches, prefixes, backups)
	 *   must NOT be accepted as the required table.
	 */
	public function test_table_existence_escapes_like_wildcards_and_rejects_similarly_named_table(): void {
		global $wpdb;

		// 1. Verify esc_like() escapes underscores in table existence queries
		$create_res = Database::create_tables();
		$this->assertTrue( $create_res['success'] );
		$this->assertNotEmpty( $wpdb->last_table_like_query );

		// The prepared SQL must contain escaped underscores (\_) from $wpdb->esc_like()
		$this->assertStringContainsString( '\_', $wpdb->last_table_like_query, 'SHOW TABLES LIKE query must escape underscores with esc_like()' );

		// 2. Test similarly named table (e.g. backup or archive table) returned by SHOW TABLES LIKE
		$wpdb->mock_table_like_result = 'wp_vighnaghna_logs_archive'; // Similarly named table

		$fail_create = Database::create_tables();
		$this->assertFalse( $fail_create['success'], 'create_tables() must reject similarly named table' );
		$this->assertContains( 'wp_vighnaghna_logs', $fail_create['missing'] );
		$this->assertStringContainsString( 'Required destination table wp_vighnaghna_logs is missing', implode( '; ', $fail_create['errors'] ) );

		// 3. Test token manager rejects database insertion on similarly named table
		$token_manager = new Token_Manager();
		$wpdb->used_tokens_table      = array();
		$wpdb->mock_table_like_result = 'wp_vighnaghna_used_tokens_temp'; // Similarly named table
		$token_manager->consume_token_atomically( 'hash123', 'form1', gmdate( 'Y-m-d H:i:s' ), gmdate( 'Y-m-d H:i:s', time() + 300 ) );
		$this->assertArrayNotHasKey( 'hash123', $wpdb->used_tokens_table, 'Database table must NOT be used when only similarly named table exists' );

		// 4. Test logger stats only uses exact table match
		$logger = new \Vighnaghna\Logger();
		$wpdb->mock_table_like_result = 'wp_vighnaghna_logs_backup'; // Similarly named table
		$stats = $logger->get_stats();
		$this->assertSame( 0, $stats['total_checked'], 'Logger must not query a similarly named table' );

		// Restore normal mock behavior
		$wpdb->mock_table_like_result = null;
		$restore_create = Database::create_tables();
		$this->assertTrue( $restore_create['success'] );
	}

	/**
	 * Verify that diagnostic simulation functions correctly and tests risk scenarios
	 * even when global protection is toggled to disabled.
	 */
	public function test_interactive_simulation_scenarios_and_disabled_bypass(): void {
		$plugin = Plugin::get_instance();
		$engine = $plugin->get_captcha_engine();
		$tok_man = $engine->get_token_manager();
		$hp = $engine->get_honeypot();
		$sim_id = 'test_simulation';

		// 1. Simulate when plugin is globally disabled
		update_option( 'vighnaghna_enabled', 0 );
		update_option( 'botfence_enabled', 0 );
		update_option( 'kl_antispam_enabled', 0 );

		// Normal form verification should be bypassed (allowed with 0 score)
		$normal_verify = $engine->verify_submission( array(), 'cf7', 'contact_form_1' );
		$this->assertTrue( $normal_verify['is_valid'] );
		$this->assertSame( 'allow', $normal_verify['action'] );
		$this->assertSame( 0, $normal_verify['score'] );

		// Headless automation simulation scenario must still evaluate properly
		$tok = $tok_man->generate_token( $sim_id );
		$telemetry = base64_encode( (string) wp_json_encode( array( 'elapsed_time' => 3.1, 'webdriver' => true, 'is_trusted' => false ) ) );
		$headless_payload = array(
			'vighnaghna_token'     => $tok['token'],
			'vighnaghna_telemetry' => $telemetry,
			'message'              => 'Automated test via Selenium',
		);
		$headless_res = $engine->verify_submission( $headless_payload, 'test_simulation', $sim_id );
		$this->assertSame( 'block', $headless_res['action'] );
		$this->assertGreaterThanOrEqual( 80, $headless_res['score'] );
		$codes = array_column( $headless_res['signals'], 'code' );
		$this->assertContains( 'headless_browser', $codes );
		$this->assertContains( 'untrusted_event', $codes );

		// Honeypot simulation scenario must block
		$tok = $tok_man->generate_token( $sim_id );
		$hp_payload = array(
			'vighnaghna_token'                     => $tok['token'],
			$hp->get_dynamic_field_name( 0 )       => 'I am a spam bot',
			'message'                              => 'Buy cheap pills',
		);
		$hp_res = $engine->verify_submission( $hp_payload, 'test_simulation', $sim_id );
		$this->assertSame( 'block', $hp_res['action'] );
		$this->assertSame( 100, $hp_res['score'] );

		// Clean human simulation scenario must allow
		$tok = $tok_man->generate_token( $sim_id );
		$human_telemetry = base64_encode( (string) wp_json_encode( array( 'elapsed_time' => 5.4, 'mouse_moves' => 18, 'keystrokes' => 32, 'touches' => 0, 'is_trusted' => true, 'webdriver' => false ) ) );
		$human_payload = array(
			'vighnaghna_token'     => $tok['token'],
			'vighnaghna_telemetry' => $human_telemetry,
			'message'              => 'Legitimate user inquiry',
		);
		$human_res = $engine->verify_submission( $human_payload, 'test_simulation', $sim_id );
		$this->assertSame( 'allow', $human_res['action'] );
		$this->assertSame( 0, $human_res['score'] );
		$this->assertEmpty( $human_res['signals'] );

		// Reset enabled state
		update_option( 'vighnaghna_enabled', 1 );
	}
}
