# Security Policy for WiseSync

## Supported Versions

WiseSync follows WordPress's versioning policy. We actively maintain and provide security updates for the latest version of our plugin.

| Version | Supported          |
| ------- | ------------------ |
| 1.0     | :white_check_mark: |
| < 1.0   | :x:               |

## Reporting a Vulnerability

We take the security of WiseSync plugin seriously. If you believe you've found a security vulnerability, please follow these steps:

1. **Do not** disclose the vulnerability publicly until it has been addressed by our team.
2. Email your findings to `security.wisesync@shubkb.com` or `security.sync@shubkb.com` with the subject line "WiseSync Security Vulnerability".
3. Include detailed steps to reproduce the issue, and if possible, a proof of concept.
4. Allow us a reasonable time to address the issue before any public disclosure.

Our security team will acknowledge receipt of your report within 48 hours and will send a more detailed response within 72 hours indicating next steps.

## Security Best Practices Used in WiseSync

- All user inputs are sanitized and validated
- Database queries are prepared using WordPress's `$wpdb->prepare()`
- Capability checks for all administrative actions
- Regular security audits and code reviews
- Adherence to WordPress coding standards

## Third-Party Libraries

WiseSync uses several third-party libraries, which are regularly monitored for security updates. We maintain a comprehensive dependency inventory and promptly update when security patches are released.

## Secure Development Process

Our development process includes:

1. Code review by at least one team member
2. Automated testing prior to deployment
3. Regular security training for developers
4. Weekly dependency vulnerability scan

Thank you for helping keep WiseSync and its users safe!
