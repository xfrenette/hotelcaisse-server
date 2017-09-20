<?php

namespace Tests\Feature\Http\Middleware\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Http\Middleware\Api\AddUpdatedData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class AddUpdatedDataTest extends TestCase
{
    use DatabaseTransactions;
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

    protected function mockRequestWithVersion($version = null)
    {
        return $this->mockRequest(['dataVersion' => $version]);
    }

    public function testHandleLoadsBusinessRelations()
    {
        $device = $this->createDevice();
        $this->logDevice($device);

        $business = $device->team->business;
        $business->bumpVersion();
        $oldVersion = $business->version;
        $business->bumpVersion([Business::MODIFICATION_TRANSACTION_MODES, Business::MODIFICATION_ROOMS]);

        $request = $this->mockRequestWithVersion($oldVersion);
        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });

        $data = $res->getData(true);
        $this->assertEquals($business->rooms->count(), count($data['business']['rooms']));
        $this->assertEquals($business->transactionModes->count(), count($data['business']['transactionModes']));
    }

    public function testHandleLoadsDeviceRelations()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);

        $business = $device->team->business;
        $business->bumpVersion();
        $oldVersion = $business->version;
        $business->bumpVersion([Business::MODIFICATION_REGISTER]);

        $request = $this->mockRequestWithVersion($oldVersion);
        $response = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });

        $this->assertTrue($response->getDevice()->relationLoaded('currentRegister'));
        $this->assertTrue($response->getDevice()->currentRegister->relationLoaded('cashMovements'));
    }
}
