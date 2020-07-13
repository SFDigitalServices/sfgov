  /**
 * @file
 * Gulp script to run build processes.
 */
'use strict';

const gulp = require('gulp')
const sass = require('gulp-sass')
const sourcemaps = require('gulp-sourcemaps')
const postcss = require('gulp-postcss')
const autoprefixer = require('autoprefixer')
const imagemin = require('gulp-imagemin')
const babel = require('gulp-babel')
const browsersync = require('browser-sync').create()
const config = require('./config')

function css() {
  return gulp.src(config.css.src)
    .pipe(sourcemaps.init())
    .pipe(sass({ outputStyle: 'expanded'}))
    .pipe(postcss([autoprefixer()]))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(config.css.dest))
    .pipe(browsersync.stream());
}

function js() {
  return gulp.src(config.js.src)
    .pipe(sourcemaps.init())
    .pipe(babel())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(config.js.dest))
    .pipe(browsersync.stream());
}

function images() {
  return gulp.src(config.images.src)
    .pipe(imagemin())
    .pipe(gulp.dest(config.images.dest));
}

function serve() {
  browsersync.init({
    proxy: 'https://sfgov.lndo.site/'
  });
}

function watch() {
  gulp.watch(config.js.src, js);
  gulp.watch(config.css.src, css);
  gulp.watch(config.images.src, images);
}

exports.js = gulp.series(js);
exports.css = gulp.series(css);
exports.images = gulp.series(images);
exports.watch = gulp.series(js, css, images, watch);

exports.default = gulp.series(
  gulp.parallel(js),
  gulp.parallel(css),
  gulp.parallel(images),
  gulp.parallel(watch, serve)
);
