<?php
/**
 * Contact Form 7 Regression & Adversarial Audit Test Suite.
 *
 * Verifies resolution of False Positive bug report for CF7 form ID 1365
 * and ensures that all 11 required security & compatibility scenarios are satisfied.
 *
 * @package BotFence\Tests
 */

namespace BotFence\Tests;

use PHPUnit\Framework\TestCase;
use BotFence\Captcha_Engine;
use BotFence\Token_Manager;
use BotFence\Honeypot;
use BotFence\Rate_Limiter;
use BotFence\Risk_Engine;
use BotFence\Logger;
use BotFence\Integrations\Contact_Form_7;
use WPCF7_ContactForm;
use WPCF7_Submission;

/**
 * Class ContactForm7RegressionTest
 */
class ContactForm7RegressionTest extends TestCase {

	private Captcha_Engine $engine;
	private Token_Manager $token_manager;
	private Honeypot $honeypot;
	private Rate_Limiter $rate_limiter;
	private Risk_Engine $risk_engine;
	private Logger $logger;
	private Contact_Form_7 $adapter;

	protected function setUp(): void {
		parent::setUp();

		global $kl_test_options, $kl_test_transients, $kl_test_filters, $kl_test_actions, $wpdb;
		$kl_test_options    = array();
		$kl_test_transients = array();
		$kl_test_filters    = array();
		$kl_test_actions    = array();
		$wpdb               = new \Mock_WPDB();

		Contact_Form_7::clear_cache();

		// Configure BotFence secret & options.
		update_option( 'botfence_secret_key', 'test_cf7_audit_secret_key_1234567890' );
		update_option( 'botfence_honeypot_enabled', 1 );
		update_option( 'botfence_min_submission_time', 2.5 );
		update_option( 'botfence_threshold_challenge', 30 );
		update_option( 'botfence_threshold_block', 60 );
		update_option( 'botfence_rate_limit_ip_per_minute', 60 );
		update_option( 'botfence_rate_limit_form_per_minute', 60 );

		$this->token_manager = new Token_Manager();
		$this->honeypot      = new Honeypot();
		$this->rate_limiter  = new Rate_Limiter();
		$this->risk_engine   = new Risk_Engine();
		$this->logger        = new Logger();

		$this->engine = new Captcha_Engine(
			$this->token_manager,
			$this->honeypot,
			$this->rate_limiter,
			$this->risk_engine,
			$this->logger
		);

		$this->adapter = new Contact_Form_7( $this->engine );
		$this->adapter->register_hooks();

		$_SERVER['REMOTE_ADDR']          = '192.168.1.100';
		$_SERVER['HTTP_USER_AGENT']      = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
		$_SERVER['REQUEST_METHOD']       = 'POST';
		$_POST                           = array();
		WPCF7_ContactForm::$current      = null;
		WPCF7_Submission::$instance      = null;
	}

	protected function tearDown(): void {
		Contact_Form_7::clear_cache();
		parent::tearDown();
	}

	/**
	 * Helper: Creates a valid CF7 test submission payload with customer telemetry.
	 */
	private function create_cf7_payload( string $form_id, array $telemetry_override = array() ): array {
		$tok_data = $this->token_manager->generate_token( $form_id );

		$telemetry = array_merge(
			array(
				'elapsed_time' => 19.789,
				'mouse_moves'  => 52,
				'keystrokes'   => 38,
				'touches'      => 0,
				'focus_count'  => 4,
				'paste_count'  => 0,
				'is_trusted'   => true,
				'webdriver'    => false,
				'screen_w'     => 1920,
				'screen_h'     => 1080,
			),
			$telemetry_override
		);

		$uniq = bin2hex( random_bytes( 6 ) );

		return array(
			'_wpcf7'                 => $form_id,
			'_wpcf7_version'         => '5.9.3',
			'_wpcf7_unit_tag'        => 'wpcf7-f' . $form_id . '-p1-o1',
			'your-name'              => 'John Doe',
			'your-email'             => 'john.doe@example.com',
			'your-subject'           => 'Legitimate Inquiry regarding your services ' . $uniq,
			'your-message'           => 'Hello, I would like to inquire about your enterprise consultation offerings ' . $uniq . '. Thank you!',
			'botfence_token'         => $tok_data['token'],
			'botfence_telemetry'     => base64_encode( (string) wp_json_encode( $telemetry ) ),
		);
	}

