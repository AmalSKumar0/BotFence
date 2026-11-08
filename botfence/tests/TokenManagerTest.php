<?php
/**
 * Test Token_Manager
 *
 * @package KL_Anti_Spam\Tests
 */

namespace KL_Anti_Spam\Tests;

use PHPUnit\Framework\TestCase;
use KL_Anti_Spam\Token_Manager;

class TokenManagerTest extends TestCase {

	protected Token_Manager $manager;

	protected function setUp(): void {
		parent::setUp();
		global $kl_test_options, $kl_test_transients;
		$kl_test_options    = array();
		$kl_test_transients = array();

		$this->manager = new Token_Manager();
		$this->manager->set_secret_key( 'test_secret_key_abcdef_0123456789_32bytes' );
	}

	public function test_generate_token_structure(): void {
		$res = $this->manager->generate_token( 'form_123', '192.168.1.50' );

		$this->assertIsArray( $res );
		$this->assertArrayHasKey( 'token', $res );
		$this->assertArrayHasKey( 'timestamp', $res );
		$this->assertArrayHasKey( 'expires_at', $res );

		$parts = explode( '.', $res['token'] );
		$this->assertCount( 2, $parts );
	}

	public function test_valid_token_passes(): void {
		$generated = $this->manager->generate_token( 'test_form', '127.0.0.1' );
		$result    = $this->manager->validate_token( $generated['token'], 'test_form', '127.0.0.1' );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( 'valid', $result['status'] );
		$this->assertSame( 0, $result['risk_points'] );
		$this->assertEmpty( $result['error_message'] );
	}

	public function test_missing_token_fails(): void {
		$result = $this->manager->validate_token( null );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'missing', $result['status'] );
		$this->assertSame( 100, $result['risk_points'] );
	}

	public function test_malformed_token_fails(): void {
		$result = $this->manager->validate_token( 'not_a_valid_dot_separated_token' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'malformed', $result['status'] );
		$this->assertSame( 100, $result['risk_points'] );
	}

	public function test_tampered_token_fails(): void {
		$generated = $this->manager->generate_token( 'test_form' );
		// Modify the signature part.
		$tampered = $generated['token'] . 'bad';
		$result   = $this->manager->validate_token( $tampered, 'test_form' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'tampered', $result['status'] );
		$this->assertSame( 100, $result['risk_points'] );
	}

	public function test_expired_token_fails(): void {
		$past_time = time() - 700; // 700 seconds old (default lifetime is 600)
		$generated = $this->manager->generate_token( 'test_form', '127.0.0.1', $past_time );

		$result = $this->manager->validate_token( $generated['token'], 'test_form', '127.0.0.1', time() );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'expired', $result['status'] );
		$this->assertSame( 80, $result['risk_points'] );
	}

	public function test_future_token_rejected(): void {
		$future_time = time() + 300; // 300s into the future
		$generated   = $this->manager->generate_token( 'test_form', '127.0.0.1', $future_time );

		$result = $this->manager->validate_token( $generated['token'], 'test_form', '127.0.0.1', time() );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( 'future_timestamp', $result['status'] );
		$this->assertSame( 100, $result['risk_points'] );
	}

	public function test_replay_token_prevented(): void {
		$generated = $this->manager->generate_token( 'test_form', '127.0.0.1' );

		// First submission: should succeed.
		$res1 = $this->manager->validate_token( $generated['token'], 'test_form', '127.0.0.1' );
		$this->assertTrue( $res1['is_valid'] );

		// Immediate replay of the exact same token: must fail!
		$res2 = $this->manager->validate_token( $generated['token'], 'test_form', '127.0.0.1' );
		$this->assertFalse( $res2['is_valid'] );
		$this->assertSame( 'replayed', $res2['status'] );
		$this->assertSame( 100, $res2['risk_points'] );
	}
}
