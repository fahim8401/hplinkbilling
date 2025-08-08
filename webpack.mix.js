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

mix.js('resources/js/main.js', 'public/js/app.js')
    .vue({ version: 3 })
    .postCss('resources/css/app.css', 'public/css', [
        require('tailwindcss'),
    ])
    .options({
        processCssUrls: false,
    });

// Production optimizations
if (mix.inProduction()) {
    mix.version();
}