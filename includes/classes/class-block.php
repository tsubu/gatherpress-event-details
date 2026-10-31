<?php
/**
 * Registers and renders the Event Details block.
 *
 * @package GatherPress_Event_Details
 * @since 0.1.0
 */

namespace GatherPress_Event_Details;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use GatherPress\Core\Traits\Singleton;
use WP_Block;

/**
 * Class Block.
 *
 * Registers the block, places it in GatherPress's default event layouts and
 * renders the stored details as a description list.
 *
 * @since 0.1.0
 */
final class Block {

	/**
	 * Enforces a single instance of this class.
	 */
	use Singleton;

	/**
	 * The block name.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	const BLOCK_NAME = 'gatherpress-event-details/event-details';

	/**
	 * The GatherPress pattern used as the Block Hooks anchor for events.
	 *
	 * Mirrors `GatherPress\Core\Event::TEMPLATE_PATTERN`.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	const EVENT_TEMPLATE_PATTERN = 'gatherpress/event-template';

	/**
	 * The GatherPress starter pattern offered for new events.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	const EVENT_STARTER_PATTERN = 'gatherpress/event-with-rsvp';

	/**
	 * The block the details are placed after in the default layouts.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	const PRECEDING_BLOCK = 'gatherpress/online-event';

	/**
	 * Constructor for the Block class.
	 *
	 * @since 0.1.0
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Sets up the hooks.
	 *
	 * The Block Hooks filter runs at the default priority so it sees the list
	 * GatherPress builds at priority 9.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	protected function setup_hooks(): void {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'add_editor_config' ) );
		add_filter( 'hooked_block_types', array( $this, 'hook_into_event_template' ), 10, 4 );
		add_filter( 'gatherpress_event_starter_patterns', array( $this, 'add_to_starter_pattern' ) );
	}

	/**
	 * Registers the block from its built metadata.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_block(): void {
		$path = GATHERPRESS_EVENT_DETAILS_CORE_PATH . '/build/blocks/event-details';

		if ( file_exists( $path . '/block.json' ) ) {
			register_block_type( $path );
		}
	}

	/**
	 * Passes the event post types and label suggestions to the editor script.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function add_editor_config(): void {
		$handle = generate_block_asset_handle( self::BLOCK_NAME, 'editorScript' );

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		wp_add_inline_script(
			$handle,
			sprintf( 'window.gatherpressEventDetails = %s;', wp_json_encode( $this->get_editor_config() ) ),
			'before'
		);
	}

	/**
	 * Returns the configuration the editor script reads.
	 *
	 * @since 0.1.0
	 *
	 * @return array{
	 *     postTypes: string[],
	 *     labelSuggestions: string[],
	 *     maxItems: int,
	 *     maxLabelLength: int,
	 *     maxValueLength: int
	 * } The configuration.
	 */
	public function get_editor_config(): array {
		return array(
			'postTypes'        => array_values( get_post_types_by_support( Meta::EVENT_SUPPORT ) ),
			'labelSuggestions' => $this->get_label_suggestions(),
			'maxItems'         => Meta::MAX_ITEMS,
			'maxLabelLength'   => Meta::MAX_LABEL_LENGTH,
			'maxValueLength'   => Meta::MAX_VALUE_LENGTH,
		);
	}

	/**
	 * Returns the labels suggested while an organizer types a detail label.
	 *
	 * @since 0.1.0
	 *
	 * @return string[] The suggested labels.
	 */
	public function get_label_suggestions(): array {
		$suggestions = array(
			__( 'Food and drinks', 'gatherpress-event-details' ),
			__( 'Parking', 'gatherpress-event-details' ),
			__( 'Accessibility', 'gatherpress-event-details' ),
			__( 'Childcare', 'gatherpress-event-details' ),
			__( 'Language', 'gatherpress-event-details' ),
			__( 'What to bring', 'gatherpress-event-details' ),
		);

		/**
		 * Filters the labels suggested in the event details editor.
		 *
		 * Suggestions only help typing: organizers can still enter any label.
		 *
		 * @since 0.1.0
		 *
		 * @param string[] $suggestions The suggested labels.
		 */
		$suggestions = apply_filters( 'gatherpress_event_details_label_suggestions', $suggestions );

		return array_values( array_unique( array_filter( array_map( 'strval', (array) $suggestions ) ) ) );
	}

