<?php
/**
 * Tests for plugin uninstall cleanup.
 *
 * @package GatherPress_Event_Details
 */

namespace GatherPress_Event_Details\Tests;

use WP_UnitTestCase;

/**
 * Class Test_Uninstall.
 *
 * @coversNothing
 */
final class Test_Uninstall extends WP_UnitTestCase {

	/**
	 * Deletes event details while preserving unrelated post meta.
	 *
	 * @return void
	 */
	public function test_uninstall_deletes_event_details_meta(): void {
		$post_id = self::factory()->post->create();

		update_post_meta(
			$post_id,
			'gatherpress_event_details',
			array(
				array(
					'label' => 'Food',
					'value' => 'Lunch',
				),
			)
		);
		update_post_meta( $post_id, 'unrelated_meta', 'keep me' );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress uninstall guard constant.
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		require dirname( __DIR__, 3 ) . '/uninstall.php';

		$this->assertFalse( metadata_exists( 'post', $post_id, 'gatherpress_event_details' ) );
		$this->assertSame( 'keep me', get_post_meta( $post_id, 'unrelated_meta', true ) );
	}

	/**
	 * Deletes event details from every site in a multisite network.
	 *
	 * @return void
	 */
	public function test_uninstall_deletes_event_details_meta_on_all_sites(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires a multisite test environment.' );
		}

		$primary_blog_id = get_current_blog_id();
		$primary_post_id = self::factory()->post->create();

		update_post_meta(
			$primary_post_id,
			'gatherpress_event_details',
			array(
				array(
					'label' => 'Food',
					'value' => 'Lunch',
				),
			)
		);

		$secondary_blog_id = self::factory()->blog->create();
		switch_to_blog( $secondary_blog_id );
		$secondary_post_id = self::factory()->post->create();

		update_post_meta(
			$secondary_post_id,
			'gatherpress_event_details',
			array(
				array(
					'label' => 'Food',
					'value' => 'Lunch',
				),
			)
		);
		restore_current_blog();

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress uninstall guard constant.
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		require dirname( __DIR__, 3 ) . '/uninstall.php';

		$this->assertFalse( metadata_exists( 'post', $primary_post_id, 'gatherpress_event_details' ) );

		switch_to_blog( $secondary_blog_id );
		$this->assertFalse( metadata_exists( 'post', $secondary_post_id, 'gatherpress_event_details' ) );
		restore_current_blog();

		$this->assertSame( $primary_blog_id, get_current_blog_id() );
	}
}
