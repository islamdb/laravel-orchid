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

mix.js("resources/js/frontend.js", "public/js")
    .js("resources/js/backend.js", "public/js")
    .postCss("resources/css/frontend.css", "public/css", [
        //
    ])
    .postCss("resources/css/backend.css", "public/css", [
        //
    ]);

if (mix.inProduction()) {
    mix.version();
}