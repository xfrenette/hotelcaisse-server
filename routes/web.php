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
Route::get('/documentation', 'HelpPagesController@index');
Route::get('/documentation/{page}', 'HelpPagesController@page');

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

        Route
            ::middleware('hasRole:admin')
            ->group(function() {
                // Registers
                Route::get('/registers', 'RegistersController@list')->name('registers.list');
                Route::get('/registers/export', 'RegistersController@export')->name('registers.export');
                Route::get('/registers/{register}', 'RegistersController@view')->name('registers.register.view');
                Route::get('/registers/{register}/recalculate', 'RegistersController@recalculate')->name('registers.register.recalculate');

                // Orders
                Route::get('/orders', 'OrdersController@list')->name('orders.list');
                Route::get('/orders/export', 'OrdersController@export')->name('orders.export');
                Route::get('/orders/{order}', 'OrdersController@view')->name('orders.order.view');
                Route::get('/orders/{order}/recalculate', 'OrdersController@recalculate')->name('orders.order.recalculate');

                // Products
                Route::get('/products', 'ProductsController@list')->name('products.list');
                Route::get('/products/export', 'ProductsController@export')->name('products.export');

                // Custom products
                Route::get('/customProducts', 'CustomProductsController@list')->name('customProducts.list');
                Route::get('/customProducts/export', 'CustomProductsController@export')->name('customProducts.export');
            });
    });
