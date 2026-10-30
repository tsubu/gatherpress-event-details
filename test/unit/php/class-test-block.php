<?php
/**
 * Tests for the Block class.
 *
 * @package GatherPress_Event_Details
 */

namespace GatherPress_Event_Details\Tests;

use GatherPress_Event_Details\Block;
use GatherPress_Event_Details\Meta;
use WP_Block;
use WP_Block_Patterns_Registry;
use WP_UnitTestCase;

/**
 * Class Test_Block.
 *
 * @coversDefaultClass \GatherPress_Event_Details\Block
 */
final class Test_Block extends WP_UnitTestCase {

	/**
	 * The list GatherPress hooks after the event date block.
	 *
	 * @var string[]
	 */
	const CORE_HOOKED = array(
		'gatherpress/add-to-calendar',
		'gatherpress/venue',
		'gatherpress/online-event',
		'gatherpress/rsvp',
		'core/paragraph',
		'gatherpress/rsvp-response',
	);

	/**
	 * Inserts the block after the online event block in the event template.
	 *
	 * @covers ::hook_into_event_template
	 *
	 * @return void
	 */
	public function test_hooks_after_online_event(): void {
		$result = Block::get_instance()->hook_into_event_template(
			self::CORE_HOOKED,
			'after',
			'gatherpress/event-date',
			array( 'name' => 'gatherpress/event-template' )
		);

		$this->assertSame( Block::BLOCK_NAME, $result[3] );
		$this->assertSame( 'gatherpress/rsvp', $result[4] );
		$this->assertCount( 7, $result );
	}

	/**
	 * Appends the block when the online event block is not hooked.
	 *
	 * @covers ::hook_into_event_template
	 *
	 * @return void
	 */
	public function test_appends_without_online_event(): void {
		$result = Block::get_instance()->hook_into_event_template(
			array( 'gatherpress/venue' ),
			'after',
			'gatherpress/event-date',
			array( 'name' => 'gatherpress/event-template' )
		);

		$this->assertSame( array( 'gatherpress/venue', Block::BLOCK_NAME ), $result );
	}

	/**
	 * Leaves other contexts, anchors, positions and repeat calls alone.
	 *
	 * @covers ::hook_into_event_template
	 *
	 * @return void
	 */
	public function test_ignores_other_contexts(): void {
		$block    = Block::get_instance();
		$template = array( 'name' => 'gatherpress/event-template' );
		$anchor   = 'gatherpress/event-date';
		$hooked   = array( 'gatherpress/online-event', Block::BLOCK_NAME );
		$cases    = array(
			array( 'after', $anchor, null ),
			array( 'after', $anchor, array( 'name' => 'other/pattern' ) ),
			array( 'after', 'core/post-title', $template ),
			array( 'before', $anchor, $template ),
		);

		foreach ( $cases as list( $position, $anchor_block, $context ) ) {
			$this->assertSame(
				self::CORE_HOOKED,
				$block->hook_into_event_template( self::CORE_HOOKED, $position, $anchor_block, $context )
			);
		}

		$this->assertSame( $hooked, $block->hook_into_event_template( $hooked, 'after', $anchor, $template ) );
	}

	/**
	 * Adds the block to the starter pattern after the online event block.
	 *
	 * @covers ::add_to_starter_pattern
	 *
	 * @return void
	 */
	public function test_adds_to_starter_pattern(): void {
		$online   = "<!-- wp:gatherpress/online-event -->\n<div></div>\n<!-- /wp:gatherpress/online-event -->";
		$details  = '<!-- wp:gatherpress-event-details/event-details /-->';
		$rsvp     = '<!-- wp:gatherpress/rsvp /-->';
		$patterns = array(
			array(
				'name'    => 'gatherpress/event-with-rsvp',
				'content' => $online . "\n" . $rsvp,
			),
			array(
				'name'    => 'other/pattern',
				'content' => '<!-- /wp:gatherpress/online-event -->',
			),
			array(
				'name'    => 'gatherpress/event-with-rsvp',
				'content' => '<!-- wp:gatherpress/rsvp /-->',
			),
		);

		$result = Block::get_instance()->add_to_starter_pattern( $patterns );

		$this->assertSame( $online . "\n" . $details . "\n" . $rsvp, $result[0]['content'] );
		$this->assertSame( $patterns[1], $result[1] );
		$this->assertSame( $patterns[2], $result[2] );

		// Running the filter again does not add a second block.
		$this->assertSame( $result, Block::get_instance()->add_to_starter_pattern( $result ) );
	}

