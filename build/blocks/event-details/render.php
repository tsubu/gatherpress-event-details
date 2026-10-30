<?php
/**
 * Render callback for the Event Details block.
 *
 * @package GatherPress_Event_Details
 * @since 0.1.0
 *
 * @var WP_Block $block The block instance.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use GatherPress_Event_Details\Block;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Block::render().
echo Block::get_instance()->render( $block );
