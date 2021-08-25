<?php

/*
 * This file is part of the OpenDataBio app.
 * (c) OpenDataBio development team https://github.com/opendatabio
 */

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| The following API routes must be associated with an API version. v0 means unstable API,
| v1 is reserved for the first stable API release.
|
| All API routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

$v0api = function () {
    Route::get('/', '\App\Http\Api\v0\TestController@index');

    Route::get('bibreferences', '\App\Http\Api\v0\BibReferenceController@index');

    Route::get('biocollections', '\App\Http\Api\v0\BiocollectionController@index');
    Route::post('biocollections', '\App\Http\Api\v0\BiocollectionController@store');

    Route::get('datasets', '\App\Http\Api\v0\DatasetController@index');

    Route::get('individuals', '\App\Http\Api\v0\IndividualController@index');
    Route::post('individuals', '\App\Http\Api\v0\IndividualController@store');

    Route::get('individual-locations', '\App\Http\Api\v0\IndividualLocationController@index');
    Route::post('individual-locations', '\App\Http\Api\v0\IndividualLocationController@store');

    Route::get('media', '\App\Http\Api\v0\MediaController@index');

    Route::get('jobs', '\App\Http\Api\v0\UserJobController@index');

    Route::get('languages', '\App\Http\Api\v0\LanguageController@index');

    Route::get('locations', '\App\Http\Api\v0\LocationController@index');
    Route::post('locations', '\App\Http\Api\v0\LocationController@store');

    Route::get('measurements', '\App\Http\Api\v0\MeasurementController@index');
    Route::post('measurements', '\App\Http\Api\v0\MeasurementController@store');

    Route::get('persons', '\App\Http\Api\v0\PersonController@index');
    Route::post('persons', '\App\Http\Api\v0\PersonController@store');

    Route::get('projects', '\App\Http\Api\v0\ProjectController@index');

    Route::post('taxons', '\App\Http\Api\v0\TaxonController@store');
    Route::get('taxons', '\App\Http\Api\v0\TaxonController@index');

    Route::get('traits', '\App\Http\Api\v0\TraitController@index');
    Route::post('traits', '\App\Http\Api\v0\TraitController@store');

    Route::get('vouchers', '\App\Http\Api\v0\VoucherController@index');
    Route::post('vouchers', '\App\Http\Api\v0\VoucherController@store');

};

Route::group(['prefix' => 'v0'], $v0api);

// With no specification, defaults to v0
Route::group(['prefix' => '/'], $v0api);
