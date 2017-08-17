<?php

namespace Tests\Feature\Http\Controllers;

use App\ApiSession;
use App\DeviceApproval;
use App\Http\Controllers\DevicesController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class DevicesControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAPI;

    protected $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->controller = new DevicesController();
    }

    public function testCode()
    {
        $device = $this->createDevice();
        // Make a first approval
        $oldApproval = $device->createApproval('1234');
        // Log the device
        $this->logDevice($device);

        $request = $this->mockRequest();
        $this->controller->code($request, $device);

        // Check ApiSession with $device are cleared
        $this->assertEquals(0, ApiSession::where('device_id', $device->id)->count());

        // Check old approval is deleted
        $this->assertEquals(0, DeviceApproval::where('id', $oldApproval->id)->count());
    }

    public function testRevoke()
    {
        $device = $this->createDevice();
        $this->logDevice($device);

        $request = $this->mockRequest();
        $this->controller->revoke($request, $device);

        $this->assertEquals(0, ApiSession::where('device_id', $device->id)->count());
    }
}
