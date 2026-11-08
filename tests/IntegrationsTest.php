<?php
/**
 * Test All 10 Supported Form Integrations
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
use KL_Anti_Spam\Integrations\Contact_Form_7;
use KL_Anti_Spam\Integrations\WPForms;
use KL_Anti_Spam\Integrations\Fluent_Forms;
use KL_Anti_Spam\Integrations\Gravity_Forms;
use KL_Anti_Spam\Integrations\Formidable_Forms;
use KL_Anti_Spam\Integrations\Ninja_Forms;
use KL_Anti_Spam\Integrations\Elementor_Forms;
use KL_Anti_Spam\Integrations\WooCommerce;
use KL_Anti_Spam\Integrations\WP_Comments;
use KL_Anti_Spam\Integrations\Custom_Forms;

class IntegrationsTest extends TestCase {

	protected Captcha_Engine $engine;
	protected Token_Manager $token_manager;
	protected Honeypot $honeypot;

	protected function setUp(): void {
		parent::setUp();
		global $kl_test_options, $kl_test_transients, $wpdb, $kl_test_wc_notices;
		$kl_test_options    = array(
			'kl_antispam_enabled'              => 1,
			'kl_antispam_secret_key'           => 'test_integrations_secret_key_12345678',
			'kl_antispam_honeypot_enabled'     => 1,
			'kl_antispam_min_submission_time'  => 2.5,
			'kl_antispam_weight_honeypot'      => 100,
			'kl_antispam_weight_invalid_token' => 100,
		);
		$kl_test_transients = array();
		$kl_test_wc_notices = array();
		$wpdb->logs_table   = array();
		$wpdb->rates_table  = array();
		Contact_Form_7::clear_cache();
		if ( class_exists( '\WPCF7_ContactForm' ) ) {
			\WPCF7_ContactForm::$current = null;
		}
		if ( class_exists( '\WPCF7_Submission' ) ) {
			\WPCF7_Submission::$instance = null;
		}

		$this->token_manager = new Token_Manager();
		$this->token_manager->set_secret_key( 'test_integrations_secret_key_12345678' );
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

	/**
	 * Helper to create legitimate submission payload.
	 */
	protected function create_clean_post( string $form_id ): array {
		$tok_data  = $this->token_manager->generate_token( $form_id, '127.0.0.1' );
		$telemetry = base64_encode( (string) wp_json_encode( array(
			'elapsed_time' => 4.5,
			'mouse_moves'  => 12,
			'keystrokes'   => 20,
			'touches'      => 0,
			'is_trusted'   => true,
			'webdriver'    => false,
		) ) );

		return array(
			'_wpcf7'                 => $form_id,
			'kl_anti_spam_token'     => $tok_data['token'],
			'kl_anti_spam_telemetry' => $telemetry,
			'message'                => 'Legitimate form message ' . bin2hex( random_bytes( 8 ) ),
		);
	}

	/**
	 * 1. Contact Form 7
	 */
	public function test_contact_form_7_integration(): void {
		$adapter = new Contact_Form_7( $this->engine );
		$this->assertSame( 'cf7', $adapter->get_id() );
		$this->assertSame( 'Contact Form 7', $adapter->get_name() );

		// Form elements injection.
		$html = $adapter->filter_form_elements( '<div class="wpcf7-fields"><input type="text" name="your-name" /></div>' );
		$this->assertStringContainsString( 'kl_anti_spam_token', $html );
		$this->assertStringContainsString( 'kl-hp-wrap', $html );

		// Clean submission -> wpcf7_spam returns false.
		$_POST = $this->create_clean_post( 'cf7_1' );
		$this->assertFalse( $adapter->filter_spam( false ) );

		// Bot honeypot submission -> wpcf7_spam returns true.
		$hp_field = $this->honeypot->get_dynamic_field_name( 0 );
		$_POST[ $hp_field ] = 'spam link';
		$this->assertTrue( $adapter->filter_spam( false ) );

		// Mail abort action: verifies mail is skipped cleanly without setting $abort = true.
		$abort = false;
		$dummy_cf = new class {
			public bool $skip_mail = false;
			public function id(): string {
				return 'cf7_1';
			}
		};
		$adapter->action_before_send_mail( $dummy_cf, $abort );
		$this->assertTrue( $dummy_cf->skip_mail );
		$this->assertFalse( $abort ); // Must NOT use $abort = true as a generic spam rejection mechanism.
	}

	/**
	 * 2. WPForms
	 */
	public function test_wpforms_integration(): void {
		$adapter = new WPForms( $this->engine );
		$this->assertSame( 'wpforms', $adapter->get_id() );

		// Frontend output injection.
		$out = $adapter->filter_frontend_output( '<form class="wpforms-form"><div class="wpforms-fields"></div></form>', array( 'id' => '10' ) );
		$this->assertStringContainsString( 'kl_anti_spam_token', $out );
		$this->assertStringContainsString( '</form>', $out );

		// Clean submission -> errors array remains empty.
		$_POST  = $this->create_clean_post( '10' );
		$errors = $adapter->validate_submission( array(), array( 'id' => '10' ) );
		$this->assertArrayNotHasKey( '10', $errors );

		// Bot submission with invalid token -> errors['10']['header'] set.
		$_POST['kl_anti_spam_token'] = 'tampered_token';
		$errors_bad = $adapter->validate_submission( array(), array( 'id' => '10' ) );
		$this->assertArrayHasKey( '10', $errors_bad );
		$this->assertNotEmpty( $errors_bad['10']['header'] );
	}

	/**
	 * 3. Fluent Forms
	 */
	public function test_fluent_forms_integration(): void {
		$adapter = new Fluent_Forms( $this->engine );
		$this->assertSame( 'fluent_forms', $adapter->get_id() );

		// Clean submission -> errors unchanged.
		$_POST  = $this->create_clean_post( 'fluent_1' );
		$errors = $adapter->validate_submission( array(), array(), (object) array( 'id' => 'fluent_1' ) );
		$this->assertArrayNotHasKey( 'kl_anti_spam', $errors );

		// Honeypot submission -> errors['kl_anti_spam'] populated.
		$hp_field = $this->honeypot->get_dynamic_field_name( 0 );
		$_POST[ $hp_field ] = 'spam text';
		$errors_bad = $adapter->validate_submission( array(), array(), (object) array( 'id' => 'fluent_1' ) );
		$this->assertArrayHasKey( 'kl_anti_spam', $errors_bad );
	}

	/**
	 * 4. Gravity Forms
	 */
	public function test_gravity_forms_integration(): void {
		$adapter = new Gravity_Forms( $this->engine );
		$this->assertSame( 'gravity_forms', $adapter->get_id() );

		// Form filter injection.
		$html = $adapter->filter_form_html( '<form id="gform_1"><div class="gform_body"></div></form>', array( 'id' => 1 ) );
		$this->assertStringContainsString( 'kl_anti_spam_token', $html );

		// Clean submission -> validation_result['is_valid'] remains true.
		$_POST = $this->create_clean_post( '1' );
		$val_res = array( 'is_valid' => true, 'form' => array( 'id' => 1 ) );
		$res = $adapter->validate_submission( $val_res );
		$this->assertTrue( $res['is_valid'] );

		// Missing token submission -> validation_result['is_valid'] is false.
		$_POST = array( 'some_field' => 'value' );
		$res_bad = $adapter->validate_submission( $val_res );
		$this->assertFalse( $res_bad['is_valid'] );
		$this->assertNotEmpty( $res_bad['form']['validation_message'] );
	}

	/**
	 * 5. Formidable Forms
	 */
	public function test_formidable_forms_integration(): void {
		$adapter = new Formidable_Forms( $this->engine );
		$this->assertSame( 'formidable', $adapter->get_id() );

		// Clean submission.
		$_POST = $this->create_clean_post( 'frm_5' );
		$errors = $adapter->validate_entry( array(), array( 'form_id' => 'frm_5' ) );
		$this->assertArrayNotHasKey( 'kl_anti_spam', $errors );

		// Bot honeypot submission.
		$hp_field = $this->honeypot->get_dynamic_field_name( 0 );
		$_POST[ $hp_field ] = 'bad bot input';
		$errors_bad = $adapter->validate_entry( array(), array( 'form_id' => 'frm_5' ) );
		$this->assertArrayHasKey( 'kl_anti_spam', $errors_bad );
	}

	/**
	 * 6. Ninja Forms
	 */
	public function test_ninja_forms_integration(): void {
		$adapter = new Ninja_Forms( $this->engine );
		$this->assertSame( 'ninja_forms', $adapter->get_id() );

		// Clean submission.
		$_POST = $this->create_clean_post( 'nf_2' );
		$submit_data = array( 'id' => 'nf_2', 'fields' => array() );
		$res = $adapter->validate_submission( $submit_data );
		$this->assertArrayNotHasKey( 'errors', $res );

		// Bot submission (missing token).
		$_POST = array();
		$res_bad = $adapter->validate_submission( $submit_data );
		$this->assertArrayHasKey( 'errors', $res_bad );
		$this->assertArrayHasKey( 'kl_anti_spam', $res_bad['errors']['forms'] );
	}

	/**
	 * 7. Elementor Forms
	 */
	public function test_elementor_forms_integration(): void {
		$adapter = new Elementor_Forms( $this->engine );
		$this->assertSame( 'elementor', $adapter->get_id() );

		// Mock Elementor ajax handler.
		$ajax_handler = new class {
			public array $errors = array();
			public function add_error_message( $msg ): void {
				$this->errors[] = $msg;
			}
		};

		$record = new class {
			public function get_form_settings( $k ): string {
				return 'elem_form_1';
			}
		};

		// Clean submission -> zero errors.
		$_POST = $this->create_clean_post( 'elem_form_1' );
		$adapter->validate_submission( $record, $ajax_handler );
		$this->assertEmpty( $ajax_handler->errors );

		// Bot honeypot submission -> error recorded.
		$hp_field = $this->honeypot->get_dynamic_field_name( 0 );
		$_POST[ $hp_field ] = 'spam payload';
		$adapter->validate_submission( $record, $ajax_handler );
		$this->assertNotEmpty( $ajax_handler->errors );
	}

	/**
	 * 8. WooCommerce Forms
	 */
	public function test_woocommerce_integration(): void {
		global $kl_test_wc_notices;
		$adapter = new WooCommerce( $this->engine );
		$this->assertSame( 'woocommerce', $adapter->get_id() );

		// Checkout validation: clean submission.
		$_POST = $this->create_clean_post( 'wc_checkout' );
		$adapter->validate_checkout();
		$this->assertEmpty( $kl_test_wc_notices );

		// Checkout validation: honeypot spam -> wc_add_notice called with 'error'.
		$hp_field = $this->honeypot->get_dynamic_field_name( 0 );
		$_POST[ $hp_field ] = 'spam buyer';
		$adapter->validate_checkout();
		$this->assertNotEmpty( $kl_test_wc_notices );
		$this->assertSame( 'error', $kl_test_wc_notices[0]['type'] );

		// Registration validation: honeypot spam -> WP_Error populated.
		$val_errors = new \WP_Error();
		$adapter->validate_registration( 'spammer', 'spam@test.com', $val_errors );
		$this->assertTrue( in_array( $val_errors->get_error_code(), array( 'botfence_spam_detected', 'kl_spam_detected' ), true ) );

		// Login validation: clean submission -> returns user.
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST = $this->create_clean_post( 'wc_login' );
		$_POST['login'] = '1';
		$user_mock = (object) array( 'ID' => 123, 'user_login' => 'legit_user' );
		$login_res = $adapter->validate_login( $user_mock, 'legit_user', 'password' );
		$this->assertSame( $user_mock, $login_res );

		// Login validation: honeypot spam -> returns WP_Error.
		$_POST[ $hp_field ] = 'spam login bot';
		$spam_res = $adapter->validate_login( $user_mock, 'legit_user', 'password' );
		$this->assertTrue( is_wp_error( $spam_res ) );
		$this->assertSame( 'botfence_spam_detected', $spam_res->get_error_code() );
	}

	/**
	 * 9. WordPress Comments
	 */
	public function test_wp_comments_integration(): void {
		$adapter = new WP_Comments( $this->engine );
		$this->assertSame( 'comments', $adapter->get_id() );

		$comment_data = array(
			'comment_post_ID'      => 42,
			'comment_author'       => 'Reader',
			'comment_author_email' => 'reader@example.com',
			'comment_content'      => 'Great article, thanks for sharing!',
		);

		// Clean comment -> commentdata returned unharmed.
		$_POST = $this->create_clean_post( 'post_42' );
		$res = $adapter->validate_comment( $comment_data );
		$this->assertIsArray( $res );
		$this->assertSame( 'Great article, thanks for sharing!', $res['comment_content'] );

		// Bot comment with populated honeypot -> returns WP_Error with 403 response.
		$hp_field = $this->honeypot->get_dynamic_field_name( 0 );
		$_POST[ $hp_field ] = 'cheap sunglasses http://spam.com';
		$err = $adapter->validate_comment( $comment_data );
		$this->assertTrue( is_wp_error( $err ) );
		$this->assertTrue( in_array( $err->get_error_code(), array( 'botfence_comment_spam', 'kl_comment_spam' ), true ) );
	}

	/**
	 * 10. Custom HTML Forms & Developer API
	 */
	public function test_custom_html_forms_integration(): void {
		$adapter = new Custom_Forms( $this->engine );
		$this->assertSame( 'custom_forms', $adapter->get_id() );

		// Content filter injection into <form method="post">.
		$content = '<p>Contact us:</p><form method="post" action="/submit"><input type="text" name="name" /></form>';
		$filtered = $adapter->filter_content_forms( $content );
		$this->assertTrue( str_contains( $filtered, 'botfence_token' ) || str_contains( $filtered, 'kl_anti_spam_token' ) );
		$this->assertTrue( str_contains( $filtered, 'botfence-hp-wrap' ) || str_contains( $filtered, 'kl-hp-wrap' ) );

		// Developer filter: kl_anti_spam_verify_submission.
		$clean_post = $this->create_clean_post( 'my_theme_form' );
		$is_valid   = $adapter->hook_verify_submission( true, $clean_post, 'my_theme_form' );
		$this->assertTrue( $is_valid );

		// Developer action: kl_anti_spam_verify_or_die.
		// Use fresh token to avoid token replay block.
		$clean_post2 = $this->create_clean_post( 'my_theme_form' );
		$adapter->hook_verify_or_die( 'my_theme_form', $clean_post2 );

		// Spam post MUST die (throws RuntimeException from wp_die mock).
		$hp_field = $this->honeypot->get_dynamic_field_name( 0 );
		$spam_post = $this->create_clean_post( 'my_theme_form' );
		$spam_post[ $hp_field ] = 'bot spam';

		$this->expectException( \RuntimeException::class );
		$adapter->hook_verify_or_die( 'my_theme_form', $spam_post );
	}
}
