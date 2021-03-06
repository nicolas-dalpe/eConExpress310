"use strict";

module.exports = function (grunt) {
    // Running local with
    // First time npm install
    // nvm use 8.9
    // grunt --moodledir=/Users/mail/OPENSOURCE/moodle-370/

    // We need to include the core Moodle grunt file too, otherwise we can't run tasks like "amd".
    try {
        if(require.resolve("grunt-load-gruntfile")){
            require("grunt-load-gruntfile")(grunt);
        }

        var MOODLE_DIR = grunt.option('moodledir') || '../../';
        grunt.loadGruntfile(MOODLE_DIR + "Gruntfile.js");

    }catch(ex){
        // Only used when running localy for compiling.
    }

    //Load all grunt tasks.
    grunt.loadNpmTasks("grunt-contrib-less");
    grunt.loadNpmTasks("grunt-contrib-watch");
    grunt.loadNpmTasks("grunt-contrib-clean");
    grunt.loadNpmTasks("grunt-fixindent");

    grunt.initConfig({
        babel: {
            options: {
                sourceMap: true,
                presets: ['@babel/preset-env']
            },
            dist: {
                files: {
                }
            }
        },
        watch: {
            // If any .less file changes in directory "less" then run the "less" task.
            less: {
                files: "less/*.less",
                tasks: ["less"]
            },
            fixindent: {
                files: "less/*.less",
                tasks: ["fixindent"]
            },
            amd: {
                files: "amd/src/*.js",
                tasks: ["amd"]
            }
        },
        stylelint: {
            css: {},
            scss: {},
            less: {},
        },
        less: {
            // Production config is also available.
            development: {
                options: {
                    // Specifies directories to scan for @import directives when parsing.
                    // Default value is the directory of the source, which is probably what you want.
                    paths: ["less/"],
                    compress: false
                },
                files: {
                    "styles.css": "less/styles.less"
                }
            },
        },
        fixindent: {
            stylesheets: {
                src: [
                    'styles.css'
                ],
                dest: 'styles.css',
                options: {
                    style: 'space',
                    size: 4
                }
            }
        },
        eslint: {
            amd: {src: "amd/src"}
        },
        uglify: {
            amd: {
                files: {
                    "amd/build/settings.min.js": ["amd/src/settings.js"],
                    "amd/build/commander.min.js": ["amd/src/commander.js"],
                },
                options: {report: 'none'}
            }
        }
    });

    // The default task (running "grunt" in console).
    grunt.registerTask("default", ["less", "fixindent", "eslint", "uglify"]);
};