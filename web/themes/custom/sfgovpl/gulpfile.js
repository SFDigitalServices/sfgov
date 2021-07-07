const autoprefixer = require('autoprefixer')
const babel = require('gulp-babel')
const browsersync = require('browser-sync').create()
const gulp = require('gulp')
const path = require('path')
const postcss = require('gulp-postcss')
const sourcemaps = require('gulp-sourcemaps')

const assets = gulp.parallel(css, js)
const watch = () => {
  gulp.watch([
    'src/sass',
    'postcss.config.js'
  ], css)
    .on('change', browsersync.reload),
  gulp.watch([
    'src/js',
    'babel.config.js'
  ], js)
    .on('change', browsersync.reload)
}

exports.css = css
exports.js = js
exports.assets = assets
exports.watch = gulp.series(assets, watch)

exports.default = gulp.series(assets, gulp.parallel(watch, serve))

function js() {
  return gulp
    .src('src/js/*.js')
    .pipe(sourcemaps.init())
    .pipe(babel())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('dist/js'))
    .pipe(browsersync.stream())
}

function css() {
  return gulp
    // we have to explicitly exclude partials here,
    // otherwise postcss will attempt to process them
    .src(['src/sass/*.scss', '!**/_*.scss'])
    .pipe(sourcemaps.init())
    .pipe(postcss()) // see postcss.config.js
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('dist/css'))
    .pipe(browsersync.stream())
}

function serve() {
  return browsersync.init({
    proxy: 'https://sfgov.lndo.site/'
  })
}
