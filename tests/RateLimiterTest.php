<?php
/**
 * Test Rate_Limiter
 *
 * @package KL_Anti_Spam\Tests
 */

namespace KL_Anti_Spam\Tests;

use PHPUnit\Framework\TestCase;
use KL_Anti_Spam\Rate_Limiter;

class RateLimiterTest extends TestCase {

	protected Rate_Limiter $limiter;

	protected function setUp(): void {
		parent::setUp();
		global $kl_test_options, $wpdb;
		$kl_test_options = array(
			'kl_antispam_secret_key'              => 'test_rate_secret_1234567890123456',
			'kl_antispam_rate_limit_ip_per_minute' => 3,
			'kl_antispam_rate_limit_ip_per_hour'   => 10,
			'kl_antispam_quarantine_duration'      => 900,
			'kl_antispam_trust_reverse_proxies'    => 0,
			'kl_antispam_trusted_proxy_ips'        => "127.0.0.1\n10.0.0.1",
		);
		$wpdb->rates_table = array();
		$this->limiter     = new Rate_Limiter();
	}

	public function test_get_client_ip_direct(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.195';
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CF_CONNECTING_IP'] );

		$this->assertSame( '203.0.113.195', $this->limiter->get_client_ip() );
	}

	public function test_rejects_spoofed_forwarded_headers_when_untrusted(): void {
		$_SERVER['REMOTE_ADDR']          = '198.51.100.4';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.1.1.1'; // Spoof attempt
		update_option( 'kl_antispam_trust_reverse_proxies', 0 );

		// Should strictly ignore X-Forwarded-For because reverse proxies are not trusted.
		$this->assertSame( '198.51.100.4', $this->limiter->get_client_ip() );
	}

	public function test_accepts_forwarded_header_from_trusted_proxy(): void {
		$_SERVER['REMOTE_ADDR']          = '10.0.0.1'; // In trusted proxy list
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.88, 10.0.0.1';
		update_option( 'kl_antispam_trust_reverse_proxies', 1 );

		$this->assertSame( '203.0.113.88', $this->limiter->get_client_ip() );
	}

	public function test_privacy_ip_hashing_and_masking(): void {
		$ip   = '192.168.1.105';
		$hash = $this->limiter->hash_ip( $ip );
		$mask = $this->limiter->mask_ip( $ip );

		$this->assertSame( 64, strlen( $hash ) );
		$this->assertSame( '192.168.1.***', $mask );

		$ipv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
		$this->assertStringContainsString( '2001:0db8:****', $this->limiter->mask_ip( $ipv6 ) );
	}

	public function test_submission_rate_limiting(): void {
		$ip = '198.51.100.77';

		// Limit is 3 per minute.
		$res1 = $this->limiter->check_submission( 'contact_form', 'Message 1', $ip );
		$this->assertTrue( $res1['allowed'] );
		$this->assertFalse( $res1['rate_exceeded'] );

		$res2 = $this->limiter->check_submission( 'contact_form', 'Message 2', $ip );
		$this->assertTrue( $res2['allowed'] );

		$res3 = $this->limiter->check_submission( 'contact_form', 'Message 3', $ip );
		$this->assertTrue( $res3['allowed'] );

		// 4th submission exceeds 3/min limit!
		$res4 = $this->limiter->check_submission( 'contact_form', 'Message 4', $ip );
		$this->assertTrue( $res4['rate_exceeded'] );
		$this->assertGreaterThanOrEqual( 40, $res4['risk_points'] );
	}

	public function test_repeated_identical_message_detection(): void {
		$ip  = '198.51.100.88';
		$msg = 'Please visit our website for discount deals and special offers right now!';

		$res1 = $this->limiter->check_submission( 'contact_form', $msg, $ip );
		$this->assertFalse( $res1['repeated_message'] );

		// Submit identical message again.
		$res2 = $this->limiter->check_submission( 'contact_form', $msg, $ip );
		$this->assertTrue( $res2['repeated_message'] );
		$this->assertGreaterThanOrEqual( 35, $res2['risk_points'] );
	}

	public function test_quarantine_block_enforcement(): void {
		global $wpdb;
		$ip      = '203.0.113.99';
		$ip_hash = $this->limiter->hash_ip( $ip );

		// Manually place in active quarantine.
		$wpdb->rates_table[ 'quarantine:' . $ip_hash . ':quarantine' ] = (object) array(
			'identifier' => 'quarantine:' . $ip_hash,
			'limit_type' => 'quarantine',
			'count'      => 1,
			'first_seen' => gmdate( 'Y-m-d H:i:s' ),
			'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 900 ),
		);

		$res = $this->limiter->check_submission( 'form_1', 'Hello', $ip );
		$this->assertFalse( $res['allowed'] );
		$this->assertTrue( $res['is_quarantined'] );
		$this->assertSame( 100, $res['risk_points'] );
	}

	public function test_expired_rate_limit_resets_counter(): void {
		global $wpdb;

		$ident      = 'ip_m:test_hash_123';
		$limit_type = 'ip_minute';

		// Seed an expired entry with count = 5 from 2 hours ago.
		$wpdb->rates_table[ $ident . ':' . $limit_type ] = (object) array(
			'identifier' => $ident,
			'limit_type' => $limit_type,
			'count'      => 5,
			'first_seen' => gmdate( 'Y-m-d H:i:s', time() - 7200 ),
			'expires_at' => gmdate( 'Y-m-d H:i:s', time() - 7140 ),
		);

		// Increment counter now with new window.
		$now_dt = gmdate( 'Y-m-d H:i:s' );
		$exp_dt = gmdate( 'Y-m-d H:i:s', time() + 60 );
		$count  = $this->limiter->increment_counter( $ident, $limit_type, $now_dt, $exp_dt );

		// Counter must reset to 1 rather than incrementing to 6.
		$this->assertSame( 1, $count );
	}

	public function test_per_form_rate_limiting(): void {
		$ip = '198.51.100.99';
		update_option( 'botfence_rate_limit_ip_per_minute', 50 );
		update_option( 'kl_antispam_rate_limit_ip_per_minute', 50 );
		update_option( 'botfence_rate_limit_form_per_minute', 3 );

		// Form limit is 3/min. Run 3 submissions.
		for ( $i = 1; $i <= 3; $i++ ) {
			$res = $this->limiter->check_submission( 'contact_vip', "Msg $i", $ip );
			$this->assertFalse( $res['rate_exceeded'] );
		}

		// 4th submission exceeds per-form rate limit.
		$res = $this->limiter->check_submission( 'contact_vip', 'Msg 4', $ip );
		$this->assertTrue( $res['rate_exceeded'] );
		$this->assertStringContainsString( 'contact_vip', $res['reason'] );
	}
}
