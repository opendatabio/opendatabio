/**
 * Here we load all of the JavaScript dependencies. Custom code should be on custom.js
 */
 import my_map from './map.js';
 window.my_map = my_map;

try {
	window.$ = window.jQuery = require('jquery');
//    window.$ = $.extend(require('jquery-ui-bundle'));
	require('bootstrap-sass');
	require('datatables');
	//window.ol = require('./map.js');
	require('datatables.net-buttons');
  require('datatables.net-buttons/js/buttons.colVis.js');
	require('jquery-datatables-checkboxes/js/dataTables.checkboxes.min.js');
	//require('vendor/yajra/laravel-datatables-buttons/src/resources/assets/buttons.server-side.js');
	//require('vendor/andrechalom/laravel-multiselect/resources/assets/js/multiselect.js');
  require('devbridge-autocomplete');
	window.FilePond = require('filepond/dist/filepond.min.js');
  window.FilePondPluginImagePreview = require('filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.js');
	//window.OlMap = require('ol/Map');
	//window.OlView = require('ol/View');
	//window.OlTileLayer = require('ol/layer/Tile');
	//window.OlOSM = require('ol/source/OSM');
} catch (e) {

}
