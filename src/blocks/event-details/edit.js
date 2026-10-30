/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import DetailsEditor from '../../components/DetailsEditor';
import {
	META_KEY,
	isDisplayable,
	isEventPostType,
	normalizeItems,
} from '../../helpers/items';

/**
 * Shows a hint in place of the list.
 *
 * @since 0.1.0
 *
 * @param {Object} props         Component props.
 * @param {string} props.message The hint.
 *
 * @return {Element} The hint.
 */
function Placeholder( { message } ) {
	return (
		<div { ...useBlockProps() }>
			<p className="gatherpress-event-details__placeholder">
				{ message }
			</p>
		</div>
	);
}

/**
 * Edits the block on an event: previews the list and edits it in the sidebar.
 *
 * @since 0.1.0
 *
 * @param {Object} props          Component props.
 * @param {number} props.postId   The event post ID.
 * @param {string} props.postType The event post type.
 *
 * @return {Element} The block editor.
 */
function EventEdit( { postId, postType } ) {
	const blockProps = useBlockProps();
	const [ meta ] = useEntityProp( 'postType', postType, 'meta', postId );
	const items = normalizeItems( meta?.[ META_KEY ] ).filter( isDisplayable );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Event details', 'gatherpress-event-details' ) }
				>
					<DetailsEditor postId={ postId } postType={ postType } />
				</PanelBody>
			</InspectorControls>

			{ 0 < items.length ? (
				<dl { ...blockProps }>
					{ items.map( ( item, index ) => (
						<div
							// Items have no stable ID; the index is their identity in this list.
							key={ index }
							className="gatherpress-event-details__item"
						>
							<dt className="gatherpress-event-details__label">
								{ item.label }
							</dt>
							<dd className="gatherpress-event-details__value">
								{ item.value }
							</dd>
						</div>
					) ) }
				</dl>
			) : (
				<div { ...blockProps }>
					<p className="gatherpress-event-details__placeholder">
						{ __(
							'Add details such as food, parking, or childcare in the block settings.',
							'gatherpress-event-details',
						) }
					</p>
				</div>
			) }
		</>
	);
}

/**
 * Edit component for the Event Details block.
 *
 * @since 0.1.0
 *
 * @param {Object} props                  Component props.
 * @param {Object} props.context          Block context.
 * @param {number} props.context.postId   The post ID the block renders for.
 * @param {string} props.context.postType The post type the block renders for.
 *
 * @return {Element} The block editor.
 */
export default function Edit( { context: { postId, postType } } ) {
	if ( postId && isEventPostType( postType ) ) {
		return <EventEdit postId={ postId } postType={ postType } />;
	}

	return (
		<Placeholder
			message={ __(
				'Event details appear here on each event.',
				'gatherpress-event-details',
			) }
		/>
	);
}
