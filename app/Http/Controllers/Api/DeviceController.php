<?php

namespace App\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Exceptions\Api\InvalidRequestException;
use App\Http\Controllers\Controller;
use App\Support\Facades\ApiAuth;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function register(Request $request, Business $business)
    {
        $passcode = $request->json('data.passcode');

        if (is_null($passcode)) {
            throw new InvalidRequestException('passcode attribute not set');
        }

        $apiResponse = new ApiResponse();
        $success = ApiAuth::attemptRegister($passcode, $business);

        if (!$success) {
            $apiResponse->setError(ApiResponse::ERROR_AUTH_FAILED, 'passcode invalid for business');
        }

        return $apiResponse;
    }
}
