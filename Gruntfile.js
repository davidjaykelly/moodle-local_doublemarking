/**
 * Grunt configuration for the double marking plugin.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

"use strict";

module.exports = function(grunt) {
    var path = require('path'),
        amdDir = 'amd/src/',
        targetDir = 'amd/build/',
        cwd = process.env.PWD || process.cwd();

    // Windows users can't run grunt in a subdirectory, so they need to set 
    // the root directory as the current directory.
    if (process.platform === 'win32') {
        cwd = path.resolve(process.env.MOODLE_DIR || '../../..');
    }

    // Load all grunt tasks.
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-eslint');

    // Project configuration.
    grunt.initConfig({
        eslint: {
            // Check JS files for errors.
            amd: {
                src: ['amd/src/**/*.js']
            }
        },
        watch: {
            // Watch for changes and recompile.
            amd: {
                files: ['amd/src/**/*.js'],
                tasks: ['eslint:amd', 'uglify:amd']
            }
        },
        clean: {
            // Clean up build directory.
            amd: {
                src: [targetDir]
            }
        },
        uglify: {
            // Minify JS files.
            amd: {
                files: [{
                    expand: true,
                    cwd: amdDir,
                    src: ['**/*.js'],
                    dest: targetDir,
                    ext: '.min.js'
                }],
                options: {
                    report: 'min',
                    sourceMap: true
                }
            }
        }
    });

    // Register tasks.
    grunt.registerTask('default', ['amd']);
    grunt.registerTask('amd', ['eslint:amd', 'clean:amd', 'uglify:amd']);
};

