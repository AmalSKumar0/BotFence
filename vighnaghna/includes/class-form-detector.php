<?php
/**
 * Form Detector and Adapter Registry for Vighnaghna.
 *
 * @package Vighnaghna
 */

namespace Vighnaghna;

use Vighnaghna\Integrations\Form_Adapter_Interface;
use Vighnaghna\Integrations\Contact_Form_7;
use Vighnaghna\Integrations\WPForms;
use Vighnaghna\Integrations\Fluent_Forms;
use Vighnaghna\Integrations\Gravity_Forms;
use Vighnaghna\Integrations\Formidable_Forms;
use Vighnaghna\Integrations\Ninja_Forms;
use Vighnaghna\Integrations\Elementor_Forms;
use Vighnaghna\Integrations\WooCommerce;
use Vighnaghna\Integrations\WP_Comments;
use Vighnaghna\Integrations\Custom_Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Form_Detector
 */
class Form_Detector {

	/**
	 * Registered adapters.
	 *
	 * @var array<string, Form_Adapter_Interface>
	 */
	protected array $adapters = array();

	/**
	 * CAPTCHA Engine instance.
	 *
	 * @var Captcha_Engine
	 */
	protected Captcha_Engine $engine;

	/**
	 * Constructor.
	 *
	 * @param Captcha_Engine $engine Engine instance.
	 */
	public function __construct( Captcha_Engine $engine ) {
		$this->engine = $engine;
		$this->register_default_adapters();
	}

	/**
	 * Registers default form builder adapters.
	 */
	protected function register_default_adapters(): void {
		$this->register_adapter( new Contact_Form_7( $this->engine ) );
		$this->register_adapter( new WPForms( $this->engine ) );
		$this->register_adapter( new Fluent_Forms( $this->engine ) );
		$this->register_adapter( new Gravity_Forms( $this->engine ) );
		$this->register_adapter( new Formidable_Forms( $this->engine ) );
		$this->register_adapter( new Ninja_Forms( $this->engine ) );
		$this->register_adapter( new Elementor_Forms( $this->engine ) );
		$this->register_adapter( new WooCommerce( $this->engine ) );
		$this->register_adapter( new WP_Comments( $this->engine ) );
		$this->register_adapter( new Custom_Forms( $this->engine ) );
	}

	/**
	 * Adds an adapter to the registry.
	 *
	 * @param Form_Adapter_Interface $adapter Adapter instance.
	 */
	public function register_adapter( Form_Adapter_Interface $adapter ): void {
		$this->adapters[ $adapter->get_id() ] = $adapter;
	}

	/**
	 * Returns all registered adapters.
	 *
	 * @return array<string, Form_Adapter_Interface>
	 */
	public function get_adapters(): array {
		return $this->adapters;
	}

	/**
	 * Returns a specific adapter by ID.
	 *
	 * @param string $id Adapter ID.
	 * @return Form_Adapter_Interface|null
	 */
	public function get_adapter( string $id ): ?Form_Adapter_Interface {
		return $this->adapters[ $id ] ?? null;
	}

	/**
	 * Initializes all active and enabled adapters by registering their hooks.
	 */
	public function init_adapters(): void {
		foreach ( $this->adapters as $adapter ) {
			if ( $adapter->is_available() && $adapter->is_enabled() ) {
				$adapter->register_hooks();
			}
		}
	}

	/**
	 * Returns status metadata for all supported adapters for admin UI display.
	 *
	 * @return array<int, array{id: string, name: string, is_available: bool, is_enabled: bool}>
	 */
	public function get_detection_status(): array {
		$status = array();

		foreach ( $this->adapters as $adapter ) {
			$status[] = array(
				'id'           => $adapter->get_id(),
				'name'         => $adapter->get_name(),
				'is_available' => $adapter->is_available(),
				'is_enabled'   => $adapter->is_enabled(),
			);
		}

		return $status;
	}
}

// Backwards compatibility aliases.
if ( ! class_exists( '\BotFence\Form_Detector', false ) ) {
	class_alias( '\Vighnaghna\Form_Detector', '\BotFence\Form_Detector' );
}
if ( ! class_exists( '\KL_Anti_Spam\Form_Detector', false ) ) {
	class_alias( '\Vighnaghna\Form_Detector', '\KL_Anti_Spam\Form_Detector' );
}
