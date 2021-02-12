const gulp = require('gulp')
const babel = require('gulp-babel')
const sass = require('gulp-sass')
const sourcemaps = require('gulp-sourcemaps')
const postcss = require('gulp-postcss')
const autoprefixer = require('autoprefixer')
const browsersync = require('browser-sync').create()
const path = require('path')

const assets = gulp.parallel(css, js)

exports.css = css
exports.js = js
exports.assets = assets
exports.watch = gulp.series(assets, watch)

exports.default = gulp.series(assets, gulp.parallel(watch, serve))

function js() {
  return gulp
    .src('./src/js/*.js')
    .pipe(sourcemaps.init())
    .pipe(babel())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('./dist/js'))
    .pipe(browsersync.stream())
}

function css() {
  return gulp
    .src('./src/sass/*.scss')
    .pipe(sourcemaps.init())
    .pipe(
      sass({
        outputStyle: 'expanded'
      })
    )
    .pipe(postcss([autoprefixer()]))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('./dist/css'))
    .pipe(browsersync.stream())
}

function serve() {
  return browsersync.init({
    proxy: 'https://sfgov.lndo.site/'
  })
}

function watch() {
  return gulp.parallel(
    gulp.watch('./src/sass', css),
    gulp.watch(['./src/js', 'babel.config.js'], js)
  )
}
