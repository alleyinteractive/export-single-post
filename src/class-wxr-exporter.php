<?php
/**
 * WXR_Exporter class file
 *
 * @package wp-export-single-post
 */

declare(strict_types=1);

namespace Alley\WP\Export_Single_Post;

/**
 * Generates WXR XML for a given set of post IDs.
 */
class WXR_Exporter {

	/**
	 * Export posts by ID and return WXR XML.
	 *
	 * Callers are responsible for sending the Content-Disposition header with
	 * the desired filename after calling this method — export_wp() queues its
	 * own Content-Disposition header, and passing true as the replace flag
	 * to header() will override it before any output is flushed.
	 *
	 * @param int[] $post_ids Post IDs to export.
	 * @return string WXR XML content, or empty string if no valid IDs given.
	 */
	public function export( array $post_ids ): string {
		global $wpdb;

		$post_ids = array_values( array_filter( array_unique( array_map( absint( ... ), $post_ids ) ) ) );

		if ( $post_ids === [] ) {
			return '';
		}

		$all_ids = $post_ids;

		foreach ( $post_ids as $post_id ) {
			$attachment_ids = get_children( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_children
				[
					'post_parent'    => $post_id,
					'post_type'      => 'attachment',
					'fields'         => 'ids',
					'posts_per_page' => -1,
				]
			);
			$all_ids        = array_merge( $all_ids, (array) $attachment_ids );
		}

		$all_ids     = array_values( array_unique( array_map( absint( ... ), $all_ids ) ) );
		$ids_sql     = implode( ',', $all_ids );
		$posts_table = $wpdb->posts;

		$restrict_ids = static function ( string $query ) use ( $ids_sql, $posts_table ): string {
			if ( str_contains( $query, "SELECT ID FROM {$posts_table}" ) ) {
				return "SELECT ID FROM {$posts_table} WHERE ID IN ({$ids_sql})";
			}
			return $query;
		};

		$term_ids = [];

		foreach ( $all_ids as $pid ) {
			$terms = wp_get_post_terms( $pid, array_values( get_taxonomies() ), [ 'fields' => 'all' ] );
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				$term_ids[ $term->term_id ] = $term->term_id;
				foreach ( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) as $ancestor_id ) {
					$term_ids[ $ancestor_id ] = $ancestor_id;
				}
			}
		}

		$term_ids_sql = implode( ',', $term_ids ) ?: '0';

		$restrict_term_ids = static function ( array $clauses, array $taxonomies ) use ( $term_ids_sql ): array {
			// When no taxonomies are specified, the query has no taxonomy restriction and would
			// return all terms — return nothing instead, since the intent is to query an empty set.
			if ( $taxonomies === [] ) {
				$clauses['where'] = $clauses['where']
					? $clauses['where'] . ' AND 1=0'
					: '1=0';
				return $clauses;
			}

			$addition         = "t.term_id IN ({$term_ids_sql})";
			$clauses['where'] = $clauses['where']
				? $clauses['where'] . " AND {$addition}"
				: $addition;
			return $clauses;
		};

		if ( ! function_exists( 'wxr_cdata' ) && ! function_exists( __NAMESPACE__ . '\\wxr_cdata' ) ) { // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.IncludingFile
			require_once ABSPATH . 'wp-admin/includes/export.php';
		}

		add_filter( 'query', $restrict_ids );
		add_filter( 'terms_clauses', $restrict_term_ids, 10, 2 );

		$xml = '';

		try {
			ob_start();
			export_wp( [ 'content' => 'all' ] );
			$xml = (string) ob_get_clean();
		} finally {
			remove_filter( 'query', $restrict_ids );
			remove_filter( 'terms_clauses', $restrict_term_ids, 10 );
		}

		return $xml;
	}
}
