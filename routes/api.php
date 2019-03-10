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

Route::group(['middleware' => 'auth:api'], function(){
    Route::post('beer', 'API\BeerController@show')->middleware('cors');
    Route::post('beer', 'API\BeerController@store')->middleware('cors');

    Route::post('container', 'API\ContainerController@show')->middleware('cors');
    Route::post('container/update', 'API\ContainerController@updateContainerTurns')->middleware('cors');

    Route::post('house', 'API\HouseController@store')->middleware('cors');
});

Route::post('login', ['as' => 'login', 'uses' => 'API\UserController@login'])->middleware('cors');
Route::get('logout', ['as' => 'logout', 'uses' => 'API\UserController@logout'])->middleware('cors');
Route::post('register', 'API\UserController@register')->middleware('cors');
Route::group(['middleware' => 'auth:api'], function(){
    Route::post('details', 'API\UserController@details')->middleware('cors');
});

Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->middleware('cors');
Route::post('password/reset', 'Auth\ResetPasswordController@reset')->middleware('cors');
