<?php

namespace Tests\Feature;

use App\ApiSession;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAPI;

    public function testLogout()
    {
        $device = $this->createDevice();
        $otherDevice  = $this->createDevice();
        $this->logDevice($device);
        $this->logDevice($device); // create another apisession
        $this->logDevice($otherDevice);

        $device->logout();
        $apiSessions = ApiSession::where('device_id', $device->id);
        $this->assertEquals(0, $apiSessions->count());

        // Test the other device is still logged
        $apiSessions = ApiSession::where('device_id', $otherDevice->id);
        $this->assertEquals(1, $apiSessions->count());
    }
}
