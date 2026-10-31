/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	Notice,
	TextControl,
	TextareaControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHStack as HStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import {
	META_KEY,
	addItem,
	getConfig,
	isDisplayable,
	moveItem,
	normalizeItems,
	removeItem,
	updateItem,
} from '../helpers/items';

/**
 * Limits a string by Unicode code points, matching the server-side character cap.
 *
 * HTML's `maxLength` counts UTF-16 code units, so the input cap is set to twice
 * the server limit while this function enforces the actual code-point limit.
 *
 * @param {string} value     The input value.
 * @param {number} maxLength The maximum number of code points.
 * @return {string} The value within the limit.
 */
function limitByCodePoints( value, maxLength ) {
	return Array.from( value ).slice( 0, maxLength ).join( '' );
}

/**
 * Edits the details of one event.
 *
 * Reads and writes the `gatherpress_event_details` post meta through the
 * entity store, so the change is saved with the post.
 *
 * @since 0.1.0
 *
 * @param {Object} props          Component props.
 * @param {number} props.postId   The event post ID.
 * @param {string} props.postType The event post type.
 *
 * @return {Element} The editor.
 */
export default function DetailsEditor( { postId, postType } ) {
	const [ meta, setMeta ] = useEntityProp(
		'postType',
		postType,
		'meta',
		postId,
	);
	const listId = useInstanceId(
		DetailsEditor,
		'gatherpress-event-details-labels',
	);
	const { labelSuggestions, maxItems, maxLabelLength, maxValueLength } =
		getConfig();
	const items = normalizeItems( meta?.[ META_KEY ] );
	const hasIncomplete = items.some( ( item ) => ! isDisplayable( item ) );

	const setItems = ( next ) => setMeta( { ...meta, [ META_KEY ]: next } );

	return (
		<VStack spacing={ 4 } className="gatherpress-event-details-editor">
			{ items.map( ( item, index ) => (
				<VStack
					// Items have no stable ID; the index is their identity in this list.
					key={ index }
					spacing={ 2 }
					className="gatherpress-event-details-editor__item"
				>
					<TextControl
						__next40pxDefaultSize
						label={ sprintf(
							/* translators: %d: Position of the detail in the list. */
							__( 'Label %d', 'gatherpress-event-details' ),
							index + 1,
						) }
						maxLength={ maxLabelLength * 2 }
						help={ sprintf(
							/* translators: %d: maximum number of characters. */
							__(
								'Maximum %d characters.',
								'gatherpress-event-details',
							),
							maxLabelLength,
						) }
						value={ item.label }
						list={ labelSuggestions.length ? listId : undefined }
						onChange={ ( label ) =>
							setItems(
								updateItem( items, index, {
									label: limitByCodePoints(
										label,
										maxLabelLength,
									),
								} ),
							)
						}
					/>
					<TextareaControl
						label={ __(
							'Description',
							'gatherpress-event-details',
						) }
						maxLength={ maxValueLength * 2 }
						help={ sprintf(
							/* translators: %d: maximum number of characters. */
							__(
								'Maximum %d characters.',
								'gatherpress-event-details',
							),
							maxValueLength,
						) }
						value={ item.value }
						rows={ 2 }
						onChange={ ( value ) =>
							setItems(
								updateItem( items, index, {
									value: limitByCodePoints(
										value,
										maxValueLength,
									),
								} ),
							)
						}
					/>
					<HStack justify="flex-start">
						<Button
							size="small"
							icon="arrow-up-alt2"
							label={ __(
								'Move up',
								'gatherpress-event-details',
							) }
							disabled={ 0 === index }
							accessibleWhenDisabled
							onClick={ () =>
								setItems( moveItem( items, index, index - 1 ) )
							}
						/>
						<Button
							size="small"
							icon="arrow-down-alt2"
							label={ __(
								'Move down',
								'gatherpress-event-details',
							) }
							disabled={ items.length - 1 === index }
							accessibleWhenDisabled
							onClick={ () =>
								setItems( moveItem( items, index, index + 1 ) )
							}
						/>
						<Button
							size="small"
							variant="tertiary"
							isDestructive
							onClick={ () =>
								setItems( removeItem( items, index ) )
							}
						>
							{ __( 'Remove', 'gatherpress-event-details' ) }
						</Button>
					</HStack>
				</VStack>
			) ) }

			{ hasIncomplete && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Details need both a label and a description to appear on the event.',
						'gatherpress-event-details',
					) }
				</Notice>
			) }

			<Button
				variant="secondary"
				disabled={ items.length >= maxItems }
				accessibleWhenDisabled
				onClick={ () => setItems( addItem( items, maxItems ) ) }
			>
				{ __( 'Add detail', 'gatherpress-event-details' ) }
			</Button>

			{ 0 < labelSuggestions.length && (
				<datalist id={ listId }>
					{ labelSuggestions.map( ( suggestion ) => (
						<option key={ suggestion } value={ suggestion } />
					) ) }
				</datalist>
			) }
		</VStack>
	);
}