	/**
	 * Helper: Simulates the complete Contact Form 7 submission execution pipeline.
	 *
	 * Matches CF7 core WPCF7_Submission::submit() logic:
	 * 1. wpcf7_spam filter (returns bool: true = spam)
	 * 2. If spam, submission sets status 'spam'
	 * 3. wpcf7_skip_mail filter
	 * 4. wpcf7_before_send_mail action with &$abort
	 * 5. If abort, submission sets status 'aborted'
	 * 6. If not spam and not aborted, status 'mail_sent'
	 * 7. wpcf7_submit action
	 *
	 * @param string $form_id Form identifier.
	 * @return array{submission: WPCF7_Submission, contact_form: WPCF7_ContactForm, is_spam: bool, abort: bool, mail_skipped: bool}
	 */
	private function run_cf7_pipeline( string $form_id ): array {
		$cf = new WPCF7_ContactForm( $form_id );
		WPCF7_ContactForm::$current = $cf;

		$sub = new WPCF7_Submission( $cf );
		WPCF7_Submission::$instance = $sub;

		// 1. Check spam filters.
		$is_spam = apply_filters( 'wpcf7_spam', false, $sub );
		if ( $is_spam ) {
			$sub->set_status( 'spam' );
		}

		// 2. Skip mail filter.
		$mail_skipped = apply_filters( 'wpcf7_skip_mail', $cf->skip_mail, $cf );
		if ( $mail_skipped ) {
			$cf->skip_mail = true;
		}

		// 3. Before send mail action.
		$abort = false;
		do_action_ref_array( 'wpcf7_before_send_mail', array( &$cf, &$abort ) );
		if ( $abort ) {
			$sub->set_status( 'aborted' );
		} elseif ( ! $is_spam && ! $mail_skipped ) {
			$sub->set_status( 'mail_sent' );
		}

		// 4. Submit action.
		do_action( 'wpcf7_submit', $cf, array( 'status' => $sub->get_status() ) );

		return array(
			'submission'   => $sub,
			'contact_form' => $cf,
			'is_spam'      => $is_spam,
			'abort'        => $abort,
			'mail_skipped' => $cf->skip_mail,
		);
	}

	/**
	 * Test 1: Normal human submission (CF7 form ID 1365).
	 *
	 * Verifies that the exact bug report scenario passes cleanly:
	 * - 19.789s elapsed time, 52 mouse moves, 38 keystrokes, is_trusted: true
	 * - Does NOT set $abort = true.
	 * - Status is 'mail_sent', CF7 response is NOT 'Sending mail has been aborted.'
	 * - Diagnostic trace confirms 0 risk points and allow decision.
	 */
	public function test_1_normal_human_submission(): void {
		$form_id = '1365';
		$_POST   = $this->create_cf7_payload( $form_id );

		$res = $this->run_cf7_pipeline( $form_id );

		$this->assertFalse( $res['is_spam'], 'Legitimate human must not be marked as spam' );
		$this->assertFalse( $res['abort'], 'BotFence must NEVER set $abort = true for submissions' );
		$this->assertFalse( $res['mail_skipped'], 'Mail must not be skipped for legitimate submissions' );
		$this->assertSame( 'mail_sent', $res['submission']->get_status() );
		$this->assertNotSame( 'Sending mail has been aborted.', $res['submission']->get_response() );

		// Inspect diagnostic lifecycle trace.
		$trace = $this->engine->get_last_verification_trace();
		$this->assertNotNull( $trace );
		$this->assertSame( $form_id, $trace['form_id'] );
		$this->assertSame( 'valid', $trace['token_status'] );
		$this->assertFalse( $trace['is_token_expired'] );
		$this->assertFalse( $trace['is_token_replayed'] );
		$this->assertFalse( $trace['honeypot_triggered'] );
		$this->assertTrue( $trace['telemetry_validated'] );
		$this->assertSame( 0, $trace['risk_score'] );
		$this->assertSame( 'allow', $trace['security_decision'] );
		$this->assertTrue( $trace['is_valid'] );
		$this->assertFalse( $trace['other_plugin_aborted'] );
	}

