<?php
/**
 * Test Captcha_Engine
 *
 * @package KL_Anti_Spam\Tests
 */

namespace KL_Anti_Spam\Tests;

use PHPUnit\Framework\TestCase;
use KL_Anti_Spam\Captcha_Engine;
use KL_Anti_Spam\Token_Manager;
use KL_Anti_Spam\Honeypot;
use KL_Anti_Spam\Rate_Limiter;
use KL_Anti_Spam\Risk_Engine;
use KL_Anti_Spam\Logger;

class CaptchaEngineTest extends TestCase {

	protected Captcha_Engine $engine;
	protected Token_Manager $token_manager;
	protected Honeypot $honeypot;

	protected function setUp(): void {
		parent::setUp();
		global $kl_test_options, $kl_test_transients, $wpdb;
		$kl_test_options    = array(
			'kl_antispam_enabled'                  => 1,
			'kl_antispam_secret_key'               => 'test_master_engine_secret_key_1234',
			'kl_antispam_honeypot_enabled'         => 1,
			'kl_antispam_risk_threshold_challenge' => 30,
			'kl_antispam_risk_threshold_block'     => 60,
			'kl_antispam_min_submission_time'      => 2.5,
			'kl_antispam_rate_limit_ip_per_minute' => 10,
			'kl_antispam_rate_limit_ip_per_hour'   => 50,
			'kl_antispam_weight_honeypot'          => 100,
			'kl_antispam_weight_invalid_token'     => 100,
			'kl_antispam_weight_fast_submit'       => 30,
		);
		$kl_test_transients = array();
		$wpdb->logs_table   = array();
		$wpdb->rates_table  = array();

		$this->token_manager = new Token_Manager();
		$this->token_manager->set_secret_key( 'test_master_engine_secret_key_1234' );
		$this->honeypot      = new Honeypot();
		$rate_limiter        = new Rate_Limiter();
		$risk_engine         = new Risk_Engine();
		$logger              = new Logger();

		$this->engine = new Captcha_Engine(
			$this->token_manager,
			$this->honeypot,
			$rate_limiter,
			$risk_engine,
			$logger
		);
	}

	public function test_legitimate_human_submission_passes(): void {
		$tok_data  = $this->token_manager->generate_token( 'form_123', '127.0.0.1' );
		$telemetry = base64_encode( (string) wp_json_encode( array(
			'elapsed_time' => 4.2,
			'mouse_moves'  => 15,
			'keystrokes'   => 28,
			'touches'      => 0,
			'is_trusted'   => true,
			'webdriver'    => false,
		) ) );

		$post_data = array(
			'kl_anti_spam_token'     => $tok_data['token'],
			'kl_anti_spam_telemetry' => $telemetry,
			'user_name'              => 'Alice',
			'user_email'             => 'alice@example.com',
			'message'                => 'Hello! I would like more information about your product.',
		);

		$result = $this->engine->verify_submission( $post_data, 'wpforms', 'form_123' );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( 'allow', $result['action'] );
		$this->assertSame( 0, $result['score'] );
		$this->assertEmpty( $result['error_message'] );
	}

	public function test_honeypot_trap_blocks_submission(): void {
		$tok_data = $this->token_manager->generate_token( 'cf7_form', '127.0.0.1' );
		$hp_name  = $this->honeypot->get_dynamic_field_name( 0 );

		$post_data = array(
			'kl_anti_spam_token' => $tok_data['token'],
			$hp_name             => 'Automated Bot Link',
			'message'            => 'Spam content',
		);

		$result = $this->engine->verify_submission( $post_data, 'cf7', 'cf7_form' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'block', $result['action'] );
		$this->assertSame( 100, $result['score'] );
		$this->assertNotEmpty( $result['error_message'] );
	}

	public function test_missing_token_blocks_submission(): void {
		$post_data = array(
			'user_name' => 'Bot With No Token',
			'message'   => 'Scraper submission',
		);

		$result = $this->engine->verify_submission( $post_data, 'custom_forms', 'custom_1' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'block', $result['action'] );
		$this->assertSame( 100, $result['score'] );
	}

	public function test_fast_submission_challenges(): void {
		$tok_data  = $this->token_manager->generate_token( 'form_1', '127.0.0.1' );
		$telemetry = base64_encode( (string) wp_json_encode( array(
			'elapsed_time' => 0.4, // < 2.5s
			'is_trusted'   => true,
		) ) );

		$post_data = array(
			'kl_anti_spam_token'     => $tok_data['token'],
			'kl_anti_spam_telemetry' => $telemetry,
			'message'                => 'Fast submit',
		);

		$result = $this->engine->verify_submission( $post_data, 'fluent_forms', 'form_1' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'challenge', $result['action'] );
		$this->assertSame( 30, $result['score'] );
	}

