<?php

namespace App\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\CashMovement;
use App\Support\Facades\ApiAuth;
use Illuminate\Http\Request;

class CashMovementsController extends ApiController
{
    /**
     * Controller method for /cashMovements/add (see docs/api.md)
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Api\Http\ApiResponse
     */
    public function add(Request $request)
    {
        $this->validate($request, [
            'uuid' => 'bail|required|string|unique:cash_movements',
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
        $cashMovement = new CashMovement([
            'uuid' => $request->json('data.uuid'),
            'note' => $request->json('data.note'),
            'amount' => $request->json('data.amount'),
        ]);
        $cashMovement->register()->associate($device->currentRegister);
        $cashMovement->save();

        // Bump the version of the Business
        $device->business->bumpVersion([Business::MODIFICATION_REGISTER]);

        return new ApiResponse();
    }
    /**
     * Controller method for /cashMovements/delete (see docs/api.md)
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Api\Http\ApiResponse
     */
    public function delete(Request $request)
    {
        $this->validate($request, [
            'uuid' => 'bail|required|string',
        ]);

        $apiResponse = new ApiResponse();
        $device = ApiAuth::getDevice();

        if (!$device->currentRegister || !$device->currentRegister->opened) {
            $apiResponse->setError(
                ApiResponse::ERROR_CLIENT_ERROR,
                'The device doesn\'t have an opened register'
            );
            return $apiResponse;
        }

        // Try to find the CashMovement in the current Register
        $cashMovements = ApiAuth::getDevice()
            ->currentRegister
            ->cashMovements()
            ->where('uuid', $request->json('data.uuid'));

        // If no CashMovement found, return an error
        if ($cashMovements->count() === 0) {
            $apiResponse->setError(
                ApiResponse::ERROR_CLIENT_ERROR,
                'The CashMovement with this UUID doesn\'t exist.'
            );
            return $apiResponse;
        }

        // Delete the cash movement
        $cashMovements->delete();

        // Bump the business version
        $device->business->bumpVersion([Business::MODIFICATION_REGISTER]);

        return $apiResponse;
    }
}