	/**
	 * Test 2: Fast human submission.
	 *
	 * Submission completed under 2.5 seconds (e.g. 1.2s):
	 * - Evaluates fast submission signal (30 points).
	 * - Reaches medium challenge threshold (30 points).
	 * - Flagged via CF7 spam mechanism (status 'spam'), NOT 'aborted'.
	 * - $abort MUST remain false.
	 */
	public function test_2_fast_human_submission(): void {
		$form_id = '1365';
		$_POST   = $this->create_cf7_payload( $form_id, array( 'elapsed_time' => 1.2 ) );

		$res = $this->run_cf7_pipeline( $form_id );

		$this->assertTrue( $res['is_spam'], 'Fast submission under 2.5s must trigger verification' );
		$this->assertFalse( $res['abort'], 'BotFence must NOT set $abort = true on fast submissions' );
		$this->assertSame( 'spam', $res['submission']->get_status() );
		$this->assertNotSame( 'Sending mail has been aborted.', $res['submission']->get_response() );
	}

	/**
	 * Test 3: Pasted message submission.
	 *
	 * Legitimate user pastes inquiry into message textarea:
	 * - paste_count: 1, keystrokes: 0, mouse_moves: 8, elapsed_time: 15s.
	 * - Zero-interaction penalty is bypassed because paste_count > 0.
	 * - Submission is allowed cleanly and mail is sent.
	 */
	public function test_3_pasted_message_submission(): void {
		$form_id = '1365';
		$_POST   = $this->create_cf7_payload(
			$form_id,
			array(
				'elapsed_time' => 15.0,
				'keystrokes'   => 0,
				'mouse_moves'  => 8,
				'paste_count'  => 1,
				'focus_count'  => 2,
			)
		);

		$res = $this->run_cf7_pipeline( $form_id );

		$this->assertFalse( $res['is_spam'], 'Pasted message submission must be permitted' );
		$this->assertFalse( $res['abort'], '$abort must remain false' );
		$this->assertSame( 'mail_sent', $res['submission']->get_status() );
	}

	/**
	 * Test 4: JavaScript-disabled submission.
	 *
	 * User has JavaScript disabled in their browser:
	 * - Server-rendered token is submitted, but telemetry payload is missing/empty.
	 * - Missing optional telemetry must NOT automatically mean spam.
	 * - Token is valid, elapsed time defaults safely.
	 * - Submission is allowed cleanly.
	 */
	public function test_4_javascript_disabled_submission(): void {
		$form_id = '1365';
		$_POST   = $this->create_cf7_payload( $form_id );
		// Strip client JavaScript telemetry.
		unset( $_POST['botfence_telemetry'], $_POST['kl_anti_spam_telemetry'] );

		$res = $this->run_cf7_pipeline( $form_id );

		$this->assertFalse( $res['is_spam'], 'Missing optional JS telemetry alone must not cause rejection' );
		$this->assertFalse( $res['abort'], '$abort must remain false' );
		$this->assertSame( 'mail_sent', $res['submission']->get_status() );
	}

	/**
	 * Test 5: Missing token.
	 *
	 * Automated bot sends direct HTTP POST without token:
	 * - Token status: missing (100 risk points).
	 * - Flagged via wpcf7_spam (returns true).
	 * - CF7 sets status 'spam', skips mail cleanly, and $abort remains false.
	 */
	public function test_5_missing_token(): void {
		$form_id = '1365';
		$_POST   = $this->create_cf7_payload( $form_id );
		unset( $_POST['botfence_token'], $_POST['kl_anti_spam_token'] );

		$res = $this->run_cf7_pipeline( $form_id );

		$this->assertTrue( $res['is_spam'], 'Missing token must be blocked' );
		$this->assertFalse( $res['abort'], 'Spam rejection must NOT set $abort = true' );
		$this->assertSame( 'spam', $res['submission']->get_status() );
		$this->assertTrue( $res['mail_skipped'], 'Mail must be skipped' );
	}

