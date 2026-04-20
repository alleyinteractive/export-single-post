# Export Single Post

Contributors: alleyinteractive

Tags: alleyinteractive, wp-export-single-post

Stable tag: 0.1.0

Requires at least: 6.3

Tested up to: 6.9

Requires PHP: 8.2

License: GPL v2 or later

[![Testing Suite](https://github.com/alleyinteractive/export-single-post/actions/workflows/all-pr-tests.yml/badge.svg?branch=develop)](https://github.com/alleyinteractive/export-single-post/actions/workflows/all-pr-tests.yml)

Exports single posts in WordPress WXR format.

## Installation

You can install the package via Composer:

```bash
composer require alleyinteractive/export-single-post
```

## Usage

Once activated, an **Export** link appears in the row actions for each post and page in the WordPress admin list view. Clicking it downloads a WXR XML file containing that post and all of its attachments. The link is only shown to users with the `export` capability.

### Filters

**`wp_export_single_post_post_types`** — Override the full list of post types that display the Export link. Receives an array of post type slugs (defaults to all post types with `can_export` set).

```php
add_filter( 'wp_export_single_post_post_types', function ( array $post_types ): array {
    return [ 'post', 'page' ];
} );
```

**`wp_export_single_post_should_include_post_type`** — Allow or block a specific post type. Receives a boolean and the post type slug.

```php
add_filter( 'wp_export_single_post_should_include_post_type', function ( bool $include, string $post_type ): bool {
    return $post_type !== 'my_private_type';
}, 10, 2 );
```

## Testing

Run `composer test` to run tests against PHPUnit and the PHP code in the plugin.
Unit testing code is written in PSR-4 format and can be found in the `tests`
directory.

## Releasing the Plugin

The plugin uses
[action-release](https://github.com/alleyinteractive/action-release) via a
[built release workflow](./.github/workflows/built-release.yml) to compile and
tag releases. Whenever a new version is detected in the root plugin's headers in
the `wp-export-single-post.php` file or in the `composer.json` file, the workflow will
automatically build the plugin and tag it with a new version.

When you are ready to release a new version of the plugin, you can run
`composer release` to start the process of setting up a new release. If you
want to do this manually you can follow these steps:

1. Change the `Version` in the `wp-export-single-post.php` file to a new higher-level version.

	```diff
	- * Version: 0.0.0
	+ * Version: 0.0.1
	```

	**✨ `composer release` will do this for you automatically.**

2. Commit your changes and push to the repository.
3. Check the actions tab in the repository to see the progress of the release.
   The action will automatically create a new tag and release for the plugin.
   You are done!

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

This project is actively maintained by [Alley
Interactive](https://github.com/alleyinteractive). Like what you see? [Come work
with us](https://alley.com/careers/).

- [Alley Interactive](https://github.com/alleyinteractive)
- [All Contributors](../../contributors)

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.