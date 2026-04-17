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
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Tests for the Export_Post_Action feature.
 */
class ExportPostActionTest extends TestCase {
	private function feature(): Export_Post_Action {
		global $wp_filter;

		if ( ! isset( $wp_filter['post_row_actions'] ) || ! $wp_filter['post_row_actions'] instanceof \WP_Hook ) {
			$this->fail( 'Expected post_row_actions hook to be registered.' );
		}

		foreach ( $wp_filter['post_row_actions']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback_data ) {
				$callback = $callback_data['function'];

				if ( is_array( $callback ) && $callback[0] instanceof Export_Post_Action && 'add_export_link' === $callback[1] ) {
					return $callback[0];
				}

				if ( $callback instanceof \Closure ) {
					$instance = ( new \ReflectionFunction( $callback ) )->getClosureThis();

					if ( $instance instanceof Export_Post_Action ) {
						return $instance;
					}
				}
			}
		}

		$this->fail( 'Could not find loaded Export_Post_Action callback on post_row_actions.' );
	}

	public function test_get_supported_post_types_returns_public_post_types(): void {
		$feature = $this->feature();
		$types   = $feature->get_supported_post_types();

		$this->assertContains( 'post', $types );
		$this->assertContains( 'page', $types );
	}

	public function test_post_types_filter_can_modify_list(): void {
		add_filter( 'wp_export_single_post_post_types', fn(): array => [ 'post' ] );

		$feature = $this->feature();
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

		$feature = $this->feature();
		$types   = $feature->get_supported_post_types();

		$this->assertNotContains( 'page', $types );
		$this->assertContains( 'post', $types );
	}

	public function test_export_link_added_for_user_with_export_capability(): void {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user->ID );

		$post = $this->factory()->post->create_and_get();

		$feature = $this->feature();
		$actions = $feature->add_export_link( [], $post );

		$this->assertArrayHasKey( 'export', $actions );
		$this->assertStringContainsString( 'export_single_post', $actions['export'] );
		$this->assertStringContainsString( (string) $post->ID, $actions['export'] );
	}

	public function test_export_link_not_added_without_export_capability(): void {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user->ID );

		$post = $this->factory()->post->create_and_get();

		$feature = $this->feature();
		$actions = $feature->add_export_link( [], $post );

		$this->assertArrayNotHasKey( 'export', $actions );
	}

	public function test_export_link_not_added_for_excluded_post_type(): void {
		add_filter( 'wp_export_single_post_post_types', fn(): array => [] );

		$user = $this->factory()->user->create_and_get( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user->ID );

		$post = $this->factory()->post->create_and_get();

		$feature = $this->feature();
		$actions = $feature->add_export_link( [], $post );

		$this->assertArrayNotHasKey( 'export', $actions );
	}

	#[RunInSeparateProcess]
	public function test_generate_export_contains_post(): void {
		$post = $this->factory()->post->create_and_get(
			[
				'post_title'   => 'Test Export Post',
				'post_content' => 'Test content.',
				'post_status'  => 'publish',
			]
		);

		$feature = $this->feature();
		$xml     = $feature->generate_export( $post );

		$this->assertStringContainsString( '<wp:post_id>' . $post->ID . '</wp:post_id>', $xml );
	}

	#[RunInSeparateProcess]
	public function test_generate_export_excludes_other_posts(): void {
		$post       = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$other_post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );

		$feature = $this->feature();
		$xml     = $feature->generate_export( $post );

		$this->assertStringContainsString( '<wp:post_id>' . $post->ID . '</wp:post_id>', $xml );
		$this->assertStringNotContainsString( '<wp:post_id>' . $other_post->ID . '</wp:post_id>', $xml );
	}

	#[RunInSeparateProcess]
	public function test_generate_export_includes_attachments(): void {
		$post       = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$attachment = $this->factory()->post->create_and_get(
			[
				'post_type'   => 'attachment',
				'post_parent' => $post->ID,
				'post_status' => 'inherit',
			]
		);

		$feature = $this->feature();
		$xml     = $feature->generate_export( $post );

		$this->assertStringContainsString( '<wp:post_id>' . $post->ID . '</wp:post_id>', $xml );
		$this->assertStringContainsString( '<wp:post_id>' . $attachment->ID . '</wp:post_id>', $xml );
	}

	#[RunInSeparateProcess]
	public function test_generate_export_includes_post_terms(): void {
		$post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$term = $this->factory()->term->create_and_get( [
			'taxonomy' => 'category',
			'name'     => 'Exported Cat',
			'slug'     => 'exported-cat',
		] );
		wp_set_post_terms( $post->ID, [ $term->term_id ], 'category' );

		$feature = $this->feature();
		$xml     = $feature->generate_export( $post );

		$this->assertStringContainsString( '<wp:category_nicename><![CDATA[exported-cat]]></wp:category_nicename>', $xml );
	}

	#[RunInSeparateProcess]
	public function test_generate_export_excludes_unrelated_terms(): void {
		$post       = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$other_post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$term       = $this->factory()->term->create_and_get( [
			'taxonomy' => 'category',
			'name'     => 'Other Cat',
			'slug'     => 'other-cat',
		] );
		wp_set_post_terms( $other_post->ID, [ $term->term_id ], 'category' );

		$feature = $this->feature();
		$xml     = $feature->generate_export( $post );

		$this->assertStringNotContainsString( '<wp:category_nicename><![CDATA[other-cat]]></wp:category_nicename>', $xml );
	}

	#[RunInSeparateProcess]
	public function test_generate_export_includes_ancestor_terms(): void {
		$parent = $this->factory()->term->create_and_get( [
			'taxonomy' => 'category',
			'name'     => 'Parent Cat',
			'slug'     => 'parent-cat',
		] );
		$child  = $this->factory()->term->create_and_get( [
			'taxonomy' => 'category',
			'name'     => 'Child Cat',
			'slug'     => 'child-cat',
			'parent'   => $parent->term_id,
		] );
		$post   = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		wp_set_post_terms( $post->ID, [ $child->term_id ], 'category' );

		$feature = $this->feature();
		$xml     = $feature->generate_export( $post );

		$this->assertStringContainsString( '<wp:category_nicename><![CDATA[child-cat]]></wp:category_nicename>', $xml );
		$this->assertStringContainsString( '<wp:category_nicename><![CDATA[parent-cat]]></wp:category_nicename>', $xml );
	}

	#[RunInSeparateProcess]
	public function test_generate_export_with_no_terms(): void {
		$post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		wp_set_post_terms( $post->ID, [], 'category' );

		$feature = $this->feature();
		$xml     = $feature->generate_export( $post );

		$this->assertStringNotContainsString( '<wp:category>', $xml );
		$this->assertStringNotContainsString( '<wp:tag>', $xml );
		$this->assertStringNotContainsString( '<wp:term>', $xml );
	}

	#[RunInSeparateProcess]
	public function test_generate_export_includes_custom_taxonomy_terms(): void {
		register_taxonomy( 'export_test_tax', 'post', [ 'public' => true ] );

		$term = $this->factory()->term->create_and_get( [
			'taxonomy' => 'export_test_tax',
			'name'     => 'Test Term',
			'slug'     => 'test-term',
		] );
		$post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		wp_set_post_terms( $post->ID, [ $term->term_id ], 'export_test_tax' );

		$feature = $this->feature();
		$xml     = $feature->generate_export( $post );

		$this->assertStringContainsString( '<wp:term_slug><![CDATA[test-term]]></wp:term_slug>', $xml );
	}
}
