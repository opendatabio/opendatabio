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
   // app css
<<<<<<< HEAD
=======
	 .copy(
	 			'node_modules/@fortawesome/fontawesome-free/webfonts',
	 			'public/fonts'
	  )
>>>>>>> bc12122f19300d4b8117e0f28f84a49229a96a7e
	 .sass('resources/assets/sass/app.scss', 'public/css');
