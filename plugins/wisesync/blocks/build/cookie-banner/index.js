( () => {
	'use strict'; const e = window.wp.blocks,
		n = window.wp.i18n,
		s = window.wp.blockEditor,
		t = window.wp.data,
		i = window.wp.components,
		o = window.ReactJSXRuntime,
		c = JSON.parse( '{"$schema":"./../../../../../schema-block.json","apiVersion":3,"name":"sync/cookie-banner","version":"1.0.0","title":"Cookie Banner","category":"sync-advance-blocks","icon":"privacy","description":"Multi Country Complaince Cookie Consent Banner with customizable settings","attributes":{"bannerTitle":{"type":"string","default":"Cookie Consent"},"bannerDescription":{"type":"string","default":"We use cookies to enhance your browsing experience, serve personalized ads or content, and analyze our traffic. By clicking \\"Accept All\\", you consent to our use of cookies."}},"parent":["core/template-part"],"supports":{"multiple":false,"html":false},"textdomain":"wisesync","editorScript":"file:./index.js","editorStyle":"file:./index.css","viewScript":"file:./view.js","style":"file:./style.css","example":{"attributes":{"bannerTitle":"Cookie Consent","bannerPosition":"bottom"}}}' ); ( 0, e.registerBlockType )( c.name, { ...c, edit( { attributes: e, setAttributes: c, clientId: a } ) {
		const { bannerTitle: r, bannerDescription: l } = e,
			p = ( 0, s.useBlockProps )(),
			{ isInFooterTemplatePart: d } = ( 0, t.useSelect )( ( ( e ) => {
				const { getBlockParents: n, getBlocksByClientId: t } = e( s.store ),
					i = n( a ); let o = ! 1; for ( const e of i ) {
					const n = t( [ e ] ); if ( n && n.length ) {
						const e = n[ 0 ]; if ( 'core/template-part' === e.name && e.attributes && 'footer' === e.attributes.area ) {
							o = ! 0; break;
						}
					}
				} return { isInFooterTemplatePart: o };
			} ), [ a ] ); return d ? ( 0, o.jsxs )( o.Fragment, { children: [ ( 0, o.jsx )( s.InspectorControls, { children: ( 0, o.jsxs )( i.PanelBody, { title: ( 0, n.__ )( 'Banner Settings', 'wisesync' ), children: [ ( 0, o.jsx )( i.TextControl, { label: ( 0, n.__ )( 'Banner Title', 'wisesync' ), value: r, onChange: ( e ) => c( { bannerTitle: e } ) } ), ( 0, o.jsx )( i.TextareaControl, { label: ( 0, n.__ )( 'Banner Description', 'wisesync' ), value: l, onChange: ( e ) => c( { bannerDescription: e } ) } ) ] } ) } ), ( 0, o.jsx )( 'div', { ...p, className: 'wisesync-cookie-banner-editor', children: ( 0, o.jsxs )( 'div', { className: 'cookie-banner-preview', children: [ ( 0, o.jsx )( 'h3', { children: r || ( 0, n.__ )( 'Cookie Consent', 'wisesync' ) } ), ( 0, o.jsx )( 'p', { children: l || ( 0, n.__ )( 'We use cookies to enhance your browsing experience…', 'wisesync' ) } ), ( 0, o.jsxs )( 'div', { className: 'cookie-banner-buttons', children: [ ( 0, o.jsx )( 'button', { className: 'cookie-accept-all', children: ( 0, n.__ )( 'Accept All', 'wisesync' ) } ), ( 0, o.jsx )( 'button', { className: 'cookie-accept-necessary', children: ( 0, n.__ )( 'Accept Necessary Only', 'wisesync' ) } ) ] } ) ] } ) } ) ] } ) : ( 0, o.jsx )( 'div', { ...p, className: 'wisesync-cookie-banner-warning', children: ( 0, o.jsxs )( 'div', { className: 'components-placeholder', children: [ ( 0, o.jsx )( 'div', { className: 'components-placeholder__label', children: ( 0, n.__ )( 'Cookie Banner', 'wisesync' ) } ), ( 0, o.jsx )( 'div', { className: 'components-placeholder__instructions', children: ( 0, n.__ )( 'This block can only be used in template parts with area="footer".', 'wisesync' ) } ) ] } ) } );
	}, save: () => null } );
} )();
