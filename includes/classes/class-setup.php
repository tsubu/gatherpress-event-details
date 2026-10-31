<?php
/**
 * Plugin setup for GatherPress Event Details.
 *
 * @package GatherPress_Event_Details
 * @since 0.1.0
 */

namespace GatherPress_Event_Details;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use GatherPress\Core\Traits\Singleton;

/**
 * Class Setup.
 *
 * Boots the plugin's subsystems.
 *
 * @since 0.1.0
 */
final class Setup {

	/**
	 * Enforces a single instance of this class.
	 */
	use Singleton;

	/**
	 * Constructor for the Setup class.
	 *
	 * @since 0.1.0
	 */
	protected function __construct() {
		Meta::get_instance();
		Block::get_instance();
	}
}
