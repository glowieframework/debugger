const gulp = require('gulp');
const concat = require('gulp-concat');
const terser = require('gulp-terser');
const watch = require('gulp-watch');
const sass = require('gulp-sass')(require('sass'));
const cleanCSS = require('gulp-clean-css');

function scripts() {
    return gulp.src('src/resources/*.js')
        .pipe(concat('script.min.js'))
        .pipe(terser())
        .pipe(gulp.dest('src/resources/dist'));
}

function styles() {
    return gulp.src('src/resources/*.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(concat('style.min.css'))
        .pipe(cleanCSS())
        .pipe(gulp.dest('src/resources/dist'));
}

function watchFiles() {
    watch('src/resources/*.js', {ignoreInitial: false, verbose: true}, scripts);
    watch('src/resources/*.scss', {ignoreInitial: false, verbose: true}, styles);
}

exports.default = gulp.series(scripts, styles);
exports.watch = gulp.series(scripts, styles, watchFiles);