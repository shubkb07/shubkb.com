import { __ } from '@wordpress/i18n';
import { 
    InspectorControls, 
    useBlockProps, 
} from '@wordpress/block-editor';
import {
    PanelBody,
    TextControl,
    SelectControl,
    ColorPicker,
    ToggleControl,
    RangeControl,
    TabPanel,
    Placeholder,
    ExternalLink,
    Notice
} from '@wordpress/components';
import './editor.scss';

export default function Edit({ attributes, setAttributes, context }) {
    const {
        bannerTitle,
        bannerDescription,
        privacyPolicyLink,
        privacyPolicyText,
        termsLink,
        termsText,
        acceptAllText,
        rejectAllText,
        customizeText,
        savePreferencesText,
        necessaryCookiesTitle,
        necessaryCookiesDescription,
        functionalCookiesTitle,
        functionalCookiesDescription,
        analyticalCookiesTitle,
        analyticalCookiesDescription,
        advertisingCookiesTitle,
        advertisingCookiesDescription,
        trackingCookiesTitle,
        trackingCookiesDescription,
        bannerPosition,
        floatingButtonPosition,
        cookieExpiration,
        primaryColor,
        textColor,
        backgroundColor,
        showOnlyInSiteEditor,
        privacyPolicyVersion,
        termsVersion,
        checkVersionChanges,
        enabledCookieTypes,
        showBanner,
        showDoNotSellLink,
        doNotSellLink,
        doNotSellText,
        consentModel
    } = attributes;

    const blockProps = useBlockProps();
    
    // Check if we're in site editor
    const { postType } = context;
    const isSiteEditor = postType === 'wp_template' || postType === 'wp_template_part';
    const showBlockNotice = showOnlyInSiteEditor && !isSiteEditor;

    const bannerPositionOptions = [
        { label: __('Bottom', 'wisesync'), value: 'bottom' },
        { label: __('Top', 'wisesync'), value: 'top' },
        { label: __('Center', 'wisesync'), value: 'center' }
    ];

    const floatingButtonPositionOptions = [
        { label: __('Bottom Right', 'wisesync'), value: 'bottom-right' },
        { label: __('Bottom Left', 'wisesync'), value: 'bottom-left' },
        { label: __('Top Right', 'wisesync'), value: 'top-right' },
        { label: __('Top Left', 'wisesync'), value: 'top-left' },
    ];

    const consentModelOptions = [
        { label: __('Opt-in (Explicit Consent)', 'wisesync'), value: 'opt-in' },
        { label: __('Opt-out (Implied Consent)', 'wisesync'), value: 'opt-out' },
        { label: __('Notice Only', 'wisesync'), value: 'notice-only' },
        { label: __('Deemed Consent', 'wisesync'), value: 'deemed-consent' },
        { label: __('Implied Consent', 'wisesync'), value: 'implied-consent' },
    ];

    const updateEnabledCookieTypes = (type, value) => {
        setAttributes({ 
            enabledCookieTypes: {
                ...enabledCookieTypes,
                [type]: value
            }
        });
    };

    return (
        <>
            <InspectorControls>
                <TabPanel
                    className="cookie-banner-tabs"
                    tabs={[
                        {
                            name: 'visibility',
                            title: __('Visibility', 'wisesync'),
                            className: 'visibility-tab',
                        },
                        {
                            name: 'general',
                            title: __('General', 'wisesync'),
                        },
                        {
                            name: 'cookieCategories',
                            title: __('Cookie Categories', 'wisesync'),
                        },
                        {
                            name: 'appearance',
                            title: __('Appearance', 'wisesync'),
                        },
                        {
                            name: 'advanced',
                            title: __('Advanced', 'wisesync'),
                        },
                        {
                            name: 'restrictions',
                            title: __('Restrictions', 'wisesync'),
                        },
                        {
                            name: 'compliance',
                            title: __('Compliance', 'wisesync'),
                        }
                    ]}
                >
                    {(tab) => {
                        if (tab.name === 'visibility') {
                            return (
                                <PanelBody title={__('Banner Visibility', 'wisesync')} initialOpen={true}>
                                    <ToggleControl
                                        label={__('Show Banner', 'wisesync')}
                                        checked={showBanner}
                                        onChange={(value) => setAttributes({ showBanner: value })}
                                        help={__('Toggle to show or minimize the banner. When hidden, it will be minimized to the bottom of the page.', 'wisesync')}
                                    />
                                    <p className="banner-visibility-note">
                                        {__('Note: This setting only affects the editor preview and frontend display. The banner will still be functional when minimized, and users can access it via the floating button.', 'wisesync')}
                                    </p>
                                </PanelBody>
                            );
                        }
                        
                        if (tab.name === 'general') {
                            return (
                                <>
                                    <PanelBody title={__('Banner Content', 'wisesync')} initialOpen={true}>
                                        <TextControl
                                            label={__('Banner Title', 'wisesync')}
                                            value={bannerTitle}
                                            onChange={(value) => setAttributes({ bannerTitle: value })}
                                        />
                                        <TextControl
                                            label={__('Banner Description', 'wisesync')}
                                            value={bannerDescription}
                                            onChange={(value) => setAttributes({ bannerDescription: value })}
                                            multiline="p"
                                        />
                                        <TextControl
                                            label={__('Privacy Policy Link', 'wisesync')}
                                            value={privacyPolicyLink}
                                            onChange={(value) => setAttributes({ privacyPolicyLink: value })}
                                            help={__('Enter the URL to your Privacy Policy page', 'wisesync')}
                                        />
                                        <TextControl
                                            label={__('Privacy Policy Text', 'wisesync')}
                                            value={privacyPolicyText}
                                            onChange={(value) => setAttributes({ privacyPolicyText: value })}
                                        />
                                        <TextControl
                                            label={__('Privacy Policy Version', 'wisesync')}
                                            value={privacyPolicyVersion}
                                            onChange={(value) => setAttributes({ privacyPolicyVersion: value })}
                                            help={__('Optional: Set a version identifier (like a date) to track policy changes', 'wisesync')}
                                        />
                                        <TextControl
                                            label={__('Terms Link', 'wisesync')}
                                            value={termsLink}
                                            onChange={(value) => setAttributes({ termsLink: value })}
                                            help={__('Enter the URL to your Terms of Service page', 'wisesync')}
                                        />
                                        <TextControl
                                            label={__('Terms Text', 'wisesync')}
                                            value={termsText}
                                            onChange={(value) => setAttributes({ termsText: value })}
                                        />
                                        <TextControl
                                            label={__('Terms Version', 'wisesync')}
                                            value={termsVersion}
                                            onChange={(value) => setAttributes({ termsVersion: value })}
                                            help={__('Optional: Set a version identifier (like a date) to track terms changes', 'wisesync')}
                                        />
                                        <ToggleControl
                                            label={__('Check for Version Changes', 'wisesync')}
                                            checked={checkVersionChanges}
                                            onChange={(value) => setAttributes({ checkVersionChanges: value })}
                                            help={__('When enabled, users will be asked to re-consent when your privacy policy or terms change', 'wisesync')}
                                        />
                                    </PanelBody>
                                    <PanelBody title={__('Button Labels', 'wisesync')} initialOpen={false}>
                                        <TextControl
                                            label={__('Accept All Text', 'wisesync')}
                                            value={acceptAllText}
                                            onChange={(value) => setAttributes({ acceptAllText: value })}
                                        />
                                        <TextControl
                                            label={__('Reject All Text', 'wisesync')}
                                            value={rejectAllText}
                                            onChange={(value) => setAttributes({ rejectAllText: value })}
                                        />
                                        <TextControl
                                            label={__('Customize Text', 'wisesync')}
                                            value={customizeText}
                                            onChange={(value) => setAttributes({ customizeText: value })}
                                        />
                                        <TextControl
                                            label={__('Save Preferences Text', 'wisesync')}
                                            value={savePreferencesText}
                                            onChange={(value) => setAttributes({ savePreferencesText: value })}
                                        />
                                    </PanelBody>
                                </>
                            );
                        }
                        
                        if (tab.name === 'cookieCategories') {
                            return (
                                <>
                                    <PanelBody title={__('Cookie Types Configuration', 'wisesync')} initialOpen={true}>
                                        <p className="cookie-category-help">
                                            {__('Enable or disable different cookie categories. Note that necessary cookies cannot be disabled.', 'wisesync')}
                                        </p>
                                        <ToggleControl
                                            label={__('Functional Cookies', 'wisesync')}
                                            checked={enabledCookieTypes.functional}
                                            onChange={(value) => updateEnabledCookieTypes('functional', value)}
                                        />
                                        <ToggleControl
                                            label={__('Analytical Cookies', 'wisesync')}
                                            checked={enabledCookieTypes.analytical}
                                            onChange={(value) => updateEnabledCookieTypes('analytical', value)}
                                        />
                                        <ToggleControl
                                            label={__('Advertising Cookies', 'wisesync')}
                                            checked={enabledCookieTypes.advertising}
                                            onChange={(value) => updateEnabledCookieTypes('advertising', value)}
                                        />
                                        <ToggleControl
                                            label={__('Tracking Cookies', 'wisesync')}
                                            checked={enabledCookieTypes.tracking}
                                            onChange={(value) => updateEnabledCookieTypes('tracking', value)}
                                        />
                                    </PanelBody>
                                
                                    <PanelBody title={__('Necessary Cookies', 'wisesync')} initialOpen={false}>
                                        <TextControl
                                            label={__('Title', 'wisesync')}
                                            value={necessaryCookiesTitle}
                                            onChange={(value) => setAttributes({ necessaryCookiesTitle: value })}
                                        />
                                        <TextControl
                                            label={__('Description', 'wisesync')}
                                            value={necessaryCookiesDescription}
                                            onChange={(value) => setAttributes({ necessaryCookiesDescription: value })}
                                            multiline="p"
                                        />
                                        <p className="cookie-category-note">{__('Necessary cookies are always enabled and cannot be rejected.', 'wisesync')}</p>
                                    </PanelBody>
                                    
                                    {enabledCookieTypes.functional && (
                                        <PanelBody title={__('Functional Cookies', 'wisesync')} initialOpen={false}>
                                            <TextControl
                                                label={__('Title', 'wisesync')}
                                                value={functionalCookiesTitle}
                                                onChange={(value) => setAttributes({ functionalCookiesTitle: value })}
                                            />
                                            <TextControl
                                                label={__('Description', 'wisesync')}
                                                value={functionalCookiesDescription}
                                                onChange={(value) => setAttributes({ functionalCookiesDescription: value })}
                                                multiline="p"
                                            />
                                        </PanelBody>
                                    )}
                                    
                                    {enabledCookieTypes.analytical && (
                                        <PanelBody title={__('Analytical Cookies', 'wisesync')} initialOpen={false}>
                                            <TextControl
                                                label={__('Title', 'wisesync')}
                                                value={analyticalCookiesTitle}
                                                onChange={(value) => setAttributes({ analyticalCookiesTitle: value })}
                                            />
                                            <TextControl
                                                label={__('Description', 'wisesync')}
                                                value={analyticalCookiesDescription}
                                                onChange={(value) => setAttributes({ analyticalCookiesDescription: value })}
                                                multiline="p"
                                            />
                                        </PanelBody>
                                    )}
                                    
                                    {enabledCookieTypes.advertising && (
                                        <PanelBody title={__('Advertising Cookies', 'wisesync')} initialOpen={false}>
                                            <TextControl
                                                label={__('Title', 'wisesync')}
                                                value={advertisingCookiesTitle}
                                                onChange={(value) => setAttributes({ advertisingCookiesTitle: value })}
                                            />
                                            <TextControl
                                                label={__('Description', 'wisesync')}
                                                value={advertisingCookiesDescription}
                                                onChange={(value) => setAttributes({ advertisingCookiesDescription: value })}
                                                multiline="p"
                                            />
                                        </PanelBody>
                                    )}
                                    
                                    {enabledCookieTypes.tracking && (
                                        <PanelBody title={__('Tracking Cookies', 'wisesync')} initialOpen={false}>
                                            <TextControl
                                                label={__('Title', 'wisesync')}
                                                value={trackingCookiesTitle}
                                                onChange={(value) => setAttributes({ trackingCookiesTitle: value })}
                                            />
                                            <TextControl
                                                label={__('Description', 'wisesync')}
                                                value={trackingCookiesDescription}
                                                onChange={(value) => setAttributes({ trackingCookiesDescription: value })}
                                                multiline="p"
                                            />
                                        </PanelBody>
                                    )}
                                </>
                            );
                        }
                        
                        if (tab.name === 'appearance') {
                            return (
                                <>
                                    <PanelBody title={__('Layout Settings', 'wisesync')} initialOpen={true}>
                                        <SelectControl
                                            label={__('Banner Position', 'wisesync')}
                                            value={bannerPosition}
                                            options={bannerPositionOptions}
                                            onChange={(value) => setAttributes({ bannerPosition: value })}
                                        />
                                        <SelectControl
                                            label={__('Floating Button Position', 'wisesync')}
                                            value={floatingButtonPosition}
                                            options={floatingButtonPositionOptions}
                                            onChange={(value) => setAttributes({ floatingButtonPosition: value })}
                                        />
                                    </PanelBody>
                                    <PanelBody title={__('Colors', 'wisesync')} initialOpen={false}>
                                        <div className="cookie-banner-color-setting">
                                            <p>{__('Primary Color', 'wisesync')}</p>
                                            <ColorPicker
                                                color={primaryColor}
                                                onChangeComplete={(value) => setAttributes({ primaryColor: value.hex })}
                                                disableAlpha
                                            />
                                        </div>
                                        <div className="cookie-banner-color-setting">
                                            <p>{__('Text Color', 'wisesync')}</p>
                                            <ColorPicker
                                                color={textColor}
                                                onChangeComplete={(value) => setAttributes({ textColor: value.hex })}
                                                disableAlpha
                                            />
                                        </div>
                                        <div className="cookie-banner-color-setting">
                                            <p>{__('Background Color', 'wisesync')}</p>
                                            <ColorPicker
                                                color={backgroundColor}
                                                onChangeComplete={(value) => setAttributes({ backgroundColor: value.hex })}
                                                disableAlpha
                                            />
                                        </div>
                                    </PanelBody>
                                </>
                            );
                        }
                        
                        if (tab.name === 'advanced') {
                            return (
                                <>
                                    <PanelBody title={__('Cookie Settings', 'wisesync')} initialOpen={true}>
                                        <RangeControl
                                            label={__('Cookie Expiration (days)', 'wisesync')}
                                            value={cookieExpiration}
                                            onChange={(value) => setAttributes({ cookieExpiration: value })}
                                            min={1}
                                            max={730}
                                        />
                                        <SelectControl
                                            label={__('Consent Model', 'wisesync')}
                                            value={consentModel}
                                            options={consentModelOptions}
                                            onChange={(value) => setAttributes({ consentModel: value })}
                                            help={__('Select the consent model to use based on your region\'s legal requirements. This will be overridden by GeoIP detection if enabled.', 'wisesync')}
                                        />
                                        <p className="cookie-info-notice">
                                            {__('Note: The cookie expiration setting may be automatically adjusted based on the visitor\'s location to comply with local regulations.', 'wisesync')}
                                        </p>
                                    </PanelBody>
                                </>
                            );
                        }
                        
                        if (tab.name === 'restrictions') {
                            return (
                                <>
                                    <PanelBody title={__('Block Usage Restrictions', 'wisesync')} initialOpen={true}>
                                        <ToggleControl
                                            label={__('Show Only in Site Editor', 'wisesync')}
                                            checked={showOnlyInSiteEditor}
                                            onChange={(value) => setAttributes({ showOnlyInSiteEditor: value })}
                                            help={__('When enabled, this block will only be visible when added to a template in the Site Editor, not in individual posts or pages.', 'wisesync')}
                                        />
                                    </PanelBody>
                                </>
                            );
                        }
                        
                        if (tab.name === 'compliance') {
                            return (
                                <>
                                    <PanelBody title={__('Regional Compliance', 'wisesync')} initialOpen={true}>
                                        <p>
                                            {__('The banner will automatically adjust to comply with privacy regulations based on the visitor\'s location.', 'wisesync')}
                                        </p>
                                        <ToggleControl
                                            label={__('Show Do Not Sell Link', 'wisesync')}
                                            checked={showDoNotSellLink}
                                            onChange={(value) => setAttributes({ showDoNotSellLink: value })}
                                            help={__('Display a "Do Not Sell or Share My Personal Information" link (required for CCPA/CPRA compliance).', 'wisesync')}
                                        />
                                        
                                        {showDoNotSellLink && (
                                            <>
                                                <TextControl
                                                    label={__('Do Not Sell Link', 'wisesync')}
                                                    value={doNotSellLink}
                                                    onChange={(value) => setAttributes({ doNotSellLink: value })}
                                                    help={__('URL to your Do Not Sell or Share My Personal Information page', 'wisesync')}
                                                />
                                                <TextControl
                                                    label={__('Do Not Sell Text', 'wisesync')}
                                                    value={doNotSellText}
                                                    onChange={(value) => setAttributes({ doNotSellText: value })}
                                                />
                                            </>
                                        )}
                                        
                                        <p className="compliance-note">
                                            {__('Note: GeoIP detection will override some of these settings to ensure compliance with local laws.', 'wisesync')}
                                        </p>
                                    </PanelBody>
                                </>
                            );
                        }
                    }}
                </TabPanel>
            </InspectorControls>
            
            <div {...blockProps} className={`${blockProps.className || ''} ${showBanner ? '' : 'minimized-banner'}`}>
                {showBlockNotice ? (
                    <Notice 
                        status="warning"
                        isDismissible={false}
                        className="cookie-banner-notice-restriction"
                    >
                        {__('This Cookie Banner block is configured to only be shown when used in the Site Editor. It will not be visible on individual posts or pages.', 'wisesync')}
                    </Notice>
                ) : (
                    <Placeholder
                        icon="privacy"
                        label={__('Cookie Consent Banner', 'wisesync')}
                        instructions={__('Configure the cookie consent banner settings in the sidebar. This block will display a GDPR compliant cookie consent banner on the frontend of your site.', 'wisesync')}
                    >
                        <div className="cookie-banner-preview">
                            <div 
                                className={`cookie-banner-mock ${showBanner ? '' : 'minimized'}`}
                                style={{
                                    backgroundColor: backgroundColor,
                                    color: textColor,
                                    borderTop: `3px solid ${primaryColor}`
                                }}
                            >
                                {showBanner && (
                                    <>
                                        <div className="cookie-banner-mock-content">
                                            <h4 style={{ color: textColor }}>{bannerTitle}</h4>
                                            <p>{bannerDescription}</p>
                                            <div className="cookie-banner-mock-links">
                                                {privacyPolicyLink && (
                                                    <span className="privacy-link">
                                                        <a 
                                                            href="#" 
                                                            style={{ color: primaryColor }}
                                                        >
                                                            {privacyPolicyText}
                                                        </a>
                                                    </span>
                                                )}
                                                
                                                {privacyPolicyLink && termsLink && (
                                                    <span className="link-separator"> | </span>
                                                )}
                                                
                                                {termsLink && (
                                                    <span className="terms-link">
                                                        <a 
                                                            href="#" 
                                                            style={{ color: primaryColor }}
                                                        >
                                                            {termsText}
                                                        </a>
                                                    </span>
                                                )}
                                                
                                                {(privacyPolicyLink || termsLink) && showDoNotSellLink && (
                                                    <span className="link-separator"> | </span>
                                                )}
                                                
                                                {showDoNotSellLink && (
                                                    <span className="do-not-sell-link">
                                                        <a 
                                                            href="#" 
                                                            style={{ color: primaryColor }}
                                                        >
                                                            {doNotSellText}
                                                        </a>
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <div className="cookie-banner-mock-buttons">
                                            <button 
                                                className="cookie-action-button secondary"
                                                style={{
                                                    borderColor: primaryColor,
                                                    color: primaryColor
                                                }}
                                            >
                                                {customizeText}
                                            </button>
                                            <button 
                                                className="cookie-action-button secondary"
                                                style={{
                                                    borderColor: primaryColor,
                                                    color: primaryColor
                                                }}
                                            >
                                                {rejectAllText}
                                            </button>
                                            <button 
                                                className="cookie-action-button primary"
                                                style={{
                                                    backgroundColor: primaryColor,
                                                    borderColor: primaryColor
                                                }}
                                            >
                                                {acceptAllText}
                                            </button>
                                        </div>
                                    </>
                                )}
                                {!showBanner && (
                                    <div className="minimized-banner-indicator" style={{ backgroundColor: primaryColor }}>
                                        <span>{__('Banner minimized. Toggle visibility in block settings.', 'wisesync')}</span>
                                    </div>
                                )}
                            </div>
                            <div 
                                className={`floating-button-mock ${floatingButtonPosition}`}
                                style={{
                                    backgroundColor: primaryColor
                                }}
                            >
                                <span className="dashicons dashicons-privacy"></span>
                            </div>
                        </div>
                        <p className="cookie-banner-notice">
                            {showBanner 
                                ? __('This block has a preview in the editor but will adapt to the visitor\'s location on the frontend.', 'wisesync')
                                : __('Banner is minimized. Users can still access settings via the floating button.', 'wisesync')
                            }
                        </p>
                        <p>
                            {__('Learn more about ', 'wisesync')}
                            <ExternalLink href="https://gdpr.eu/cookies/">
                                {__('GDPR Cookie Compliance', 'wisesync')}
                            </ExternalLink>
                        </p>
                    </Placeholder>
                )}
            </div>
        </>
    );
}