	/**
	 * Hooks the block into GatherPress's event template, after the online event block.
	 *
	 * @since 0.1.0
	 *
	 * @param string[]                          $hooked_block_types The hooked block types.
	 * @param string                            $relative_position  The position relative to the anchor block.
	 * @param string|null                       $anchor_block_type  The anchor block type.
	 * @param array|\WP_Post|\WP_Block_Template $context          The block template, template part, post or pattern.
	 *
	 * @return string[] The hooked block types.
	 */
	public function hook_into_event_template(
		array $hooked_block_types,
		string $relative_position,
		?string $anchor_block_type,
		$context
	): array {
		if (
			! is_array( $context )
			|| self::EVENT_TEMPLATE_PATTERN !== ( $context['name'] ?? '' )
			|| 'gatherpress/event-date' !== $anchor_block_type
			|| 'after' !== $relative_position
			|| in_array( self::BLOCK_NAME, $hooked_block_types, true )
		) {
			return $hooked_block_types;
		}

		$index = array_search( self::PRECEDING_BLOCK, $hooked_block_types, true );

		if ( false === $index ) {
			$hooked_block_types[] = self::BLOCK_NAME;
		} else {
			array_splice( $hooked_block_types, $index + 1, 0, array( self::BLOCK_NAME ) );
		}

		return $hooked_block_types;
	}

	/**
	 * Adds the block to GatherPress's starter pattern for new events.
	 *
	 * The starter pattern is static markup rather than a Block Hooks anchor, so
	 * the block is inserted after the online event block's closing comment. A
	 * pattern without that block, or one that already holds the details block,
	 * is left alone.
	 *
	 * @since 0.1.0
	 *
	 * @param array $patterns The starter pattern definitions.
	 *
	 * @return array The starter pattern definitions.
	 */
	public function add_to_starter_pattern( array $patterns ): array {
		$marker = sprintf( '<!-- /wp:%s -->', self::PRECEDING_BLOCK );
		$block  = sprintf( '<!-- wp:%s /-->', self::BLOCK_NAME );

		foreach ( $patterns as $key => $pattern ) {
			$content         = $pattern['content'] ?? '';
			$marker_position = is_string( $content ) ? strpos( $content, $marker ) : false;

			if (
				self::EVENT_STARTER_PATTERN !== ( $pattern['name'] ?? '' )
				|| ! is_string( $content )
				|| false === $marker_position
				|| has_block( self::BLOCK_NAME, $content )
			) {
				continue;
			}

			$patterns[ $key ]['content'] = substr_replace(
				$content,
				$marker . "\n" . $block,
				$marker_position,
				strlen( $marker )
			);
		}

		return $patterns;
	}

	/**
	 * Renders the block.
	 *
	 * Prints nothing outside an event, or when the event has no detail with
	 * both a label and a value.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Block $block The block instance.
	 *
	 * @return string The block markup.
	 */
	public function render( WP_Block $block ): string {
		$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
		$items   = array();

		if ( $post_id && post_type_supports( (string) get_post_type( $post_id ), Meta::EVENT_SUPPORT ) ) {
			$items = Meta::get_instance()->get_displayable_items( $post_id );
		}

		if ( empty( $items ) ) {
			return '';
		}

		$rows = '';

		foreach ( $items as $item ) {
			$rows .= sprintf(
				'<div class="gatherpress-event-details__item"><dt class="gatherpress-event-details__label">%1$s</dt>'
					. '<dd class="gatherpress-event-details__value">%2$s</dd></div>',
				esc_html( $item['label'] ),
				nl2br( esc_html( $item['value'] ), false )
			);
		}

		return sprintf( '<dl %1$s>%2$s</dl>', get_block_wrapper_attributes(), $rows );
	}
}
