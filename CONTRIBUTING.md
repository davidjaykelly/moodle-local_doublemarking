# Contributing to Double Marking Plugin

Thank you for your interest in contributing to the Double Marking plugin for Moodle. This document provides guidelines and instructions for contributing to this project.

## Plugin Overview

The Double Marking plugin enables sophisticated assignment marking workflows with the following features:
- First and Second marking allocation
- Blind and Double-blind marking options
- Grade ratification process
- Integration with Moodle's Assignment module

## Development Setup

1. Clone the plugin into your Moodle installation:
   ```bash
   cd /path/to/moodle/local
   git clone [repository-url] doublemarking
   ```

2. Install dependencies:
   ```bash
   cd doublemarking
   composer install
   ```

3. Install the plugin through Moodle's admin interface or CLI:
   ```bash
   php admin/cli/upgrade.php
   ```

## Code Structure

- `classes/`
  - `hook/` - Assignment module integration hooks
  - `privacy/` - GDPR compliance implementation
  - `external/` - Web service endpoints (if needed)
- `db/`
  - `install.xml` - Database schema
  - `access.php` - Capability definitions
  - `hooks.php` - Hook registrations
- `lang/en/` - Language strings
- `tests/` - PHPUnit tests
- `amd/` - JavaScript modules

## Development Guidelines

1. **Coding Standards**
   - Follow [Moodle Coding Style](https://docs.moodle.org/dev/Coding_style)
   - Use PHP_CodeSniffer with Moodle standards
   - Run `grunt` for JavaScript linting

2. **Testing**
   - Write PHPUnit tests for new features
   - Include Behat tests for UI interactions
   - Test with different Moodle versions (4.3+)

3. **Database Changes**
   - Always update install.xml using XMLDB editor
   - Provide upgrade.php steps for schema changes

4. **Privacy**
   - Implement privacy API methods
   - Document data storage and processing

5. **Accessibility**
   - Follow WCAG 2.1 Level AA guidelines
   - Test with screen readers
   - Provide appropriate ARIA attributes

## Pull Request Process

1. Create a feature branch from main
2. Write clear commit messages
3. Include tests for new features
4. Update documentation
5. Submit PR with description of changes

## Bug Reports

Please include:
- Moodle version
- Plugin version
- Steps to reproduce
- Expected vs actual behavior
- Error messages and logs

## Feature Requests

Please provide:
- Clear use case description
- Expected behavior
- Context of usage

## License

This plugin is licensed under GNU GPL v3. All contributions must be compatible with this license.

