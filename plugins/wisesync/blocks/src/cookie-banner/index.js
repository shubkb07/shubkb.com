import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './edit';
import metadata from './block.json';

/**
 * Register the cookie banner block with enhanced visibility.
 * This ensures the block appears in template parts with area="footer".
 */
registerBlockType(metadata.name, {
    ...metadata,
    title: __('Cookie Banner', 'wisesync'),
    description: __('Multi Country Complaince Cookie Consent Banner with customizable settings', 'wisesync'),
    edit: Edit,
    // Server-side rendering with PHP
    save: () => null,
});
