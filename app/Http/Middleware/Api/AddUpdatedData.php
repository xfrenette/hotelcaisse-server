<?php

namespace App\Http\Middleware\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Device;
use App\Support\Facades\ApiAuth;
use Closure;

/**
 * Middleware that checks if updates to the data (business or device) should be added to the response. Does
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
            $this->setResponseDevice($response, $diff, $device);
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
     * Sets the `device` attribute in the $response based on the $diff. Only set it if the $diff contains
     * Device modifications and if `device` is not already set in the $response.
     *
     * @param \App\Api\Http\ApiResponse $response
     * @param array $diff
     * @param \App\Device $device
     */
    public function setResponseDevice(ApiResponse &$response, $diff, Device $device)
    {
        if (!Device::containsRelatedModifications($diff)) {
            return;
        }

        // Does nothing if response already has device
        if (!is_null($response->getDevice())) {
            return;
        }

        $device->loadToArrayRelations();

        $response->setDevice($device);
    }
}
