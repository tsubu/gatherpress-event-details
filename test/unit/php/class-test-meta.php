<?php
/**
 * Tests for the Meta class.
 *
 * @package GatherPress_Event_Details
 */

namespace GatherPress_Event_Details\Tests;

use GatherPress_Event_Details\Meta;
use WP_UnitTestCase;

/**
 * Class Test_Meta.
 *
 * @coversDefaultClass \GatherPress_Event_Details\Meta
 */
final class Test_Meta extends WP_UnitTestCase {

	/**
	 * Re-registers the plugin meta after the test suite resets its registry.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		Meta::get_instance()->register_for_event_post_types();
	}

	/**
	 * Cleans up the post types registered by a test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		unregister_post_type( 'test_production' );
		unregister_post_type( 'test_late_event' );

		parent::tear_down();
	}

	/**
	 * Registers the meta on GatherPress's event post type.
	 *
	 * @covers ::maybe_register
	 *
	 * @return void
	 */
	public function test_registers_on_gatherpress_event(): void {
		$this->assertArrayHasKey( Meta::META_KEY, get_registered_meta_keys( 'post', 'gatherpress_event' ) );
	}

	/**
	 * Restores the meta when the registered-meta registry loses its entry.
	 *
	 * @covers ::maybe_register
	 *
	 * @return void
	 */
	public function test_reregisters_after_meta_registry_reset(): void {
		unregister_meta_key( 'post', Meta::META_KEY, 'gatherpress_event' );

		Meta::get_instance()->maybe_register( 'gatherpress_event' );

		$this->assertArrayHasKey( Meta::META_KEY, get_registered_meta_keys( 'post', 'gatherpress_event' ) );
	}

	/**
	 * Does not register the meta on a post type without event date support.
	 *
	 * @covers ::maybe_register
	 *
	 * @return void
	 */
	public function test_skips_post_type_without_support(): void {
		$this->assertArrayNotHasKey( Meta::META_KEY, get_registered_meta_keys( 'post', 'post' ) );
	}

	/**
	 * Registers the meta on a companion post type that declares the support.
	 *
	 * @covers ::maybe_register
	 *
	 * @return void
	 */
	public function test_registers_on_post_type_declaring_support(): void {
		register_post_type(
			'test_production',
			array(
				'show_in_rest' => true,
				'supports'     => array( 'title', 'custom-fields', 'gatherpress-event-date' ),
			)
		);

		$this->assertArrayHasKey( Meta::META_KEY, get_registered_meta_keys( 'post', 'test_production' ) );
	}

	/**
	 * Registers the meta when the support is added after the post type.
	 *
	 * @covers ::register_for_event_post_types
	 *
	 * @return void
	 */
	public function test_registers_when_support_is_added_later(): void {
		register_post_type( 'test_late_event', array( 'show_in_rest' => true ) );
		add_post_type_support( 'test_late_event', 'gatherpress-event-date' );

		Meta::get_instance()->register_for_event_post_types();

		$this->assertArrayHasKey( Meta::META_KEY, get_registered_meta_keys( 'post', 'test_late_event' ) );
	}

	/**
	 * Returns an empty list for a value that is not an array.
	 *
	 * @covers ::sanitize
	 *
	 * @return void
	 */
	public function test_sanitize_non_array(): void {
		$this->assertSame( array(), Meta::get_instance()->sanitize( 'food' ) );
	}

	/**
	 * Drops invalid and empty items and keeps half-written ones.
	 *
	 * @covers ::sanitize
	 *
	 * @return void
	 */
	public function test_sanitize_drops_invalid_and_empty_items(): void {
		$result = Meta::get_instance()->sanitize(
			array(
				'not an item',
				array(
					'label' => '',
					'value' => '   ',
				),
				array( 'label' => 'Parking' ),
				array(
					'label' => '<b>Food</b>',
					'value' => "Lunch\nCoffee <script>x</script>",
				),
			)
		);

		$this->assertSame(
			array(
				array(
					'label' => 'Parking',
					'value' => '',
				),
				array(
					'label' => 'Food',
					'value' => "Lunch\nCoffee",
				),
			),
			$result
		);
	}

	/**
	 * Trims fields to their maximum length.
	 *
	 * @covers ::sanitize
	 *
	 * @return void
	 */
	public function test_sanitize_trims_lengths(): void {
		$result = Meta::get_instance()->sanitize(
			array(
				array(
					'label' => str_repeat( 'あ', Meta::MAX_LABEL_LENGTH + 5 ),
					'value' => str_repeat( 'b', Meta::MAX_VALUE_LENGTH + 5 ),
				),
			)
		);

		$this->assertSame( Meta::MAX_LABEL_LENGTH, mb_strlen( $result[0]['label'] ) );
		$this->assertSame( Meta::MAX_VALUE_LENGTH, mb_strlen( $result[0]['value'] ) );
	}

	/**
	 * Keeps at most MAX_ITEMS items.
	 *
	 * @covers ::sanitize
	 *
	 * @return void
	 */
	public function test_sanitize_caps_item_count(): void {
		$items = array_fill(
			0,
			Meta::MAX_ITEMS + 3,
			array(
				'label' => 'Label',
				'value' => 'Value',
			)
		);

		$this->assertCount( Meta::MAX_ITEMS, Meta::get_instance()->sanitize( $items ) );
	}

	/**
	 * Lets a user who can edit the post write the meta.
	 *
	 * @covers ::can_edit
	 *
	 * @return void
	 */
	public function test_can_edit(): void {
		$post_id    = self::factory()->post->create( array( 'post_type' => 'gatherpress_event' ) );
		$editor     = self::factory()->user->create( array( 'role' => 'editor' ) );
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$meta       = Meta::get_instance();

		$this->assertTrue( $meta->can_edit( false, Meta::META_KEY, $post_id, $editor ) );
		$this->assertFalse( $meta->can_edit( true, Meta::META_KEY, $post_id, $subscriber ) );
	}

	/**
	 * Saves through the sanitizer and returns only complete items for display.
	 *
	 * @covers ::get_items
	 * @covers ::get_displayable_items
	 *
	 * @return void
	 */
	public function test_get_items_and_displayable_items(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'gatherpress_event' ) );

		update_post_meta(
			$post_id,
			Meta::META_KEY,
			array(
				array(
					'label' => 'Food',
					'value' => 'Lunch',
				),
				array(
					'label' => 'Parking',
					'value' => '',
				),
			)
		);

		$meta = Meta::get_instance();

		$this->assertCount( 2, $meta->get_items( $post_id ) );
		$this->assertSame(
			array(
				array(
					'label' => 'Food',
					'value' => 'Lunch',
				),
			),
			$meta->get_displayable_items( $post_id )
		);
	}

	/**
	 * Returns an empty list for an event without details.
	 *
	 * @covers ::get_items
	 *
	 * @return void
	 */
	public function test_get_items_without_meta(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'gatherpress_event' ) );

		$this->assertSame( array(), Meta::get_instance()->get_items( $post_id ) );
	}
}
