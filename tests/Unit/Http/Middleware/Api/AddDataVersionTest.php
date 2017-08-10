<?php

namespace Tests\Unit\Http\Middleware\Api;

use App\Api\Auth\ApiAuth;
use App\Api\Http\ApiResponse;
use App\Business;
use App\Device;
use App\Http\Middleware\Api\AddDataVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class AddDataVersionTest extends TestCase
{
    /**
     * @var AddDataVersion
     */
    protected $middleware;

    protected function setUp()
    {
        parent::setUp();
        $this->middleware = new AddDataVersion();
    }

    protected function makeRequest()
    {
        return Request::create('/test');
    }

    protected function makeMockDeviceWithBusiness($version)
    {
        $device = new Device();
        $business = $this->getMockBuilder(Business::class)
            ->setMethods(['getVersionAttribute'])
            ->getMock();
        $business->method('getVersionAttribute')
            ->willReturn($version);

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
        $request = $this->makeRequest();
        $testContent = 'string content';
        $res = $this->middleware->handle($request, function () use ($testContent) {
            return $testContent;
        });
        $this->assertEquals($testContent, $res);
    }

    public function testDoesNothingIfNotAuth()
    {
        $request = $this->makeRequest();
        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });
        $this->assertNull($res->getDataVersion());
    }

    public function testSetDataVersionOnApiResponse()
    {
        $version = 'test-version';
        $request = $this->makeRequest();
        $device = $this->makeMockDeviceWithBusiness($version);
        $this->mockApiAuthDevice($device);
        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });
        $this->assertEquals($version, $res->getDataVersion());
    }
}
