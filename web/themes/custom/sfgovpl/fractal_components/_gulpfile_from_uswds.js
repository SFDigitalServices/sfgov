'use strict';

const gulp = require('gulp');
const sass = require('gulp-sass');
const bourbon = require('bourbon');
const neat = require("node-neat").includePaths;
const livereload = require('gulp-livereload');
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const combineMq = require('gulp-combine-mq');
const imagemin = require('gulp-imagemin');
const sassGlob = require('gulp-sass-glob');
const concat = require("gulp-concat");
const notify = require("gulp-notify");
const path = require('path');

require('./config/gulp/sass');
require('./config/gulp/javascript'); // TODO: testing javascript bundles from USWDS.
require('./config/gulp/fonts');
require('./config/gulp/images');

var paths = {
    // sass: './sass/**/*.scss',
    sass: './styleguide-sfgov-src/stylesheets/**/*.scss',
    templates: './templates/**/*.twig',
    js: '.styleguide-sfgov-src/js/**/*.js',
    styleguide: 'styleguide'
};

// Error notifications with notify
// Shows a banner on macOs
var reportError = function(error) {
  notify({
    title: 'Gulp Task Error',
    message: 'Check the console.'
  }).write(error);
  console.log(error.toString());
  this.emit('end');
};

/*
 * Configure a Fractal instance.
 *
 * This configuration could also be done in a separate file, provided that this file
 * then imported the configured fractal instance from it to work with in your Gulp tasks.
 * i.e. const fractal = require('./my-fractal-config-file');
 */

const fractal = require('@frctl/fractal').create();
const mandelbrot = require('@frctl/mandelbrot');
const twigAdapter = require('@frctl/twig');

// TODO: integrate a customized theme:
// var myCustomisedTheme = mandelbrot({
//     panels: ["notes", "html", "view", "context", "resources", "info"],
//     skin: "black",
//     static: {
//         "mount": "theme",
//     }
// });

// Add an additional static assets path
// @see https://github.com/frctl/fractal/issues/122
// TODO: integrate a customized theme:
// const dir = path.join(__dirname, 'images');
// const staticImages = 'images';
// myCustomisedTheme.addStatic(dir, staticImages);

// fractal.web.theme(myCustomisedTheme); // tell Fractal to use the configured theme by default
fractal.set('project.title', 'SFGOV Styleguide'); // title for the project
fractal.web.set('builder.dest', 'styleguide'); // destination for the static export
// fractal.web.set('static.path', `${__dirname}/css`);
fractal.web.set('static.path', `${__dirname}/dist`); // TODO: override USWDS.
fractal.web.set('server.sync', true); // browsersync
// fractal.docs.set('path', `${__dirname}/styleguide-src/docs`); // location of the documentation directory.
// fractal.docs.set('path', `${__dirname}/uswds/docs`); // TODO: override USWDS and set basic documentation.
// fractal.docs.set('ext', '.hbs');
// fractal.components.set('path', `${__dirname}/styleguide-src/components`); // location of the component directory.
fractal.components.set('path', `${__dirname}/styleguide-sfgov-src/components`); // TODO: overriden for USWDS.
fractal.components.set('default.preview', '@uswds'); // let Fractal know that this preview layout should be used as the default layout for our components
fractal.components.set('default.status', 'wip'); // set default components status to work in progress. This has to be overridden in component.config.js files

// any other configuration or customisation here

const logger = fractal.cli.console; // keep a reference to the fractal CLI console utility

fractal.components.engine(twigAdapter);
fractal.components.set('ext', '.twig');

// Minify jpg, png, gif, svg
// gulp.task('images', () =>
//   gulp.src('styleguide-sfgov-src/img/**/*')
//     .pipe(imagemin())
//     .pipe(gulp.dest('dist/images'))
// );

// Sass compilation
gulp.task('sass', function () {
  gulp.src(paths.sass)
    .pipe(sassGlob())
    .pipe(sass({
      errLogToConsole: false,
      sourceComments: true,
      outputStyle: 'expanded',
      precision: 3,
      includePaths: [].concat(
        bourbon.includePaths,
        neat,
        'node_modules/mappy-breakpoints'
      )
    }))
    .on('error', reportError)
    .pipe(gulp.dest('./dist/css'))
    .pipe(livereload());
});

// Sass production build
gulp.task('sass:build', function () {
  var processors = [
    autoprefixer({browsers: ['last 2 versions']}),
  ];
  return gulp.src(paths.sass)
    .pipe(sassGlob())
    .pipe(sass({
      errLogToConsole: true,
      outputStyle: 'compressed',
      precision: 3,
      includePaths: [].concat(
        bourbon.includePaths,
        neat,
        'node_modules/mappy-breakpoints'
      )
    }))
    .pipe(combineMq({
      beautify: false // false will inline css
    }))
    .pipe(postcss(processors))
    .on('error', reportError)
    .pipe(gulp.dest('./dist/css'))
});

/*
 * Start the Fractal server
 *
 * In this example we are passing the option 'sync: true' which means that it will
 * use BrowserSync to watch for changes to the filesystem and refresh the browser automatically.
 * Obviously this is completely optional!
 *
 * This task will also log any errors to the console.
 */

gulp.task('fractal:start', function(){
    const server = fractal.web.server({
        sync: true
    });
    server.on('error', err => logger.error(err.message));
    return server.start().then(() => {
        logger.success(`Fractal server is now running at ${server.url}`);
    });
});

/*
 * Run a static export of the project web UI.
 *
 * This task will report on progress using the 'progress' event emitted by the
 * builder instance, and log any errors to the terminal.
 *
 * The build destination will be the directory specified in the 'builder.dest'
 * configuration option set above.
 */

gulp.task('fractal:build', function(){
    const builder = fractal.web.builder();
    builder.on('progress', (completed, total) => logger.update(`Exported ${completed} of ${total} items`, 'info'));
    builder.on('error', err => logger.error(err.message));
    return builder.build().then(() => {
        logger.success('Fractal build completed!');
    });
});

/**
 * Build tasks
 */

// Watch sass files and update css folder
gulp.task('default', ['watch']);

// Watch sass files & generate styleguide
// gulp.task('watch', ['sass'], function() {
gulp.task('watch', ['sass','fractal:start', 'javascript', 'fonts', 'images'], function() {
  // Start watching changes and update styleguide & theme css file whenever changes are detected
  // Styleguide automatically detects existing server instance
  livereload.listen({
    "port": 35734
  });
  // reload only .css when sass is changed
  gulp.watch(paths.sass, ['sass']);
  // reload full page when templates changes
  gulp.watch(paths.templates, function (files){
      livereload.changed(files)
    });
});
