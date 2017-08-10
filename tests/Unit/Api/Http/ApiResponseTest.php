<?php

namespace Tests\Unit\Api\Http;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Register;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    /**
     * @var ApiResponse
     */
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

    public function testJsonSerializeBusiness()
    {
        $res = $this->response->jsonSerialize();
        $this->assertArrayNotHasKey('business', $res);

        $expected = [
            'rooms' => [
                ['id' => 123, 'name' => 'Test 1'],
                ['id' => 456, 'name' => 'Test 2'],
            ],
        ];

        $business = $this->getMockBuilder(Business::class)
            ->setMethods(['toArray'])
            ->getMock();
        $business->method('toArray')
            ->willReturn($expected);

        /** @noinspection PhpParamsInspection */
        $this->response->setBusiness($business);
        $res = $this->response->jsonSerialize();
        $this->assertEquals(
            $expected,
            $res['business']
        );

        $this->response->setBusiness(null);
        $res = $this->response->jsonSerialize();
        $this->assertArrayNotHasKey('business', $res);
    }

    public function testJsonSerializeDeviceRegister()
    {
        $res = $this->response->jsonSerialize();
        $this->assertArrayNotHasKey('deviceRegister', $res);

        $expected = [
            'cashMovements' => [
                ['id' => 123, 'amount' => 12.34],
                ['id' => 456, 'amount' => -8.69],
            ],
        ];

        $register = $this->getMockBuilder(Register::class)
            ->setMethods(['toArray'])
            ->getMock();
        $register->method('toArray')
            ->willReturn($expected);

        /** @noinspection PhpParamsInspection */
        $this->response->setDeviceRegister($register);
        $res = $this->response->jsonSerialize();
        $this->assertEquals($expected, $res['deviceRegister']);

        // Null is a valid values, contrary to other attributes
        $this->response->setDeviceRegister(null);
        $res = $this->response->jsonSerialize();
        $this->assertNull($res['deviceRegister']);

        // Explicitly unset the deviceRegister
        $this->response->unsetDeviceRegister();
        $res = $this->response->jsonSerialize();
        $this->assertArrayNotHasKey('deviceRegister', $res);
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

    public function testSetBusinessUpdatesData()
    {
        $expected = [
            'rooms' => [
                ['id' => 123, 'name' => 'Test 1'],
                ['id' => 456, 'name' => 'Test 2'],
            ],
        ];

        $business = $this->getMockBuilder(Business::class)
            ->setMethods(['toArray'])
            ->getMock();
        $business->method('toArray')
            ->willReturn($expected);

        /** @noinspection PhpParamsInspection */
        $this->response->setBusiness($business);
        $data = $this->response->getData(true);
        $this->assertEquals($expected, $data['business']);
    }

    public function testSetAndUnsetDeviceRegisterUpdatesData()
    {
        $expected = [
            'cashMovements' => [
                ['id' => 123, 'amount' => 12.34],
                ['id' => 456, 'amount' => -8.69],
            ],
        ];

        $register = $this->getMockBuilder(Register::class)
            ->setMethods(['toArray'])
            ->getMock();
        $register->method('toArray')
            ->willReturn($expected);

        /** @noinspection PhpParamsInspection */
        $this->response->setDeviceRegister($register);
        $data = $this->response->getData(true);
        $this->assertEquals($expected, $data['deviceRegister']);

        $this->response->setDeviceRegister(null);
        $data = $this->response->getData(true);
        $this->assertEquals(null, $data['deviceRegister']);

        $this->response->unsetDeviceRegister();
        $data = $this->response->getData(true);
        $this->assertArrayNotHasKey('deviceRegister', $data);
    }

    public function testAlwaysHasStatus()
    {
        $data = $this->response->getData();
        $this->assertEquals('ok', $data->status);
    }
}
