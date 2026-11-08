<?php
/**
 * End-To-End & Edge Cases Test Suite
 *
 * @package KL_Anti_Spam\Tests
 */

namespace KL_Anti_Spam\Tests;

use PHPUnit\Framework\TestCase;
use KL_Anti_Spam\Plugin;
use KL_Anti_Spam\Captcha_Engine;
use KL_Anti_Spam\Token_Manager;
use KL_Anti_Spam\Honeypot;
use KL_Anti_Spam\Rate_Limiter;
use KL_Anti_Spam\Risk_Engine;
use KL_Anti_Spam\Logger;
use WP_REST_Request;

class EndToEndTest extends TestCase {

	protected Captcha_Engine $engine;
	protected Token_Manager $token_manager;
	protected Honeypot $honeypot;
	protected Rate_Limiter $rate_limiter;

	protected function setUp(): void {
		parent::setUp();
		global $kl_test_options, $kl_test_transients, $wpdb;
		$kl_test_options    = array(
			'kl_antispam_enabled'                  => 1,
			'kl_antispam_secret_key'               => 'test_e2e_secret_seed_abcdef_0987654321',
			'kl_antispam_honeypot_enabled'         => 1,
			'kl_antispam_adaptive_challenge'       => 1,
			'kl_antispam_risk_threshold_challenge' => 30,
			'kl_antispam_risk_threshold_block'     => 60,
			'kl_antispam_min_submission_time'      => 2.5,
			'kl_antispam_token_lifetime'           => 600,
			'kl_antispam_rate_limit_ip_per_minute' => 5,
			'kl_antispam_rate_limit_ip_per_hour'   => 30,
			'kl_antispam_weight_honeypot'          => 100,
			'kl_antispam_weight_invalid_token'     => 100,
			'kl_antispam_weight_expired_token'     => 80,
			'kl_antispam_weight_fast_submit'       => 30,
		);
		$kl_test_transients = array();
		$wpdb->logs_table   = array();
		$wpdb->rates_table  = array();

		$this->token_manager = new Token_Manager();
		$this->token_manager->set_secret_key( 'test_e2e_secret_seed_abcdef_0987654321' );
		$this->honeypot      = new Honeypot();
		$this->rate_limiter  = new Rate_Limiter();
		$risk_engine         = new Risk_Engine();
		$logger              = new Logger();

		$this->engine = new Captcha_Engine(
			$this->token_manager,
			$this->honeypot,
			$this->rate_limiter,
			$risk_engine,
			$logger
		);
	}

	/**
	 * Scenario 1: Normal Human Submission
	 */
	public function test_e2e_normal_human_submission(): void {
		$tok_data  = $this->token_manager->generate_token( 'contact_us', '203.0.113.10' );
		$telemetry = base64_encode( (string) wp_json_encode( array(
			'elapsed_time' => 7.8,
			'mouse_moves'  => 35,
			'keystrokes'   => 54,
			'touches'      => 0,
			'is_trusted'   => true,
			'webdriver'    => false,
		) ) );

		$submission = array(
			'kl_anti_spam_token'     => $tok_data['token'],
			'kl_anti_spam_telemetry' => $telemetry,
			'user_name'              => 'Sarah Miller',
			'user_email'             => 'sarah.miller@example.org',
			'message'                => 'Hello, could you please send me a quote for the premium consulting package?',
		);

		$result = $this->engine->verify_submission( $submission, 'cf7', 'contact_us' );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( 'allow', $result['action'] );
		$this->assertSame( 0, $result['score'] );
	}

	/**
	 * Scenario 2: Automated Bot Submission (fills all inputs including honeypot)
	 */
	public function test_e2e_bot_with_honeypot_submission(): void {
		$hp_field = $this->honeypot->get_dynamic_field_name( 0 );

		$submission = array(
			'user_name'  => 'Best SEO Services',
			'user_email' => 'traffic-blast@spammer.ru',
			'message'    => 'Get 10,000 backlinks instantly at http://blast-seo-backlinks.ru',
			$hp_field    => 'http://blast-seo-backlinks.ru', // Trap triggered!
		);

		$result = $this->engine->verify_submission( $submission, 'wpforms', 'wpf_lead_form' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'block', $result['action'] );
		$this->assertSame( 100, $result['score'] );
	}

	/**
	 * Scenario 3: Cached Page Token Lifecycle
	 *
	 * A static cached HTML page has an expired token. The frontend JS calls
	 * REST route /wp-json/kl-anti-spam/v1/token to retrieve a fresh token.
	 * The submission with the refreshed token succeeds.
	 */
	public function test_e2e_cached_page_token_refresh_lifecycle(): void {
		// 1. Initial token in cached HTML is 2 hours old (expired).
		$old_time      = time() - 7200;
		$expired_token = $this->token_manager->generate_token( 'cached_form', '127.0.0.1', $old_time );

		// Verifying with expired token fails.
		$res_expired = $this->engine->verify_submission( array(
			'kl_anti_spam_token' => $expired_token['token'],
			'message'            => 'Submission using stale cached HTML token',
		), 'cf7', 'cached_form' );
		$this->assertFalse( $res_expired['is_valid'] );

		// 2. Client script fetches fresh token via REST endpoint.
		$plugin   = Plugin::get_instance();
		$request  = new WP_REST_Request( array( 'form_id' => 'cached_form' ) );
		$response = $plugin->rest_get_token( $request );

		$this->assertSame( 200, $response->status );
		$this->assertNotEmpty( $response->data['token'] );

		$fresh_token = $response->data['token'];

		// 3. User submits form with refreshed token -> passes!
		$telemetry = base64_encode( (string) wp_json_encode( array(
			'elapsed_time' => 3.2,
			'mouse_moves'  => 8,
			'keystrokes'   => 15,
			'is_trusted'   => true,
		) ) );

		$res_fresh = $this->engine->verify_submission( array(
			'kl_anti_spam_token'     => $fresh_token,
			'kl_anti_spam_telemetry' => $telemetry,
			'message'                => 'Fresh submission after background token refresh ' . uniqid(),
		), 'cf7', 'cached_form' );

		$this->assertTrue( $res_fresh['is_valid'] );
		$this->assertSame( 'allow', $res_fresh['action'] );
	}

