<?php

namespace Tests\Unit\Http\Middleware\Api;

use App\Api\Auth\ApiAuth;
use App\Api\Http\ApiResponse;
use App\Business;
use App\Device;
use App\Http\Middleware\Api\AddUpdatedData;
use App\Register;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class AddUpdatedDataTest extends TestCase
{
    /**
     * @var AddUpdatedData
     */
    protected $middleware;

    protected function setUp()
    {
        parent::setUp();
        $this->middleware = new AddUpdatedData();
    }

    protected function makeRequest($version = null)
    {
        $request = Request::create('/test');

        if (!is_null($version)) {
            /** @noinspection PhpParamsInspection */
            $request->setJson(collect([
                'dataVersion' => $version,
            ]));
        }

        return $request;
    }

    protected function makeMockDeviceWithBusiness($version, $diff = [])
    {
        $device = new Device();
        $methods = ['getVersionAttribute', 'getVersionDiff', 'loadAllRelations'];

        $business = $this->getMockBuilder(Business::class)
            ->setMethods($methods)
            ->getMock();
        $business->method('getVersionAttribute')
            ->willReturn($version);
        $business->method('getVersionDiff')
            ->willReturn($diff);

        $device->business()->associate($business);
        return $device;
    }

    protected function mockApiAuthDevice($device)
    {
        $stub = $this->createMock(ApiAuth::class);
        $stub->method('getDevice')
            ->will($this->returnValue($device));
        $stub->method('check')
            ->will($this->returnValue(true));
        App::instance('apiauth', $stub);
        return $stub;
    }

    public function testWorksIfNotApiResponse()
    {
        $content = 'test content';
        $request = $this->makeRequest();
        $res = $this->middleware->handle($request, function () use ($content) {
            return $content;
        });
        $this->assertEquals($content, $res);
    }

    public function testDoesNothingIfNotAuth()
    {
        $request = $this->makeRequest();
        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });
        $this->assertArrayNotHasKey('business', $res->getData(true));
        $this->assertArrayNotHasKey('deviceRegister', $res->getData(true));
    }

    public function testHandleDoesNothingIfLatestVersion()
    {
        $version = 'v4';
        $request = $this->makeRequest($version);
        $device = $this->makeMockDeviceWithBusiness($version);
        $this->mockApiAuthDevice($device);

        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });

        $this->assertArrayNotHasKey('business', $res->getData(true));
        $this->assertArrayNotHasKey('deviceRegister', $res->getData(true));
    }

    public function testHandleDoesNothingIfNoVersion()
    {
        $version = 'v4';
        $request = $this->makeRequest();
        $device = $this->makeMockDeviceWithBusiness($version);
        $this->mockApiAuthDevice($device);

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
        $request = $this->makeRequest($requestVersion);
        $device = $this->makeMockDeviceWithBusiness($businessVersion, [Business::MODIFICATION_CATEGORIES]);
        $this->mockApiAuthDevice($device);

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
        $request = $this->makeRequest($requestVersion);
        $device = $this->makeMockDeviceWithBusiness($businessVersion, [Business::MODIFICATION_REGISTER]);
        $this->mockApiAuthDevice($device);

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

        $existingBusiness = $this->getMockBuilder(Business::class)
            ->setMethods(['toArray'])
            ->getMock();
        $existingBusiness->method('toArray')
            ->willReturn($businessData);

        $existingRegister = $this->getMockBuilder(Register::class)
            ->setMethods(['toArray'])
            ->getMock();
        $existingRegister->method('toArray')
            ->willReturn($registerData);

        $requestVersion = 'v3';
        $businessVersion = 'v4';
        $request = $this->makeRequest($requestVersion);
        $device = $this->makeMockDeviceWithBusiness(
            $businessVersion,
            [Business::MODIFICATION_REGISTER, Business::MODIFICATION_CATEGORIES]
        );
        $this->mockApiAuthDevice($device);

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
