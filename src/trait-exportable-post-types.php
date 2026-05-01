<?php
/**
 * Exportable_Post_Types trait file
 *
 * @package wp-export-single-post
 */

declare(strict_types=1);

namespace Alley\WP\Export_Single_Post;

/**
 * Provides get_supported_post_types() for feature classes.
 */
trait Exportable_Post_Types {

	/**
	 * Return the post types that support export.
	 *
	 * @return string[]
	 */
	public function get_supported_post_types(): array {
		$post_types = array_keys( get_post_types( [ 'can_export' => true ] ) );
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
