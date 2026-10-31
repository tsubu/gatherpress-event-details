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
define( 'GATHERPRESS_EVENT_DETAILS_MIN_GATHERPRESS_VERSION', '0.36.1' );

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
	if ( ! gatherpress_event_details_is_gatherpress_compatible() ) {
		return;
	}

	GatherPress_Event_Details\Setup::get_instance();
}
add_action( 'gatherpress_loaded', 'gatherpress_event_details_setup' );

/**
 * Checks whether the active GatherPress version meets the plugin requirement.
 *
 * @since 0.1.0
 *
 * @param string|null $version The version to check, or null for the active version.
 *
 * @return bool Whether the GatherPress version is supported.
 */
function gatherpress_event_details_is_gatherpress_compatible( ?string $version = null ): bool {
	if ( null === $version ) {
		$version = defined( 'GATHERPRESS_VERSION' ) ? GATHERPRESS_VERSION : '';
	}

	return version_compare( $version, GATHERPRESS_EVENT_DETAILS_MIN_GATHERPRESS_VERSION, '>=' );
}

/**
 * Surfaces why GatherPress Event Details is inert when GatherPress is absent or too old.
 *
 * Stays on `plugins_loaded` because it must run in exactly the case where
 * `gatherpress_loaded` never fires, including on GatherPress versions before 0.34.1.
 *
 * @since 0.1.0
 *
 * @return void
 */
function gatherpress_event_details_maybe_notify_missing_gatherpress(): void {
	if ( ! defined( 'GATHERPRESS_VERSION' ) ) {
		$message = esc_html__( 'GatherPress Event Details requires GatherPress.', 'gatherpress-event-details' );
	} elseif ( ! gatherpress_event_details_is_gatherpress_compatible() ) {
		$message = sprintf(
			/* translators: %s: minimum required GatherPress version. */
			esc_html__(
				'GatherPress Event Details requires GatherPress %s or later.',
				'gatherpress-event-details'
			),
			esc_html( GATHERPRESS_EVENT_DETAILS_MIN_GATHERPRESS_VERSION )
		);
	} else {
		return;
	}

	$show_notice = static function () use ( $message ): void {
		wp_admin_notice( $message, array( 'type' => 'error' ) );
	};

	add_action( 'admin_notices', $show_notice );
	add_action( 'network_admin_notices', $show_notice );
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
