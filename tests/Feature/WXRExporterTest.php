<?php
/**
 * WXR Exporter Tests
 *
 * @package wp-export-single-post
 */

declare(strict_types=1);

namespace Alley\WP\Export_Single_Post\Tests\Feature;

use Alley\WP\Export_Single_Post\Tests\TestCase;
use Alley\WP\Export_Single_Post\WXR_Exporter;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class WXRExporterTest extends TestCase {

	public function test_export_returns_empty_string_for_empty_ids(): void {
		$this->assertSame( '', ( new WXR_Exporter() )->export( [] ) );
	}

	#[RunInSeparateProcess]
	public function test_export_contains_all_selected_posts(): void {
		$post1 = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$post2 = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );

		$xml = ( new WXR_Exporter() )->export( [ $post1->ID, $post2->ID ] );

		$this->assertStringContainsString( '<wp:post_id>' . $post1->ID . '</wp:post_id>', $xml );
		$this->assertStringContainsString( '<wp:post_id>' . $post2->ID . '</wp:post_id>', $xml );
	}

	#[RunInSeparateProcess]
	public function test_export_excludes_other_posts(): void {
		$post  = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$other = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );

		$xml = ( new WXR_Exporter() )->export( [ $post->ID ] );

		$this->assertStringNotContainsString( '<wp:post_id>' . $other->ID . '</wp:post_id>', $xml );
	}

	#[RunInSeparateProcess]
	public function test_export_deduplicates_duplicate_ids(): void {
		$post = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );

		$xml = ( new WXR_Exporter() )->export( [ $post->ID, $post->ID ] );

		$this->assertSame( 1, substr_count( $xml, '<wp:post_id>' . $post->ID . '</wp:post_id>' ) );
	}

	#[RunInSeparateProcess]
	public function test_export_includes_attachments_from_all_posts(): void {
		$post1 = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$post2 = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$att1  = $this->factory()->post->create_and_get( [
			'post_type'   => 'attachment',
			'post_parent' => $post1->ID,
			'post_status' => 'inherit',
		] );
		$att2  = $this->factory()->post->create_and_get( [
			'post_type'   => 'attachment',
			'post_parent' => $post2->ID,
			'post_status' => 'inherit',
		] );

		$xml = ( new WXR_Exporter() )->export( [ $post1->ID, $post2->ID ] );

		$this->assertStringContainsString( '<wp:post_id>' . $att1->ID . '</wp:post_id>', $xml );
		$this->assertStringContainsString( '<wp:post_id>' . $att2->ID . '</wp:post_id>', $xml );
	}

	#[RunInSeparateProcess]
	public function test_shared_terms_appear_once(): void {
		$term  = $this->factory()->term->create_and_get( [
			'taxonomy' => 'category',
			'name'     => 'Shared Cat',
			'slug'     => 'shared-cat',
		] );
		$post1 = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		$post2 = $this->factory()->post->create_and_get( [ 'post_status' => 'publish' ] );
		wp_set_post_terms( $post1->ID, [ $term->term_id ], 'category' );
		wp_set_post_terms( $post2->ID, [ $term->term_id ], 'category' );

		$xml = ( new WXR_Exporter() )->export( [ $post1->ID, $post2->ID ] );

		$this->assertSame( 1, substr_count( $xml, '<![CDATA[shared-cat]]>' ) );
	}
}
