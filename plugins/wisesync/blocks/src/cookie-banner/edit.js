import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, store as blockEditorStore } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { TextControl, TextareaControl, PanelBody, Notice } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { bannerTitle, bannerDescription } = attributes;

	const blockProps = useBlockProps();

	// Check if block is inside a template part with area="footer"
	const { isInFooterTemplatePart, currentTemplatePartArea } = useSelect( ( select ) => {
		const { getBlockParents, getBlock } = select( blockEditorStore );

		const parentBlockIds = getBlockParents( clientId );
		let isFooterArea = false;
		let templatePartArea = null;

		// Check if any parent block is a template part and get its area
		for ( const parentId of parentBlockIds ) {
			const parentBlock = getBlock( parentId );
			if ( parentBlock && parentBlock.name === 'core/template-part' && parentBlock.attributes ) {
				templatePartArea = parentBlock.attributes.area || null;
				if ( templatePartArea === 'footer' ) {
					isFooterArea = true;
				}
				break;
			}
		}

		return {
			isInFooterTemplatePart: isFooterArea,
			currentTemplatePartArea: templatePartArea,
		};
	}, [ clientId ] );

	// Always render the block content, but show a notice if not in a footer template part
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Banner Settings', 'wisesync' ) }>
					{ ! isInFooterTemplatePart && currentTemplatePartArea !== null && (
						<Notice status="warning" isDismissible={ false }>
							{ __( 'This block is designed to be used only in template parts with area="footer".', 'wisesync' ) }
						</Notice>
					) }

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

			<div { ...blockProps } className={ `wisesync-cookie-banner-editor ${ ! isInFooterTemplatePart ? 'non-footer-warning' : '' }` }>
				{ ! isInFooterTemplatePart && (
					<div className="wisesync-cookie-banner-warning">
						<p>
							{ __( '⚠️ Warning: This block should only be used in template parts with area="footer".', 'wisesync' ) }
						</p>
					</div>
				) }

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
