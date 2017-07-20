<?php

namespace App\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Register;
use App\Support\Facades\ApiAuth;
use Illuminate\Http\Request;

class RegisterController extends ApiController
{
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
}