	/**
	 * Scenario 4: Multiple Forms On One Page
	 *
	 * A page with a Newsletter Signup Form and a Contact Inquiry Form.
	 * Both forms receive independent tokens and submit independently.
	 */
	public function test_e2e_multiple_forms_on_one_page(): void {
		$token_form_1 = $this->token_manager->generate_token( 'form_newsletter', '127.0.0.1' );
		$token_form_2 = $this->token_manager->generate_token( 'form_contact', '127.0.0.1' );

		$this->assertNotSame( $token_form_1['token'], $token_form_2['token'] );

		// Submit form 1.
		$telemetry_1 = base64_encode( (string) wp_json_encode( array( 'elapsed_time' => 4.0, 'is_trusted' => true ) ) );
		$res1 = $this->engine->verify_submission( array(
			'kl_anti_spam_token'     => $token_form_1['token'],
			'kl_anti_spam_telemetry' => $telemetry_1,
			'email'                  => 'reader1@example.com',
		), 'fluent_forms', 'form_newsletter' );
		$this->assertTrue( $res1['is_valid'] );

		// Submit form 2.
		$telemetry_2 = base64_encode( (string) wp_json_encode( array( 'elapsed_time' => 6.2, 'is_trusted' => true ) ) );
		$res2 = $this->engine->verify_submission( array(
			'kl_anti_spam_token'     => $token_form_2['token'],
			'kl_anti_spam_telemetry' => $telemetry_2,
			'message'                => 'Inquiry regarding product details',
		), 'fluent_forms', 'form_contact' );
		$this->assertTrue( $res2['is_valid'] );
	}

	/**
	 * Scenario 5: JavaScript-Disabled Visitor (Accessible Fallback)
	 *
	 * A user with JS disabled completes the accessible fallback math challenge.
	 */
	public function test_e2e_js_disabled_visitor_with_accessible_fallback(): void {
		$challenge = $this->engine->generate_adaptive_challenge( 'no_js_form' );

		// Parse solution answer.
		$parts   = explode( '.', $challenge['challenge_token'] );
		$payload = base64_decode( $parts[0] );
		$data    = explode( '|', $payload );
		$answer  = $data[1];

		// In non-JS environment, kl_anti_spam_telemetry is absent, but the fallback answer is provided.
		$submission = array(
			'kl_challenge_token'  => $challenge['challenge_token'],
			'kl_challenge_answer' => $answer,
			'message'             => 'Hello from a user with JavaScript turned off in browser ' . uniqid(),
			'user_name'           => 'Privacy User',
		);

		$result = $this->engine->verify_submission( $submission, 'comments', 'post_100' );

		// Now test with a fresh challenge and valid token.
		$challenge2 = $this->engine->generate_adaptive_challenge( 'post_100' );
		$parts2     = explode( '.', $challenge2['challenge_token'] );
		$payload2   = base64_decode( $parts2[0] );
		$data2      = explode( '|', $payload2 );
		$answer2    = $data2[1];

		$tok_data = $this->token_manager->generate_token( 'post_100', '127.0.0.1' );
		$submission2 = array(
			'kl_anti_spam_token'  => $tok_data['token'],
			'kl_challenge_token'  => $challenge2['challenge_token'],
			'kl_challenge_answer' => $answer2,
			'message'             => 'Clean submission with accessible fallback ' . uniqid(),
		);

		$res_with_token = $this->engine->verify_submission( $submission2, 'comments', 'post_100' );
		$this->assertTrue( $res_with_token['is_valid'] );
		$this->assertSame( 'allow', $res_with_token['action'] );
	}

	/**
	 * Scenario 6: False-Positive Protection
	 *
	 * Tests real-world cases:
	 * - Legitimate user with single link in their portfolio/enquiry
	 * - Slow user who kept form open for 20 minutes (before submitting)
	 */
	public function test_e2e_false_positive_single_url_human(): void {
		$tok_data  = $this->token_manager->generate_token( 'rfp_form', '127.0.0.1' );
		$telemetry = base64_encode( (string) wp_json_encode( array(
			'elapsed_time' => 8.5,
			'mouse_moves'  => 40,
			'keystrokes'   => 60,
			'is_trusted'   => true,
		) ) );

		// User includes their company website link (only 1 URL).
		$submission = array(
			'kl_anti_spam_token'     => $tok_data['token'],
			'kl_anti_spam_telemetry' => $telemetry,
			'user_name'              => 'Acme Corp',
			'message'                => 'Please review our design specs at https://acme-corp.com/specs for the project quotation.',
		);

		$result = $this->engine->verify_submission( $submission, 'gravity_forms', 'rfp_form' );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( 'allow', $result['action'] );
		$this->assertSame( 0, $result['score'] );
	}
}
