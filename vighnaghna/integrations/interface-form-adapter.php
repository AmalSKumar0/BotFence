<?php
/**
 * Form Adapter Interface for Vighnaghna.
 *
 * @package Vighnaghna\Integrations
 */

namespace Vighnaghna\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Form_Adapter_Interface
 */
interface Form_Adapter_Interface {

	/**
	 * Unique identifier for the form adapter.
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Human-readable display name for the form builder.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Detects whether the form builder plugin is currently active.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Detects whether the form builder plugin is currently active / detected.
	 *
	 * Alias of is_available().
	 *
	 * @return bool
	 */
	public function is_detected(): bool;

	/**
	 * Checks whether protection for this form builder is enabled in settings.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool;

	/**
	 * Registers all hooks (rendering, submission intercept, AJAX, etc.).
	 */
	public function register_hooks(): void;

	/**
	 * Generates hidden anti-spam fields (honeypot, token input, fallback).
	 *
	 * @param string $form_id Form identifier.
	 * @return string HTML markup.
	 */
	public function render_fields( string $form_id = '' ): string;
}

// Backwards compatibility aliases.
if ( ! interface_exists( '\BotFence\Integrations\Form_Adapter_Interface', false ) ) {
	class_alias( '\Vighnaghna\Integrations\Form_Adapter_Interface', '\BotFence\Integrations\Form_Adapter_Interface' );
}
if ( ! interface_exists( '\KL_Anti_Spam\Integrations\Form_Adapter_Interface', false ) ) {
	class_alias( '\Vighnaghna\Integrations\Form_Adapter_Interface', '\KL_Anti_Spam\Integrations\Form_Adapter_Interface' );
}
