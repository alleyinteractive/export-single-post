<?php
/**
 * The main plugin function
 *
 * @package wp-export-single-post
 */

namespace Alley\WP\Export_Single_Post;

use Alley\WP\Features\Group;

/**
 * Instantiate the plugin.
 */
function main(): void {
	$plugin = new Group(
		new Features\Export_Post_Action(),
		new Features\Export_Bulk_Action(),
	);

	/*
	 * Add additional features here.
	 *
	 * Example:
	 *
	 *   $plugin->include( new Features\My_New_Feature() );
	 *
	 * You can generate a new feature using `npx @alleyinteractive/scaffolder@latest feature`.
	 *
	 * @see https://github.com/alleyinteractive/wp-type-extensions
	 */

	$plugin->boot();
}
