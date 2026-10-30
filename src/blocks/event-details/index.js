/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import DocumentPanel from '../../components/DocumentPanel';
import './style.scss';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );

// The block's editor script loads in every post editor, so it also carries
// the sidebar fill; DocumentPanel renders nothing outside an event.
registerPlugin( 'gatherpress-event-details', {
	render: DocumentPanel,
} );
