var gulp = require('gulp'),
    sass = require('gulp-sass'),
    postcss = require('gulp-postcss'),
    autoprefixer = require('autoprefixer'),
    sourcemaps = require('gulp-sourcemaps'),
    merge = require('merge-stream'),
    uglify = require('gulp-uglify'),
    uglifycss = require('gulp-uglifycss'),
    concat = require('gulp-concat'),
    notify = require('gulp-notify'),
    rename = require('gulp-rename');

var vendor_js = [
    './resources/assets/js/vendor/jquery-2.2.0.min.js',
    './resources/assets/js/vendor/foundation.min.js',
];

var vendor_css = [
    './resources/assets/css/vendor/foundation.min.css',
];

var custom_js = [

];

gulp.task('compress-vendorJs', function () {
    return gulp.src(vendor_js)
        .pipe(concat('vendors.js'))
        .pipe(rename({suffix: '.min'}))
        .pipe(uglify())
        .pipe(gulp.dest('./public/js'))
        .pipe(notify({message: 'Vendor javascript files successfully compressed.'}));
});

gulp.task('compress-vendorCSS', function () {
    return gulp.src(vendor_css)
        .pipe(concat('vendors.css'))
        .pipe(rename({suffix: '.min'}))
        .pipe(uglifycss())
        .pipe(gulp.dest('./public/css'))
        .pipe(notify({message: 'Vendor css files successfully compressed.'}));
});

/**
 * Compile files from  css folder
 */
gulp.task('sass', function () {

    var frontend = gulp.src('./resources/assets/css/custom/app.scss')
        .pipe(sourcemaps.init())
        .pipe(sass())
        .pipe(postcss([autoprefixer({browsers: ['last 30 versions', '> 1%', 'ie 8', 'ie 7']})]))
        .pipe(sourcemaps.write('./maps'))
        .pipe(uglifycss({
            "max-line-len": 80
        }))
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest('./public/css/'));
});

gulp.task('js', function () {
    return gulp.src(custom_js)
        .pipe(concat('app.js'))
        .pipe(rename({suffix: '.min'}))
        .pipe(uglify())
        .pipe(gulp.dest('./public/js'))
        .pipe(notify({message: 'Custom javascripts successfully compressed.'}));
});

/*
 * Watch scss files for changes & recompile
 */
gulp.task('watch-sass', function () {
    gulp.watch('./resources/assets/css/**/*.scss', ['sass']);
});

/*
 * Watch javascript files for changes & recompile
 */
gulp.task('watch-js', function () {
    gulp.watch('./resources/assets/js/**/*.js', ['js']);
});

/*
 * Default task, running just `gulp` will compile the sass,
 */
gulp.task('default', ['sass', 'js', 'watch-sass', 'watch-js']);