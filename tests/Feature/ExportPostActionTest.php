<?php
/**
 * Export Single Post Tests: Export Post Action Feature
 *
 * @package wp-export-single-post
 */

declare(strict_types=1);

namespace Alley\WP\Export_Single_Post\Tests\Feature;

use Alley\WP\Export_Single_Post\Features\Export_Post_Action;
use Alley\WP\Export_Single_Post\Tests\TestCase;

/**
 * Tests for the Export_Post_Action feature.
 */
class ExportPostActionTest extends TestCase {

	public function test_get_supported_post_types_returns_public_post_types(): void {
		$feature = new Export_Post_Action();
		$types   = $feature->get_supported_post_types();

		$this->assertContains( 'post', $types );
		$this->assertContains( 'page', $types );
	}

	public function test_post_types_filter_can_modify_list(): void {
		add_filter( 'wp_export_single_post_post_types', fn(): array => [ 'post' ] );

		$feature = new Export_Post_Action();
		$types   = $feature->get_supported_post_types();

		$this->assertContains( 'post', $types );
		$this->assertNotContains( 'page', $types );
	}

	public function test_should_include_filter_can_exclude_type(): void {
		add_filter(
			'wp_export_single_post_should_include_post_type',
			fn( bool $include, string $type ): bool => 'page' !== $type,
			10,
			2
		);

		$feature = new Export_Post_Action();
		$types   = $feature->get_supported_post_types();

		$this->assertNotContains( 'page', $types );
		$this->assertContains( 'post', $types );
	}

	public function test_export_link_added_for_user_with_export_capability(): void {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user->ID );

		$post = $this->factory()->post->create_and_get();

		$feature = new Export_Post_Action();
		$actions = $feature->add_export_link( [], $post );

		$this->assertArrayHasKey( 'export', $actions );
		$this->assertStringContainsString( 'export_single_post', $actions['export'] );
		$this->assertStringContainsString( (string) $post->ID, $actions['export'] );
	}

	public function test_export_link_not_added_without_export_capability(): void {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user->ID );

		$post = $this->factory()->post->create_and_get();

		$feature = new Export_Post_Action();
		$actions = $feature->add_export_link( [], $post );

		$this->assertArrayNotHasKey( 'export', $actions );
	}

	public function test_export_link_not_added_for_excluded_post_type(): void {
		add_filter( 'wp_export_single_post_post_types', fn(): array => [] );

		$user = $this->factory()->user->create_and_get( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user->ID );

		$post = $this->factory()->post->create_and_get();

		$feature = new Export_Post_Action();
		$actions = $feature->add_export_link( [], $post );

		$this->assertArrayNotHasKey( 'export', $actions );
	}

	public function test_generate_export_contains_post(): void {
		$post = $this->factory()->post->create_and_get(
			[
				'post_title'   => 'Test Export Post',
				'post_content' => 'Test content.',
				'post_status'  => 'publish',
			]
		);

		$feature = new Export_Post_Action();
		$xml     = $feature->generate_export( $post );

		$this->assertStringContainsString( '<wp:post_id>' . $post->ID . '</wp:post_id>', $xml );
	}

	public function test_generate_export_excludes_other_posts(): void {
		$post       = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$other_post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );

		$feature = new Export_Post_Action();
		$xml     = $feature->generate_export( $post );

		$this->assertStringContainsString( '<wp:post_id>' . $post->ID . '</wp:post_id>', $xml );
		$this->assertStringNotContainsString( '<wp:post_id>' . $other_post->ID . '</wp:post_id>', $xml );
	}

	public function test_generate_export_includes_attachments(): void {
		$post       = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$attachment = $this->factory()->post->create_and_get(
			[
				'post_type'   => 'attachment',
				'post_parent' => $post->ID,
				'post_status' => 'inherit',
			]
		);

		$feature = new Export_Post_Action();
		$xml     = $feature->generate_export( $post );

		$this->assertStringContainsString( '<wp:post_id>' . $post->ID . '</wp:post_id>', $xml );
		$this->assertStringContainsString( '<wp:post_id>' . $attachment->ID . '</wp:post_id>', $xml );
	}
}
