( () => {
	'use strict'; const o = window.wp.blocks,
		e = window.wp.data,
		n = window.wp.blockEditor,
		l = window.wp.i18n,
		s = window.ReactJSXRuntime; ( 0, o.registerBlockType )( 'my-plugin/footer-only-block', { edit: ( { context: o } ) => {
		const t = ( 0, n.useBlockProps )(),
			c = 'footer' === o?.templateSlug; return ( 0, e.useSelect )( ( ( o ) => o( 'core/block-editor' ).getBlocks().filter( ( ( o ) => 'my-plugin/footer-only-block' === o.name ) ).length ), [] ), c ? ( 0, s.jsx )( 'div', { ...t, children: ( 0, s.jsx )( 'div', { className: 'footer-only-block', children: ( 0, s.jsx )( n.InnerBlocks, {} ) } ) } ) : ( 0, s.jsx )( 'div', { ...t, className: 'footer-only-block-warning', children: ( 0, s.jsx )( 'p', { children: ( 0, l.__ )( 'This block can only be used in footer template parts.', 'my-plugin' ) } ) } );
	}, save: () => {
		const o = n.useBlockProps.save(); return ( 0, s.jsx )( 'div', { ...o, children: ( 0, s.jsx )( 'div', { className: 'footer-only-block', children: ( 0, s.jsx )( n.InnerBlocks.Content, {} ) } ) } );
	} } );
} )();
