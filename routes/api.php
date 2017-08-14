<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register the API routes for your application as
| the routes are automatically authenticated using the API guard and
| loaded automatically by this application's RouteServiceProvider.
|
*/

// This route is not auth protected since it is equivalent to a 'login' method
Route::post('device/register', 'DeviceController@register')->name('device.register');

// The following routes are auth protected
Route::middleware('apiauth')
    ->group(function () {
        Route::post('register/open', 'RegisterController@open')->name('register.open');
        Route::post('register/close', 'RegisterController@close')->name('register.close');
        Route::post('cashMovements/add', 'CashMovementsController@add')->name('cashMovements.add');
        Route::post('cashMovements/delete', 'CashMovementsController@delete')->name('cashMovements.delete');
        Route::post('orders/new', 'OrdersController@new')->name('orders.new');
        Route::post('orders/edit', 'OrdersController@edit')->name('orders.edit');
        Route::post('orders', 'OrdersController@list')->name('orders.list');
        Route::post('deviceData', 'DeviceDataController@handle')->name('deviceData');
    });
