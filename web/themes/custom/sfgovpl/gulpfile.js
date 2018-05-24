// Include gulp.
let gulp = require('gulp');
let browserSync = require('browser-sync').create();
var config = require('./config.json');

// Include plugins.
var sass = require('gulp-sass');
var shell = require('gulp-shell');
var plumber = require('gulp-plumber');
var notify = require('gulp-notify');
var autoprefix = require('gulp-autoprefixer');
var glob = require('gulp-sass-glob');
var rename = require('gulp-rename');
var sourcemaps = require('gulp-sourcemaps');
var scssLint = require('gulp-scss-lint');
var shell = require('gulp-shell');

// CSS.
gulp.task('css', function() {
  return gulp.src(config.css.src)
    .pipe(glob())
    .pipe(plumber({
      errorHandler: function (error) {
        notify.onError({
          title:    "Gulp",
          subtitle: "Failure!",
          message:  "Error: <%= error.message %>",
          sound:    "Beep"
        }) (error);
        this.emit('end');
      }}))
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: 'compressed',
      errLogToConsole: true,
      includePaths: config.css.includePaths
    }))
    .pipe(autoprefix('last 2 versions', '> 1%', 'ie 9', 'ie 10'))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(config.css.dest))
    .pipe(browserSync.reload({ stream: true, match: '**/*.css' }));
});

// Pattern lab css compilation.
gulp.task('pl:scss', function() {
  return gulp.src(config.css.src, { base: './' })
    .pipe(glob())
    .pipe(plumber({
      errorHandler: function (error) {
        notify.onError({
          title:    "Gulp",
          subtitle: "Failure!",
          message:  "Error: <%= error.message %>",
          sound:    "Beep"
        }) (error);
        this.emit('end');
      }}))
    .pipe(sourcemaps.init())
    .pipe(sass(config.sass))
    .pipe(autoprefix(config.autoprefixer))
    .pipe(rename(function(path) {
      path.dirname = path.dirname.replace(/src/i, 'dist');
      return path;
    }))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('.'))
    .pipe(browserSync.reload({ stream: true, match: '**/*.css' }));
});

// Image tasks
//------------------------------------------------------------------------------
// copy images placed in src/img and compresses it an paste them in the
// public PL directory.

gulp.task('copyfiles', function() {
  // Exclude the icon directory and its contents, explicitly name file types to
  // prevent creation of an empty directory.
  return gulp.src([
    'src/img/*.{svg,gif,jpg,png}',
    'src/img/**/*.{svg,gif,jpg,png}'
  ])
    .pipe(gulp.dest('pattern-lab/source/images'));
});

// ------------------------------------------------------------ //


// Watch task.
gulp.task('watch', function() {
  gulp.watch(config.css.src, ['css', 'pl:scss', 'pl:generate']);
  // gulp.watch(config.pattern_lab.src, ['pl:scss', 'pl:generate']);
  gulp.watch(config.pattern_lab.src, ['pl:generate', 'copyfiles']);
  gulp.watch(['src/img/*.{svg,gif,jpg,png}', 'src/img/**.*.{svg,gif,jpg,png}'], ['copyfiles', 'pl:generate']);
});

// Static Server + Watch
gulp.task('serve', ['css', 'watch', 'pl:generate'], function() {
  browserSync.init({
    proxy: config.browserSyncProxy
  });
});

// Run drush to clear the theme registry.
// TODO: replace this with a pattern lab generate.
gulp.task('drush', shell.task([
  'drush cache-clear theme-registry'
]));

// Generate pl with PHP.
gulp.task('pl:generate', shell.task('php pattern-lab/core/console --generate'));

// SCSS Linting.
gulp.task('scss-lint', function() {
  return gulp.src([config.css.src])
    .pipe(scssLint())
    .pipe(scssLint.format())
    .pipe(scssLint.failOnError());
});

// Initialize.
// ------------------------------------------------------------------------------------------------------------------ //
// this task copies the twig files from the sfgov-pattern-lab pattern library that was installed with `npm install`
// and place them in the src file, so the components library is able to use those twig files.
// then copies the pattern-lab css and js file from the sfgov-pattern-library to the dist folder.
// once the css and js files are in the dist folder it can be commited and used by the site.
// ------------------------------------------------------------------------------------------------------------------ //

gulp.task('initialize', function() {

});

// Default Task
gulp.task('default', ['']);
