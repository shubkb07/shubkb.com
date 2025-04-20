/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

/**
 * Register the block
 */
registerBlockType( 'my-plugin/footer-only-block', {
	edit: ( { } ) => {
		const blockProps = useBlockProps();

		// Normal block content
		return (
			<div { ...blockProps }>
				<div className="footer-only-block">
					<InnerBlocks />
				</div>
			</div>
		);
	},

	save: () => {
		const blockProps = useBlockProps.save();

		return (
			<div { ...blockProps }>
				<div className="footer-only-block">
					<InnerBlocks.Content />
				</div>
			</div>
		);
	},
} );
