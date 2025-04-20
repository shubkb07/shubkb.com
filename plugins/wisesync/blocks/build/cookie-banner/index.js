( () => {
	'use strict'; const e = window.wp.blocks,
		n = window.wp.i18n,
		s = window.wp.blockEditor,
		t = window.wp.data,
		o = window.wp.components,
		i = window.ReactJSXRuntime,
		c = JSON.parse( '{"$schema":"./../../../../../schema-block.json","apiVersion":3,"name":"sync/cookie-banner","version":"1.0.0","title":"Cookie Banner","category":"sync-advance-blocks","icon":"privacy","description":"Multi Country Complaince Cookie Consent Banner with customizable settings","attributes":{"bannerTitle":{"type":"string","default":"Cookie Consent"},"bannerDescription":{"type":"string","default":"We use cookies to enhance your browsing experience, serve personalized ads or content, and analyze our traffic. By clicking \\"Accept All\\", you consent to our use of cookies."}},"parent":["core/template-part"],"supports":{"multiple":false,"html":false},"textdomain":"wisesync","editorScript":"file:./index.js","editorStyle":"file:./index.css","viewScript":"file:./view.js","style":"file:./style.css","example":{"attributes":{"bannerTitle":"Cookie Consent","bannerPosition":"bottom"}}}' ); ( 0, e.registerBlockType )( c.name, { ...c, edit( { attributes: e, setAttributes: c, clientId: r } ) {
		const { bannerTitle: a, bannerDescription: l } = e,
			d = ( 0, s.useBlockProps )(),
			{ isInFooter: p } = ( 0, t.useSelect )( ( ( e ) => {
				const { getBlockParents: n, getBlocksByClientId: t } = e( s.store ),
					o = n( r ); let i = ! 1; for ( const e of o ) {
					const n = t( [ e ] ); if ( n && n.length ) {
						const e = n[ 0 ]; if ( 'core/template-part' === e.name && e.attributes && 'footer' === e.attributes.area ) {
							i = ! 0; break;
						}
					}
				} return { isInFooter: i };
			} ), [ r ] ); return p ? ( 0, i.jsxs )( i.Fragment, { children: [ ( 0, i.jsx )( s.InspectorControls, { children: ( 0, i.jsxs )( o.PanelBody, { title: ( 0, n.__ )( 'Banner Settings', 'wisesync' ), children: [ ( 0, i.jsx )( o.TextControl, { label: ( 0, n.__ )( 'Banner Title', 'wisesync' ), value: a, onChange: ( e ) => c( { bannerTitle: e } ) } ), ( 0, i.jsx )( o.TextareaControl, { label: ( 0, n.__ )( 'Banner Description', 'wisesync' ), value: l, onChange: ( e ) => c( { bannerDescription: e } ) } ) ] } ) } ), ( 0, i.jsx )( 'div', { ...d, className: 'wisesync-cookie-banner-editor', children: ( 0, i.jsxs )( 'div', { className: 'cookie-banner-preview', children: [ ( 0, i.jsx )( 'h3', { children: a || ( 0, n.__ )( 'Cookie Consent', 'wisesync' ) } ), ( 0, i.jsx )( 'p', { children: l || ( 0, n.__ )( 'We use cookies to enhance your browsing experience…', 'wisesync' ) } ), ( 0, i.jsxs )( 'div', { className: 'cookie-banner-buttons', children: [ ( 0, i.jsx )( 'button', { className: 'cookie-accept-all', children: ( 0, n.__ )( 'Accept All', 'wisesync' ) } ), ( 0, i.jsx )( 'button', { className: 'cookie-accept-necessary', children: ( 0, n.__ )( 'Accept Necessary Only', 'wisesync' ) } ) ] } ) ] } ) } ) ] } ) : ( 0, i.jsx )( 'div', { ...d, className: 'wisesync-cookie-banner-warning', children: ( 0, i.jsxs )( 'div', { className: 'components-placeholder', children: [ ( 0, i.jsx )( 'div', { className: 'components-placeholder__label', children: ( 0, n.__ )( 'Cookie Banner', 'wisesync' ) } ), ( 0, i.jsx )( 'div', { className: 'components-placeholder__instructions', children: ( 0, n.__ )( 'This block can only be used in footer template parts.', 'wisesync' ) } ) ] } ) } );
	}, save: () => null } );
} )();
