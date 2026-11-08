<?php
/**
 * Test Risk_Engine
 *
 * @package KL_Anti_Spam\Tests
 */

namespace KL_Anti_Spam\Tests;

use PHPUnit\Framework\TestCase;
use KL_Anti_Spam\Risk_Engine;

class RiskEngineTest extends TestCase {

	protected Risk_Engine $engine;

	protected function setUp(): void {
		parent::setUp();
		global $kl_test_options;
		$kl_test_options = array(
			'kl_antispam_risk_threshold_challenge' => 30,
			'kl_antispam_risk_threshold_block'     => 60,
			'kl_antispam_min_submission_time'      => 2.5,
			'kl_antispam_weight_honeypot'          => 100,
			'kl_antispam_weight_invalid_token'     => 100,
			'kl_antispam_weight_expired_token'     => 80,
			'kl_antispam_weight_fast_submit'       => 30,
			'kl_antispam_weight_rate_limit'        => 40,
			'kl_antispam_weight_excessive_urls'    => 25,
			'kl_antispam_weight_suspicious_telemetry' => 20,
			'kl_antispam_weight_repeated_message'  => 35,
		);
		$this->engine = new Risk_Engine();
	}

	public function test_clean_human_submission_allowed(): void {
		$context = array(
			'honeypot_triggered' => false,
			'token_status'       => 'valid',
			'elapsed_time'       => 6.5,
			'rate_exceeded'      => false,
			'repeated_message'   => false,
			'url_count'          => 0,
			'telemetry'          => array(
				'mouse_moves' => 20,
				'keystrokes'  => 45,
				'touches'     => 0,
				'is_trusted'  => true,
				'webdriver'   => false,
			),
			'text_length'        => 80,
		);

		$res = $this->engine->evaluate( $context );

		$this->assertSame( 0, $res['score'] );
		$this->assertSame( 'allow', $res['action'] );
		$this->assertFalse( $res['is_spam'] );
		$this->assertEmpty( $res['signals'] );
	}

	public function test_honeypot_triggers_instant_block(): void {
		$context = array(
			'honeypot_triggered' => true,
			'honeypot_field'     => 'kl_field_123',
			'token_status'       => 'valid',
			'elapsed_time'       => 5.0,
		);

		$res = $this->engine->evaluate( $context );

		$this->assertSame( 100, $res['score'] );
		$this->assertSame( 'block', $res['action'] );
		$this->assertTrue( $res['is_spam'] );
		$this->assertSame( Risk_Engine::SIGNAL_HONEYPOT, $res['signals'][0]['code'] );
	}

	public function test_invalid_token_triggers_block(): void {
		$context = array(
			'honeypot_triggered' => false,
			'token_status'       => 'tampered',
			'elapsed_time'       => 4.0,
		);

		$res = $this->engine->evaluate( $context );

		$this->assertSame( 100, $res['score'] );
		$this->assertSame( 'block', $res['action'] );
		$this->assertTrue( $res['is_spam'] );
	}

	public function test_expired_token_triggers_block(): void {
		$context = array(
			'honeypot_triggered' => false,
			'token_status'       => 'expired',
			'token_age'          => 750,
			'elapsed_time'       => 4.0,
		);

		$res = $this->engine->evaluate( $context );

		$this->assertSame( 80, $res['score'] );
		$this->assertSame( 'block', $res['action'] );
		$this->assertTrue( $res['is_spam'] );
	}

	public function test_fast_submission_triggers_challenge(): void {
		$context = array(
			'honeypot_triggered' => false,
			'token_status'       => 'valid',
			'elapsed_time'       => 0.8, // Faster than 2.5s
			'rate_exceeded'      => false,
			'telemetry'          => array( 'is_trusted' => true ),
		);

		$res = $this->engine->evaluate( $context );

		$this->assertSame( 30, $res['score'] );
		$this->assertSame( 'challenge', $res['action'] );
		$this->assertTrue( $res['is_spam'] );
		$this->assertSame( Risk_Engine::SIGNAL_FAST_SUBMISSION, $res['signals'][0]['code'] );
	}

	public function test_excessive_urls_increases_risk(): void {
		$context = array(
			'honeypot_triggered' => false,
			'token_status'       => 'valid',
			'elapsed_time'       => 5.0,
			'url_count'          => 4, // 4 URLs
			'telemetry'          => array( 'is_trusted' => true ),
		);

		$res = $this->engine->evaluate( $context );

		$this->assertSame( 25, $res['score'] );
		$this->assertSame( 'allow', $res['action'] ); // 25 is < 30 threshold
		$this->assertSame( Risk_Engine::SIGNAL_EXCESSIVE_URLS, $res['signals'][0]['code'] );
	}

	public function test_multiple_signals_accumulate_to_block(): void {
		$context = array(
			'honeypot_triggered' => false,
			'token_status'       => 'valid',
			'elapsed_time'       => 1.2, // +30 fast submit
			'url_count'          => 5,   // +25 excessive urls
			'repeated_message'   => true, // +35 repeated message
			// Total = 30 + 25 + 35 = 90
		);

		$res = $this->engine->evaluate( $context );

		$this->assertSame( 90, $res['score'] );
		$this->assertSame( 'block', $res['action'] );
		$this->assertCount( 3, $res['signals'] );
	}

	public function test_automated_browser_and_untrusted_event(): void {
		$context = array(
			'honeypot_triggered' => false,
			'token_status'       => 'valid',
			'elapsed_time'       => 4.0,
			'telemetry'          => array(
				'webdriver'  => true,  // +50
				'is_trusted' => false, // +45
			),
		);

		$res = $this->engine->evaluate( $context );

		$this->assertSame( 95, $res['score'] );
		$this->assertSame( 'block', $res['action'] );
	}
}
