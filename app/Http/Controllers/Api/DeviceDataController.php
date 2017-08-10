<?php

namespace App\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Support\Facades\ApiAuth;

class DeviceDataController extends ApiController
{
    /**
     * Method for the api.deviceData route which returns all data that the device requires.
     *
     * @return \App\Api\Http\ApiResponse
     */
    public function handle()
    {
        $response = new ApiResponse();

        $device = ApiAuth::getDevice();

        // Add Business
        $business = $device->business;
        $business->loadAllRelations();
        $response->setBusiness($business);

        // Add deviceRegister (can be null)
        $register = $device->currentRegister;

        if ($register) {
            $register->load('cashMovements');
        }

        $response->setDeviceRegister($register);

        return $response;
    }
}
