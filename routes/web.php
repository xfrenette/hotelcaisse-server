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
    });
