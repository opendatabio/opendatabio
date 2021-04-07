const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js([
	'resources/assets/js/app.js',
	'resources/assets/js/custom.js',
	'vendor/yajra/laravel-datatables-buttons/src/resources/assets/buttons.server-side.js',
	'vendor/andrechalom/laravel-multiselect/resources/assets/js/multiselect.js',
	'node_modules/spectrum-colorpicker/spectrum.js',
	], 'public/js')
	 .copy(
	 			'node_modules/@fortawesome/fontawesome-free/webfonts',
	 			'public/fonts'
	  )
	 .sass('resources/assets/sass/app.scss', 'public/css');
