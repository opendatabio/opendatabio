<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

// Landing page
Route::get('/', 'WelcomeController@index');

// Setting the locale:
Route::get('welcome/{locale}', 'WelcomeController@setAppLocale');
Route::get('home/{locale}', 'HomeController@setAppLocale');

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
Route::get('persons/{id}/history', 'PersonController@activity');
Route::resource('persons', 'PersonController');

Route::post('userjobs/{userjob}/retry', 'UserJobController@retry');
Route::post('userjobs/{userjob}/cancel', 'UserJobController@cancel');
Route::resource('userjobs', 'UserJobController', ['only' => ['index', 'show', 'destroy']]);

Route::get('references/{id}/history', 'BibReferenceController@activity');
Route::get('references/autocomplete', 'BibReferenceController@autocomplete');
Route::resource('references', 'BibReferenceController');

Route::post('biocollections/checkih', 'BiocollectionController@checkih')->name('checkih');
Route::get('biocollections/autocomplete','BiocollectionController@autocomplete');
Route::resource('biocollections', 'BiocollectionController', ['only' => ['index', 'show', 'store', 'destroy']]);

Route::post('importLocations', 'LocationController@importJob');
Route::get('locations/{id}/activity', 'LocationController@activity');
Route::get('locations/{id}/project', 'LocationController@indexProjects');
Route::get('locations/{id}/dataset', 'LocationController@indexDatasets');
Route::get('locations/autocomplete', 'LocationController@autocomplete');

Route::post('locations/autodetect', 'LocationController@autodetect')->name('autodetect');
Route::post('locations/individual_location', 'LocationController@saveForIndividual')->name('saveForIndividual');

Route::resource('locations', 'LocationController');

Route::get('taxons/{id}/project', 'TaxonController@indexProjects');
Route::get('taxons/{id}/dataset', 'TaxonController@indexDatasets');
Route::get('taxons/{id}/activity', 'TaxonController@activity');
Route::get('taxons/{id}/taxon', 'TaxonController@indexTaxons');
Route::get('taxons/{id}/taxon_project', 'TaxonController@indexTaxonsProjects');
Route::get('taxons/{id}/taxon_dataset', 'TaxonController@indexTaxonsDatasets');
Route::get('taxons/{id}/taxon_location', 'TaxonController@indexTaxonsLocations');
Route::get('taxons/{id}/location', 'TaxonController@indexLocations');
Route::get('taxons/{id}/location_project', 'TaxonController@indexLocationsProjects');
Route::get('taxons/{id}/location_dataset', 'TaxonController@indexLocationsDatasets');


Route::post('taxons/checkapis', 'TaxonController@checkapis')->name('checkapis');
Route::get('taxons/autocomplete', 'TaxonController@autocomplete');

Route::resource('taxons', 'TaxonController');


Route::get('projects/{id}/activity', 'ProjectController@activity');
Route::post('projects/{id}/summarize_project', 'ProjectController@summarize_project')->name('projectsummary');
Route::post('projects/{id}/identifications_summary', 'ProjectController@summarize_identifications')->name('project_identification_summary');
Route::get('projects/{id}/tags','ProjectController@indexTags');
Route::get('projects/{id}/dataset','ProjectController@indexDatasets');
Route::post('projects/{id}/emailrequest', 'ProjectController@sendEmail');
Route::get('projects/{id}/request', 'ProjectController@projectRequestForm');

Route::resource('projects', 'ProjectController');


Route::get('datasets/{id}/project', 'DatasetController@indexProjects');
Route::get('datasets/{id}/download', 'DatasetController@prepDownloadFile');
Route::get('datasets/{id}/request', 'DatasetController@datasetRequestForm');
Route::get('datasets/{id}/activity','DatasetController@activity');
Route::get('datasets/{id}/tags','DatasetController@indexTags');
Route::post('datasets/{id}/emailrequest', 'DatasetController@sendEmail');
Route::post('datasets/{id}/identifications_summary','DatasetController@summarize_identifications')->name('datasetTaxonInfo');
Route::resource('datasets', 'DatasetController');


Route::post('references/findbibtexfromdoi', 'BibReferenceController@findBibtexFromDoi')->name('findbibtexfromdoi');

Route::post('individuals/saveIndividualLocation', 'IndividualController@saveIndividualLocation')->name('saveIndividualLocation');
Route::get('individuals/getIndividualLocation', 'IndividualController@getIndividualLocation')->name('getIndividualLocation');
Route::get('individuals/deleteIndividualLocation', 'IndividualController@deleteIndividualLocation')->name('deleteIndividualLocation');
Route::get('individuals/getIndividualForVoucher', 'IndividualController@getIndividualForVoucher')->name('getIndividualForVoucher');
Route::get('individuals/autocomplete', 'IndividualController@autocomplete');
Route::get('individuals/{id}/datasets', 'IndividualController@indexDatasets');
Route::get('individuals/{id}/activity', 'IndividualController@activity');

Route::post('individuals/batchidentify', 'IndividualController@batchidentifications');

//Route::get('locations/{id}/plants/create', 'PlantController@createLocations');
//Route::get('locations/{id}/plants', 'PlantController@indexLocations');

Route::get('individuals/{id}/location', 'IndividualController@indexLocations');
Route::get('individuals/{id}/location/create', 'IndividualController@createLocations');
Route::get('individuals/{id}/location', 'IndividualController@indexLocations');
Route::get('individuals/{id}/location_project', 'IndividualController@indexLocationsProjects');
Route::get('individuals/{id}/location_dataset', 'IndividualController@indexLocationsDatasets');
Route::get('individuals/{id}/taxon', 'IndividualController@indexTaxons');
Route::get('individuals/{id}/taxon_project', 'IndividualController@indexTaxonsProjects');
Route::get('individuals/{id}/taxon_dataset', 'IndividualController@indexTaxonsDatasets');
Route::get('individuals/{id}/taxon_location', 'IndividualController@indexTaxonsLocations');
Route::get('individuals/{id}/project', 'IndividualController@indexProjects');

