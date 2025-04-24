# Contributing to WiseSync

Thank you for considering contributing to WiseSync! This document outlines the guidelines for contributing to our WordPress plugin.

## How to Contribute

There are many ways to contribute to WiseSync:

### Reporting Bugs

Before submitting a bug report:
1. Check the [existing issues](https://github.com/shubkb07/shubkb.com/issues) to avoid duplicates
2. Use our issue template when creating a new issue
3. Include detailed steps to reproduce the bug
4. Add screenshots or screen recordings if possible
5. Note your WordPress version, PHP version, and browser

### Suggesting Features

We welcome feature requests that align with the plugin's purpose:
1. Clearly describe the feature and its benefits
2. Explain how it would work from a user perspective
3. Consider edge cases and potential impact on existing functionality

### Pull Requests

For code contributions:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Follow WordPress [coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
4. Add appropriate unit tests
5. Commit your changes with meaningful messages
6. Push to your branch (`git push origin feature/amazing-feature`)
7. Open a pull request with a detailed description

## Development Setup

1. Set up a local WordPress development environment (we recommend [LocalWP](https://localwp.com/))
2. Clone the repository to your `/wp-content/plugins/` directory
3. Run `composer install` to install dependencies
4. Activate the plugin in your WordPress admin

## WordPress Coding Standards

We follow the WordPress coding standards:

- PHP: [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- CSS: [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- JavaScript: [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- HTML: [WordPress HTML Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/html/)

## Testing

- Run `composer run test` for PHP unit tests
- Run `npm run test` for JavaScript tests
- Ensure all tests pass before submitting a pull request

## Documentation

- Update the README.md with details of changes to the interface
- Update documentation in the `/docs` folder as needed
- Add inline comments for complex functionality

## Review Process

1. A maintainer will review your pull request
2. Feedback might request changes or clarifications
3. Once approved, your contribution will be merged

## Recognition

Contributors will be acknowledged in:
- The plugin's README.md
- Release notes when your contribution is included
- Plugin credits in the WordPress admin interface

Thank you for contributing to WiseSync!
