const gulp = require('gulp');
const sass = require('gulp-sass');
const sourcemaps = require('gulp-sourcemaps');
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const browsersync = require('browser-sync').create();
const path = require('path');

// drupal libraries which include sass source files
const drupalLibraries = path.resolve(__dirname, '../../../libraries');

exports.css = css;
exports.watch = gulp.series(css, watch);

exports.default = gulp.series(
  gulp.parallel(css),
  gulp.parallel(watch, serve)
);

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

function serve() {
  return browsersync.init({
    proxy: 'https://sfgov.lndo.site/'
  });
}

function watch() {
  return gulp.watch(config.css.source, css);
}
