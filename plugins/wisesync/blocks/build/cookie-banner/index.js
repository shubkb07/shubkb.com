( () => {
	'use strict'; const e = window.wp.blocks,
		n = window.wp.i18n,
		s = window.wp.blockEditor,
		t = window.wp.data,
		i = window.wp.components,
		o = window.ReactJSXRuntime,
		r = JSON.parse( '{"$schema":"./../../../../../schema-block.json","apiVersion":3,"name":"sync/cookie-banner","version":"1.0.0","title":"Cookie Banner","category":"sync-advance-blocks","icon":"privacy","description":"Multi Country Complaince Cookie Consent Banner with customizable settings","attributes":{"bannerTitle":{"type":"string","default":"Cookie Consent"},"bannerDescription":{"type":"string","default":"We use cookies to enhance your browsing experience, serve personalized ads or content, and analyze our traffic. By clicking \\"Accept All\\", you consent to our use of cookies."}},"ancestor":["core/template-part"],"supports":{"multiple":false,"html":false},"textdomain":"wisesync","editorScript":"file:./index.js","editorStyle":"file:./editor.scss","viewScript":"file:./view.js","style":"file:./style.scss","example":{"attributes":{"bannerTitle":"Cookie Consent","bannerPosition":"bottom"}}}' ); ( 0, e.registerBlockType )( r.name, { ...r, title: ( 0, n.__ )( 'Cookie Banner', 'wisesync' ), description: ( 0, n.__ )( 'Multi Country Complaince Cookie Consent Banner with customizable settings', 'wisesync' ), edit( { attributes: e, setAttributes: r, clientId: a } ) {
		const { bannerTitle: c, bannerDescription: l } = e,
			p = ( 0, s.useBlockProps )(),
			{ isInFooterTemplatePart: d, currentTemplatePartArea: u } = ( 0, t.useSelect )( ( ( e ) => {
				const { getBlockParents: n, getBlock: t } = e( s.store ),
					i = n( a ); let o = ! 1,
					r = null; for ( const e of i ) {
					const n = t( e ); if ( n && 'core/template-part' === n.name && n.attributes ) {
						r = n.attributes.area || null, 'footer' === r && ( o = ! 0 ); break;
					}
				} return { isInFooterTemplatePart: o, currentTemplatePartArea: r };
			} ), [ a ] ); return ( 0, o.jsxs )( o.Fragment, { children: [ ( 0, o.jsx )( s.InspectorControls, { children: ( 0, o.jsxs )( i.PanelBody, { title: ( 0, n.__ )( 'Banner Settings', 'wisesync' ), children: [ ! d && null !== u && ( 0, o.jsx )( i.Notice, { status: 'warning', isDismissible: ! 1, children: ( 0, n.__ )( 'This block is designed to be used only in template parts with area="footer".', 'wisesync' ) } ), ( 0, o.jsx )( i.TextControl, { label: ( 0, n.__ )( 'Banner Title', 'wisesync' ), value: c, onChange: ( e ) => r( { bannerTitle: e } ) } ), ( 0, o.jsx )( i.TextareaControl, { label: ( 0, n.__ )( 'Banner Description', 'wisesync' ), value: l, onChange: ( e ) => r( { bannerDescription: e } ) } ) ] } ) } ), ( 0, o.jsxs )( 'div', { ...p, className: 'wisesync-cookie-banner-editor ' + ( d ? '' : 'non-footer-warning' ), children: [ ! d && ( 0, o.jsx )( 'div', { className: 'wisesync-cookie-banner-warning', children: ( 0, o.jsx )( 'p', { children: ( 0, n.__ )( '⚠️ Warning: This block should only be used in template parts with area="footer".', 'wisesync' ) } ) } ), ( 0, o.jsxs )( 'div', { className: 'cookie-banner-preview', children: [ ( 0, o.jsx )( 'h3', { children: c || ( 0, n.__ )( 'Cookie Consent', 'wisesync' ) } ), ( 0, o.jsx )( 'p', { children: l || ( 0, n.__ )( 'We use cookies to enhance your browsing experience…', 'wisesync' ) } ), ( 0, o.jsxs )( 'div', { className: 'cookie-banner-buttons', children: [ ( 0, o.jsx )( 'button', { className: 'cookie-accept-all', children: ( 0, n.__ )( 'Accept All', 'wisesync' ) } ), ( 0, o.jsx )( 'button', { className: 'cookie-accept-necessary', children: ( 0, n.__ )( 'Accept Necessary Only', 'wisesync' ) } ) ] } ) ] } ) ] } ) ] } );
	}, save: () => null } );
} )();
