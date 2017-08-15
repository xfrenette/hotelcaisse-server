<?php

namespace Tests\Unit\Http\Middleware\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Device;
use App\Http\Middleware\Api\AddDataVersion;
use App\Team;
use Illuminate\Http\Request;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class AddDataVersionTest extends TestCase
{
    use InteractsWithAPI;

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
        $team = new Team();
        $team->business()->associate($business);
        $device->team()->associate($team);
        return $device;
    }

    protected function mockApiAuthDevice($device)
    {
        $stub = $this->mockApiAuth();
        $stub->shouldReceive('getDevice')->andReturn($this->returnValue($device));
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
        $business = \Mockery::mock(Business::class)->makePartial();
        $business->shouldReceive('getVersionAttribute')->andReturn($version);
        $apiAuth = $this->mockApiAuth();
        $apiAuth->shouldReceive('getBusiness')->andReturn($business);

        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });

        $this->assertEquals($version, $res->getDataVersion());
    }
}
