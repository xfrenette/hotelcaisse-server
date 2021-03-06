<?php

namespace App\Api\Http;

use App\Device;
use Illuminate\Http\JsonResponse;
use JsonSerializable;

class ApiResponse extends JsonResponse implements JsonSerializable
{
    const ERROR_AUTH_FAILED = 'auth:failed';
    const ERROR_NOT_FOUND = 'request:notFound';
    const ERROR_CLIENT_ERROR = 'request:error';
    const ERROR_SERVER_ERROR = 'server:error';

    /**
     * @var string
     */
    protected $token = null;

    /**
     * @var string
     */
    protected $dataVersion = null;

    /**
     * @var mixed
     */
    protected $responseData = null;

    /**
     * @var string
     */
    protected $errorCode = null;

    /**
     * @var string
     */
    protected $errorMessage = null;

    /**
     * New version of the Business. Can be a partial Business (ex: having only `products`)
     * @var \App\Business
     */
    protected $business = null;

    /**
     * New version of the Device and all its data (with its current register).
     *
     * @var \App\Device
     */
    protected $device = null;

    /**
     * @param integer $status
     * @param array $headers
     * @param integer $options
     */
    public function __construct($status = 200, $headers = [], $options = 0)
    {
        parent::__construct($this->jsonSerialize(), $status, $headers, $options);
    }

    /**
     * Returns true if the ApiResponse is in error.
     *
     * @return boolean
     */
    public function inError()
    {
        return !is_null($this->errorCode);
    }

    /**
     * Sets the token and updates the data
     *
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
        $this->updateData();
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Sets the dataVersion and updates the data
     *
     * @param string $dataVersion
     */
    public function setDataVersion($dataVersion)
    {
        $this->dataVersion = $dataVersion;
        $this->updateData();
    }

    /**
     * @return string
     */
    public function getDataVersion()
    {
        return $this->dataVersion;
    }

    /**
     * Sets the object returned in the JSON 'data' attribute and updates the data
     *
     * @param mixed $data
     */
    public function setResponseData($data)
    {
        $this->responseData = $data;
        $this->updateData();
    }

    /**
     * @return mixed
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * Sets the error code and message. If null is passed, removes the error. Updates the data.
     *
     * @param string $code
     * @param string $message
     */
    public function setError($code, $message = null)
    {
        $this->errorCode = $code;
        $this->errorMessage = $message;
        $this->updateData();
    }

    /**
     * Sets the Business (can be a partial Business). If null is passed, removes the Business. Updates the data.
     *
     * @param \App\Business $business
     */
    public function setBusiness($business)
    {
        $this->business = $business;
        $this->updateData();
    }

    /**
     * @return \App\Business
     */
    public function getBusiness()
    {
        return $this->business;
    }

    /**
     * Sets the device. Updates the data.
     *
     * @param \App\Device $device
     */
    public function setDevice(Device $device = null)
    {
        $this->device = $device;
        $this->updateData();
    }

    /**
     * @return \App\Device
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Updates the response's content by updating the json data.
     */
    public function updateData()
    {
        $this->setData($this);
    }

    /**
     * Returns an associative array of data that can be serialized in json. Returns a array with the
     * following keys:
     * - status: 'ok' or 'error' if in error
     * - token: only if the $this->token is a string
     * - dataVersion: only if the $this->dataVersion is a string
     * - data: only if the $this->data is not null
     * - error (only if status is 'error'): array with 'code' and 'message'
     *      keys.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $data = [
            'status' => $this->inError() ? 'error' : 'ok',
        ];

        if (is_string($this->token)) {
            $data['token'] = $this->token;
        }

        if (is_string($this->dataVersion)) {
            $data['dataVersion'] = $this->dataVersion;
        }

        if (!is_null($this->responseData)) {
            $data['data'] = $this->responseData;
        }

        if ($this->inError()) {
            $data['error'] = [
                'code' => $this->errorCode,
                'message' => $this->errorMessage,
            ];
        }

        if (!is_null($this->business)) {
            $data['business'] = $this->business->toArray();
        }

        if (!is_null($this->device)) {
            $data['device'] = $this->device->toArray();
        }

        return $data;
    }
}
