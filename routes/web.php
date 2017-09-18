<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', 'WelcomeController@show');

Route
    ::middleware('auth')
    ->group(function () {
        Route::get('/home', 'HomeController@show')->name('home');

        // Devices
        Route::get('/devices', 'DevicesController@list')->name('devices.list');
        Route::get('/devices/add', 'DevicesController@add')->name('devices.add');
        Route::put('/devices/store', 'DevicesController@store')->name('devices.store');
        Route::get('/devices/{device}/code', 'DevicesController@code')->name('devices.device.code');
        Route::get('/devices/{device}/revoke', 'DevicesController@revoke')->name('devices.device.revoke');

        // Registers
        Route::get('/registers', 'RegistersController@list')->name('registers.list');
        Route::get('/registers/{register}', 'RegistersController@view')->name('registers.view');
    });
