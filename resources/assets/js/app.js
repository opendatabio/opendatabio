/**
 * Here we load all of the JavaScript dependencies. Custom code should be on custom.js
 */

try {
	window.$ = window.jQuery = require('jquery');
//    window.$ = $.extend(require('jquery-ui-bundle'));
	require('bootstrap-sass');
	require('datatables');
	require('datatables.net-buttons');
    require('devbridge-autocomplete');
} catch (e) {}

