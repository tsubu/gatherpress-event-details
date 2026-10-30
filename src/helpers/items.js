/**
 * The post meta key holding the details.
 *
 * Mirrors `GatherPress_Event_Details\Meta::META_KEY`.
 *
 * @since 0.1.0
 *
 * @type {string}
 */
export const META_KEY = 'gatherpress_event_details';

/**
 * Configuration used when the server did not print one.
 *
 * @since 0.1.0
 *
 * @type {Object}
 */
const DEFAULT_CONFIG = {
	postTypes: [ 'gatherpress_event' ],
	labelSuggestions: [],
	maxItems: 20,
};

/**
 * Returns the configuration printed by `Block::add_editor_config()`.
 *
 * @since 0.1.0
 *
 * @return {{postTypes: string[], labelSuggestions: string[], maxItems: number}} The configuration.
 */
export function getConfig() {
	return { ...DEFAULT_CONFIG, ...globalThis.gatherpressEventDetails };
}

/**
 * Checks whether a post type acts as a GatherPress event.
 *
 * @since 0.1.0
 *
 * @param {string|undefined} postType The post type name.
 *
 * @return {boolean} True when the post type declares event date support.
 */
export function isEventPostType( postType ) {
	return Boolean( postType ) && getConfig().postTypes.includes( postType );
}

/**
 * Normalizes a stored value into a list of items.
 *
 * @since 0.1.0
 *
 * @param {*} value The raw meta value.
 *
 * @return {Array<{label: string, value: string}>} The items.
 */
export function normalizeItems( value ) {
	if ( ! Array.isArray( value ) ) {
		return [];
	}

	return value
		.filter( ( item ) => item && 'object' === typeof item )
		.map( ( item ) => ( {
			label: 'string' === typeof item.label ? item.label : '',
			value: 'string' === typeof item.value ? item.value : '',
		} ) );
}

/**
 * Appends an empty item, unless the list is full.
 *
 * @since 0.1.0
 *
 * @param {Array<{label: string, value: string}>} items    The items.
 * @param {number}                                maxItems The maximum number of items.
 *
 * @return {Array<{label: string, value: string}>} The new items.
 */
export function addItem( items, maxItems = DEFAULT_CONFIG.maxItems ) {
	if ( items.length >= maxItems ) {
		return items;
	}

	return [ ...items, { label: '', value: '' } ];
}

/**
 * Changes fields of one item.
 *
 * @since 0.1.0
 *
 * @param {Array<{label: string, value: string}>} items   The items.
 * @param {number}                                index   The index of the item to change.
 * @param {Object}                                changes The fields to change.
 *
 * @return {Array<{label: string, value: string}>} The new items.
 */
export function updateItem( items, index, changes ) {
	return items.map( ( item, current ) =>
		current === index ? { ...item, ...changes } : item
	);
}

/**
 * Removes one item.
 *
 * @since 0.1.0
 *
 * @param {Array<{label: string, value: string}>} items The items.
 * @param {number}                                index The index of the item to remove.
 *
 * @return {Array<{label: string, value: string}>} The new items.
 */
export function removeItem( items, index ) {
	return items.filter( ( item, current ) => current !== index );
}

/**
 * Moves one item to another position.
 *
 * @since 0.1.0
 *
 * @param {Array<{label: string, value: string}>} items The items.
 * @param {number}                                from  The current index.
 * @param {number}                                to    The new index.
 *
 * @return {Array<{label: string, value: string}>} The new items, or the same items when either index is out of range.
 */
export function moveItem( items, from, to ) {
	if (
		from === to ||
		from < 0 ||
		to < 0 ||
		from >= items.length ||
		to >= items.length
	) {
		return items;
	}

	const next = [ ...items ];
	const [ moved ] = next.splice( from, 1 );
	next.splice( to, 0, moved );

	return next;
}

/**
 * Checks whether an item is shown on the front end.
 *
 * Mirrors `Meta::get_displayable_items()`: both fields must hold more than
 * whitespace.
 *
 * @since 0.1.0
 *
 * @param {{label: string, value: string}} item The item.
 *
 * @return {boolean} True when the item has a label and a value.
 */
export function isDisplayable( item ) {
	return '' !== item.label.trim() && '' !== item.value.trim();
}
