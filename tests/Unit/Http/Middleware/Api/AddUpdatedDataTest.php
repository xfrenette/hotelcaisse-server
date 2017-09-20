<?php

namespace Tests\Unit\Http\Middleware\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Device;
use App\Http\Middleware\Api\AddUpdatedData;
use App\Register;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class AddUpdatedDataTest extends TestCase
{
    use InteractsWithAPI;

    /**
     * @var AddUpdatedData
     */
    protected $middleware;

    protected function setUp()
    {
        parent::setUp();
        $this->middleware = new AddUpdatedData();
    }

    protected function mockRequestWithVersion($version)
    {
        return $this->mockRequest(['dataVersion' => $version]);
    }

    protected function mockBusinessWithVersion($version, $diff = [])
    {
        $business = \Mockery::mock(Business::class)->makePartial();
        $business->shouldReceive('getVersionAttribute')->andReturn($version);
        $business->shouldReceive('getVersionDiff')->andReturn($diff);
        $business->shouldReceive('toArray')->andReturn([]);
        $business->shouldReceive('loadAllRelations')->andReturn();
        return $business;
    }

    public function testWorksIfNotApiResponse()
    {
        $content = 'test content';
        $request = $this->mockRequest();
        $res = $this->middleware->handle($request, function () use ($content) {
            return $content;
        });
        $this->assertEquals($content, $res);
    }

    public function testDoesNothingIfNotAuth()
    {
        $request = $this->mockRequest();
        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });
        $this->assertArrayNotHasKey('business', $res->getData(true));
        $this->assertArrayNotHasKey('device', $res->getData(true));
    }

    public function testHandleDoesNothingIfLatestVersion()
    {
        $version = 'v4';
        $request = $this->mockRequestWithVersion($version);
        $business = $this->mockBusinessWithVersion($version);
        $apiAuth = $this->mockApiAuth();
        $apiAuth->shouldReceive('getBusiness')->andReturn($business);

        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });

        $this->assertArrayNotHasKey('business', $res->getData(true));
        $this->assertArrayNotHasKey('device', $res->getData(true));
    }

    public function testHandleAddsOnlyBusinessWhenOnlyBusinessModifications()
    {
        $requestVersion = 'v3';
        $businessVersion = 'v4';
        $request = $this->mockRequestWithVersion($requestVersion);
        $business = $this->mockBusinessWithVersion($businessVersion, [Business::MODIFICATION_CATEGORIES]);
        $device = $this->mockDevice();
        $apiAuth = $this->mockApiAuth();
        $apiAuth->shouldReceive('getDevice')->andReturn($device);
        $apiAuth->shouldReceive('getBusiness')->andReturn($business);

        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });

        $this->assertArrayHasKey('business', $res->getData(true));
        $this->assertArrayNotHasKey('device', $res->getData(true));
    }

    public function testHandleAddsDataWithNullRequestVersion()
    {
        $request = $this->mockRequest();
        $business = $this->mockBusinessWithVersion('v4', [Business::MODIFICATION_CATEGORIES]);
        $device = $this->mockDevice();
        $apiAuth = $this->mockApiAuth();
        $apiAuth->shouldReceive('getDevice')->andReturn($device);
        $apiAuth->shouldReceive('getBusiness')->andReturn($business);

        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });

        $this->assertEquals($business, $res->getBusiness());
        $this->assertEquals($device, $res->getDevice());
    }

    public function testHandleAddsOnlyDeviceWhenOnlyDeviceModifications()
    {
        $requestVersion = 'v3';
        $businessVersion = 'v4';
        $request = $this->mockRequestWithVersion($requestVersion);
        $business = $this->mockBusinessWithVersion($businessVersion, [Business::MODIFICATION_REGISTER]);
        $device = $this->mockDevice();
        $apiAuth = $this->mockApiAuth();
        $apiAuth->shouldReceive('getDevice')->andReturn($device);
        $apiAuth->shouldReceive('getBusiness')->andReturn($business);

        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });

        $this->assertArrayNotHasKey('business', $res->getData(true));
        $this->assertArrayHasKey('device', $res->getData(true));
    }

    public function testHandleDoesntChangeIfAlreadyPresent()
    {
        $businessData = ['test-business' => true];
        $deviceData = ['test-device' => true];

        $existingBusiness = \Mockery::mock(Business::class)->makePartial();
        $existingBusiness->shouldReceive('toArray')->andReturn($businessData);

        $existingDevice = \Mockery::mock(Device::class)->makePartial();
        $existingDevice->shouldReceive('toArray')->andReturn($deviceData);

        $device = $this->mockDevice();
        $device->setRelation('currentRegister', new Register());

        $requestVersion = 'v3';
        $businessVersion = 'v4';
        $request = $this->mockRequestWithVersion($requestVersion);
        $business = $this->mockBusinessWithVersion($businessVersion, [Business::MODIFICATION_REGISTER]);
        $apiAuth = $this->mockApiAuth();
        $apiAuth->shouldReceive('getDevice')->andReturn($device);
        $apiAuth->shouldReceive('getBusiness')->andReturn($business);

        $res = $this->middleware->handle($request, function () use ($existingBusiness, $existingDevice) {
            $response = new ApiResponse();
            /** @noinspection PhpParamsInspection */
            $response->setBusiness($existingBusiness);
            $response->setDevice($existingDevice);
            return $response;
        });

        $data = $res->getData(true);
        $this->assertArraySubset([
            'business' => $businessData,
            'device' => $deviceData,
        ], $data);
    }
}
