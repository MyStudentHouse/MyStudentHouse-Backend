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
    Route::get('details', 'API\UserController@getDetails')->middleware('cors');
    Route::post('details', 'API\UserController@updateDetails')->middleware('cors');
    Route::post('logout', ['as' => 'logout', 'uses' => 'API\UserController@logout'])->middleware('cors');

    Route::get('beer/{house_id}', 'API\BeerController@show')->middleware('cors');
    Route::post('beer', 'API\BeerController@store')->middleware('cors');

    Route::post('container', 'API\ContainerController@show')->middleware('cors');
    Route::post('container/update', 'API\ContainerController@updateContainerTurns')->middleware('cors');

    Route::get('house', 'API\HouseController@index')->middleware('cors');
    Route::get('house/user', 'API\HouseController@userBelongsTo')->middleware('cors');
    Route::get('house/{house_id}', 'API\HouseController@show')->middleware('cors');
    Route::post('house/assign', 'API\HouseController@assignUser')->middleware('cors');
    Route::post('house/remove', 'API\HouseController@removeUser')->middleware('cors');
});

Route::post('login', ['as' => 'login', 'uses' => 'API\UserController@login'])->middleware('cors');
Route::post('register', 'API\UserController@register')->middleware('cors');

Route::post('password/email', ['as' => 'password.email', 'uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail'])->middleware('cors');
Route::post('password/reset', ['as' => 'password.reset', 'uses' => 'Auth\ResetPasswordController@reset'])->middleware('cors');