	/**
	 * Adds the block to GatherPress's own bundled starter pattern.
	 *
	 * @covers ::add_to_starter_pattern
	 *
	 * @return void
	 */
	public function test_adds_to_bundled_starter_pattern(): void {
		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'gatherpress/event-with-rsvp' );

		$this->assertIsArray( $pattern );
		$this->assertStringContainsString(
			'<!-- wp:gatherpress-event-details/event-details /-->',
			$pattern['content']
		);
	}

	/**
	 * Applies the label suggestions filter and drops empty and duplicate labels.
	 *
	 * @covers ::get_label_suggestions
	 * @covers ::get_editor_config
	 *
	 * @return void
	 */
	public function test_label_suggestions_filter(): void {
		$callback = static fn(): array => array( 'Food', '', 'Food', 'Wi-Fi' );

		add_filter( 'gatherpress_event_details_label_suggestions', $callback );
		$config = Block::get_instance()->get_editor_config();
		remove_filter( 'gatherpress_event_details_label_suggestions', $callback );

		$this->assertSame( array( 'Food', 'Wi-Fi' ), $config['labelSuggestions'] );
		$this->assertContains( 'gatherpress_event', $config['postTypes'] );
		$this->assertSame( Meta::MAX_ITEMS, $config['maxItems'] );
	}

	/**
	 * Renders nothing outside an event and for an event without details.
	 *
	 * @covers ::render
	 *
	 * @return void
	 */
	public function test_render_empty(): void {
		$post_id  = self::factory()->post->create();
		$event_id = self::factory()->post->create( array( 'post_type' => 'gatherpress_event' ) );

		update_post_meta(
			$post_id,
			Meta::META_KEY,
			array(
				array(
					'label' => 'Food',
					'value' => 'Lunch',
				),
			)
		);

		$this->assertSame( '', Block::get_instance()->render( $this->make_block( $post_id ) ) );
		$this->assertSame( '', Block::get_instance()->render( $this->make_block( $event_id ) ) );
	}

	/**
	 * Renders complete details as an escaped description list.
	 *
	 * @covers ::render
	 *
	 * @return void
	 */
	public function test_render_items(): void {
		$event_id = self::factory()->post->create( array( 'post_type' => 'gatherpress_event' ) );

		update_post_meta(
			$event_id,
			Meta::META_KEY,
			array(
				array(
					'label' => 'Food & drinks',
					'value' => "Lunch\nCoffee",
				),
				array(
					'label' => 'Parking',
					'value' => '',
				),
			)
		);

		$html = Block::get_instance()->render( $this->make_block( $event_id ) );

		$this->assertStringStartsWith( '<dl ', $html );
		$this->assertStringContainsString(
			'<dt class="gatherpress-event-details__label">Food &amp; drinks</dt>',
			$html
		);
		$this->assertStringContainsString(
			'<dd class="gatherpress-event-details__value">Lunch<br>' . "\n" . 'Coffee</dd>',
			$html
		);
		$this->assertStringNotContainsString( 'Parking', $html );
	}

	/**
	 * Builds a block instance with a post ID in its context.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return WP_Block The block.
	 */
	private function make_block( int $post_id ): WP_Block {
		return new WP_Block(
			array(
				'blockName'    => Block::BLOCK_NAME,
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			),
			array( 'postId' => $post_id )
		);
	}
}
