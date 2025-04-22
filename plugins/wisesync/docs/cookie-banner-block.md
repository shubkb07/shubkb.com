# Cookie Banner Block Documentation

The Cookie Banner Block is a versatile GDPR-compliant cookie consent solution for WordPress. It provides a fully customizable cookie consent banner with multiple configuration options.

## Features

- **GDPR Compliant**: Fully compliant with GDPR requirements for cookie consent
- **Cookie Categories**: Supports necessary, functional, analytics, and advertising cookie categories
- **Customizable UI**: Configure colors, text, and positions to match your site design
- **Floating Settings Button**: Allows visitors to access cookie preferences anytime
- **Cookie Preferences Management**: Users can customize which cookie categories they accept
- **Developer API**: Integrate with your own scripts via JavaScript callbacks

## How to Use

### Adding the Cookie Banner to Your Site

1. Edit any page or post where you want to add the banner
2. Click the + button to add a new block
3. Search for "Cookie Banner" and select it
4. Configure the block settings in the sidebar

### Configuration Options

#### General Settings Tab

- **Banner Title**: The title displayed at the top of the cookie banner
- **Banner Description**: The main text explaining your cookie usage
- **Privacy Policy Link**: URL to your privacy policy page
- **Privacy Policy Text**: The text that links to your privacy policy
- **Button Labels**: Customize text for all action buttons

#### Cookie Categories Tab

Configure the title and description for each cookie category:

- **Necessary Cookies**: Essential cookies that can't be disabled
- **Functional Cookies**: Cookies for enhanced functionality
- **Analytics Cookies**: Cookies for website analytics
- **Advertising Cookies**: Cookies for advertising purposes

#### Appearance Tab

- **Banner Position**: Choose between bottom, top, or center positioning
- **Floating Button Position**: Choose where the settings button appears
- **Colors**: Customize primary, text, and background colors

#### Advanced Tab

- **Cookie Expiration**: Set how many days until consent expires

## Developer Integration

### JavaScript API

The Cookie Banner Block provides an API to integrate with your own scripts:

```javascript
// Register a callback to run when a specific cookie category is accepted
window.registerCookieCallback('analytical', function() {
    // Your analytics code here
    console.log('Analytics cookies accepted');
});

// Available categories: necessary, functional, analytical, advertising
```

### Consent Status Event

You can listen to consent changes:

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
    "advertising": false
}
```

## Best Practices

1. **Be Transparent**: Clearly explain what cookies you use and why
2. **Keep it Simple**: Use clear, concise language in your descriptions
3. **Respect User Choices**: Only load scripts when the user has given consent
4. **Update Regularly**: Keep your cookie policy up to date
5. **Link to Privacy Policy**: Always include a link to your detailed privacy policy

## Compliance Notes

This plugin provides the technical implementation for cookie consent, but proper compliance also requires:

- An up-to-date Privacy Policy
- A Cookie Policy detailing all cookies used
- Proper implementation of script loading based on consent

Always consult a legal professional to ensure full compliance with privacy regulations in your jurisdiction.