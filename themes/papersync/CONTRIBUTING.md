# Contributing to PaperSync Theme

Thank you for your interest in contributing to the PaperSync WordPress theme! This document provides guidelines and instructions for contributing.

## Ways to Contribute

There are several ways you can contribute to PaperSync:

### Reporting Bugs

Before submitting a bug report:
1. Check if the issue has already been reported in our [issues list](https://github.com/shubkb07/shubkb.com/issues)
2. Make sure you're using the latest version of the theme
3. Include the following information in your report:
   - Steps to reproduce the bug
   - Expected behavior vs actual behavior
   - Screenshots if applicable
   - WordPress version and browser details
   - List of active plugins that might be relevant

### Feature Suggestions

We welcome ideas to improve PaperSync:
1. Check existing feature requests to avoid duplicates
2. Describe your suggestion in detail
3. Explain how it benefits theme users
4. Provide mockups or designs if applicable

### Code Contributions

If you'd like to contribute code:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-new-feature`)
3. Follow the WordPress [theme coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
4. Test your changes across multiple browsers and devices
5. Commit changes with clear messages (`git commit -am 'Add feature: description'`)
6. Push to your branch (`git push origin feature/my-new-feature`)
7. Submit a pull request

## Development Environment Setup

1. Set up a local WordPress development environment
2. Clone the repository into your WordPress themes directory
3. Install development dependencies:
   ```
   npm install
   composer install
   ```
4. Run the build process:
   ```
   npm run build
   ```

## Theme Development Standards

### WordPress Theme Standards
- Follow the [WordPress Theme Handbook](https://developer.wordpress.org/themes/)
- Ensure compatibility with the latest WordPress version
- Support internationalization (i18n) and localization (l10n)

### CSS/SCSS Guidelines
- Use the BEM (Block Element Modifier) methodology
- Organize SCSS files according to component structure
- Maintain responsive design principles

### JavaScript Guidelines
- Use vanilla JavaScript or jQuery as appropriate
- Document complex functions and components
- Support accessibility standards

### Accessibility
- Follow WCAG 2.1 AA standards
- Test using screen readers
- Ensure keyboard navigation works properly

## Pull Request Process

1. Update the README.md with details of changes to the interface
2. Update documentation in the theme's `docs/` directory 
3. The version number will be updated according to [SemVer](http://semver.org/)
4. Your PR will be merged once approved by a maintainer

## Translation Contributions

We welcome translations:
1. Use the .pot file in the languages directory
2. Submit .po and .mo files for your language
3. Ensure translations are accurate and culturally appropriate

## Design Contributions

For design improvements:
1. Submit mockups in common design formats (.psd, .sketch, .fig)
2. Explain your design rationale
3. Consider different device sizes and viewports

Thank you for helping make PaperSync better!