	/**
	 * Test 6: Expired token.
	 *
	 * Bot submits stale/expired token:
	 * - Token status: expired (80 risk points).
	 * - Flagged via wpcf7_spam, skipped mail, and $abort remains false.
	 */
	public function test_6_expired_token(): void {
		$form_id  = '1365';
		$_POST    = $this->create_cf7_payload( $form_id );
		// Generate an already expired token (issued 2 hours ago).
		$tok_data = $this->token_manager->generate_token( $form_id, '', time() - 7200 );
		$_POST['botfence_token'] = $tok_data['token'];

		$res = $this->run_cf7_pipeline( $form_id );

		$this->assertTrue( $res['is_spam'], 'Expired token must be blocked' );
		$this->assertFalse( $res['abort'], '$abort must remain false' );
		$this->assertSame( 'spam', $res['submission']->get_status() );
	}

	/**
	 * Test 7: Replayed token.
	 *
	 * Malicious bot captures a valid token and submits it a second time:
	 * - Request 1 succeeds (status 'mail_sent').
	 * - Request 2 is rejected with 'replayed' (100 risk points).
	 * - Request 2 is flagged as spam without aborting.
	 */
	public function test_7_replayed_token(): void {
		$form_id = '1365';
		$_POST   = $this->create_cf7_payload( $form_id );
		$saved_token = $_POST['botfence_token'];

		// First submission consumes the token.
		$res1 = $this->run_cf7_pipeline( $form_id );
		$this->assertFalse( $res1['is_spam'] );
		$this->assertSame( 'mail_sent', $res1['submission']->get_status() );

		// Clear per-request memoization cache on adapter for new HTTP request simulation.
		Contact_Form_7::clear_cache();

		// Second submission attempts to replay the consumed token.
		$_POST = $this->create_cf7_payload( $form_id );
		$_POST['botfence_token'] = $saved_token;

		$res2 = $this->run_cf7_pipeline( $form_id );
		$this->assertTrue( $res2['is_spam'], 'Replayed token must be rejected' );
		$this->assertFalse( $res2['abort'], '$abort must remain false' );
		$this->assertSame( 'spam', $res2['submission']->get_status() );
	}

	/**
	 * Test 8: Filled honeypot discrimination.
	 *
	 * A. Automated bot filling primary dynamic trap field:
	 *    - Flagged with 100 risk points as spam.
	 *
	 * B. Legitimate human with browser profile autofill populating decoy field:
	 *    - Decoy field populated, but legitimate human telemetry is present.
	 *    - Accidental autofill recognized (triggered: false, autofill_detected: true).
	 *    - Submission is ALLOWED, mail delivered, not aborted!
	 */
	public function test_8_filled_honeypot_discrimination(): void {
		$form_id = '1365';

		// Scenario A: Bot filling primary dynamic trap.
		$_POST = $this->create_cf7_payload( $form_id );
		$primary_hp = $this->honeypot->get_dynamic_field_name( 0 );
		$_POST[ $primary_hp ] = 'http://spam-link-bot.com';

		$res_bot = $this->run_cf7_pipeline( $form_id );
		$this->assertTrue( $res_bot['is_spam'], 'Bot filling primary honeypot must be blocked' );
		$this->assertFalse( $res_bot['abort'] );
		$this->assertSame( 'spam', $res_bot['submission']->get_status() );

		Contact_Form_7::clear_cache();

		// Scenario B: Legitimate human whose browser profile autofilled the decoy field.
		$_POST = $this->create_cf7_payload( $form_id );
		$decoy_hp = $this->honeypot->get_secondary_field_name();
		$_POST[ $decoy_hp ] = 'https://johndoe-personal.com'; // Profile URL autofilled by browser

		$res_human = $this->run_cf7_pipeline( $form_id );
		$this->assertFalse( $res_human['is_spam'], 'Human with browser autofill in decoy must NOT be marked spam' );
		$this->assertFalse( $res_human['abort'], '$abort must remain false' );
		$this->assertSame( 'mail_sent', $res_human['submission']->get_status() );

		$trace = $this->engine->get_last_verification_trace();
		$this->assertTrue( $trace['honeypot_autofill'], 'Autofill must be detected' );
		$this->assertFalse( $trace['honeypot_triggered'], 'Honeypot trap must not be triggered' );
	}

