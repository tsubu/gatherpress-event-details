<?php
/**
 * Registers and sanitizes the event details post meta.
 *
 * @package GatherPress_Event_Details
 * @since 0.1.0
 */

namespace GatherPress_Event_Details;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use GatherPress\Core\Traits\Singleton;

/**
 * Class Meta.
 *
 * Stores event details as one post meta value: an ordered list of
 * `array{label: string, value: string}` items, on every post type that
 * declares `gatherpress-event-date` support.
 *
 * @since 0.1.0
 *
 * @phpstan-type Item array{label: string, value: string}
 */
final class Meta {

	/**
	 * Enforces a single instance of this class.
	 */
	use Singleton;

	/**
	 * The post meta key holding the details.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	const META_KEY = 'gatherpress_event_details';

	/**
	 * The GatherPress support that marks a post type as an event.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	const EVENT_SUPPORT = 'gatherpress-event-date';

	/**
	 * The maximum number of items stored per event.
	 *
	 * @since 0.1.0
	 *
	 * @var int
	 */
	const MAX_ITEMS = 20;

	/**
	 * The maximum length of a label, in characters.
	 *
	 * @since 0.1.0
	 *
	 * @var int
	 */
	const MAX_LABEL_LENGTH = 100;

	/**
	 * The maximum length of a value, in characters.
	 *
	 * @since 0.1.0
	 *
	 * @var int
	 */
	const MAX_VALUE_LENGTH = 1000;

	/**
	 * Constructor for the Meta class.
	 *
	 * @since 0.1.0
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Sets up the hooks.
	 *
	 * `registered_post_type` covers post types that declare the support in
	 * `register_post_type()`, including GatherPress's own. GatherPress requires
	 * this declaration timing so its own per-post-type hooks are wired too.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	protected function setup_hooks(): void {
		add_action( 'registered_post_type', array( $this, 'maybe_register' ) );
	}

	/**
	 * Registers the meta on a post type when it acts as an event.
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_type The post type name.
	 *
	 * @return void
	 */
	public function maybe_register( string $post_type ): void {
		if ( ! post_type_supports( $post_type, self::EVENT_SUPPORT ) ) {
			return;
		}

		$registered_meta = get_registered_meta_keys( 'post', $post_type );

		if ( isset( $registered_meta[ self::META_KEY ] ) ) {
			return;
		}

		register_post_meta(
			$post_type,
			self::META_KEY,
			array(
				'type'              => 'array',
				'description'       => __( 'Labeled details about the event.', 'gatherpress-event-details' ),
				'single'            => true,
				'default'           => array(),
				'sanitize_callback' => array( $this, 'sanitize' ),
				'auth_callback'     => array( $this, 'can_edit' ),
				'revisions_enabled' => post_type_supports( $post_type, 'revisions' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'     => 'array',
						'maxItems' => self::MAX_ITEMS,
						'items'    => array(
							'type'                 => 'object',
							'additionalProperties' => false,
							'properties'           => array(
								'label' => array( 'type' => 'string' ),
								'value' => array( 'type' => 'string' ),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Checks whether a user may write the meta on a post.
	 *
	 * @since 0.1.0
	 *
	 * @param bool   $allowed   Whether the user can edit the meta. Required by WP's auth_callback signature.
	 * @param string $meta_key  The meta key. Required by WP's auth_callback signature.
	 * @param int    $object_id The post ID.
	 * @param int    $user_id   The user ID.
	 *
	 * @return bool Whether the user can edit the post.
	 */
	public function can_edit( bool $allowed, string $meta_key, int $object_id, int $user_id ): bool {
		return user_can( $user_id, 'edit_post', $object_id );
	}

	/**
	 * Sanitizes a list of details.
	 *
	 * Drops items that are not arrays and items whose label and value are both
	 * empty, trims both fields to their maximum length, and keeps at most
	 * `MAX_ITEMS` items. Items with only one of the two fields are kept so an
	 * organizer does not lose a half-written row; they are not rendered.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value The raw value.
	 *
	 * @return array<int, Item> The sanitized list.
	 */
	public function sanitize( $value ): array {
		$items = array();

		if ( ! is_array( $value ) ) {
			return $items;
		}

		foreach ( $value as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$raw_label = $item['label'] ?? '';
			$raw_text  = $item['value'] ?? '';
			$label     = is_scalar( $raw_label ) ? sanitize_text_field( (string) $raw_label ) : '';
			$text      = is_scalar( $raw_text ) ? sanitize_textarea_field( (string) $raw_text ) : '';
			$label     = mb_substr( $label, 0, self::MAX_LABEL_LENGTH );
			$text      = mb_substr( $text, 0, self::MAX_VALUE_LENGTH );

			if ( '' === $label && '' === $text ) {
				continue;
			}

			$items[] = array(
				'label' => $label,
				'value' => $text,
			);

			if ( self::MAX_ITEMS === count( $items ) ) {
				break;
			}
		}

		return $items;
	}

	/**
	 * Returns the stored details of an event.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id The event post ID.
	 *
	 * @return array<int, Item> The details, in display order.
	 */
	public function get_items( int $post_id ): array {
		return $this->sanitize( get_post_meta( $post_id, self::META_KEY, true ) );
	}

	/**
	 * Returns the details of an event that have both a label and a value.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id The event post ID.
	 *
	 * @return array<int, Item> The displayable details, in display order.
	 */
	public function get_displayable_items( int $post_id ): array {
		return array_values(
			array_filter(
				$this->get_items( $post_id ),
				static fn( array $item ): bool => '' !== trim( $item['label'] ) && '' !== trim( $item['value'] )
			)
		);
	}
}
