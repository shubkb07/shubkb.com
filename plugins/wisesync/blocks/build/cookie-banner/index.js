( () => {
	'use strict'; const e = window.wp.blocks,
		t = window.wp.data,
		i = window.wp.blockEditor,
		o = window.ReactJSXRuntime; ( 0, e.registerBlockType )( 'my-plugin/site-editor-only-block', { edit: ( { context: e } ) => {
		const { postType: s } = e,
			n = ( 0, t.useSelect )( ( ( e ) => {
				const { getEditorMode: t } = e( 'core/edit-site' ) || {}; return !! t;
			} ), [] ),
			c = ( 0, i.useBlockProps )(); return n ? ( 0, o.jsxs )( 'div', { ...c, children: [ ( 0, o.jsx )( 'h2', { children: 'Site Editor Only Block' } ), ( 0, o.jsx )( 'p', { children: 'This content only shows in the Site Editor.' } ) ] } ) : ( 0, o.jsx )( 'div', { ...c, children: 'This block is only available in the Site Editor.' } );
	}, save: () => {
		const e = i.useBlockProps.save(); return ( 0, o.jsxs )( 'div', { ...e, children: [ ( 0, o.jsx )( 'h2', { children: 'Site Editor Only Block' } ), ( 0, o.jsx )( 'p', { children: 'This is the saved content that shows on the frontend.' } ) ] } );
	} } );
} )();
