<?php

namespace Tests\Feature;

use App\ApiSession;
use App\DeviceApproval;
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

    public function testClearApprovals()
    {
        $device = $this->createDevice();

        for ($i = 0; $i < 2; $i++) {
            $deviceApproval = factory(DeviceApproval::class)->make();
            $deviceApproval->device()->associate($device);
            $deviceApproval->save();
        }

        $this->assertEquals(2, $device->approvals()->count());

        $device->clearApprovals();

        $this->assertEquals(0, $device->approvals()->count());
    }

    public function testCreateApproval()
    {
        $passcode = '0065';
        $device = $this->createDevice();
        $approval = $device->createApproval($passcode);
        $expected = $device->approvals()->first();

        $this->assertEquals(1, $device->approvals()->count());

        $this->assertEquals($expected->id, $approval->id);
        $this->assertTrue($expected->check($passcode));
    }
}
