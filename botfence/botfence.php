<?php
/**
 * BotFence legacy loader.
 *
 * Provides backwards compatibility for WordPress installations that previously
 * activated the plugin as botfence/botfence.php.
 *
 * @package BotFence
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/BotFence.php';
