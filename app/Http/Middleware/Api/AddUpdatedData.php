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
     * Handle an incoming request. The middleware might be executed after a request logs in the device, so we do our
     * check for authentication after the main request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Before executing the request (which can modify the Business version), we retrieve the current version of
        // Business. If we are not auth, we leave it to null.
        $initialVersion = ApiAuth::check() ? ApiAuth::getBusiness()->version : null;

        // Execute the request
        $response = $next($request);

        // Does nothing if not authenticated, even after the main request
        if (!ApiAuth::check()) {
            return $response;
        }

        // Does nothing if $response is not ApiResponse
        if (!($response instanceof ApiResponse)) {
            return $response;
        }

        $device = ApiAuth::getDevice();
        $business = ApiAuth::getBusiness();

        $requestVersion = $request->json('dataVersion');

        if (is_null($requestVersion) || is_null($initialVersion)) {
            $this->setResponseBusiness($response, null, $business);
            $this->setResponseDevice($response, null, $device);
        } elseif ($requestVersion !== $initialVersion) {
            $diff = $business->getVersionDiff($requestVersion);
            $this->setResponseBusiness($response, $diff, $business);
            $this->setResponseDevice($response, $diff, $device);
        }

        return $response;
    }

    /**
     * Sets the `business` attribute in the $response based on the $diff. Only set it if the $diff is null or if it
     * contains Business modifications and if `business` is not already set in the $response.
     *
     * @param \App\Api\Http\ApiResponse $response
     * @param {array|null} $diff
     * @param \App\Business $business
     */
    protected function setResponseBusiness(ApiResponse &$response, $diff, Business $business)
    {
        if (!is_null($diff) && !Business::containsRelatedModifications($diff)) {
            return;
        }

        // Do nothing if response already has business
        if (!is_null($response->getBusiness())) {
            return;
        }

        $partialBusiness = clone $business;
        if (!is_null($diff)) {
            $partialBusiness->setVisibleFromModifications($diff);
        }
        $partialBusiness->loadAllRelations();
        $response->setBusiness($partialBusiness);
    }

    /**
     * Sets the `device` attribute in the $response based on the $diff. Only set it if the $diff is null or if it
     * contains Device modifications and if `device` is not already set in the $response.
     *
     * @param \App\Api\Http\ApiResponse $response
     * @param {array|null} $diff
     * @param \App\Device $device
     */
    public function setResponseDevice(ApiResponse &$response, $diff, Device $device)
    {
        if (!is_null($diff) && !Device::containsRelatedModifications($diff)) {
            return;
        }

        // Does nothing if response already has device
        if (!is_null($response->getDevice())) {
            return;
        }
        $partialDevice = clone $device;
        $partialDevice->loadToArrayRelations();
        $response->setDevice($partialDevice);
    }
}
