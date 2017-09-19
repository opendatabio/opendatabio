<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| 
| Notice: These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
| REMEMBER: always place the more specific rules BEFORE resources
*/

# Landing page
Route::get('/', 'WelcomeController@index');

# Setting the locale:
Route::get('welcome/{locale}', 'WelcomeController@setAppLocale');

# Default auth routes
Auth::routes();

# Users can update their own data
Route::get('/selfedit', 'Auth\SelfEditController@selfedit')->name('selfedit');
Route::put('/selfupdate', 'Auth\SelfEditController@selfupdate')->name('selfupdate');

# Home controller for logged in users
Route::get('/home', 'HomeController@index')->name('home');

# Resources (with non-default BEFORE resources):

Route::get('persons/getdata', 'PersonController@getdata');
Route::resource('persons', 'PersonController');

Route::post('userjobs/{userjob}/retry', 'UserJobsController@retry');
Route::post('userjobs/{userjob}/cancel', 'UserJobsController@cancel');
Route::resource('userjobs', 'UserJobsController', ['only' => ['index', 'show', 'destroy']]);

Route::get('references/getdata', 'BibReferenceController@getdata');
Route::resource('references', 'BibReferenceController');

Route::post('herbaria/checkih', 'HerbariumController@checkih')->name('checkih');
Route::resource('herbaria', 'HerbariumController', ['only' => ['index', 'show', 'store', 'destroy']]);

Route::resource('locations', 'LocationController');

Route::post('taxons/checkapis', 'TaxonController@checkapis')->name('checkapis');
Route::get('taxons/autocomplete', 'TaxonController@autocomplete');
Route::resource('taxons', 'TaxonController');

Route::resource('projects', 'ProjectController');

Route::resource('datasets', 'DatasetController');

Route::resource('plants', 'PlantController');

Route::resource('vouchers', 'VoucherController');

Route::resource('tags', 'TagController');

Route::resource('traits', 'TraitController');

# Users can be resources for the admin
Route::resource('users', 'UserController', ['only' => ['index', 'show', 'edit', 'update', 'destroy']]);

