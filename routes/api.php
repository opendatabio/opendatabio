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
    Route::post('taxons', '\App\Http\Api\v0\TaxonController@store');
    Route::get('taxons', '\App\Http\Api\v0\TaxonController@index');
    Route::get('locations', '\App\Http\Api\v0\LocationController@index');
    Route::post('locations', '\App\Http\Api\v0\LocationController@store');
    Route::get('jobs', '\App\Http\Api\v0\UserJobController@index');
    Route::get('persons', '\App\Http\Api\v0\PersonController@index');
    Route::post('persons', '\App\Http\Api\v0\PersonController@store');
};

Route::group(['prefix' => 'v0'], $v0api);

// With no specification, defaults to v0
Route::group(['prefix' => '/'], $v0api);
