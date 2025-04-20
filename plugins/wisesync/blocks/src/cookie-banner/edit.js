import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { TextControl, TextareaControl, PanelBody } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { bannerTitle, bannerDescription } = attributes;

	const blockProps = useBlockProps();

	// Check if block is inside a template part with area="footer"
	const { isInFooterTemplatePart } = useSelect( ( select ) => {
		const { getBlockParents, getBlocksByClientId } = select( blockEditorStore );

		const parentBlockIds = getBlockParents( clientId );
		let isFooterArea = false;

		// Check if any parent block is a template part with area="footer"
		for ( const parentId of parentBlockIds ) {
			const parentBlocks = getBlocksByClientId( [ parentId ] );
			if ( parentBlocks && parentBlocks.length ) {
				const parentBlock = parentBlocks[ 0 ];
				if ( parentBlock.name === 'core/template-part' &&
					parentBlock.attributes &&
					parentBlock.attributes.area === 'footer' ) {
					isFooterArea = true;
					break;
				}
			}
		}

		return {
			isInFooterTemplatePart: isFooterArea,
		};
	}, [ clientId ] );

	// If not in a template part with area="footer", show a notice
	if ( ! isInFooterTemplatePart ) {
		return (
			<div { ...blockProps } className="wisesync-cookie-banner-warning">
				<div className="components-placeholder">
					<div className="components-placeholder__label">
						{ __( 'Cookie Banner', 'wisesync' ) }
					</div>
					<div className="components-placeholder__instructions">
						{ __( 'This block can only be used in template parts with area="footer".', 'wisesync' ) }
					</div>
				</div>
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Banner Settings', 'wisesync' ) }>
					<TextControl
						label={ __( 'Banner Title', 'wisesync' ) }
						value={ bannerTitle }
						onChange={ ( value ) => setAttributes( { bannerTitle: value } ) }
					/>
					<TextareaControl
						label={ __( 'Banner Description', 'wisesync' ) }
						value={ bannerDescription }
						onChange={ ( value ) => setAttributes( { bannerDescription: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps } className="wisesync-cookie-banner-editor">
				<div className="cookie-banner-preview">
					<h3>{ bannerTitle || __( 'Cookie Consent', 'wisesync' ) }</h3>
					<p>{ bannerDescription || __( 'We use cookies to enhance your browsing experience…', 'wisesync' ) }</p>
					<div className="cookie-banner-buttons">
						<button className="cookie-accept-all">{ __( 'Accept All', 'wisesync' ) }</button>
						<button className="cookie-accept-necessary">{ __( 'Accept Necessary Only', 'wisesync' ) }</button>
					</div>
				</div>
			</div>
		</>
	);
}
