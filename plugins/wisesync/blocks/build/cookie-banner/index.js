( () => {
	'use strict'; const o = window.wp.blocks,
		n = ( window.wp.data, window.wp.blockEditor ),
		e = ( window.wp.i18n, window.ReactJSXRuntime ); ( 0, o.registerBlockType )( 'my-plugin/footer-only-block', { edit: ( {} ) => {
		const o = ( 0, n.useBlockProps )(); return ( 0, e.jsx )( 'div', { ...o, children: ( 0, e.jsx )( 'div', { className: 'footer-only-block', children: ( 0, e.jsx )( n.InnerBlocks, {} ) } ) } );
	}, save: () => {
		const o = n.useBlockProps.save(); return ( 0, e.jsx )( 'div', { ...o, children: ( 0, e.jsx )( 'div', { className: 'footer-only-block', children: ( 0, e.jsx )( n.InnerBlocks.Content, {} ) } ) } );
	} } );
} )();
