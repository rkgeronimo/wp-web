const gulp            = require('gulp');
const include         = require('gulp-include');
const concat          = require('gulp-concat');
const sourcemaps      = require('gulp-sourcemaps');
const plumber         = require('gulp-plumber');
const uglify          = require('gulp-uglify');
const babel           = require('gulp-babel');
const postcss         = require('gulp-postcss');
const postcssImport   = require('postcss-import');
const colorFunction   = require('postcss-color-function');
const colorAdjustment = require('postcss-color-adjustment');
const utilities       = require('postcss-utilities');
const mixins          = require('postcss-mixins');
const precss          = require('precss');
const sugarss         = require('sugarss');
const autoprefixer    = require('autoprefixer');
const mqpacker        = require('css-mqpacker');
// const cssnano         = require('cssnano');
const del             = require('del');
const path            = require('path');

gulp.task('clean', () => del([
    'web/app/themes/rkg-theme/style.css',
    'web/app/themes/rkg-theme/static/site.js',
    'web/app/plugins/rkgeronimo/js/script.js',
]));

gulp.task('postclean', () => del(['css']));

gulp.task('scriptsPlugin', () => gulp.src('src/js/plugin/*.js')
    .pipe(sourcemaps.init())
    .pipe(plumber())
    .pipe(babel({
        presets: ['@babel/env'],
    }))
    .pipe(concat('script.js'))
    .pipe(uglify())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('web/app/plugins/rkgeronimo/js')));

gulp.task('scriptsTheme', () => gulp.src('src/js/theme/site.js')
    .pipe(sourcemaps.init())
    .pipe(plumber())
    .pipe(include())
    .on('error', console.log)
    .pipe(babel({
        presets: ['@babel/env'],
    }))
    // .pipe(uglify())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('web/app/themes/rkg-theme/static')));

gulp.task('styles', () => gulp.src('src/sss/style.sss')
    .pipe(sourcemaps.init())
    .pipe(plumber())
    .pipe(concat('style.css'))
    .pipe(postcss([
        postcssImport,
        utilities,
        mixins({
            mixinsFiles: path.join(__dirname, 'src/js/mixins', '!(*.def.js)'),
        }),
        precss,
        colorFunction,
        autoprefixer(),
        mqpacker(),
        // cssnano(),
    ],
    {parser: sugarss}))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('web/app/themes/rkg-theme')));

gulp.task('styles-admin', () => gulp.src('src/sss/style-admin.sss')
    .pipe(sourcemaps.init())
    .pipe(plumber())
    .pipe(concat('style-admin.css'))
    .pipe(postcss([
        postcssImport,
        utilities,
        mixins({
            mixinsFiles: path.join(__dirname, 'src/js/mixins', '!(*.def.js)'),
        }),
        precss,
        colorAdjustment,
        autoprefixer(),
        mqpacker(),
        // cssnano(),
    ],
    {parser: sugarss}))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('web/app/themes/rkg-theme')));

gulp.task('styles-survey', () => gulp.src('src/sss/style-survey.sss')
    .pipe(sourcemaps.init())
    .pipe(plumber())
    .pipe(concat('style-survey.css'))
    .pipe(postcss([
        postcssImport,
        utilities,
        mixins({
            mixinsFiles: path.join(__dirname, 'src/js/mixins', '!(*.def.js)'),
        }),
        precss,
        colorAdjustment,
        autoprefixer(),
        mqpacker(),
        // cssnano(),
    ],
    {parser: sugarss}))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('web/app/themes/rkg-theme')));

gulp.task(
    'default',
    gulp.series(
        'clean',
        gulp.parallel(
            'styles',
            'scriptsTheme',
            'styles-admin',
            'styles-survey',
            'scriptsPlugin',
        ),
        'postclean',
        (cb) => {
            cb();
        },
    ),
);
