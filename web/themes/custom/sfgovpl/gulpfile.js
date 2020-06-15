"use strict";

const gulp = require('gulp');
const sass = require('gulp-sass');
const sourcemaps = require('gulp-sourcemaps');
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const browsersync = require('browser-sync').create();

const config = require('./config');

function css() {
  const plugins = [
    autoprefixer(config.autoprefixer)
  ];
  return gulp
    .src(config.css.source)
    .pipe(sourcemaps.init())
    .pipe(sass({ outputStyle: 'expanded'}))
    .pipe(postcss(plugins))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(config.css.dest))
    .pipe(browsersync.stream());
}

function serve() {
  browsersync.init({
    proxy: 'https://sfgov.lndo.site/'
  });
}

function watch() {
  gulp.watch(config.css.source, css);
}

exports.css = gulp.series(css);
exports.watch = gulp.series(css, watch);

exports.default = gulp.series(
  gulp.parallel(css),
  gulp.parallel(watch, serve)
);
