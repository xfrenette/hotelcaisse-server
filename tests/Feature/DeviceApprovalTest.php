<?php

namespace Tests\Feature;

use App\DeviceApproval;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class DeviceApprovalTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAPI;

    public function testScopeValid()
    {
        $device = $this->createDevice();
        $team = $device->team;
        $approval = $device->createApproval('1234');
        $approval->until(Carbon::now()->addSeconds(10));
        $approval->save();

        $this->assertEquals(1, $team->deviceApprovals()->valid()->count());
    }
}
