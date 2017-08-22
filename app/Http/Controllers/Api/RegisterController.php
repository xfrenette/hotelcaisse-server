<?php

namespace App\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Exceptions\Api\InvalidRequestException;
use App\Http\Requests\Api\RegisterOpen;
use App\Register;
use App\Support\Facades\ApiAuth;
use Carbon\Carbon;
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
    public function open(RegisterOpen $request)
    {
        $this->validateRegisterNotOpened();

        $device = ApiAuth::getDevice();
        $business = ApiAuth::getBusiness();

        $register = new Register([
            'uuid' => $request->json('data.uuid'),
        ]);
        $register->device()->associate($device);
        $register->open($request->json('data.employee'), $request->json('data.cashAmount'));
        $openedAt = $request->json('data.openedAt', false);
        if ($openedAt && $openedAt <= Carbon::now()->getTimestamp()) {
            $register->opened_at = Carbon::createFromTimestamp($openedAt);
        }
        $register->save();

        $device->currentRegister()->associate($register);
        $device->save();

        $business->bumpVersion([Business::MODIFICATION_REGISTER]);

        return new ApiResponse();
    }

    /**
     * Controller method for /register/close (see docs/api.md)
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Api\Http\ApiResponse
     * @throws \App\Exceptions\Api\InvalidRequestException
     */
    public function close(Request $request)
    {
        $this->validateClose($request);
        $this->validateRegisterOpened();

        $apiResponse = new ApiResponse();
        $device = ApiAuth::getDevice();
        $business = ApiAuth::getBusiness();
        $currentRegister = $device->currentRegister;

        // Throws validation error if device has no currentRegister or has not the same uuid
        if ($currentRegister->uuid !== $request->json('data.uuid')) {
            $message = 'The UUID does not correspond to the current register of the device. The request was ignored.';
            throw new InvalidRequestException($message);
        }

        // Close the register
        $currentRegister->close(
            $request->json('data.cashAmount'),
            $request->json('data.POSTRef'),
            $request->json('data.POSTAmount')
        );
        $closedAt = $request->json('data.closedAt', false);
        if ($closedAt && $closedAt <= Carbon::now()->getTimestamp()) {
            $currentRegister->closed_at = Carbon::createFromTimestamp($closedAt);
        }
        $currentRegister->save();

        // Bump the business version
        $business->bumpVersion([Business::MODIFICATION_REGISTER]);

        return $apiResponse;
    }

    /**
     * Validates the parameters for the 'close' controller method. Throws exception in case of validation error.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @param \Illuminate\Http\Request $request
     */
    public function validateClose(Request $request)
    {
        $this->validate($request, [
            'uuid' => 'bail|required|string',
            'cashAmount' => 'bail|required|numeric|min:0',
            'POSTRef' => 'bail|required|string',
            'POSTAmount' => 'bail|required|numeric|min:0',
            'closedAt' => 'sometimes|required|numeric|min:0|not_in:0',
        ]);
    }
}
