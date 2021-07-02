<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Routes without authentication
// UserController
Route::post('register', 'Api\UserController@store')->middleware('cors');
Route::post('login', ['as' => 'login', 'uses' => 'Api\UserController@login'])->middleware('cors');

// Routes with authentication
Route::group(['middleware' => 'auth:api'], function() {
    // UserController
    Route::get('details', 'Api\UserController@index')->middleware('cors');
    Route::post('details', 'Api\UserController@update')->middleware('cors', 'verified');
    Route::post('logout', ['as' => 'logout', 'uses' => 'Api\UserController@logout'])->middleware('cors');

    // HouseController
    Route::get('house', 'HouseController@index')->middleware('cors');
    Route::get('house/user', 'HouseController@userBelongsTo')->middleware('cors');
    Route::get('house/{house_id}', 'HouseController@show')->middleware('cors');
    Route::get('house/{house_id}/users', 'HouseController@showUsers')->middleware('cors');
    Route::post('house/create', 'HouseController@store')->middleware('cors', 'verified');
    Route::post('house/assign', 'HouseController@assignUser')->middleware('cors', 'verified');
    Route::post('house/invite', 'HouseController@inviteNonUser')->middleware('cors', 'verified');
    Route::post('house/remove', 'HouseController@removeUser')->middleware('cors', 'verified');
});