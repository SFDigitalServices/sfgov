  /**
 * @file
 * Gulp script to run build processes.
 */
'use strict';


// Require Gulp + Plug-ins
//------------------------------------------------------------------------------

const gulp = require('gulp'),
autoprefixer = require('gulp-autoprefixer'),
babel = require('gulp-babel'),
browsersync = require('browser-sync').create(),
configDefault = require('./config.json'),
notify = require('gulp-notify'),
plumber = require('gulp-plumber'),
rename = require('gulp-rename'),
sass = require('gulp-sass'),
sasslint = require('gulp-sass-lint'),
sourcemaps = require('gulp-sourcemaps'),
imagemin = require('gulp-imagemin'),
watch = require('gulp-watch');


// Error Handler
//------------------------------------------------------------------------------
// This function uses gulp-notify to pipes errors through to OS X notifications
// and terminal. If you have a syntax error in your JavaScript, this code will
// notify you of the error w/sound and will remain running, allowing you to fix
// without having to restart Gulp.
//------------------------------------------------------------------------------

var errorHandler = function(error) {
  notify.onError({
    title: 'Task Failed [' + error.plugin + ']',
    message: 'Something went wrong: <%= error.message %>',
    sound: 'Sosumi'
  })(error);

  // Log error to console, unless that's already happening. Sass lint provides
  // good error handling/feedback in the terminal, so in this case we only want
  // the OS X notification w/sound.
  if (error.plugin !== 'gulp-sass') {
    console.log(error.toString());
  }

  // Prevent Gulp watch from stopping.
  this.emit('end');
};


// Configuration
//------------------------------------------------------------------------------
// Default configuration settings are located in `config.json`. Some of these
// may be overridden according to personal preference by creating a file locally
// called `config.local.json`. An example file is provided in this directory.
//------------------------------------------------------------------------------

var configLocal = {};

try {
  // Try to find a local configuration file.
  configLocal = require('./config.local.json');
}
catch (e) {
  // If there is no local configuration file, that's fine, prevent an error.
}

// Merge the two configurations, with the local version overwriting the default.
// NOTE: When overriding a top level key, the entire key is replaced (not deep
// merged), so be sure `config.local.json` contains the needed settings.
const config = { ...configDefault, ...configLocal };

// Set the proxy for BrowserSync.
config.browsersync.proxy = config.proxy;


// Sass
//------------------------------------------------------------------------------
// Sass in `../css/src` is compiled to CSS and written to `../css/dist`.
//------------------------------------------------------------------------------

// General CSS.
gulp.task('sass:css', ['sass-lint'], () => {
  gulp.src('../css/src/**/*.scss')
    .pipe(plumber({ errorHandler: errorHandler }))
    .pipe(sourcemaps.init())
    .pipe(sass(config.sass))
    .pipe(autoprefixer(config.autoprefixer))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('../css/dist'))
    .pipe(browsersync.stream({ match: '../css/dist/**/*.css' }));
});


// Sass Lint
//------------------------------------------------------------------------------
// This task runs with all Sass changes, and checks the code style against the
// rules defined in .sass-lint.yml.
//------------------------------------------------------------------------------

gulp.task('sass-lint', () => {
  return gulp.src(['../css/src/**/*.scss'])
    .pipe(plumber({ errorHandler: errorHandler }))
    .pipe(sasslint())
    .pipe(sasslint.format())
    .pipe(sasslint.failOnError())
});


// JavaScript
//------------------------------------------------------------------------------
// JavaScript in the `../js/src` and compiled using Babel to the `../js/dist`.
//------------------------------------------------------------------------------

gulp.task('js', () => {
  return gulp.src('../js/src/**/*.js')
    .pipe(sourcemaps.init())
    .pipe(babel())
    .on('error', errorHandler)
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('../js/dist'))
    .pipe(browsersync.stream({ match: '../js/dist/**/*.js' }));
});

gulp.task('images', () => {
  return gulp
    .src('../css/src/images/**/*')
    .pipe(imagemin())
    .pipe(gulp.dest('../css/dist/images'));
});


// Watch
//------------------------------------------------------------------------------
// The watch task initializes BrowserSync, and watches source directories for
// file system changes, and triggers the appropriate tasks.
//------------------------------------------------------------------------------

gulp.task('watch', () => {
  browsersync.init(config.browsersync);
  gulp.watch('../css/src/**/*.scss', ['sass:css']);
  gulp.watch('../js/src/**/*.js', ['js']);
});


// Default task
//------------------------------------------------------------------------------
gulp.task('default', ['sass:css', 'js', 'images', 'watch']);