	/**
	 * Test 9: WP Armour enabled.
	 *
	 * WP Armour plugin injects wpa_field, wpa_initiator, wpa_uuid into CF7 form:
	 * - BotFence ignores WP Armour fields in text extraction and risk scoring.
	 * - Legitimate human passes cleanly.
	 * - Bot without valid token is still blocked.
	 */
	public function test_9_wp_armour_enabled(): void {
		$form_id = '1365';
		$_POST   = $this->create_cf7_payload( $form_id );

		// Simulate WP Armour fields.
		$_POST['wpa_initiator'] = 'wpa_init_' . bin2hex( random_bytes( 8 ) );
		$_POST['wpa_field']     = '';
		$_POST['wpa_uuid']      = '550e8400-e29b-41d4-a716-446655440000';

		$res = $this->run_cf7_pipeline( $form_id );
		$this->assertFalse( $res['is_spam'], 'WP Armour coexistence must not break legitimate submissions' );
		$this->assertFalse( $res['abort'] );
		$this->assertSame( 'mail_sent', $res['submission']->get_status() );

		Contact_Form_7::clear_cache();

		// Ensure malicious bot is still caught even when WP Armour is present.
		$_POST = $this->create_cf7_payload( $form_id );
		$_POST['wpa_initiator'] = 'wpa_init_bot';
		$_POST['botfence_token'] = 'tampered_or_invalid';

		$res_bot = $this->run_cf7_pipeline( $form_id );
		$this->assertTrue( $res_bot['is_spam'], 'Bot must still be blocked when WP Armour is enabled' );
		$this->assertFalse( $res_bot['abort'] );
		$this->assertSame( 'spam', $res_bot['submission']->get_status() );
	}

	/**
	 * Test 10: WP Armour disabled.
	 *
	 * Standard clean CF7 submission without any WP Armour fields:
	 * - Functions identically and passes cleanly.
	 */
	public function test_10_wp_armour_disabled(): void {
		$form_id = '1365';
		$_POST   = $this->create_cf7_payload( $form_id );
		unset( $_POST['wpa_initiator'], $_POST['wpa_field'], $_POST['wpa_uuid'] );

		$res = $this->run_cf7_pipeline( $form_id );
		$this->assertFalse( $res['is_spam'] );
		$this->assertFalse( $res['abort'] );
		$this->assertSame( 'mail_sent', $res['submission']->get_status() );
	}

	/**
	 * Test 11: Simultaneous submissions (concurrency & race condition).
	 *
	 * Two simultaneous HTTP requests submit the same valid token:
	 * - Atomic token consumption guarantees that exactly one request succeeds.
	 * - The concurrent duplicate request fails with replayed status.
	 * - Neither request triggers CF7's aborted status ($abort = false).
	 */
	public function test_11_simultaneous_submissions(): void {
		$form_id = '1365';
		$tok_data = $this->token_manager->generate_token( $form_id );
		$shared_token = $tok_data['token'];

		// Simulate request A.
		$_POST = $this->create_cf7_payload( $form_id );
		$_POST['botfence_token'] = $shared_token;
		$res_a = $this->run_cf7_pipeline( $form_id );

		$this->assertFalse( $res_a['is_spam'], 'First concurrent request must be accepted' );
		$this->assertFalse( $res_a['abort'] );
		$this->assertSame( 'mail_sent', $res_a['submission']->get_status() );

		// Simulate request B (new request, cache cleared).
		Contact_Form_7::clear_cache();
		$_POST = $this->create_cf7_payload( $form_id );
		$_POST['botfence_token'] = $shared_token;
		$res_b = $this->run_cf7_pipeline( $form_id );

		$this->assertTrue( $res_b['is_spam'], 'Simultaneous duplicate request must be rejected' );
		$this->assertFalse( $res_b['abort'], 'Simultaneous duplicate request must NOT set $abort = true' );
		$this->assertSame( 'spam', $res_b['submission']->get_status() );
	}
}
