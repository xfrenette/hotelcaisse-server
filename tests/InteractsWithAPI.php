<?php

namespace Tests;

use App\Api\Auth\ApiAuth;
use App\Device;
use App\Register;
use Illuminate\Support\Facades\App;

trait InteractsWithAPI
{
    /**
     * @var \App\Business
     */
    protected $business;

    protected function queryAPI($routeName, $data = [])
    {
        $uri = route($routeName, ['business' => $this->business->slug]);
        return $this->json('POST', $uri, $data);
    }

    protected function createDevice()
    {
        $device = factory(Device::class)->make();
        $device->business()->associate($this->business);
        $device->save();

        return $device;
    }

    protected function createDeviceWithRegister()
    {
        $device = $this->createDevice();

        $register = factory(Register::class)->make();
        $register->device()->associate($device);
        $register->save();

        $device->currentRegister()->associate($register);
        $device->save();

        return $device;
    }

    protected function createDeviceWithOpenedRegister()
    {
        $device = $this->createDeviceWithRegister();
        $device->currentRegister->open('test', 12.34);
        $device->currentRegister->save();

        return $device;
    }

    protected function mockApiAuthDevice($device)
    {
        $stub = $this->createMock(ApiAuth::class);
        $stub->method('getDevice')
            ->will($this->returnValue($device));
        App::instance('apiauth', $stub);
        return $stub;
    }
}
