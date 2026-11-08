<?php
/**
 * Test Form_Detector
 *
 * @package KL_Anti_Spam\Tests
 */

namespace KL_Anti_Spam\Tests;

use PHPUnit\Framework\TestCase;
use KL_Anti_Spam\Form_Detector;
use KL_Anti_Spam\Captcha_Engine;

class FormDetectorTest extends TestCase {

	protected Form_Detector $detector;

	protected function setUp(): void {
		parent::setUp();
		global $kl_test_options;
		$kl_test_options = array(
			'kl_antispam_secret_key' => 'detector_secret_key_1234567890123',
		);
		$engine         = new Captcha_Engine();
		$this->detector = new Form_Detector( $engine );
	}

	public function test_registers_all_ten_adapters(): void {
		$adapters = $this->detector->get_adapters();

		$this->assertCount( 10, $adapters );

		$expected_ids = array(
			'cf7',
			'wpforms',
			'fluent_forms',
			'gravity_forms',
			'formidable',
			'ninja_forms',
			'elementor',
			'woocommerce',
			'comments',
			'custom_forms',
		);

		foreach ( $expected_ids as $id ) {
			$this->assertArrayHasKey( $id, $adapters );
			$this->assertSame( $id, $adapters[ $id ]->get_id() );
		}
	}

	public function test_detection_status(): void {
		$status = $this->detector->get_detection_status();

		$this->assertCount( 10, $status );
		foreach ( $status as $item ) {
			$this->assertArrayHasKey( 'id', $item );
			$this->assertArrayHasKey( 'name', $item );
			$this->assertArrayHasKey( 'is_available', $item );
			$this->assertArrayHasKey( 'is_enabled', $item );
		}
	}

	public function test_enable_disable_adapter(): void {
		$adapter = $this->detector->get_adapter( 'cf7' );
		$this->assertNotNull( $adapter );
		$this->assertTrue( $adapter->is_enabled() );

		update_option( 'kl_antispam_enable_cf7', 0 );
		$this->assertFalse( $adapter->is_enabled() );
	}
}
