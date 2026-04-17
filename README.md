# Export Single Post

Contributors: alleyinteractive

Tags: alleyinteractive, wp-export-single-post

Stable tag: 0.0.0

Requires at least: 6.3

Tested up to: 6.7

Requires PHP: 8.2

License: GPL v2 or later

[![Testing Suite](https://github.com/alleyinteractive/wp-export-single-post/actions/workflows/all-pr-tests.yml/badge.svg?branch=develop)](https://github.com/alleyinteractive/wp-export-single-post/actions/workflows/all-pr-tests.yml)

Exports single posts in WordPress WXR format.

## Installation

You can install the package via Composer:

```bash
composer require alleyinteractive/wp-export-single-post
```

## Usage

Activate the plugin in WordPress and use it like so:

```php
$plugin = Create_WordPress_Plugin\Export_Single_Post\Export_Single_Post();
$plugin->perform_magic();
```

## Development

To setup a WordPress installation and run the plugin in a local environment, you
can use `wp-env` via the `composer dev` command:

```sh
npm install
composer dev
```

The command will start a local WordPress environment with the plugin activated
while also running the front-end assets build process. You can also run `npm run
start` to start the front-end assets build process separately. The front-end
assets will be compiled into the `build` directory and will be enqueued
automatically by the plugin.

## Registering Meta

The plugin supports registering post and term meta via JSON files located in the
`config` directory. Out of the box, the plugin will look for
`config/post-meta.json` for post meta and `config/term-meta.json` for term meta.

```json
{
  "$schema": "https://raw.githubusercontent.com/alleyinteractive/mantle-framework/HEAD/src/mantle/support/schema/meta.json",
  "example_meta_key": {
    "post_types": "article",
    "type": "string"
  },
  "another_meta_key": {
    "post_types": [ "article", "page" ],
    "type": "number",
    "single": false,
    "default": 0
  }
}
```

For more information on how to register meta via JSON files,
[see the documentation](https://mantle.alley.com/docs/features/support/helpers#register_meta_from_file).

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
automatically build the plugin and tag it with a new version. The built tag will
contain all the required front-end assets the plugin may require. This works
well for publishing to WordPress.org or for submodule-ing.

When you are ready to release a new version of the plugin, you can run
`npm run release`/`composer release` to start the process of setting up a new
release. If you want to do this manually you can follow these steps:

1. Change the `Version` in the `wp-export-single-post.php` file to a new higher-level version.

	```diff
	- * Version: 0.0.0
	+ * Version: 0.0.1
	```

	**✨ `npm run release` will do this for you automatically.**

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