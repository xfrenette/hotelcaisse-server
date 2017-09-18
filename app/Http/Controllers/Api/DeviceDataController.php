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
        $business = ApiAuth::getBusiness();

        // Add Business
        $business->loadAllRelations();
        $response->setBusiness($business);

        // Add device
        $device->loadToArrayRelations();
        $response->setDevice($device);

        return $response;
    }
}
