<?php
/**
 * Test Admin Dashboard and UI Rendering
 *
 * @package BotFence\Tests
 */

namespace BotFence\Tests;

use PHPUnit\Framework\TestCase;
use BotFence\Admin;
use BotFence\Captcha_Engine;
use BotFence\Form_Detector;

class AdminTest extends TestCase {

	protected Captcha_Engine $engine;
	protected Form_Detector $detector;
	protected Admin $admin;

	protected function setUp(): void {
		parent::setUp();
		global $kl_test_options;
		$kl_test_options = array(
			'botfence_secret_key' => 'admin_secret_key_123456789012345',
			'kl_antispam_secret_key' => 'admin_secret_key_123456789012345',
		);

		$this->engine   = new Captcha_Engine();
		$this->detector = new Form_Detector( $this->engine );
		$this->admin    = new Admin( $this->engine, $this->detector );
	}

	public function test_render_admin_page_renders_successfully(): void {
		ob_start();
		$this->admin->render_admin_page();
		$output = ob_get_clean();

		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'BotFence', $output );
		$this->assertStringContainsString( 'Protected Adapters', $output );
		$this->assertStringContainsString( '/ 10', $output );
		$this->assertStringContainsString( 'Spams Blocked Till Now', $output );
		$this->assertStringContainsString( 'id="tab-dashboard"', $output );
		$this->assertStringContainsString( 'id="tab-integrations"', $output );
		$this->assertStringContainsString( 'id="tab-risk"', $output );
		$this->assertStringContainsString( 'id="tab-rates"', $output );
		$this->assertStringContainsString( 'id="tab-logs"', $output );
		$this->assertStringContainsString( 'id="tab-diagnostics"', $output );
	}

	public function test_all_six_tabs_accessible_via_query_param(): void {
		$tabs = array( 'dashboard', 'integrations', 'risk', 'rates', 'logs', 'diagnostics' );

		foreach ( $tabs as $tab ) {
			$_GET['tab'] = $tab;
			ob_start();
			$this->admin->render_admin_page();
			$output = ob_get_clean();

			$this->assertStringContainsString( 'href="#tab-' . $tab . '" class="botfence-nav-tab kl-nav-tab active"', $output );
			$this->assertStringContainsString( 'id="tab-' . $tab . '" class="botfence-tab-pane kl-tab-pane" style=""', $output );
		}
		unset( $_GET['tab'] );
	}

	public function test_adapters_implement_is_detected_alias(): void {
		$adapters = $this->detector->get_adapters();
		$this->assertNotEmpty( $adapters );

		foreach ( $adapters as $adapter ) {
			$this->assertTrue( method_exists( $adapter, 'is_detected' ) );
			$this->assertSame( $adapter->is_available(), $adapter->is_detected() );
		}
	}

	public function test_active_adapters_calculation_with_disabled_adapter(): void {
		// Native comments and custom forms are available in mock WP environment.
		update_option( 'botfence_enable_comments', 0 );
		update_option( 'kl_antispam_enable_comments', 0 );

		ob_start();
		$this->admin->render_admin_page();
		$output = ob_get_clean();

		// Custom forms + WooCommerce mock may still be active, but comments is excluded.
		$this->assertStringContainsString( '/ 10', $output );
	}
}
