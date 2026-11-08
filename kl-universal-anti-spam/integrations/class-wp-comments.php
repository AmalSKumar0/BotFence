<?php
/**
 * WordPress Native Comments Adapter for KL Universal Anti-Spam.
 *
 * @package KL_Anti_Spam\Integrations
 */

namespace KL_Anti_Spam\Integrations;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_Comments
 */
class WP_Comments extends Abstract_Form_Adapter {

	/**
	 * Adapter ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'comments';
	}

	/**
	 * Adapter display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'WordPress Comments';
	}

	/**
	 * Always available as part of core WordPress.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return true;
	}

	/**
	 * Registers WordPress comment hooks.
	 */
	public function register_hooks(): void {
		if ( ! $this->is_available() || ! $this->is_enabled() ) {
			return;
		}

		// Inject fields into comment forms.
		add_action( 'comment_form_after_fields', array( $this, 'render_comment_fields' ), 20 );
		add_action( 'comment_form_logged_in_after', array( $this, 'render_comment_fields' ), 20 );

		// Intercept and validate comment submissions before database insertion.
		add_filter( 'preprocess_comment', array( $this, 'validate_comment' ), 1, 1 );
	}

	/**
	 * Outputs security fields in comment form.
	 */
	public function render_comment_fields(): void {
		global $post;
		$form_id = ( $post && isset( $post->ID ) ) ? 'post_' . $post->ID : 'comment';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_fields( $form_id );
	}

	/**
	 * Validates comment submission before saving.
	 *
	 * @param array<string, mixed> $commentdata Comment data.
	 * @return array<string, mixed>|WP_Error Comment data or error.
	 */
	public function validate_comment( $commentdata ) {
		// Bypass check if user has moderate_comments capability.
		if ( current_user_can( 'moderate_comments' ) ) {
			return $commentdata;
		}

		$post_id   = isset( $commentdata['comment_post_ID'] ) ? 'post_' . $commentdata['comment_post_ID'] : 'comment';
		$post_data = ! empty( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Merge comment text into submission data if not in post superglobal.
		if ( ! empty( $commentdata['comment_content'] ) && empty( $post_data['comment'] ) ) {
			$post_data['comment'] = $commentdata['comment_content'];
		}

		$res = $this->verify( $post_data, $post_id );

		if ( ! $res['is_valid'] ) {
			$msg = ! empty( $res['error_message'] ) ? $res['error_message'] : __( 'Comment blocked by anti-spam verification.', 'kl-universal-anti-spam' );
			// Return WP_Error which wp-comments-post.php terminates with cleanly.
			return new WP_Error( 'kl_comment_spam', $msg, array( 'response' => 403 ) );
		}

		return $commentdata;
	}
}
