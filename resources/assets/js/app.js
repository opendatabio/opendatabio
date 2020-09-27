/**
 * Here we load all of the JavaScript dependencies. Custom code should be on custom.js
 */


try {
	window.$ = window.jQuery = require('jquery');
//    window.$ = $.extend(require('jquery-ui-bundle'));
	require('bootstrap-sass');
	require('datatables');
	require('datatables.net-buttons');
  require('datatables.net-buttons/js/buttons.colVis.js');
	require('jquery-datatables-checkboxes/js/dataTables.checkboxes.min.js');
  require('devbridge-autocomplete');

	window.FilePond = require('filepond/dist/filepond.min.js');
  window.FilePondPluginImagePreview = require('filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.js');


} catch (e) {

}
