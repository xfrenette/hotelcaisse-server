<?php

namespace App\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\CashMovement;
use App\Support\Facades\ApiAuth;
use Illuminate\Http\Request;

class CashMovementsController extends ApiController
{
    public function add(Request $request)
    {
        $this->validate($request, [
            'note' => 'bail|required|string',
            'amount' => 'bail|required|numeric|not_in:0',
        ]);

        $apiResponse = new ApiResponse();
        $device = ApiAuth::getDevice();
        $register = $device->currentRegister;

        // A register must be opened on the device
        if (is_null($register) || !$register->opened) {
            $apiResponse->setError(
                ApiResponse::ERROR_CLIENT_ERROR,
                'The device must have an opened register. This request is ignored.'
            );
            return $apiResponse;
        }

        // Create a CashMovement and add it to the register
        $cashMovement = new CashMovement();
        $cashMovement->note = $request->json('data.note');
        $cashMovement->amount = $request->json('data.amount');
        $cashMovement->register()->associate($device->currentRegister);
        $cashMovement->save();

        // Bump the version of the Business
        $device->business->bumpVersion([Business::MODIFICATION_REGISTER]);

        return new ApiResponse();
    }
}
