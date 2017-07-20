<?php

namespace App\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Register;
use App\Support\Facades\ApiAuth;
use Illuminate\Http\Request;

class RegisterController extends ApiController
{
    /**
     * Controller method for /register/open (see docs/api.md)
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Api\Http\ApiResponse
     */
    public function open(Request $request)
    {
        $this->validate($request, [
            'employee' => 'bail|required|string',
            'cashAmount' => 'bail|required|numeric|min:0',
        ]);

        $apiResponse = new ApiResponse();
        $device = ApiAuth::getDevice();

        // If the logged device has a register that is already opened, return an error
        $currentRegister = $device->currentRegister;
        if (!is_null($currentRegister) && $currentRegister->opened) {
            $apiResponse->setError(
                ApiResponse::ERROR_CLIENT_ERROR,
                'The device has an already opened register. Close it first. This request was ignored.'
            );

            return $apiResponse;
        }

        $register = new Register();
        $register->device()->associate($device);
        $register->open($request->json('data.employee'), $request->json('data.cashAmount'));
        $register->save();

        $device->currentRegister()->associate($register);
        $device->save();

        $device->business->bumpVersion([Business::MODIFICATION_REGISTER]);

        return $apiResponse;
    }

    /**
     * Controller method for /register/close (see docs/api.md)
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Api\Http\ApiResponse
     */
    public function close(Request $request)
    {
        $this->validate($request, [
            'cashAmount' => 'bail|required|numeric|min:0',
            'POSTRef' => 'bail|required|string',
            'POSTAmount' => 'bail|required|numeric|min:0',
        ]);

        $apiResponse = new ApiResponse();
        $device = ApiAuth::getDevice();
        $currentRegister = $device->currentRegister;

        // Return error if the $device has no currentRegister or if it is not opened
        if (is_null($currentRegister) || !$currentRegister->opened) {
            $apiResponse->setError(
                ApiResponse::ERROR_CLIENT_ERROR,
                'The device doesn\'t have a register assigned or it is not opened. The request was ignored.'
            );

            return $apiResponse;
        }

        // Close the register
        $currentRegister->close(
            $request->json('data.cashAmount'),
            $request->json('data.POSTRef'),
            $request->json('data.POSTAmount')
        );
        $currentRegister->save();

        // Bump the business version
        $device->business->bumpVersion([Business::MODIFICATION_REGISTER]);

        return $apiResponse;
    }
}
