<?php
/**
 * Contains functions for working with meta.
 *
 * @package wp-export-single-post
 */

declare(strict_types=1);

namespace Alley\WP\Export_Single_Post;

use function Mantle\Support\Helpers\register_meta_from_file;

/**
 * Reads the post meta definitions from config and registers them.
 */
function register_post_meta_from_defs(): void {
	register_meta_from_file( dirname( __DIR__ ) . '/config/post-meta.json', 'post', false );
}

/**
 * Reads the term meta definitions from config and registers them.
 */
function register_term_meta_from_defs(): void {
	register_meta_from_file( dirname( __DIR__ ) . '/config/term-meta.json', 'term', false );
}
