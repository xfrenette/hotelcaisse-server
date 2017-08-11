<?php

namespace Tests\Feature\Http\Middleware\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Http\Middleware\Api\AddUpdatedData;
use Illuminate\Http\Request;
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
        $this->business = Business::first();
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

    public function testHandleLoadsBusinessRelations()
    {
        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);

        $this->business->bumpVersion();
        $oldVersion = $this->business->version;
        $this->business->bumpVersion([Business::MODIFICATION_TAXES, Business::MODIFICATION_ROOMS]);

        $request = $this->makeRequest($oldVersion);
        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });

        $data = $res->getData(true);
        $this->assertEquals($this->business->rooms->count(), count($data['business']['rooms']));
        $this->assertEquals($this->business->taxes->count(), count($data['business']['taxes']));
    }

    public function testHandleLoadsRegisterRelations()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $register = $device->currentRegister;
        $this->mockApiAuthDevice($device);

        $this->business->bumpVersion();
        $oldVersion = $this->business->version;
        $this->business->bumpVersion([Business::MODIFICATION_REGISTER]);

        $request = $this->makeRequest($oldVersion);
        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });

        $data = $res->getData(true);
        $this->assertEquals($register->cashMovements->count(), count($data['deviceRegister']['cashMovements']));
    }

    public function testHandleWorksWithNoRegister()
    {
        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);

        $this->business->bumpVersion();
        $oldVersion = $this->business->version;
        $this->business->bumpVersion([Business::MODIFICATION_REGISTER]);

        $request = $this->makeRequest($oldVersion);
        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });

        $data = $res->getData(true);
        $this->assertNull($data['deviceRegister']);
    }
}
