var gulp = require('gulp'),
    compass = require('gulp-compass'),
    autoprefixer = require('gulp-autoprefixer'),
    minifycss = require('gulp-minify-css'),
    uglify = require('gulp-uglify'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    notify = require('gulp-notify');


var paths = {
    js_scripts: ['../../app/javascript/analytics/**/*.js'],
    js_destination:'../../public/analytics/js/',
    sass: ['../../app/sass/**/*.scss'],
    css_destination:'../../public/analytics/css/'
};

//styles
gulp.task('sass', function() {
    return gulp.src(['../../app//sass/analytics**/*.scss'])
        .pipe(compass({
            css: '../../public/analytics/css',
            sass: '../../app/sass/analytics/',
            image: '../../public/analytics/img'
        }))
        .pipe(autoprefixer('last 2 version', 'safari 5', 'ie 7', 'ie 8', 'ie 9', 'opera 12.1', 'ios 6', 'android 4'))
        .pipe(gulp.dest('../../public/analytics/css'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(minifycss())
        .pipe(gulp.dest('../../public/analytics/css'))
});

gulp.task('scripts', function() {
    // Minify and copy all JavaScript (except vendor scripts)
    return gulp.src(paths.js_scripts)
        .pipe(concat('app.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(paths.js_destination));
});


gulp.task('watch', function() {
    gulp.run('sass');

    //watch .scss files
    gulp.watch(paths.sass, function(event) {
        gulp.run('sass');
    });

});

// The default task (called when you run `gulp` from cli)
gulp.task('default', ['watch']);
