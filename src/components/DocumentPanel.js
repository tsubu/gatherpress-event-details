/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { BaseControl, Fill } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import DetailsEditor from './DetailsEditor';
import { isEventPostType } from '../helpers/items';

/**
 * Adds the details editor to GatherPress's "Event settings" sidebar panel.
 *
 * Fills the `EventPluginDocumentSettings` slot, the entry point GatherPress
 * provides for companion plugins.
 *
 * @since 0.1.0
 *
 * @return {Element|null} The fill, or null outside an event.
 */
export default function DocumentPanel() {
	const { postId, postType } = useSelect(
		( select ) => ( {
			postId: select( editorStore ).getCurrentPostId(),
			postType: select( editorStore ).getCurrentPostType(),
		} ),
		[],
	);

	if ( ! isEventPostType( postType ) ) {
		return null;
	}

	return (
		<Fill name="EventPluginDocumentSettings">
			<BaseControl
				id="gatherpress-event-details-panel"
				label={ __( 'Event details', 'gatherpress-event-details' ) }
				help={ __(
					'Shown on the event by the Event Details block.',
					'gatherpress-event-details',
				) }
			>
				<DetailsEditor postId={ postId } postType={ postType } />
			</BaseControl>
		</Fill>
	);
}
