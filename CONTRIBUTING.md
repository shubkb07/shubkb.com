# Contributing to Our WordPress Projects

Thank you for your interest in contributing to our WordPress development projects! This document provides guidelines for contributing to both the WiseSync plugin and PaperSync theme.

## General Contribution Guidelines

### Code of Conduct

All contributors are expected to adhere to our [Code of Conduct](CODE_OF_CONDUCT.md). Please read it before participating.

### Getting Started

1. **Fork the repository** you want to contribute to
2. **Clone your fork** to your local machine
3. **Create a new branch** for your contribution (`git checkout -b feature/your-feature-name`)
4. **Make your changes** following our coding standards
5. **Test thoroughly** across different environments
6. **Commit your changes** with clear messages
7. **Push to your fork** and submit a pull request

## Project-Specific Guidelines

### WiseSync Plugin

For contributing to our WordPress plugin:

- Follow the [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- Ensure backward compatibility when possible
- Document hooks, filters, and functions using PHPDoc standards
- Check [WiseSync specific guidelines](plugins/wisesync/CONTRIBUTING.md) for more details

### PaperSync Theme

For contributing to our WordPress theme:

- Follow the [WordPress Theme Developer Handbook](https://developer.wordpress.org/themes/)
- Maintain responsive design principles
- Ensure accessibility compliance
- Check [PaperSync specific guidelines](themes/papersync/CONTRIBUTING.md) for more details

## Development Environment

### Recommended Setup

- Local WordPress development environment ([LocalWP](https://localwp.com/), [XAMPP](https://www.apachefriends.org/), etc.)
- Node.js and npm for frontend asset building
- Composer for PHP dependency management
- Git for version control

### Installation

```bash
# Clone the repository
git clone https://github.com/shubkb07/shubkb.com.git

# Navigate to project directory
cd shubkb.com/wp-content

# Install dependencies
composer install
npm install

# Build assets
npm run build
```

## Coding Standards

We follow WordPress coding standards across all our projects:

- [PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- [CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- [HTML Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/html/)

### Code Quality Tools

We use the following tools to maintain code quality:

- PHP_CodeSniffer with WordPress Coding Standards
- ESLint for JavaScript
- Stylelint for CSS/SCSS
- PHPMD for PHP mess detection

## Pull Request Process

1. Update documentation to reflect changes
2. Add or update unit tests as necessary
3. Ensure your code passes all automated tests
4. Get at least one code review from a maintainer
5. Make requested changes if any
6. Once approved, your PR will be merged

## Documentation

Good documentation is crucial for our projects:

- Update README.md files when needed
- Document all functions, classes, hooks, and filters
- Create or update user documentation in the `/docs` folder
- Keep inline comments clear and helpful

## Reporting Bugs

When reporting bugs:

1. Use the GitHub issue tracker for the specific project
2. Check if the issue has already been reported
3. Include detailed steps to reproduce the bug
4. Mention your WordPress version, browser, and other relevant details
5. Add screenshots or recordings if possible

## Feature Requests

We welcome feature requests:

1. Clearly describe the feature and its benefits
2. Explain how it would benefit users
3. Consider edge cases and potential implementation challenges
4. Be open to discussion and feedback

## Thank You

Your contributions help make our WordPress projects better for everyone. We appreciate your time and effort in improving our code!
