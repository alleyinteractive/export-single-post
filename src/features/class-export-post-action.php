<?php
/**
 * Export_Post_Action class file
 *
 * @package wp-export-single-post
 */

declare(strict_types=1);

namespace Alley\WP\Export_Single_Post\Features;

use Alley\WP\Types\Feature;

/**
 * Adds an "Export" link to post row actions that streams a WXR file.
 */
class Export_Post_Action implements Feature {

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

		// Content-Disposition is set by export_wp() via the export_wp_filename filter in generate_export().
		echo $this->generate_export( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Generate the WXR XML for the given post and its attachments.
	 *
	 * Uses WordPress core's export_wp() for WXR generation. Because export_wp()
	 * uses raw $wpdb queries rather than WP_Query, the posts_where filter does
	 * not apply. The ID-collection query is intercepted via the 'query' filter
	 * and replaced with an exact IN() list of the target IDs.
	 *
	 * @param \WP_Post $post Post to export.
	 * @return string WXR XML content.
	 */
	public function generate_export( \WP_Post $post ): string {
		global $wpdb;

		$attachment_ids = get_children( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_children
			[
				'post_parent'    => $post->ID,
				'post_type'      => 'attachment',
				'fields'         => 'ids',
				'posts_per_page' => -1,
			]
		);

		$ids_sql     = implode( ',', array_map( intval( ... ), array_merge( [ $post->ID ], (array) $attachment_ids ) ) );
		$posts_table = $wpdb->posts;

		$restrict_ids = static function ( string $query ) use ( $ids_sql, $posts_table ): string {
			if ( str_contains( $query, "SELECT ID FROM {$posts_table}" ) ) {
				return "SELECT ID FROM {$posts_table} WHERE ID IN ({$ids_sql})";
			}
			return $query;
		};

		$filename     = sanitize_file_name( $post->post_name ?: (string) $post->ID ) . '.xml';
		$set_filename = static fn(): string => $filename;

		require_once ABSPATH . 'wp-admin/includes/export.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.IncludingFile

		add_filter( 'query', $restrict_ids );
		add_filter( 'export_wp_filename', $set_filename );

		ob_start();
		export_wp( [ 'content' => 'all' ] );
		$xml = (string) ob_get_clean();

		remove_filter( 'query', $restrict_ids );
		remove_filter( 'export_wp_filename', $set_filename );

		return $xml;
	}

	/**
	 * Return the post types that should display the Export link.
	 *
	 * @return string[]
	 */
	public function get_supported_post_types(): array {
		$post_types = array_keys( get_post_types( [ 'public' => true ] ) );
		$post_types = (array) apply_filters( 'wp_export_single_post_post_types', $post_types );

		return array_values(
			array_filter(
				$post_types,
				fn( string $post_type ): bool => (bool) apply_filters(
					'wp_export_single_post_should_include_post_type',
					true,
					$post_type
				)
			)
		);
	}
}
