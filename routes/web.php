<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

// Landing page
Route::get('/', 'WelcomeController@index');

// Setting the locale:
Route::get('welcome/{locale}', 'WelcomeController@setAppLocale');

// Default auth routes (login, logout, change password, etc)
Auth::routes();

// Users can update their own data
Route::get('/token', 'Auth\SelfEditController@token');
Route::post('/token', 'Auth\SelfEditController@resetToken');
Route::get('/selfedit', 'Auth\SelfEditController@selfedit')->name('selfedit');
Route::put('/selfupdate', 'Auth\SelfEditController@selfupdate')->name('selfupdate');

// Home controller for logged in users
Route::get('/home', 'HomeController@index')->name('home');

// Resources (with non-default BEFORE resources):
Route::get('persons/autocomplete', 'PersonController@autocomplete');
Route::resource('persons', 'PersonController');

Route::post('userjobs/{userjob}/retry', 'UserJobController@retry');
Route::post('userjobs/{userjob}/cancel', 'UserJobController@cancel');
Route::resource('userjobs', 'UserJobController', ['only' => ['index', 'show', 'destroy']]);

Route::get('references/autocomplete', 'BibReferenceController@autocomplete');
Route::resource('references', 'BibReferenceController');

Route::post('herbaria/checkih', 'HerbariumController@checkih')->name('checkih');
Route::resource('herbaria', 'HerbariumController', ['only' => ['index', 'show', 'store', 'destroy']]);

Route::get('locations/autocomplete', 'LocationController@autocomplete');
Route::post('locations/autodetect', 'LocationController@autodetect')->name('autodetect');
Route::resource('locations', 'LocationController');

Route::post('taxons/checkapis', 'TaxonController@checkapis')->name('checkapis');
Route::get('taxons/autocomplete', 'TaxonController@autocomplete');
Route::resource('taxons', 'TaxonController');

Route::resource('projects', 'ProjectController');

Route::resource('datasets', 'DatasetController');

Route::resource('plants', 'PlantController');

Route::resource('vouchers', 'VoucherController');

Route::resource('tags', 'TagController');

Route::get('traits/autocomplete', 'TraitController@autocomplete');
Route::get('traits/getformelement', 'TraitController@getFormElement');
Route::resource('traits', 'TraitController');

// Users can be resources for the admin
Route::resource('users', 'UserController', ['only' => ['index', 'show', 'edit', 'update', 'destroy']]);

// Measures use a somewhat complicated schema for routes?
Route::get('plants/{id}/measurements/create', 'MeasurementController@createPlants');
Route::get('locations/{id}/measurements/create', 'MeasurementController@createLocations');
Route::get('taxons/{id}/measurements/create', 'MeasurementController@createTaxons');
Route::get('vouchers/{id}/measurements/create', 'MeasurementController@createVouchers');
Route::get('plants/{id}/measurements', 'MeasurementController@indexPlants');
Route::get('locations/{id}/measurements', 'MeasurementController@indexLocations');
Route::get('taxons/{id}/measurements', 'MeasurementController@indexTaxons');
Route::get('vouchers/{id}/measurements', 'MeasurementController@indexVouchers');
Route::resource('measurements', 'MeasurementController', ['only' => ['show', 'store', 'edit', 'update']]);
