<?php

namespace Tests\Unit\Http\Middleware\Api;

use App\Api\Http\ApiResponse;
use App\Business;
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
        $this->assertArrayNotHasKey('deviceRegister', $res->getData(true));
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
        $this->assertArrayNotHasKey('deviceRegister', $res->getData(true));
    }

    public function testHandleDoesNothingIfNoVersion()
    {
        $version = 'v4';
        $request = $this->mockRequest();
        $business = $this->mockBusinessWithVersion($version);
        $apiAuth = $this->mockApiAuth();
        $apiAuth->shouldReceive('getBusiness')->andReturn($business);

        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });

        $this->assertArrayNotHasKey('business', $res->getData(true));
        $this->assertArrayNotHasKey('deviceRegister', $res->getData(true));
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
        $this->assertArrayNotHasKey('deviceRegister', $res->getData(true));
    }

    public function testHandleAddsOnlyRegisterWhenOnlyRegisterModifications()
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
        $this->assertArrayHasKey('deviceRegister', $res->getData(true));
    }

    public function testHandleDoesntChangeIfAlreadyPresent()
    {
        $businessData = ['test-business' => true];
        $registerData = ['test-register' => true];

        $existingBusiness = \Mockery::mock(Business::class)->makePartial();
        $existingBusiness->shouldReceive('toArray')->andReturn($businessData);

        $existingRegister = \Mockery::mock(Register::class)->makePartial();
        $existingRegister->shouldReceive('toArray')->andReturn($registerData);

        $device = $this->mockDevice();
        $device->setRelation('currentRegister', new Register());

        $requestVersion = 'v3';
        $businessVersion = 'v4';
        $request = $this->mockRequestWithVersion($requestVersion);
        $business = $this->mockBusinessWithVersion($businessVersion, [Business::MODIFICATION_REGISTER]);
        $apiAuth = $this->mockApiAuth();
        $apiAuth->shouldReceive('getDevice')->andReturn($device);
        $apiAuth->shouldReceive('getBusiness')->andReturn($business);

        $res = $this->middleware->handle($request, function () use ($existingBusiness, $existingRegister) {
            $response = new ApiResponse();
            /** @noinspection PhpParamsInspection */
            $response->setBusiness($existingBusiness);
            $response->setDeviceRegister($existingRegister);
            return $response;
        });

        $data = $res->getData(true);
        $this->assertArraySubset([
            'business' => $businessData,
            'deviceRegister' => $registerData,
        ], $data);
    }
}
