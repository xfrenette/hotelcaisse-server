<?php

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class DeviceDataControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAPI;
    use WithoutMiddleware;

    public function testHandleSetsBusiness()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->team->business;
        $this->logDevice($device);

        $response = $this->queryAPI('api.deviceData');

        // Make sure at least one relation is loaded (normally, the controller will have loaded all relations)
        $business->load('rooms');
        $expected = $business->toArray();

        $response->assertJson([
            'status' => 'ok',
            'business' => $expected,
        ]);
    }

    public function testHandleSetsDeviceRegister()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);

        $response = $this->queryAPI('api.deviceData');

        $register = $device->currentRegister;
        // Make sure the cashMovements relation is loaded before generating expected result
        $register->load('cashMovements');
        $expected = $register->toArray();

        $response->assertJson([
            'status' => 'ok',
            'deviceRegister' => $expected,
        ]);
    }

    public function testHandleSetsNullDeviceRegisterWhenNull()
    {
        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);

        $response = $this->queryAPI('api.deviceData');

        $response->assertJson([
            'status' => 'ok',
            'deviceRegister' => null,
        ]);
    }
}
