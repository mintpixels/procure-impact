const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/admin/admin.js', 'public/js').vue()
   .js('resources/js/store/store.js', 'public/js').vue()
    .sass('resources/sass/admin/admin.scss', 'public/css')
    .sass('resources/sass/store/store.scss', 'public/css');


if (mix.inProduction()) {
    mix.version();
}