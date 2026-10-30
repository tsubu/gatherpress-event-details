<?php
/**
 * Plugin Name:       GatherPress Event Details
 * Plugin URI:        https://github.com/GatherPress/gatherpress/issues/2236
 * Description:       Labeled details for GatherPress events, such as food, parking, or childcare.
 * Author:            tsubu
 * Version:           0.1.0
 * Requires PHP:      8.1
 * Requires at least: 7.0
 * Requires Plugins:  gatherpress
 * Text Domain:       gatherpress-event-details
 * Domain Path:       /languages
 * License:           GNU General Public License v2.0 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package GatherPress_Event_Details
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

// Bail when a sibling copy is already loaded (e.g., when WordPress includes a
// duplicate folder during activation).
if ( defined( 'GATHERPRESS_EVENT_DETAILS_VERSION' ) ) {
	return;
}

define(
	'GATHERPRESS_EVENT_DETAILS_VERSION',
	get_file_data( __FILE__, array( 'Version' => 'Version' ), 'plugin' )['Version']
);
define( 'GATHERPRESS_EVENT_DETAILS_CORE_PATH', __DIR__ );

/**
 * Adds the GatherPress_Event_Details namespace to the autoloader.
 *
 * @since 0.1.0
 *
 * @param array $namespaces An associative array of namespaces and their paths.
 *
 * @return array Modified array of namespaces and their paths.
 */
function gatherpress_event_details_autoloader( array $namespaces ): array {
	$namespaces['GatherPress_Event_Details'] = GATHERPRESS_EVENT_DETAILS_CORE_PATH;

	return $namespaces;
}
add_filter( 'gatherpress_autoloader', 'gatherpress_event_details_autoloader' );

/**
 * Boots the GatherPress Event Details runtime.
 *
 * Hooked on `gatherpress_loaded`, which GatherPress fires only after its own
 * requirements check passes, so the GatherPress classes this plugin uses are
 * guaranteed to be loadable.
 *
 * @since 0.1.0
 *
 * @return void
 */
function gatherpress_event_details_setup(): void {
	GatherPress_Event_Details\Setup::get_instance();
}
add_action( 'gatherpress_loaded', 'gatherpress_event_details_setup' );

/**
 * Surfaces why GatherPress Event Details is inert when GatherPress is absent.
 *
 * Stays on `plugins_loaded` because it must run in exactly the case where
 * `gatherpress_loaded` never fires.
 *
 * @since 0.1.0
 *
 * @return void
 */
function gatherpress_event_details_maybe_notify_missing_gatherpress(): void {
	if ( defined( 'GATHERPRESS_VERSION' ) ) {
		return;
	}

	add_action(
		'admin_notices',
		static function (): void {
			wp_admin_notice(
				esc_html__( 'GatherPress Event Details requires GatherPress.', 'gatherpress-event-details' ),
				array( 'type' => 'error' )
			);
		}
	);
}
add_action( 'plugins_loaded', 'gatherpress_event_details_maybe_notify_missing_gatherpress' );

/**
 * Announces this plugin to GatherPress's coexistence guard.
 *
 * @since 0.1.0
 *
 * @return void
 */
function gatherpress_event_details_register_coexistence_guard(): void {
	do_action(
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- GatherPress's own action.
		'gatherpress_register_coexistence_guard',
		'gatherpress-event-details',
		'GatherPress Event Details',
		__FILE__
	);
}
add_action( 'gatherpress_loaded', 'gatherpress_event_details_register_coexistence_guard' );
