<?php

namespace Tests;

use App\Api\Auth\ApiAuth;
use App\Api\Http\ApiResponse;
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

    protected function assertValidatesData($routeName, $baseData, $attribute, $values, $testNotPresent = true)
    {
        $notPresentTested = false;

        foreach ($values as $value) {
            $data = $baseData;

            if ($testNotPresent && !$notPresentTested) {
                unset($data['data'][$attribute]);
                $notPresentTested = true;
            } else {
                $data['data'][$attribute] = $value;
            }

            $response = $this->queryAPI($routeName, $data);
            $response->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => ApiResponse::ERROR_CLIENT_ERROR,
                ],
            ]);
        }
    }
}
