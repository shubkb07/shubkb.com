import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { useBlockProps } from '@wordpress/block-editor';

registerBlockType( 'my-plugin/site-editor-only-block', {
	edit: ( { context } ) => {
		const { postType } = context;
		const isSiteEditor = useSelect( ( select ) => {
	  const { getEditorMode } = select( 'core/edit-site' ) || {};
	  return !! getEditorMode;
		}, [] );

		const blockProps = useBlockProps();

		// If we're not in the site editor, show nothing or a placeholder
		if ( ! isSiteEditor ) {
	  return <div { ...blockProps }>This block is only available in the Site Editor.</div>;
		}

		// Your actual block content for site editor
		return (
			<div { ...blockProps }>
				<h2>Site Editor Only Block</h2>
				<p>This content only shows in the Site Editor.</p>
			</div>
		);
	},

	save: () => {
		const blockProps = useBlockProps.save();
		return (
			<div { ...blockProps }>
				<h2>Site Editor Only Block</h2>
				<p>This is the saved content that shows on the frontend.</p>
			</div>
		);
	},
} );
