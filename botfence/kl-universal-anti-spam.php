<?php
/**
 * KL Universal Anti-Spam legacy loader.
 *
 * Provides backwards compatibility for WordPress installations that previously
 * activated the plugin as kl-universal-anti-spam.php.
 *
 * @package BotFence
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/BotFence.php';
