<?php
/**
 * Zero-Time Bot Submission Bypass Regression & Security Test Suite.
 *
 * Verifies the mandatory blocking policy and timing enforcement:
 * 1. Verified duration = 0 ms -> Block (score != 0, action != allow)
 * 2. Verified duration between 1–999 ms -> Block
 * 3. Verified duration = 1,000–1,999 ms -> High risk; block at configured threshold
 * 4. Missing timing data -> High risk; do not treat as valid
 * 5. Invalid or tampered timing data -> Reject
 * 6. Future timestamp -> Reject
 * 7. Negative duration -> Reject
 * 8. JavaScript disabled -> Safe failure
 * 9. Contact Form 7 -> Timing enforcement
 * 10. Other supported integrations -> Timing enforcement
 * 11. Legitimate autofill -> No false positive when valid timing exists (> 2s)
 *
 * @package Vighnaghna\Tests
 */

namespace Vighnaghna\Tests;

use PHPUnit\Framework\TestCase;
use Vighnaghna\Captcha_Engine;
use Vighnaghna\Token_Manager;
use Vighnaghna\Honeypot;
use Vighnaghna\Rate_Limiter;
use Vighnaghna\Risk_Engine;
use Vighnaghna\Logger;
use Vighnaghna\Integrations\Contact_Form_7;
use Vighnaghna\Integrations\WPForms;
use Vighnaghna\Integrations\Fluent_Forms;
use Vighnaghna\Integrations\Gravity_Forms;
use Vighnaghna\Integrations\Ninja_Forms;
use Vighnaghna\Integrations\Formidable_Forms;
use Vighnaghna\Integrations\Elementor_Forms;
use Vighnaghna\Integrations\WooCommerce;
use Vighnaghna\Integrations\WP_Comments;
use Vighnaghna\Integrations\Custom_Forms;

class ZeroTimeSubmissionTest extends TestCase {

	protected Captcha_Engine $engine;
	protected Token_Manager $token_manager;
	protected Honeypot $honeypot;
	protected Rate_Limiter $rate_limiter;
	protected Risk_Engine $risk_engine;
	protected Logger $logger;

	protected function setUp(): void {
		parent::setUp();

		global $kl_test_options, $kl_test_transients, $wpdb;
		$kl_test_options    = array(
			'vighnaghna_enabled'                  => 1,
			'vighnaghna_secret_key'               => 'secret_key_zero_time_audit_1234567890123456',
			'vighnaghna_honeypot_enabled'         => 1,
			'vighnaghna_risk_threshold_challenge' => 30,
			'vighnaghna_risk_threshold_block'     => 60,
			'vighnaghna_min_submission_time'      => 2.5,
			'vighnaghna_token_lifetime'           => 600,
		);
		$kl_test_transients = array();
		$wpdb->logs_table   = array();
		$wpdb->rates_table  = array();

		Contact_Form_7::clear_cache();

		$this->token_manager = new Token_Manager();
		$this->token_manager->set_secret_key( 'secret_key_zero_time_audit_1234567890123456' );
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
	}

	protected function tearDown(): void {
		Contact_Form_7::clear_cache();
		parent::tearDown();
	}

