<?php

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
        Route::post('deviceData', 'DeviceDataController@handle')->name('deviceData');
    });
