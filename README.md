# Moodle Double Marking Plugin

This plugin provides double marking functionality for Moodle assignments.

## Features

- Assign multiple markers to student submissions
- Support for blind and double-blind marking
- Grade difference detection and warnings
- Ratification workflow for resolving grade differences
- Integration with Moodle's grading interface

## Requirements

- Moodle 4.3 or higher
- mod_assign plugin

## Installation

1. Clone this repository or download it as a zip file
2. Extract the contents to the `local/doublemarking` directory in your Moodle installation
3. Visit your Moodle site as an administrator to complete the installation

## Development

### JavaScript Building

This plugin uses AMD modules for JavaScript. To build the JavaScript files:

1. Install Node.js and npm
2. Run `npm install` in the plugin directory to install dependencies
3. Run `npm run build` to build the JavaScript files

#### Available npm scripts

- `npm start` - Same as `npm run build`
- `npm run build` - Build AMD modules
- `npm run watch` - Watch for changes and rebuild
- `npm run lint` - Run ESLint to check JavaScript files

## Testing

To run the unit tests:

```bash
cd /path/to/moodle
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --testsuite local_doublemarking_testsuite
```

## License

This plugin is licensed under the GNU GPL v3 or later. See the LICENSE file for details.

## Credits

Developed by Your Name <your@email.com>

# Moodle Double Marking Plugin

⚠️ **WORK IN PROGRESS** - This plugin is currently in early development and not ready for production use. Features may be incomplete or change significantly.

A comprehensive double marking solution for Moodle assignments that supports blind marking, grade ratification, and marker allocation workflows.

## Features

- **First and Second Marking**
  - Independent marker allocation
  - Separate feedback and grades from each marker
  - Configurable grade comparison thresholds

- **Blind Marking Options**
  - Standard blind marking (markers can see each other)
  - Double-blind marking (markers cannot see each other's grades)
  - Optional student anonymity

- **Grade Ratification**
  - Third marker ratification role
  - Automated notification of grade discrepancies
  - Final grade confirmation workflow

- **Assignment Integration**
  - Seamless integration with Moodle assignments
  - Bulk marker allocation tools
  - Compatible with existing assignment workflows

## Requirements

- Moodle 4.3 or higher
- PHP 7.4 or higher
- Assignment module enabled

## Installation

1. Download the plugin:
   ```bash
   cd /path/to/moodle/local
   git clone https://github.com/yourusername/moodle-local_doublemarking.git doublemarking
   ```

2. Install via Moodle UI:
   - Log in as admin and go to Site administration
   - Navigate to Notifications
   - Follow the installation prompts

3. Or install via CLI:
   ```bash
   php admin/cli/upgrade.php
   ```

## Configuration

1. Navigate to Site administration → Plugins → Local plugins → Double Marking

2. Configure default settings:
   - Grade difference threshold
   - Default blind marking setting
   - Mark visibility options

3. Set up capabilities:
   - local/doublemarking:mark1 (First marker)
   - local/doublemarking:mark2 (Second marker)
   - local/doublemarking:ratify (Grade ratifier)
   - local/doublemarking:allocate (Marker allocation)
   - local/doublemarking:manage (Plugin management)

## Usage

### Setting Up Double Marking

1. Create or edit an assignment
2. Enable double marking in assignment settings
3. Configure blind marking options
4. Save and return to course

### Allocating Markers

1. Navigate to assignment settings
2. Select "Allocate markers"
3. Choose first and second markers
4. Optionally assign ratifiers
5. Use bulk allocation tools for multiple students

### Marking Process

1. First marker completes grading
2. Second marker independently grades
3. System compares grades and notifies if threshold exceeded
4. Ratifier reviews and confirms final grade

## Development

See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed development guidelines.

### Quick Start for Developers

1. Set up development environment:
   ```bash
   git clone https://github.com/yourusername/moodle-local_doublemarking.git
   cd moodle-local_doublemarking
   composer install
   ```

2. Run tests:
   ```bash
   php admin/tool/phpunit/cli/init.php
   vendor/bin/phpunit
   ```

### Directory Structure

```
local/doublemarking/
├── classes/
│   ├── hook/mod_assign.php
│   ├── privacy/provider.php
│   └── external/
├── db/
│   ├── access.php
│   ├── hooks.php
│   └── install.xml
├── lang/en/
├── tests/
├── lib.php
└── version.php
```

## Support

- Report bugs via [GitHub Issues](https://github.com/yourusername/moodle-local_doublemarking/issues)
- Feature requests and discussions in [GitHub Discussions](https://github.com/yourusername/moodle-local_doublemarking/discussions)

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

## Authors

- Your Name (@yourusername)

## Changelog

### [0.1.0] - 2025-05-06
- Initial release
- Basic double marking functionality
- Blind marking support
- Grade ratification workflow
- Assignment module integration
