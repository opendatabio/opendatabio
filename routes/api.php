<?php

use Illuminate\Http\Request;

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


function v0api() {
    Route::get('/',           '\App\Api\v0\TestController@index');
    Route::get('taxons',      '\App\Api\v0\TaxonController@index');
    Route::get('taxons/{id}', '\App\Api\v0\TaxonController@show');
}
// With no specification, defaults to v0
v0api();


Route::group(['prefix' => 'v0'], function() { 
    v0api();
});
