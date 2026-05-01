<?php
/**
 * Export_Bulk_Action class file
 *
 * @package wp-export-single-post
 */

declare(strict_types=1);

namespace Alley\WP\Export_Single_Post\Features;

use Alley\WP\Export_Single_Post\Exportable_Post_Types;
use Alley\WP\Export_Single_Post\WXR_Exporter;
use Alley\WP\Types\Feature;

/**
 * Adds an "Export" option to the bulk actions dropdown on post list tables.
 */
class Export_Bulk_Action implements Feature {

	use Exportable_Post_Types;

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		foreach ( $this->get_supported_post_types() as $post_type ) {
			add_filter( "bulk_actions-edit-{$post_type}", $this->add_bulk_export_action( ... ) );
			add_filter( "handle_bulk_actions-edit-{$post_type}", $this->handle_bulk_export( ... ), 10, 3 );
		}
	}

	/**
	 * Add the Export option to the bulk actions dropdown.
	 *
	 * @param array<string,string> $actions Existing bulk actions.
	 * @return array<string,string>
	 */
	public function add_bulk_export_action( array $actions ): array {
		if ( ! current_user_can( 'export' ) ) {
			return $actions;
		}

		$actions['export_posts_wxr'] = __( 'Export', 'wp-export-single-post' );

		return $actions;
	}

	/**
	 * Handle the bulk export action.
	 *
	 * @param string   $redirect_url Redirect URL after bulk action.
	 * @param string   $action       Current bulk action name.
	 * @param string[] $post_ids     Selected post IDs (strings from form POST).
	 */
	public function handle_bulk_export( string $redirect_url, string $action, array $post_ids ): string {
		if ( 'export_posts_wxr' !== $action ) {
			return $redirect_url;
		}

		if ( ! current_user_can( 'export' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to export posts.', 'wp-export-single-post' ),
				'',
				[ 'response' => 403 ]
			);
		}

		$valid_ids = $this->resolve_valid_post_ids( $post_ids );

		if ( $valid_ids === [] ) {
			wp_die(
				esc_html__( 'No exportable posts selected.', 'wp-export-single-post' ),
				'',
				[ 'response' => 400 ]
			);
		}

		$xml = ( new WXR_Exporter() )->export( $valid_ids );

		if ( '' === $xml ) {
			wp_die(
				esc_html__( 'Export failed.', 'wp-export-single-post' ),
				'',
				[ 'response' => 500 ]
			);
		}

		$site_slug = sanitize_title( get_bloginfo( 'name' ) );
		$date      = gmdate( 'Y-m-d' );
		$filename  = "{$site_slug}-export-{$date}.xml";

		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"', true );

		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Resolve a raw list of post ID strings to valid, exportable post IDs.
	 *
	 * @param string[] $post_ids Raw post ID strings from form POST.
	 * @return int[]
	 */
	public function resolve_valid_post_ids( array $post_ids ): array {
		$supported_types = $this->get_supported_post_types();
		$valid_ids       = [];

		foreach ( $post_ids as $post_id ) {
			$post = get_post( absint( $post_id ) );

			if ( $post instanceof \WP_Post && in_array( $post->post_type, $supported_types, true ) ) {
				$valid_ids[] = $post->ID;
			}
		}

		return $valid_ids;
	}
}
