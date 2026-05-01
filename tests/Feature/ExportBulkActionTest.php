<?php
/**
 * Export Bulk Action Tests
 *
 * @package wp-export-single-post
 */

declare(strict_types=1);

namespace Alley\WP\Export_Single_Post\Tests\Feature;

use Alley\WP\Export_Single_Post\Features\Export_Bulk_Action;
use Alley\WP\Export_Single_Post\Tests\TestCase;

class ExportBulkActionTest extends TestCase {

	private function feature(): Export_Bulk_Action {
		return new Export_Bulk_Action();
	}

	public function test_export_option_added_for_user_with_export_capability(): void {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user->ID );

		$actions = $this->feature()->add_bulk_export_action( [] );

		$this->assertArrayHasKey( 'export_posts_wxr', $actions );
		$this->assertSame( 'Export', $actions['export_posts_wxr'] );
	}

	public function test_export_option_not_added_without_export_capability(): void {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user->ID );

		$actions = $this->feature()->add_bulk_export_action( [] );

		$this->assertArrayNotHasKey( 'export_posts_wxr', $actions );
	}

	public function test_existing_bulk_actions_preserved(): void {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user->ID );

		$actions = $this->feature()->add_bulk_export_action( [ 'trash' => 'Move to Trash' ] );

		$this->assertArrayHasKey( 'trash', $actions );
		$this->assertArrayHasKey( 'export_posts_wxr', $actions );
	}

	public function test_get_supported_post_types_returns_public_post_types(): void {
		$types = $this->feature()->get_supported_post_types();

		$this->assertContains( 'post', $types );
		$this->assertContains( 'page', $types );
	}

	public function test_post_types_filter_applies_to_bulk_action(): void {
		add_filter( 'wp_export_single_post_post_types', fn(): array => [ 'post' ] );

		$types = $this->feature()->get_supported_post_types();

		$this->assertContains( 'post', $types );
		$this->assertNotContains( 'page', $types );
	}

	public function test_resolve_valid_post_ids_returns_valid_post_ids(): void {
		$post1 = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$post2 = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );

		$ids = $this->feature()->resolve_valid_post_ids( [ (string) $post1->ID, (string) $post2->ID ] );

		$this->assertContains( $post1->ID, $ids );
		$this->assertContains( $post2->ID, $ids );
		$this->assertCount( 2, $ids );
	}

	public function test_resolve_valid_post_ids_skips_unsupported_post_types(): void {
		add_filter( 'wp_export_single_post_post_types', fn(): array => [ 'post' ] );

		$post = $this->factory()->post->create_and_get( [
			'post_status' => 'publish',
			'post_type'   => 'post',
		] );
		$page = $this->factory()->post->create_and_get( [
			'post_status' => 'publish',
			'post_type'   => 'page',
		] );

		$ids = $this->feature()->resolve_valid_post_ids( [ (string) $post->ID, (string) $page->ID ] );

		$this->assertContains( $post->ID, $ids );
		$this->assertNotContains( $page->ID, $ids );
	}

	public function test_resolve_valid_post_ids_returns_empty_for_invalid_ids(): void {
		$ids = $this->feature()->resolve_valid_post_ids( [ '99999', '0' ] );

		$this->assertSame( [], $ids );
	}
}