	public function test_adaptive_challenge_generation_and_verification(): void {
		$challenge = $this->engine->generate_adaptive_challenge( 'form_test' );

		$this->assertNotEmpty( $challenge['question'] );
		$this->assertNotEmpty( $challenge['challenge_token'] );
		$this->assertGreaterThan( time(), $challenge['expires_at'] );

		// Verify answer is NOT leaked in the payload.
		$parts   = explode( '.', $challenge['challenge_token'] );
		$payload = base64_decode( $parts[0] );
		$this->assertStringNotContainsString( 'answer', $payload );

		// Solve challenge by parsing math question.
		preg_match( '/What is (\d+) \+ (\d+)\?/', $challenge['question'], $matches );
		$answer = (string) ( (int) $matches[1] + (int) $matches[2] );

		// Valid answer solves challenge.
		$is_valid = $this->engine->verify_adaptive_challenge( $challenge['challenge_token'], $answer, 'form_test' );
		$this->assertTrue( $is_valid );

		// Wrong answer fails.
		$wrong = $this->engine->verify_adaptive_challenge( $challenge['challenge_token'], '999', 'form_test' );
		$this->assertFalse( $wrong );
	}

	public function test_solving_challenge_allows_medium_risk(): void {
		$tok_data  = $this->token_manager->generate_token( 'form_1', '127.0.0.1' );
		$challenge = $this->engine->generate_adaptive_challenge( 'form_1' );

		// Solve challenge from question text.
		preg_match( '/What is (\d+) \+ (\d+)\?/', $challenge['question'], $matches );
		$answer = (string) ( (int) $matches[1] + (int) $matches[2] );

		// Submission that was fast (30 points = medium risk), but includes correct challenge solution.
		$telemetry = base64_encode( (string) wp_json_encode( array(
			'elapsed_time' => 0.5,
			'is_trusted'   => true,
		) ) );

		$post_data = array(
			'kl_anti_spam_token'     => $tok_data['token'],
			'kl_anti_spam_telemetry' => $telemetry,
			'kl_challenge_token'     => $challenge['challenge_token'],
			'kl_challenge_answer'    => $answer,
			'message'                => 'Fast submission with solved challenge',
		);

		$result = $this->engine->verify_submission( $post_data, 'wpforms', 'form_1' );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( 'allow', $result['action'] );
	}

	public function test_form_exclusion_whitelist(): void {
		update_option( 'kl_antispam_excluded_forms', "excluded_form_99\ncf7:special_form" );

		$post_data = array(
			'message' => 'No token provided at all',
		);

		// Excluded form by ID.
		$result = $this->engine->verify_submission( $post_data, 'generic', 'excluded_form_99' );
		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( 'allow', $result['action'] );

		// Excluded form by type:id.
		$res2 = $this->engine->verify_submission( $post_data, 'cf7', 'special_form' );
		$this->assertTrue( $res2['is_valid'] );
		$this->assertSame( 'allow', $res2['action'] );
	}

	public function test_challenge_security_constraints(): void {
		$challenge = $this->engine->generate_adaptive_challenge( 'form_payment' );

		preg_match( '/What is (\d+) \+ (\d+)\?/', $challenge['question'], $matches );
		$answer = (string) ( (int) $matches[1] + (int) $matches[2] );

		// Cross-form challenge reuse must fail.
		$mismatch = $this->engine->verify_adaptive_challenge( $challenge['challenge_token'], $answer, 'form_contact' );
		$this->assertFalse( $mismatch );

		// Tampered challenge signature must fail.
		$tampered = $challenge['challenge_token'] . 'bad';
		$bad_sig  = $this->engine->verify_adaptive_challenge( $tampered, $answer, 'form_payment' );
		$this->assertFalse( $bad_sig );
	}

	public function test_double_hook_memoization_avoids_replay_self_denial(): void {
		$tok_data = $this->token_manager->generate_token( 'cf7_memo', '127.0.0.1' );
		$adapter  = new \BotFence\Integrations\Contact_Form_7( $this->engine );

		$post_data = array(
			'botfence_token' => $tok_data['token'],
			'your-name'      => 'Alice Human',
			'your-email'     => 'alice@example.com',
			'your-message'   => 'Legitimate form message for testing memoization',
		);

		// First hook execution (e.g. wpcf7_spam): consumes token and validates.
		$res1 = $adapter->verify( $post_data, 'cf7_memo' );
		$this->assertTrue( $res1['is_valid'] );

		// Second hook execution in same request (e.g. wpcf7_before_send_mail): returns memoized result!
		$res2 = $adapter->verify( $post_data, 'cf7_memo' );
		$this->assertTrue( $res2['is_valid'] );
		$this->assertSame( $res1['score'], $res2['score'] );
	}
}
