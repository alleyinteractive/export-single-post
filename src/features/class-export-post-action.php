<?php
/**
 * Export_Post_Action class file
 *
 * @package wp-export-single-post
 */

declare(strict_types=1);

namespace Alley\WP\Export_Single_Post\Features;

use Alley\WP\Export_Single_Post\Exportable_Post_Types;
use Alley\WP\Export_Single_Post\WXR_Exporter;
use Alley\WP\Types\Feature;

/**
 * Adds an "Export" link to post row actions that streams a WXR file.
 */
class Export_Post_Action implements Feature {

	use Exportable_Post_Types;

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'post_row_actions', $this->add_export_link( ... ), 10, 2 );
		add_filter( 'page_row_actions', $this->add_export_link( ... ), 10, 2 );
		add_action( 'admin_post_export_single_post', $this->handle_export( ... ) );
		add_action(
			'admin_post_nopriv_export_single_post',
			static function (): void {
				auth_redirect();
			}
		);
	}

	/**
	 * Add an "Export" link to the post row actions.
	 *
	 * @param array<string,string> $actions Existing row actions.
	 * @param \WP_Post             $post    Current post object.
	 * @return array<string,string>
	 */
	public function add_export_link( array $actions, \WP_Post $post ): array {
		if ( ! in_array( $post->post_type, $this->get_supported_post_types(), true ) ) {
			return $actions;
		}

		if ( ! current_user_can( 'export' ) ) {
			return $actions;
		}

		$url = add_query_arg(
			[
				'action'   => 'export_single_post',
				'post_id'  => $post->ID,
				'_wpnonce' => wp_create_nonce( "export_single_post_{$post->ID}" ),
			],
			admin_url( 'admin-post.php' )
		);

		$actions['export'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'Export', 'wp-export-single-post' )
		);

		return $actions;
	}

	/**
	 * Handle the export request from admin-post.php.
	 */
	public function handle_export(): void {
		$post_id = absint( $_GET['post_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		check_admin_referer( "export_single_post_{$post_id}" );

		if ( ! current_user_can( 'export' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to export posts.', 'wp-export-single-post' ),
				'',
				[ 'response' => 403 ]
			);
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			wp_die(
				esc_html__( 'Invalid post.', 'wp-export-single-post' ),
				'',
				[ 'response' => 404 ]
			);
		}

		if ( ! in_array( $post->post_type, $this->get_supported_post_types(), true ) ) {
			wp_die(
				esc_html__( 'This post type cannot be exported.', 'wp-export-single-post' ),
				'',
				[ 'response' => 400 ]
			);
		}

		$xml      = $this->generate_export( $post );
		$filename = sanitize_file_name( $post->post_name ?: (string) $post->ID ) . '.xml';

		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"', true );

		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Generate the WXR XML for the given post and its attachments.
	 *
	 * @param \WP_Post $post Post to export.
	 * @return string WXR XML content.
	 */
	public function generate_export( \WP_Post $post ): string {
		return ( new WXR_Exporter() )->export( [ $post->ID ] );
	}
}
