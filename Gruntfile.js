module.exports  = function(grunt){

    var plugin_name = 'lz_theme_options',
        base_dir = '',
        build_dir = '../../../../../PACKAGES/PLUGINS/'+plugin_name+'/'+plugin_name,
        build_to_zip = '../../../../../PACKAGES/PLUGINS/'+plugin_name,
        build_zip_dir = '../../../../../PACKAGES/PLUGINS/ZIPS/';

        // configure the tasks
        grunt.initConfig({
            clean: {
                src: {
                    options: {
                        force: true
                    },
                    src: [ build_to_zip ]
                },
                build: {
                    options: {
                        force: true
                    },
                    src: [ build_dir+'/node_modules' ]
                }
            },
            copy: {
                src: {
                    cwd: '',
                    src: [ '**', '!node_modules' ],
                    dest: build_dir,
                    expand: true,
                    options: {
                        processContentExclude: ['**/*.{png,gif,jpg,ico,eot,svg,ttf,woff}']
                    }
                }
            },
            cssmin: {
                build: {
                    files: [{
                        cwd: build_dir+'/assets/css',
                        src: ['*.css', '!*.min.css'],
                        dest: build_dir+'/assets/css',
                        expand: true
                    }]
                }
            },
            compress: {
                build: {
                    options: {
                        archive: build_zip_dir+'/'+getName()
                    },
                    files: [
                        {
                            cwd: build_to_zip,
                            src: ['**/*'],
                            expand: true
                        }
                    ]
                }
            }
        });

    require('load-grunt-tasks')(grunt);

    grunt.registerTask(
        'build',
        'Compiles all of the assets and copies the files to the build directory.',
        [ 'clean:src', 'copy', 'cssmin', 'clean:build' ] //, 'imagemin'
    );

    function getName(){
        var date = new Date();
        return plugin_name+'-'+date.getFullYear()+date.getMonth()+date.getDate()+date.getHours()+date.getMinutes()+'.zip';
    }
};