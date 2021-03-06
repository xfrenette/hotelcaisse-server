<?php

namespace Tests\Unit;

use App\Device;
use App\Register;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    public function testToArray()
    {
        $register = new Register();
        $register->state = Register::STATE_OPENED;
        $register->id = 4;

        $device = new Device();
        $device->name = 'test name';
        $device->id = 1;
        $device->currentRegister()->associate($register);

        $nextRegisterNumber = $device->nextRegisterNumber;

        $expected = [
            'currentRegister' => $register->toArray(),
            'nextRegisterNumber' => $nextRegisterNumber,
        ];

        $this->assertEquals($expected, $device->toArray());
    }

    public function testToArrayWithoutRegister()
    {

        $device = new Device();
        $device->name = 'test name';
        $device->id = 1;

        $expected = [
            'currentRegister' => null,
        ];

        $this->assertArraySubset($expected, $device->toArray());
    }
}