//Route::get('persons/{id}/individuals', 'IndividualController@indexPersons');
Route::resource('individuals', 'IndividualController');


Route::get('vouchers/{id}/dataset', 'VoucherController@indexDatasets');
Route::get('vouchers/{id}/activity', 'VoucherController@activity');
Route::get('individuals/{id}/vouchers/create', 'VoucherController@createIndividuals');
//Route::get('locations/{id}/vouchers/create', 'VoucherController@createLocations');
Route::get('locations/{id}/vouchers', 'VoucherController@indexLocations');
Route::get('individuals/{id}/vouchers', 'VoucherController@indexIndividuals');
Route::get('vouchers/{id}/taxon', 'VoucherController@indexTaxons');
Route::get('vouchers/{id}/taxon_project', 'VoucherController@indexTaxonsProjects');
Route::get('vouchers/{id}/taxon_dataset', 'VoucherController@indexTaxonsDatasets');
Route::get('vouchers/{id}/taxon_location', 'VoucherController@indexTaxonsLocations');
Route::get('vouchers/{id}/location', 'VoucherController@indexLocations');
Route::get('vouchers/{id}/location_project', 'VoucherController@indexLocationsProjects');
Route::get('vouchers/{id}/location_dataset', 'VoucherController@indexLocationsDatasets');
Route::get('vouchers/autocomplete', 'VoucherController@autocomplete');
Route::get('vouchers/{id}/project', 'VoucherController@indexProjects');
Route::get('persons/{id}/vouchers', 'VoucherController@indexPersons');
Route::resource('vouchers', 'VoucherController');

Route::resource('tags', 'TagController');

Route::get('traits/{id}/activity', 'TraitController@activity');
Route::get('traits/autocomplete', 'TraitController@autocomplete');
Route::get('traits/getformelement', 'TraitController@getFormElement');
Route::resource('traits', 'TraitController');

// Users can be resources for the admin
Route::get('users/autocomplete', 'UserController@autocomplete');
Route::get('users/autocomplete_all', 'UserController@autocompleteAll');
Route::resource('users', 'UserController', ['only' => ['index', 'show', 'edit', 'update', 'destroy']]);

// Measures use a somewhat complicated schema for routes?
Route::get('measurements/{id}/activity', 'MeasurementController@activity');
Route::get('individuals/{id}/measurements/create', 'MeasurementController@createIndividuals');
Route::get('locations/{id}/measurements/create', 'MeasurementController@createLocations');
Route::get('taxons/{id}/measurements/create', 'MeasurementController@createTaxons');
Route::get('vouchers/{id}/measurements/create', 'MeasurementController@createVouchers');
Route::get('measurements/{id}/individual', 'MeasurementController@indexIndividuals');
Route::get('measurements/{id}/individual_dataset', 'MeasurementController@indexIndividualsDatasets');

Route::get('locations/{id}/measurements', 'MeasurementController@indexLocations');
Route::get('locations/{id}/measurements_root', 'MeasurementController@indexLocationsRoot');
Route::get('measurements/{id}/taxon', 'MeasurementController@indexTaxons');
Route::get('measurements/{id}/taxon_project', 'MeasurementController@indexTaxonsProjects');
Route::get('measurements/{id}/taxon_dataset', 'MeasurementController@indexTaxonsDatasets');
Route::get('measurements/{id}/taxon_location', 'MeasurementController@indexTaxonsLocations');
Route::get('measurements/{id}/location', 'MeasurementController@indexLocations');
Route::get('measurements/{id}/location_project', 'MeasurementController@indexLocationsProjects');
Route::get('measurements/{id}/location_dataset', 'MeasurementController@indexLocationsDatasets');


Route::get('vouchers/{id}/measurements', 'MeasurementController@indexVouchers');
Route::get('measurements/{id}/dataset', 'MeasurementController@indexDatasets')->name('ajax.measurementdataset');
Route::get('measurements/{id}/trait', 'MeasurementController@indexTraits');
Route::resource('measurements', 'MeasurementController', ['only' => ['show', 'store', 'edit', 'update']]);



/* IMPORT ROUTES */
Route::post('ImportIndividuals', 'IndividualController@importJob');
Route::post('importTaxons', 'TaxonController@importJob');
Route::post('importVouchers', 'VoucherController@importJob');
Route::post('importTraits', 'TraitController@importJob');
Route::post('importMeasurements', 'MeasurementController@importJob');
Route::post('importPersons', 'PersonController@importJob');
Route::post('ImportBiocollections', 'BiocollectionController@importJob');
Route::get('import/{model}',function($model) {
  return view('common.import',compact('model'));
});




//PICTURES
//Batch upload pictures_files
Route::get('pictures/uploadForm', 'PictureController@uploadForm')->name('uploadPictures');
Route::post('importPictures', 'PictureController@uploadSubmit');

//Picture object
Route::get('taxons/{id}/pictures/create', 'PictureController@createTaxons');
Route::get('locations/{id}/pictures/create', 'PictureController@createLocations');
Route::get('individuals/{id}/pictures/create', 'PictureController@createIndividuals');
Route::get('vouchers/{id}/pictures/create', 'PictureController@createVouchers');
Route::resource('pictures', 'PictureController', ['only' => ['show', 'store', 'edit', 'update']]);


Route::get('forms/{id}/prepare', 'FormController@prepare');
Route::post('forms/{id}/fill', 'FormController@fill');

Route::resource('forms', 'FormController');


Route::post('exportdata', 'ExportController@exportData');
