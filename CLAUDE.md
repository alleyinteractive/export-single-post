# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer dev        # Start local wp-env WordPress environment with plugin activated
composer serve      # Start local environment only (no asset build)
composer lint       # Run PHPCS + Rector checks
composer lint:fix   # Auto-fix code style issues
composer test       # Run full suite: lint + PHPUnit
```

Run a single test file:
```bash
./vendor/bin/phpunit tests/Unit/ExampleUnitTest.php
```

## Architecture

**Initialization flow:** `wp-export-single-post.php` → loads Composer autoloader → requires `src/meta.php` and `src/main.php` → calls `register_post_meta_from_defs()`, `register_term_meta_from_defs()`, and `main()`.

**Feature system:** `main()` creates an `Alley\WP\Features\Group` container and boots all features. Each feature implements `Alley\WP\Types\Feature` and has a `boot()` method. New functionality should be added as a feature class in `src/features/` and registered in `src/main.php`.

**Namespace:** `Alley\WP\Export_Single_Post\` maps via PSR-4 to `src/`.

**Meta registration:** Post and term meta are registered from JSON config files at `config/post-meta.json` and `config/term-meta.json`. Schema reference: `https://raw.githubusercontent.com/alleyinteractive/mantle-framework/HEAD/src/mantle/support/schema/meta.json`.

## Testing

Tests use [Mantle Testkit](https://mantle.alley.com/) (WordPress-aware PHPUnit). The base `TestCase` in `tests/TestCase.php` extends `Mantle\Testkit\Test_Case` with `Prevent_Remote_Requests`. Tests run against SQLite.

- `tests/Unit/` — isolated unit tests
- `tests/Feature/` — full WordPress environment integration tests

## Releasing

Bump `Version:` in `wp-export-single-post.php` (and `composer.json`), push to the repo, and the GitHub Actions workflow auto-builds and tags the release. Run `composer release` to automate the version bump step.
