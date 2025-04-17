# Cookie Banner Block Documentation

The Cookie Banner Block is a versatile GDPR-compliant cookie consent solution for WordPress that provides advanced control over user cookie preferences and service integrations.

## Features

- **GDPR Compliant**: Fully compliant with GDPR requirements for cookie consent
- **Cookie Categories**: Supports necessary, functional, analytical, advertising, and tracking cookie categories
- **Customizable UI**: Configure colors, text, and positions to match your site design
- **Floating Settings Button**: Allows visitors to access cookie preferences anytime
- **Cookie Preferences Management**: Users can customize which cookie categories they accept
- **Site Editor Integration**: Option to only display the banner when used in the Site Editor
- **Policy Version Tracking**: Prompts users to re-consent when privacy policy or terms change
- **Service Registration API**: Register third-party services like Google Analytics or Facebook Pixel
- **Developer API**: Integrate with your own scripts via JavaScript callbacks
- **Terms & Privacy Policy Integration**: Link to your legal documents and track version changes

## How to Use

### Adding the Cookie Banner to Your Site

1. Edit any page or post where you want to add the banner, or preferably use the Site Editor
2. Click the + button to add a new block
3. Search for "Cookie Banner" and select it
4. Configure the block settings in the sidebar

### Configuration Options

#### General Settings Tab

- **Banner Title**: The title displayed at the top of the cookie banner
- **Banner Description**: The main text explaining your cookie usage
- **Privacy Policy Link**: URL to your privacy policy page
- **Privacy Policy Text**: The text that links to your privacy policy
- **Privacy Policy Version**: Set a version identifier to track policy changes
- **Terms Link**: URL to your terms of service page
- **Terms Text**: The text that links to your terms of service
- **Terms Version**: Set a version identifier to track terms changes
- **Check Version Changes**: When enabled, users will be asked to re-consent when your policies change
- **Button Labels**: Customize text for all action buttons

#### Cookie Categories Tab

Configure which cookie categories are enabled and customize their descriptions:

- **Necessary Cookies**: Essential cookies that can't be disabled
- **Functional Cookies**: Cookies for enhanced functionality
- **Analytics Cookies**: Cookies for website analytics
- **Advertising Cookies**: Cookies for advertising purposes
- **Tracking Cookies**: Cookies used for user tracking and behavior analysis

#### Appearance Tab

- **Banner Position**: Choose between bottom, top, or center positioning
- **Floating Button Position**: Choose where the settings button appears
- **Colors**: Customize primary, text, and background colors

#### Advanced Tab

- **Cookie Expiration**: Set how many days until consent expires

#### Restrictions Tab

- **Show Only in Site Editor**: When enabled, the block will only be visible when added to a template in the Site Editor

## Developer Integration

### PHP Functions

```php
// Check if a specific cookie type has consent
if (is_sync_cookie_permission_to('analytical')) {
    // Load analytics code
}

// Register a service with the cookie consent manager
register_cookie_service('ga4', 'Google Analytics', 'analytical');
```

### JavaScript API

```javascript
// Check if a specific cookie permission is granted
if (window.is_sync_cookie_permission_to('advertising')) {
    // Initialize ad scripts
}

// Register a third-party service
window.register_cookie_service('facebook_pixel', 'Facebook Pixel', 'advertising');

// Register a callback to run when a specific cookie category is accepted
window.registerCookieCallback('analytical', function() {
    // Initialize analytics code
    console.log('Analytics cookies accepted');
});

// Listen to consent changes
document.addEventListener('cookieConsentUpdated', function(event) {
    const consent = event.detail.consent;
    console.log('Cookie consent updated:', consent);
});

// Listen to when a specific service is activated
document.addEventListener('cookieServiceActivated', function(event) {
    const service = event.detail;
    console.log('Service activated:', service.name);
});
```

### Consent Status Event

```javascript
document.addEventListener('cookieConsentUpdated', function(event) {
    const consent = event.detail.consent;
    console.log('Cookie consent updated:', consent);
    
    // Example: checking if analytical cookies are accepted
    if (consent.analytical) {
        // Initialize analytics
    }
});
```

### Cookie Structure

The plugin stores consent in a cookie named `wisesync_cookie_consent` with a JSON value:

```json
{
    "necessary": true,
    "functional": true,
    "analytical": false,
    "advertising": false,
    "tracking": false
}
```

Policy versions are stored in a separate cookie named `wisesync_policy_versions`:

```json
{
    "privacy": "2023-04-15",
    "terms": "2023-04-15"
}
```

## Best Practices

1. **Be Transparent**: Clearly explain what cookies you use and why
2. **Keep it Simple**: Use clear, concise language in your descriptions
3. **Respect User Choices**: Only load scripts when the user has given consent
4. **Update Versions**: When you update your privacy policy or terms, update the version identifiers
5. **Site Editor Usage**: For best results, add the banner to a site-wide template in the Site Editor
6. **Service Registration**: Register third-party services properly to ensure they only run with consent

## Compliance Notes

This plugin provides the technical implementation for cookie consent, but proper compliance also requires:

- An up-to-date Privacy Policy
- A Cookie Policy detailing all cookies used
- A Terms of Service document
- Proper implementation of script loading based on consent

Always consult a legal professional to ensure full compliance with privacy regulations in your jurisdiction.