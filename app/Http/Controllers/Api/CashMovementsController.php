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
        $this->validateAdd($request);
        $this->validateRegisterOpened();

        $device = ApiAuth::getDevice();
        $business = ApiAuth::getBusiness();

        // Create a CashMovement and add it to the register
        $cashMovement = new CashMovement([
            'uuid' => $request->json('data.uuid'),
            'note' => $request->json('data.note'),
            'amount' => $request->json('data.amount'),
        ]);
        $cashMovement->register()->associate($device->currentRegister);
        $createdAt = $request->json('data.createdAt', false);
        if ($createdAt && $createdAt <= Carbon::now()->getTimestamp()) {
            $cashMovement->created_at = Carbon::createFromTimestamp($createdAt);
        }
        $cashMovement->save();

        // Bump the version of the Business
        $business->bumpVersion([Business::MODIFICATION_REGISTER]);

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
        $this->validateDelete($request);
        $this->validateRegisterOpened();

        $apiResponse = new ApiResponse();
        $business = ApiAuth::getBusiness();

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
        $business->bumpVersion([Business::MODIFICATION_REGISTER]);

        return $apiResponse;
    }

    /**
     * Validate the Request for the `add` method. If valid, does nothing, else throws a ValidationException.
     *
     * @param \Illuminate\Http\Request $request
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateAdd(Request $request)
    {
        $this->validate($request, [
            'uuid' => 'bail|required|string|unique:cash_movements',
            'note' => 'bail|required|string',
            'amount' => 'bail|required|numeric|not_in:0',
            'createdAt' => 'sometimes|required|numeric|min:0|not_in:0',
        ]);
    }

    /**
     * Validate the Request for the `delete` method. If valid, does nothing, else throws a ValidationException.
     *
     * @param \Illuminate\Http\Request $request
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateDelete(Request $request)
    {
        $this->validate($request, [
            'uuid' => 'bail|required|string',
        ]);
    }
}