	/**
	 * 1. Verified duration = 0 ms -> Block.
	 *
	 * A bot submits instantly with 0 ms duration.
	 * Must receive a non-zero risk score (100) and an explicit 'block' action.
	 */
	public function test_zero_ms_verified_duration_blocked(): void {
		$form_id  = 'cf7_test_zero';
		$now      = microtime( true );
		$tok_data = $this->token_manager->generate_token( $form_id, '127.0.0.1', $now );

		$submission = array(
			'vighnaghna_token' => $tok_data['token'],
			'your-name'        => 'Bot 0ms',
			'your-email'       => 'bot0ms@example.com',
			'your-message'     => 'Spam content submitted in 0 ms',
		);

		// Test direct token validation with 0 ms duration.
		$tok_direct     = $this->token_manager->generate_token( 'cf7_direct_zero', '127.0.0.1', $now );
		$tok_validation = $this->token_manager->validate_token( $tok_direct['token'], 'cf7_direct_zero', '127.0.0.1', $now );
		$this->assertSame( 0, $tok_validation['duration_ms'] );
		$this->assertSame( 'valid', $tok_validation['timing_status'] );

		// Direct Risk Engine evaluation with duration = 0 ms must block with score 100.
		$risk_eval = $this->risk_engine->evaluate( array(
			'duration_ms'   => 0,
			'timing_status' => 'valid',
		) );
		$this->assertTrue( $risk_eval['is_spam'] );
		$this->assertSame( 'block', $risk_eval['action'] );
		$this->assertSame( 100, $risk_eval['score'] );
		$eval_signals = array_column( $risk_eval['signals'], 'code' );
		$this->assertContains( 'instant_submission', $eval_signals );

		// Verify fresh token through engine.
		$result = $this->engine->verify_submission( $submission, 'cf7', $form_id );

		$this->assertFalse( $result['is_valid'], '0 ms submission must NOT be allowed' );
		$this->assertSame( 'block', $result['action'], '0 ms submission action must be block' );
		$this->assertNotSame( 0, $result['score'], '0 ms submission must NEVER receive a risk score of 0' );
		$this->assertSame( 100, $result['score'], '0 ms submission must receive 100 risk score' );

		// Verify signal.
		$codes = array_column( $result['signals'], 'code' );
		$this->assertContains( 'instant_submission', $codes );

		// Verify logger recorded duration <= sub-second instant velocity and score 100.
		$trace = $this->engine->get_last_verification_trace();
		$this->assertNotNull( $trace );
		$this->assertLessThanOrEqual( 5, $trace['duration_ms'] );
		$this->assertGreaterThanOrEqual( 0, $trace['duration_ms'] );
		$this->assertSame( 100, $trace['risk_score'] );
		$this->assertFalse( $trace['is_valid'] );
	}

	/**
	 * 2. Verified duration = 1 ms -> Block.
	 */
	public function test_one_ms_verified_duration_blocked(): void {
		$form_id = 'cf7_1ms';
		$now_ms  = (int) round( microtime( true ) * 1000 );
		// Token generated 1 ms before verification.
		$tok_data = $this->token_manager->generate_token( $form_id, '127.0.0.1', $now_ms - 1 );

		$submission = array(
			'vighnaghna_token' => $tok_data['token'],
			'your-name'        => 'Bot 1ms',
			'your-message'     => 'Submission at 1ms velocity',
		);

		$result = $this->engine->verify_submission( $submission, 'cf7', $form_id );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'block', $result['action'] );
		$this->assertSame( 100, $result['score'] );

