const gulp = require('gulp');
const babel = require('gulp-babel');
const sass = require('gulp-sass');
const sourcemaps = require('gulp-sourcemaps');
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const browsersync = require('browser-sync').create();
const path = require('path');

// drupal libraries which include sass source files
const drupalLibraries = path.resolve(__dirname, '../../../libraries');

exports.css = css;
exports.js = js;
exports.assets = assets;
exports.watch = gulp.series(assets, watch);

exports.default = gulp.series(
  assets,
  gulp.parallel(watch, serve)
);

function js() {
  return gulp
    .src('./src/js/*.js')
    .pipe(sourcemaps.init())
    .pipe(babel())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('./dist/js'))
    .pipe(browsersync.stream());
}

function css() {
  const plugins = [
    autoprefixer()
  ];
  return gulp
    .src('./src/sass/**/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({ 
      outputStyle: 'expanded',
      includePaths: [drupalLibraries]
    }))
    .pipe(postcss(plugins))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('./dist/css'))
    .pipe(browsersync.stream());
}

function assets() {
  return gulp.parallel(css, js);
}

function serve() {
  return browsersync.init({
    proxy: 'https://sfgov.lndo.site/'
  });
}

function watch() {
  return gulp.watch('./src', assets);
}
