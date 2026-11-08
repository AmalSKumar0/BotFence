<?php
/**
 * Test Honeypot
 *
 * @package KL_Anti_Spam\Tests
 */

namespace KL_Anti_Spam\Tests;

use PHPUnit\Framework\TestCase;
use KL_Anti_Spam\Honeypot;

class HoneypotTest extends TestCase {

	protected Honeypot $honeypot;

	protected function setUp(): void {
		parent::setUp();
		global $kl_test_options;
		$kl_test_options = array(
			'kl_antispam_honeypot_enabled' => 1,
			'kl_antispam_secret_key'       => 'test_honeypot_secret_seed_12345',
		);
		$this->honeypot = new Honeypot();
	}

	public function test_dynamic_field_name_generation(): void {
		$name_today     = $this->honeypot->get_dynamic_field_name( 0 );
		$name_yesterday = $this->honeypot->get_dynamic_field_name( -1 );

		$this->assertNotEmpty( $name_today );
		$this->assertStringStartsWith( 'kl_field_', $name_today );
		$this->assertNotSame( $name_today, $name_yesterday );
	}

	public function test_render_fields_markup(): void {
		$html = $this->honeypot->render_fields( 'form_abc' );

		$this->assertStringContainsString( 'class="kl-hp-wrap"', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html );
		$this->assertStringContainsString( 'tabindex="-1"', $html );
		$this->assertStringContainsString( 'position: absolute', $html );
		$this->assertStringContainsString( 'left: -9999px', $html );

		$name_today = $this->honeypot->get_dynamic_field_name( 0 );
		$this->assertStringContainsString( 'name="' . $name_today . '"', $html );
		$this->assertStringContainsString( 'name="kl_url_website_hp"', $html );
	}

	public function test_clean_submission_passes(): void {
		$data   = array(
			'user_name'  => 'John Doe',
			'user_email' => 'john@example.com',
			'message'    => 'Valid human message',
		);
		$result = $this->honeypot->check_submission( $data );

		$this->assertFalse( $result['triggered'] );
		$this->assertEmpty( $result['field'] );
		$this->assertEmpty( $result['value'] );
	}

	public function test_clean_submission_with_empty_honeypot_passes(): void {
		$name_today = $this->honeypot->get_dynamic_field_name( 0 );
		$data       = array(
			$name_today         => '   ', // whitespace only
			'kl_url_website_hp' => '',
			'user_name'         => 'Jane',
		);
		$result = $this->honeypot->check_submission( $data );

		$this->assertFalse( $result['triggered'] );
	}

	public function test_bot_filling_primary_honeypot_is_detected(): void {
		$name_today = $this->honeypot->get_dynamic_field_name( 0 );
		$data       = array(
			$name_today  => 'http://spambot-link.com',
			'user_name'  => 'Bot User',
			'user_email' => 'bot@spammer.com',
		);
		$result = $this->honeypot->check_submission( $data );

		$this->assertTrue( $result['triggered'] );
		$this->assertSame( $name_today, $result['field'] );
		$this->assertSame( 'http://spambot-link.com', $result['value'] );
	}

	public function test_bot_filling_secondary_honeypot_is_detected(): void {
		$data   = array(
			'kl_url_website_hp' => 'http://malicious-site.com',
			'user_name'         => 'Bot User',
		);
		$result = $this->honeypot->check_submission( $data );

		$this->assertTrue( $result['triggered'] );
		$this->assertSame( 'kl_url_website_hp', $result['field'] );
	}

	public function test_honeypot_disabled_option(): void {
		update_option( 'kl_antispam_honeypot_enabled', 0 );

		$html = $this->honeypot->render_fields( 'form_1' );
		$this->assertSame( '', $html );

		$name_today = $this->honeypot->get_dynamic_field_name( 0 );
		$data       = array( $name_today => 'bot value' );
		$result     = $this->honeypot->check_submission( $data );
		$this->assertFalse( $result['triggered'] );
	}
}