		$codes = array_column( $result['signals'], 'code' );
		$this->assertContains( 'instant_submission', $codes );
	}

	/**
	 * 3. Verified duration = 999 ms -> Block.
	 */
	public function test_nine_hundred_ninety_nine_ms_verified_duration_blocked(): void {
		$form_id = 'cf7_999ms';
		$now_ms  = (int) round( microtime( true ) * 1000 );
		// Token generated 999 ms before verification.
		$tok_data = $this->token_manager->generate_token( $form_id, '127.0.0.1', $now_ms - 999 );

		$submission = array(
			'vighnaghna_token' => $tok_data['token'],
			'your-name'        => 'Bot 999ms',
			'your-message'     => 'Sub-second 999ms automated velocity',
		);

		$result = $this->engine->verify_submission( $submission, 'cf7', $form_id );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'block', $result['action'] );
		$this->assertSame( 100, $result['score'] );

		$codes = array_column( $result['signals'], 'code' );
		$this->assertContains( 'instant_submission', $codes );
	}

	/**
	 * 4. Verified duration = 1,000–1,999 ms -> High risk; block at the configured threshold.
	 */
	public function test_one_thousand_ms_to_nineteen_ninety_nine_ms_high_risk_block(): void {
		$form_id = 'cf7_1500ms';
		$now_ms  = (int) round( microtime( true ) * 1000 );

		// Test exact 1,000 ms.
		$tok_1000 = $this->token_manager->generate_token( $form_id, '127.0.0.1', $now_ms - 1000 );
		$res_1000 = $this->engine->verify_submission( array(
			'vighnaghna_token' => $tok_1000['token'],
			'your-name'        => 'Fast User 1000ms',
			'your-message'     => 'Submitted at 1000ms',
		), 'cf7', $form_id );

		$this->assertFalse( $res_1000['is_valid'] );
		$this->assertSame( 'block', $res_1000['action'] );
		$this->assertGreaterThanOrEqual( 60, $res_1000['score'], 'Must block at configured threshold (default 60)' );

		Contact_Form_7::clear_cache();

		// Test 1,500 ms.
		$tok_1500 = $this->token_manager->generate_token( $form_id, '127.0.0.1', $now_ms - 1500 );
		$res_1500 = $this->engine->verify_submission( array(
			'vighnaghna_token' => $tok_1500['token'],
			'your-name'        => 'Fast User 1500ms',
			'your-message'     => 'Submitted at 1500ms',
		), 'cf7', $form_id );

		$this->assertFalse( $res_1500['is_valid'] );
		$this->assertSame( 'block', $res_1500['action'] );
		$this->assertGreaterThanOrEqual( 60, $res_1500['score'] );
		$codes = array_column( $res_1500['signals'], 'code' );
		$this->assertContains( 'fast_submission', $codes );
	}

	/**
	 * 5. Missing timing data -> High risk; do not treat as valid.
	 */
	public function test_missing_timing_data_high_risk(): void {
		$submission_no_token = array(
			'your-name'    => 'Bot Stripping Tokens',
			'your-message' => 'Direct HTTP scraper without tokens',
		);

		$result = $this->engine->verify_submission( $submission_no_token, 'cf7', 'cf7_missing' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'block', $result['action'] );
		$this->assertGreaterThanOrEqual( 60, $result['score'], 'Missing timing data must not be treated as valid' );

		// Test Risk_Engine directly with missing timing context.
		$context_missing = array(
			'honeypot_triggered' => false,
			'token_status'       => 'valid',
			'timing_status'      => 'missing',
			'duration_ms'        => null,
		);
		$eval_missing = $this->risk_engine->evaluate( $context_missing );
		$this->assertSame( 'block', $eval_missing['action'] );
		$this->assertGreaterThanOrEqual( 60, $eval_missing['score'] );
		$codes = array_column( $eval_missing['signals'], 'code' );
		$this->assertContains( 'missing_timing', $codes );
	}

	/**
	 * 6. Invalid or tampered timing data -> Reject.
	 */
	public function test_tampered_timestamp_rejected(): void {
		$tok_data = $this->token_manager->generate_token( 'cf7_tamper', '127.0.0.1', microtime( true ) - 5.0 );
		$tampered = $tok_data['token'] . 'bad_signature';

		$submission = array(
			'vighnaghna_token' => $tampered,
			'your-name'        => 'Attacker',
			'your-message'     => 'Payload with tampered token',
		);

		$result = $this->engine->verify_submission( $submission, 'cf7', 'cf7_tamper' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'block', $result['action'] );
		$this->assertSame( 100, $result['score'] );

		$trace = $this->engine->get_last_verification_trace();
		$this->assertSame( 'tampered', $trace['token_status'] );
	}

	/**
	 * 7. Future timestamp -> Reject.
	 */
	public function test_future_timestamp_rejected(): void {
		$future_ms = (int) round( ( microtime( true ) + 300 ) * 1000 );
		$tok_data  = $this->token_manager->generate_token( 'cf7_future', '127.0.0.1', $future_ms );

		$submission = array(
			'vighnaghna_token' => $tok_data['token'],
			'your-name'        => 'Future Bot',
			'your-message'     => 'Submission with timestamp 300s into the future',
		);

		$result = $this->engine->verify_submission( $submission, 'cf7', 'cf7_future' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'block', $result['action'] );
		$this->assertSame( 100, $result['score'] );

		$codes = array_column( $result['signals'], 'code' );
		$this->assertTrue( in_array( 'future_timestamp', $codes, true ) || in_array( 'invalid_token', $codes, true ) );
	}

	/**
	 * 8. Negative duration -> Reject.
	 */
	public function test_negative_duration_rejected(): void {
		// Context with negative elapsed time.
		$context = array(
			'honeypot_triggered' => false,
			'token_status'       => 'valid',
			'duration_ms'        => -500, // Negative duration
			'timing_status'      => 'negative_duration',
		);

		$eval = $this->risk_engine->evaluate( $context );

		$this->assertFalse( $eval['action'] === 'allow' );
		$this->assertSame( 'block', $eval['action'] );
		$this->assertSame( 100, $eval['score'] );

		$codes = array_column( $eval['signals'], 'code' );
		$this->assertContains( 'negative_duration', $codes );
	}

	/**
	 * 9. JavaScript disabled: Safe failure.
	 *
	 * When a bot submits without JavaScript telemetry:
	 * - Zero or sub-second duration is blocked.
	 * - Missing token is blocked.
	 * - Legitimate human with JS disabled who solves accessible fallback challenge is allowed.
	 */
	public function test_javascript_disabled_safe_failure(): void {
		$form_id = 'cf7_no_js';

		// Scenario A: Bot without JS submitting instantly (0 ms) -> BLOCKED.
		$bot_tok = $this->token_manager->generate_token( $form_id, '127.0.0.1', microtime( true ) );
		$res_bot = $this->engine->verify_submission( array(
			'vighnaghna_token' => $bot_tok['token'],
			'your-name'        => 'Bot No JS',
			'your-message'     => 'Automated submission without JS',
		), 'cf7', $form_id );

		$this->assertFalse( $res_bot['is_valid'], 'Bot without JS at 0ms must be blocked' );
		$this->assertSame( 'block', $res_bot['action'] );

		Contact_Form_7::clear_cache();

		// Scenario B: Human with JS disabled completing fallback challenge -> ALLOWED.
		$challenge = $this->engine->generate_adaptive_challenge( $form_id );
		preg_match( '/What is (\d+) \+ (\d+)\?/', $challenge['question'], $matches );
		$answer = (string) ( (int) $matches[1] + (int) $matches[2] );

		$human_tok = $this->token_manager->generate_token( $form_id, '127.0.0.1', microtime( true ) - 5.0 );
		$res_human = $this->engine->verify_submission( array(
			'vighnaghna_token'            => $human_tok['token'],
			'vighnaghna_challenge_token'  => $challenge['challenge_token'],
			'vighnaghna_challenge_answer' => $answer,
			'your-name'                   => 'Human No JS',
			'your-message'                => 'Human submission with solved fallback math challenge',
		), 'cf7', $form_id );

		$this->assertTrue( $res_human['is_valid'], 'Human with JS disabled solving fallback challenge must be allowed' );
		$this->assertSame( 'allow', $res_human['action'] );
	}

	/**
	 * 10. Contact Form 7: Timing enforcement.
	 *
	 * CF7 adapter filters must catch zero-time bot submissions and flag as spam.
	 */
	public function test_contact_form_7_timing_enforcement(): void {
		$adapter = new Contact_Form_7( $this->engine );
		$form_id = 'cf7_timing';

		// Case A: 0ms bot submission via $_POST.
		$tok_0ms = $this->token_manager->generate_token( $form_id, '127.0.0.1', microtime( true ) );
		$_POST   = array(
			'_wpcf7'           => $form_id,
			'vighnaghna_token' => $tok_0ms['token'],
			'your-name'        => 'Instant Bot',
			'your-message'     => 'Spam via CF7 in 0 ms',
		);

		$is_spam = $adapter->filter_spam( false );
		$this->assertTrue( $is_spam, 'Contact Form 7 filter_spam must flag 0ms submission as spam' );

		$skip_mail = $adapter->filter_skip_mail( false );
		$this->assertTrue( $skip_mail, 'Contact Form 7 filter_skip_mail must skip mail for 0ms submission' );

		Contact_Form_7::clear_cache();

		// Case B: Clean human taking 4.5s -> Allowed.
		$tok_human = $this->token_manager->generate_token( $form_id, '127.0.0.1', microtime( true ) - 4.5 );
		$_POST     = array(
			'_wpcf7'           => $form_id,
			'vighnaghna_token' => $tok_human['token'],
			'your-name'        => 'Legitimate Human',
			'your-message'     => 'Hello, I took 4.5 seconds to write this message.',
		);

		$this->assertFalse( $adapter->filter_spam( false ), 'Clean human submission on CF7 must not be flagged as spam' );
	}

	/**
	 * 11. Other supported integrations: Timing enforcement across all 10 adapters.
	 */
	public function test_all_supported_integrations_timing_enforcement(): void {
		$adapters = array(
			new WPForms( $this->engine ),
			new Fluent_Forms( $this->engine ),
			new Gravity_Forms( $this->engine ),
			new Ninja_Forms( $this->engine ),
			new Formidable_Forms( $this->engine ),
			new Elementor_Forms( $this->engine ),
			new WooCommerce( $this->engine ),
			new WP_Comments( $this->engine ),
			new Custom_Forms( $this->engine ),
		);

		foreach ( $adapters as $index => $adapter ) {
			$form_id = 'form_' . $adapter->get_id();
			$ip      = '10.0.1.' . ( $index + 1 );
			$_SERVER['REMOTE_ADDR'] = $ip;

			// 0 ms submission.
			$tok_0ms = $this->token_manager->generate_token( $form_id, $ip, microtime( true ) );
			$bad_post = array(
				'vighnaghna_token' => $tok_0ms['token'],
				'message'          => 'Instant 0ms bot submission ' . $adapter->get_id() . '_' . uniqid(),
			);

			$result = $adapter->verify( $bad_post, $form_id );
			$this->assertFalse(
				$result['is_valid'],
				sprintf( 'Integration [%s] must block 0ms submission', $adapter->get_name() )
			);
			$this->assertSame( 'block', $result['action'] );
			$this->assertSame( 100, $result['score'] );

			// Clean human (5.0s) -> allowed.
			$tok_human = $this->token_manager->generate_token( $form_id, $ip, microtime( true ) - 5.0 );
			$clean_post = array(
				'vighnaghna_token' => $tok_human['token'],
				'message'          => 'Legitimate 5.0s human submission ' . $adapter->get_id() . '_' . uniqid(),
			);

			$clean_result = $adapter->verify( $clean_post, $form_id );
			$this->assertTrue(
				$clean_result['is_valid'],
				sprintf( 'Integration [%s] must allow legitimate human submission', $adapter->get_name() )
			);
			$this->assertSame( 'allow', $clean_result['action'] );
			$this->assertSame( 0, $clean_result['score'] );
		}
	}

	/**
	 * 12. Legitimate autofill: No false-positive block when valid timing exists (> 2s).
	 */
	public function test_legitimate_autofill_no_false_positive_when_valid_timing_exists(): void {
		$form_id = 'cf7_autofill';
		// User took 3.5 seconds on page, browser autofilled name and email.
		$tok_data = $this->token_manager->generate_token( $form_id, '127.0.0.1', microtime( true ) - 3.5 );

		$telemetry = base64_encode( (string) wp_json_encode( array(
			'elapsed_time' => 3.5,
			'mouse_moves'  => 0, // Browser autofill: no mouse movements
			'keystrokes'   => 0, // Browser autofill: no keystrokes
			'touches'      => 0,
			'autofill'     => true, // Autofill flag recorded
			'is_trusted'   => true,
			'webdriver'    => false,
		) ) );

		$submission = array(
			'vighnaghna_token'     => $tok_data['token'],
			'vighnaghna_telemetry' => $telemetry,
			'your-name'            => 'John Smith',
			'your-email'           => 'john.smith@example.org',
			'your-message'         => 'Legitimate inquiry filled with browser autofill after 3.5 seconds.',
		);

		$result = $this->engine->verify_submission( $submission, 'cf7', $form_id );

		$this->assertTrue( $result['is_valid'], 'Legitimate autofill must NOT be blocked when valid timing exists' );
		$this->assertSame( 'allow', $result['action'] );
		$this->assertSame( 0, $result['score'], 'Legitimate autofill must receive 0 risk score' );
		$this->assertEmpty( $result['signals'] );
	}
}
