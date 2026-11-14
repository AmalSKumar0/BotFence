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
}
