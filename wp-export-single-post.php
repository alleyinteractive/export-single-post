<?php
/**
 * Plugin Name: Export Single Post
 * Plugin URI: https://github.com/alleyinteractive/wp-export-single-post
 * Description: Exports single posts in WordPress WXR format
 * Version: 0.1.0
 * Author: Alley Interactive
 * Author URI: https://github.com/alleyinteractive/wp-export-single-post
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Tested up to: 7.0.2
 *
 * Text Domain: wp-export-single-post
 * Domain Path: /languages/
 *
 * @package wp-export-single-post
 */

namespace Alley\WP\Export_Single_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Root directory to this plugin.
 */
define( 'WP_EXPORT_SINGLE_POST_DIR', __DIR__ );

/* Start Composer Loader */

// Check if Composer is installed (remove if Composer is not required for your plugin).
if ( ! file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	// Will also check for the presence of an already loaded Composer autoloader
	// to see if the Composer dependencies have been installed in a parent
	// folder. This is useful for when the plugin is loaded as a Composer
	// dependency in a larger project.
	if ( ! class_exists( \Composer\InstalledVersions::class ) ) {
		\add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Composer is not installed and wp-export-single-post cannot load. Try using a `*-built` branch if the plugin is being loaded as a submodule.', 'wp-export-single-post' ); ?></p>
				</div>
				<?php
			}
		);

		return;
	}
} else {
	// Load Composer dependencies.
	require_once __DIR__ . '/vendor/wordpress-autoload.php';
}

/* End Composer Loader */

// Load the plugin's main files.
require_once __DIR__ . '/src/main.php';

main();
