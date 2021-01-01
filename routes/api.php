<?php

use Illuminate\Http\Request;

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

Route::redirect('/login', '/');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'auth:api'], function() {
    // UserController
    Route::get('details', 'Api\UserController@getDetails')->middleware('cors');
    Route::post('details', 'Api\UserController@updateDetails')->middleware('cors', 'verified');

    // BeerController
    Route::get('beer/{house_id}', 'Api\BeerController@show')->middleware('cors');
    Route::post('beer', 'Api\BeerController@store')->middleware('cors', 'verified');

    // TaskController
    Route::post('task', 'Api\TaskController@storeTask')->middleware('cors', 'verified');
    Route::post('task/update', 'Api\TaskController@updateTask')->middleware('cors', 'verified');
    Route::post('task/destroy', 'Api\TaskController@destroyTask')->middleware('cors', 'verified');

    Route::get('task/{task_id}', 'Api\TaskController@indexTask')->middleware('cors', 'verified');
    Route::get('task/house/{house_id}', 'Api\TaskController@indexHouse')->middleware('cors', 'verified');
    Route::get('task/user/{user_id}', 'Api\TaskController@indexUser')->middleware('cors', 'verified');

    Route::get('task/{task_id}/{no_weeks}', 'Api\TaskController@indexTaskPerWeek')->middleware('cors', 'verified');
    Route::get('task/house/{house_id}/{no_weeks}', 'Api\TaskController@indexHousePerWeek')->middleware('cors', 'verified');
    Route::get('task/user/{user_id}/{no_weeks}', 'Api\TaskController@indexUserPerWeek')->middleware('cors', 'verified');
    
    Route::post('task/user/assign', 'Api\TaskController@assignUser')->middleware('cors', 'verified');
    Route::post('task/user/remove', 'Api\TaskController@removeUser')->middleware('cors', 'verified');

    // ContainerController
    Route::post('container', 'Api\ContainerController@show')->middleware('cors');
    Route::post('container/update', 'Api\ContainerController@updateContainerTurns')->middleware('cors', 'verified');

    // HouseController
    Route::get('house', 'Api\HouseController@index')->middleware('cors');
    Route::get('house/user', 'Api\HouseController@userBelongsTo')->middleware('cors');
    Route::get('house/{house_id}', 'Api\HouseController@show')->middleware('cors');
    Route::get('house/{house_id}/users', 'Api\HouseController@showUsers')->middleware('cors');
    Route::post('house/create', 'Api\HouseController@store')->middleware('cors', 'verified');
    Route::post('house/assign', 'Api\HouseController@assignUser')->middleware('cors', 'verified');
    Route::post('house/remove', 'Api\HouseController@removeUser')->middleware('cors', 'verified');

    Route::post('logout', ['as' => 'logout', 'uses' => 'Api\UserController@logout'])->middleware('cors');
});

Route::group(['middleware' => 'auth:api'], function() {
    Route::get('verify/resend', 'Api\VerificationController@resend')->name('verification.resend');
});

Route::post('login', ['as' => 'login', 'uses' => 'Api\UserController@login'])->middleware('cors');
Route::post('register', 'Api\UserController@register')->middleware('cors');

Route::get('verify/{id}', 'Api\VerificationController@verify')->name('verification.verify')->middleware('cors');

Route::post('password/email', ['as' => 'password.email', 'uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail'])->middleware('cors');
Route::post('password/reset', ['as' => 'password.reset', 'uses' => 'Auth\ResetPasswordController@reset'])->middleware('cors');
