/**
 * Internal dependencies
 */
import {
	META_KEY,
	addItem,
	getConfig,
	isDisplayable,
	isEventPostType,
	moveItem,
	normalizeItems,
	removeItem,
	updateItem,
} from '../../../src/helpers/items';

const items = [
	{ label: 'Food', value: 'Lunch' },
	{ label: 'Parking', value: 'Free' },
	{ label: 'Language', value: 'English' },
];

afterEach( () => {
	delete globalThis.gatherpressEventDetails;
} );

describe( 'META_KEY', () => {
	it( 'matches the PHP meta key', () => {
		expect( META_KEY ).toBe( 'gatherpress_event_details' );
	} );
} );

describe( 'getConfig', () => {
	it( 'falls back to defaults without a server config', () => {
		expect( getConfig() ).toEqual( {
			postTypes: [ 'gatherpress_event' ],
			labelSuggestions: [],
			maxItems: 20,
			maxLabelLength: 100,
			maxValueLength: 1000,
		} );
	} );

	it( 'prefers the server config', () => {
		globalThis.gatherpressEventDetails = {
			postTypes: [ 'production' ],
			maxItems: 5,
			maxLabelLength: 12,
			maxValueLength: 34,
		};

		expect( getConfig() ).toEqual( {
			postTypes: [ 'production' ],
			labelSuggestions: [],
			maxItems: 5,
			maxLabelLength: 12,
			maxValueLength: 34,
		} );
	} );
} );

describe( 'isEventPostType', () => {
	it( 'is true for a configured post type', () => {
		expect( isEventPostType( 'gatherpress_event' ) ).toBe( true );
	} );

	it( 'is false for another post type', () => {
		expect( isEventPostType( 'post' ) ).toBe( false );
	} );

	it( 'is false without a post type', () => {
		expect( isEventPostType( undefined ) ).toBe( false );
	} );
} );

describe( 'normalizeItems', () => {
	it( 'returns an empty list for a non-array', () => {
		expect( normalizeItems( undefined ) ).toEqual( [] );
		expect( normalizeItems( 'text' ) ).toEqual( [] );
	} );

	it( 'keeps string fields', () => {
		expect(
			normalizeItems( [ { label: 'Food', value: 'Lunch' } ] ),
		).toEqual( [ { label: 'Food', value: 'Lunch' } ] );
	} );

	it( 'drops non-objects and fills missing fields', () => {
		expect(
			normalizeItems( [ null, 'x', { label: 'Food' }, { value: 3 } ] ),
		).toEqual( [
			{ label: 'Food', value: '' },
			{ label: '', value: '' },
		] );
	} );
} );

describe( 'addItem', () => {
	it( 'appends an empty item', () => {
		expect( addItem( items ) ).toEqual( [
			...items,
			{ label: '', value: '' },
		] );
	} );

	it( 'leaves a full list unchanged', () => {
		expect( addItem( items, 3 ) ).toBe( items );
	} );

	it( 'uses the default limit', () => {
		const full = Array.from( { length: 20 }, () => ( {
			label: 'a',
			value: 'b',
		} ) );

		expect( addItem( full ) ).toBe( full );
	} );
} );

describe( 'updateItem', () => {
	it( 'changes only the given item', () => {
		expect( updateItem( items, 1, { value: 'Paid' } ) ).toEqual( [
			items[ 0 ],
			{ label: 'Parking', value: 'Paid' },
			items[ 2 ],
		] );
	} );
} );

describe( 'removeItem', () => {
	it( 'removes the given item', () => {
		expect( removeItem( items, 0 ) ).toEqual( [ items[ 1 ], items[ 2 ] ] );
	} );
} );

describe( 'moveItem', () => {
	it( 'moves an item down', () => {
		expect( moveItem( items, 0, 1 ) ).toEqual( [
			items[ 1 ],
			items[ 0 ],
			items[ 2 ],
		] );
	} );

	it( 'moves an item up', () => {
		expect( moveItem( items, 2, 1 ) ).toEqual( [
			items[ 0 ],
			items[ 2 ],
			items[ 1 ],
		] );
	} );

	it.each( [
		[ 1, 1 ],
		[ -1, 0 ],
		[ 0, -1 ],
		[ 3, 0 ],
		[ 0, 3 ],
	] )( 'leaves the list unchanged for %i → %i', ( from, to ) => {
		expect( moveItem( items, from, to ) ).toBe( items );
	} );
} );

describe( 'isDisplayable', () => {
	it( 'needs both a label and a value', () => {
		expect( isDisplayable( { label: 'Food', value: 'Lunch' } ) ).toBe(
			true,
		);
		expect( isDisplayable( { label: 'Food', value: ' ' } ) ).toBe( false );
		expect( isDisplayable( { label: ' ', value: 'Lunch' } ) ).toBe( false );
	} );
} );
