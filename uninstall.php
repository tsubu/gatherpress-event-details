<?php
/**
 * Uninstall GatherPress Event Details.
 *
 * Deletes the event details post meta on every site. Runs only from
 * WordPress's uninstall flow, when the plugin is deleted.
 *
 * @package GatherPress_Event_Details
 * @since 0.1.0
 */

// Exit if accessed directly or not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Mirrors GatherPress_Event_Details\Meta::META_KEY; the plugin is not loaded during uninstall.
$gatherpress_event_details_meta_key = 'gatherpress_event_details';

if ( is_multisite() ) {
	// `'number' => 0` lifts get_sites()'s default limit of 100 sites.
	$gatherpress_event_details_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $gatherpress_event_details_site_ids as $gatherpress_event_details_site_id ) {
		switch_to_blog( (int) $gatherpress_event_details_site_id );
		delete_post_meta_by_key( $gatherpress_event_details_meta_key );
		restore_current_blog();
	}
} else {
	delete_post_meta_by_key( $gatherpress_event_details_meta_key );
}
