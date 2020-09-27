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
Route::get('persons/{id}/history', 'PersonController@history');
Route::resource('persons', 'PersonController');

Route::post('userjobs/{userjob}/retry', 'UserJobController@retry');
Route::post('userjobs/{userjob}/cancel', 'UserJobController@cancel');
Route::resource('userjobs', 'UserJobController', ['only' => ['index', 'show', 'destroy']]);

Route::get('references/{id}/history', 'BibReferenceController@history');
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

Route::post('batchidentifications', 'PlantController@batchidentifications');
Route::get('locations/{id}/plants', 'PlantController@indexLocations');
Route::get('locations/{id}/plants/create', 'PlantController@createLocations');
Route::get('taxons/{id}/plants', 'PlantController@indexTaxons');
Route::get('projects/{id}/plants', 'PlantController@indexProjects');
Route::get('persons/{id}/plants', 'PlantController@indexPersons');
Route::resource('plants', 'PlantController');

Route::get('plants/{id}/vouchers/create', 'VoucherController@createPlants');
Route::get('locations/{id}/vouchers/create', 'VoucherController@createLocations');
Route::get('locations/{id}/vouchers', 'VoucherController@indexLocations');
Route::get('plants/{id}/vouchers', 'VoucherController@indexPlants');
Route::get('taxons/{id}/vouchers', 'VoucherController@indexTaxons');
Route::get('projects/{id}/vouchers', 'VoucherController@indexProjects');
Route::get('persons/{id}/vouchers', 'VoucherController@indexPersons');
Route::resource('vouchers', 'VoucherController', ['except' => ['create']]);

Route::resource('tags', 'TagController');

Route::get('traits/autocomplete', 'TraitController@autocomplete');
Route::get('traits/getformelement', 'TraitController@getFormElement');
Route::resource('traits', 'TraitController');

// Users can be resources for the admin
Route::get('users/autocomplete', 'UserController@autocomplete');
Route::get('users/autocomplete_all', 'UserController@autocompleteAll');
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
Route::get('datasets/{id}/measurements', 'MeasurementController@indexDatasets')->name('ajax.measurementdataset');
Route::get('traits/{id}/measurements', 'MeasurementController@indexTraits');
Route::resource('measurements', 'MeasurementController', ['only' => ['show', 'store', 'edit', 'update']]);

//PICTURES
//Batch upload pictures_files
Route::get('pictures/uploadForm', 'PictureController@uploadForm')->name('uploadPictures');
Route::post('importPictures', 'PictureController@uploadSubmit');
//Picture object
Route::get('taxons/{id}/pictures/create', 'PictureController@createTaxons');
Route::get('locations/{id}/pictures/create', 'PictureController@createLocations');
Route::get('plants/{id}/pictures/create', 'PictureController@createPlants');
Route::get('vouchers/{id}/pictures/create', 'PictureController@createVouchers');
Route::resource('pictures', 'PictureController', ['only' => ['show', 'store', 'edit', 'update']]);


Route::get('forms/{id}/prepare', 'FormController@prepare');
Route::post('forms/{id}/fill', 'FormController@fill');

Route::resource('forms', 'FormController');
