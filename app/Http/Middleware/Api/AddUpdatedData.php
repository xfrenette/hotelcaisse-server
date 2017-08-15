<?php

namespace App\Http\Middleware\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Register;
use App\Support\Facades\ApiAuth;
use Closure;

/**
 * Middleware that checks if updates to the data (business or deviceRegister) should be added to the response. Does
 * nothing if the device is not authenticated.
 *
 * Class AddUpdatedData
 * @package App\Http\Middleware\Api
 */
class AddUpdatedData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Does nothing if not authenticated
        if (!ApiAuth::check()) {
            return $next($request);
        }

        $device = ApiAuth::getDevice();
        $business = ApiAuth::getBusiness();

        // Before executing the request (which can modify the Business version), we retrieve the current version of
        // Business.
        $initialVersion = $business->version;

        // Execute the request
        $response = $next($request);

        // Does nothing if $response is not ApiResponse
        if (!($response instanceof ApiResponse)) {
            return $response;
        }

        $requestVersion = $request->json('dataVersion');

        // If the same version, we do nothing
        if (!is_null($requestVersion) && $requestVersion !== $initialVersion) {
            $diff = $business->getVersionDiff($requestVersion);
            $this->setResponseBusiness($response, $diff, $business);
            $this->setResponseDeviceRegister($response, $diff, $device->currentRegister);
        }

        return $response;
    }

    /**
     * Sets the `business` attribute in the $response based on the $diff. Only set it if the $diff contains Business
     * modifications and if `business` is not already set in the $response.
     *
     * @param \App\Api\Http\ApiResponse $response
     * @param array $diff
     * @param \App\Business $business
     */
    protected function setResponseBusiness(ApiResponse &$response, $diff, Business $business)
    {
        if (!Business::containsRelatedModifications($diff)) {
            return;
        }

        // Do nothing if response already has business
        if (!is_null($response->getBusiness())) {
            return;
        }

        $partialBusiness = clone $business;
        $partialBusiness->setVisibleFromModifications($diff);
        $partialBusiness->loadAllRelations();
        $response->setBusiness($partialBusiness);
    }

    /**
     * Sets the `deviceRegister` attribute in the $response based on the $diff. Only set it if the $diff contains
     * Register modifications and if `deviceRegister` is not already set in the $response. Note that `null` is a valid
     * $register value.
     *
     * @param \App\Api\Http\ApiResponse $response
     * @param array $diff
     * @param \App\Register|null $register
     */
    public function setResponseDeviceRegister(ApiResponse &$response, $diff, $register)
    {
        if (!Register::containsRelatedModifications($diff)) {
            return;
        }

        // Does nothing if response already has deviceRegister
        if ($response->hasDeviceRegister()) {
            return;
        }

        if (!is_null($register)) {
            $register->loadAllRelations();
        }

        $response->setDeviceRegister($register);
    }
}
