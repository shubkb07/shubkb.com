import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';

/**
 * Register the cookie banner block.
 */
registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	// Server-side rendering with PHP.
	save: () => null,
} );
