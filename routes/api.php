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
Route::post('register', 'UserController@store')->middleware('cors');
Route::post('login', ['as' => 'login', 'uses' => 'UserController@login'])->middleware('cors');

// Routes with authentication
Route::group(['middleware' => 'auth:api'], function() {
    // UserController
    Route::get('details', 'UserController@index')->middleware('cors');
    Route::post('details/{id}', 'UserController@show')->middleware('cors', 'verified');
    Route::post('details', 'UserController@update')->middleware('cors', 'verified');
    Route::post('logout', ['as' => 'logout', 'uses' => 'UserController@logout'])->middleware('cors');

    // HouseController
    Route::get('house', 'HouseController@index')->middleware('cors');
    Route::get('house/user', 'HouseController@userBelongsTo')->middleware('cors');
    Route::get('house/{house_id}', 'HouseController@show')->middleware('cors');
    Route::get('house/{house_id}/users', 'HouseController@showUsers')->middleware('cors');
    Route::post('house', 'HouseController@update')->middleware('cors', 'verified');
    Route::post('house/create', 'HouseController@store')->middleware('cors', 'verified');
    Route::post('house/assign', 'HouseController@assignUser')->middleware('cors', 'verified');
    Route::post('house/invite', 'HouseController@inviteNonUser')->middleware('cors', 'verified');
    Route::post('house/remove', 'HouseController@removeUser')->middleware('cors', 'verified');

    // InventoryController
    Route::get('inventory/{house_id}', 'Api\InventoryController@show')->middleware('cors');
    Route::post('inventory', 'Api\InventoryController@store')->middleware('cors', 'verified');

    // TaskController
    Route::post('task', 'TaskController@store')->middleware('cors', 'verified');
    Route::post('task/update', 'TaskController@update')->middleware('cors', 'verified');
    Route::post('task/destroy', 'TaskController@destroy')->middleware('cors', 'verified');

    Route::get('task/{task_id}', 'TaskController@show')->middleware('cors', 'verified');
    Route::get('task/house/{house_id}', 'TaskController@showHouse')->middleware('cors', 'verified');
    Route::get('task/user/{user_id}', 'TaskController@showUser')->middleware('cors', 'verified');

    Route::get('task/{task_id}/{no_weeks}', 'TaskController@showTaskPerWeek')->middleware('cors', 'verified');
    Route::get('task/house/{house_id}/{no_weeks}', 'TaskController@showHousePerWeek')->middleware('cors', 'verified');
    Route::get('task/user/{user_id}/{no_weeks}', 'TaskController@showUserPerWeek')->middleware('cors', 'verified');
    
    Route::post('task/user/assign', 'TaskController@assignUser')->middleware('cors', 'verified');
    Route::post('task/user/remove', 'TaskController@removeUser')->middleware('cors', 'verified');
});