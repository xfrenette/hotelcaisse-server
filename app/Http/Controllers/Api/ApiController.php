<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Api\InvalidRegisterStateException;
use App\Support\Facades\ApiAuth;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class ApiController extends BaseController
{
    use DispatchesJobs, ValidatesRequests;

    /**
     * Validate the 'data' attribute's values in the JSON request with the given rules.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     */
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $data = $this->getRequestData($request);

        $validator = $this->getValidationFactory()->make($data, $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            $this->throwValidationException($request, $validator);
        }
    }

    /**
     * Validates that the current device has an opened register.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateRegisterOpened()
    {
        $valid = false;

        if (ApiAuth::check()) {
            $device = ApiAuth::getDevice();
            $valid = $device->isCurrentRegisterOpened;
        }

        if (!$valid) {
            throw new InvalidRegisterStateException('The device must have an opened register.');
        }
    }

    /**
     * Validates that the current device doesn't have an opened register (register is not opened, or no register at
     * all).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateRegisterNotOpened()
    {
        $valid = true;

        if (ApiAuth::check()) {
            $device = ApiAuth::getDevice();
            $valid = !$device->isCurrentRegisterOpened;
        }

        if (!$valid) {
            throw new InvalidRegisterStateException('The device must *not* have an opened register.');
        }
    }

    /**
     * Makes a Validator that can be used for manual validation or to add conditional rules.
     *
     * @param \Illuminate\Http\Request $request
     * @param $rules
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function makeValidator(Request $request, $rules)
    {
        $data = $this->getRequestData($request);

        return $this->getValidationFactory()->make($data, $rules);
    }

    /**
     * Returns the data object in the request. Returns an empty array if no data.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|mixed
     */
    public function getRequestData(Request $request)
    {
        $data = $request->json('data');

        if (is_null($data)) {
            $data = [];
        }

        return $data;
    }
}
