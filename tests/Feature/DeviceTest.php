<?php

namespace Tests\Feature;

use App\ApiSession;
use App\DeviceApproval;
use App\Register;
use Carbon\Carbon;
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

    public function testNextRegisterNumberWithRegisters()
    {
        $device = $this->createDevice();

        $register1 = factory(Register::class)->make();
        $register1->device()->associate($device);
        $register1->number = 1;
        $register1->opened_at = Carbon::yesterday()->subSeconds(2);
        $register1->save();

        $register2 = factory(Register::class)->make();
        $register2->device()->associate($device);
        $register2->number = 2;
        $register2->opened_at = Carbon::yesterday();
        $register2->save();

        $this->assertEquals($register2->number + 1, $device->nextRegisterNumber);
    }

    public function testNextRegisterNumberWithoutRegisters()
    {
        $device = $this->createDevice();
        $device->initial_register_number = 235;
        $this->assertEquals($device->initial_register_number, $device->nextRegisterNumber);
    }
}
