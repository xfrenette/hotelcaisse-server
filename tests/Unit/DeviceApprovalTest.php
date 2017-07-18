<?php

namespace Unit;

use App\DeviceApproval;
use Carbon\Carbon;
use Tests\TestCase;

class DeviceApprovalTest extends TestCase
{
    protected $deviceApproval;

    protected function setUp()
    {
        parent::setUp();
        $this->deviceApproval = factory(DeviceApproval::class)->make();
    }

    public function testExpired()
    {
        $this->deviceApproval->expires_at = Carbon::now()->addSecond(1);
        $this->assertFalse($this->deviceApproval->expired());

        $this->deviceApproval->expires_at = Carbon::now();
        $this->assertTrue($this->deviceApproval->expired());

        $this->deviceApproval->expires_at = Carbon::now()->subSecond(1);
        $this->assertTrue($this->deviceApproval->expired());
    }

    public function testExpire()
    {
        // First make sure it is not expired
        $this->deviceApproval->expires_at = Carbon::tomorrow();
        $this->deviceApproval->expire();
        $this->assertTrue($this->deviceApproval->expired());
    }

    public function testSetPasscodeHashesIt()
    {
        $this->deviceApproval->passcode = '1234';
        $this->assertNotEquals('1234', $this->deviceApproval->passcode);
    }
}
