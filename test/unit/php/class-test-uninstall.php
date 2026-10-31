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
}
