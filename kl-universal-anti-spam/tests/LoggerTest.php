<?php
/**
 * Test Logger
 *
 * @package KL_Anti_Spam\Tests
 */

namespace KL_Anti_Spam\Tests;

use PHPUnit\Framework\TestCase;
use KL_Anti_Spam\Logger;

class LoggerTest extends TestCase {

	protected Logger $logger;

	protected function setUp(): void {
		parent::setUp();
		global $wpdb;
		$wpdb->logs_table = array();
		$this->logger     = new Logger();
	}

	public function test_logging_stores_anonymized_data(): void {
		$id = $this->logger->log( array(
			'form_type'       => 'wpforms',
			'form_id'         => 'form_12',
			'risk_score'      => 85,
			'action_taken'    => 'block',
			'reasons'         => array(
				array( 'code' => 'honeypot_completed', 'name' => 'Honeypot filled', 'points' => 100 ),
			),
			'ip_hash'         => 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890',
			'masked_ip'       => '192.168.1.***',
			'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
			'submission_time' => 1.25,
			'is_spam'         => 1,
		) );

		$this->assertGreaterThan( 0, $id );

		$logs = $this->logger->get_logs();
		$this->assertCount( 1, $logs['items'] );

		$entry = $logs['items'][0];
		$this->assertSame( 'wpforms', $entry->form_type );
		$this->assertSame( 'form_12', $entry->form_id );
		$this->assertSame( 85, (int) $entry->risk_score );
		$this->assertSame( 'block', $entry->action_taken );
		$this->assertSame( '192.168.1.***', $entry->masked_ip );
		$this->assertSame( 1, (int) $entry->is_spam );
	}

	public function test_stats_aggregation(): void {
		// Log 2 blocked spam submissions and 3 allowed submissions.
		$this->logger->log( array(
			'form_type'    => 'cf7',
			'action_taken' => 'block',
			'is_spam'      => 1,
			'reasons'      => array( array( 'name' => 'Honeypot field filled' ) ),
		) );
		$this->logger->log( array(
			'form_type'    => 'cf7',
			'action_taken' => 'block',
			'is_spam'      => 1,
			'reasons'      => array( array( 'name' => 'Honeypot field filled' ) ),
		) );
		$this->logger->log( array(
			'form_type'    => 'wpforms',
			'action_taken' => 'allow',
			'is_spam'      => 0,
			'reasons'      => array(),
		) );
		$this->logger->log( array(
			'form_type'    => 'wpforms',
			'action_taken' => 'allow',
			'is_spam'      => 0,
			'reasons'      => array(),
		) );
		$this->logger->log( array(
			'form_type'    => 'comments',
			'action_taken' => 'allow',
			'is_spam'      => 0,
			'reasons'      => array(),
		) );

		$stats = $this->logger->get_stats();

		$this->assertSame( 5, $stats['total_checked'] );
		$this->assertSame( 2, $stats['total_blocked'] );
		$this->assertSame( 3, $stats['total_allowed'] );
		$this->assertSame( 40.0, $stats['block_rate'] );
		$this->assertArrayHasKey( 'Honeypot field filled', $stats['top_signals'] );
		$this->assertSame( 2, $stats['top_signals']['Honeypot field filled'] );
	}

	public function test_clear_logs(): void {
		$this->logger->log( array( 'form_type' => 'test' ) );
		$this->assertCount( 1, $this->logger->get_logs()['items'] );

		$this->logger->clear_logs();
		$this->assertCount( 0, $this->logger->get_logs()['items'] );
	}
}
