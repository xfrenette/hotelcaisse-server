<?php

namespace Tests\Unit\Api\Http;

use Tests\TestCase;
use App\Api\Http\ApiResponse;

class ApiResponseTest extends TestCase
{
    protected $response;

    public function setUp()
    {
        parent::setUp();
        $this->response = new ApiResponse();
    }

    public function testSetError()
    {
        $this->response->setError('test', 'test');
        $this->assertTrue($this->response->inError());

        $this->response->setError(null);
        $this->assertFalse($this->response->inError());
    }

    public function testJsonSerializeToken()
    {
        $res = $this->response->jsonSerialize();
        $this->assertArrayNotHasKey('token', $res);

        $testToken = 'test';
        $this->response->setToken($testToken);
        $res = $this->response->jsonSerialize();
        $this->assertEquals($testToken, $res['token']);
    }

    public function testJsonSerializeDataVersion()
    {
        $res = $this->response->jsonSerialize();
        $this->assertArrayNotHasKey('dataVersion', $res);

        $testDataVersion = '2';
        $this->response->setDataVersion($testDataVersion);
        $res = $this->response->jsonSerialize();
        $this->assertEquals($testDataVersion, $res['dataVersion']);
    }

    public function testJsonSerializeData()
    {
        $res = $this->response->jsonSerialize();
        $this->assertArrayNotHasKey('data', $res);

        $testData = ['a' => 'b'];
        $this->response->setResponseData($testData);
        $res = $this->response->jsonSerialize();
        $this->assertEquals($testData, $res['data']);
    }

    public function testJsonSerializeStatus()
    {
        $res = $this->response->jsonSerialize();
        $this->assertEquals('ok', $res['status']);

        $this->response->setError('test');
        $res = $this->response->jsonSerialize();
        $this->assertEquals('error', $res['status']);

        $this->response->setError(null);
        $res = $this->response->jsonSerialize();
        $this->assertEquals('ok', $res['status']);
    }

    public function testJsonSerializeError()
    {
        $res = $this->response->jsonSerialize();
        $this->assertArrayNotHasKey('error', $res);

        $errorCode = 'test-code';
        $errorMessage = 'test-message';
        $this->response->setError($errorCode, $errorMessage);
        $res = $this->response->jsonSerialize();
        $this->assertEquals(
            ['code' => $errorCode, 'message' => $errorMessage],
            $res['error']
        );

        $this->response->setError(null);
        $res = $this->response->jsonSerialize();
        $this->assertArrayNotHasKey('error', $res);
    }

    public function testSetTokenUpdatesData()
    {
        $testValue = 'test';
        $this->response->setToken($testValue);
        $data = $this->response->getData();
        $this->assertEquals($testValue, $data->token);
    }

    public function testSetDataVersionUpdatesData()
    {
        $testValue = 'test';
        $this->response->setDataVersion($testValue);
        $data = $this->response->getData();
        $this->assertEquals($testValue, $data->dataVersion);
    }

    public function testSetResponseDataUpdatesData()
    {
        $testValue = 'test';
        $this->response->setResponseData($testValue);
        $data = $this->response->getData();
        $this->assertEquals($testValue, $data->data);
    }

    public function testSetErrorUpdatesData()
    {
        $testValue = 'test';
        $this->response->setError($testValue);
        $data = $this->response->getData();
        $this->assertEquals($testValue, $data->error->code);
        $this->assertEquals('error', $data->status);
    }
}
