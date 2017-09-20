<?php

namespace Tests;

use App\Api\Auth\ApiAuth;
use App\Api\Http\ApiResponse;
use App\ApiSession;
use App\Device;
use App\Register;
use App\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Mockery as m;

trait InteractsWithAPI
{
    /**
     * @var \App\Business
     */
    protected $business;

    /**
     * @param $routeName
     * @param array $data
     *
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function queryAPI($routeName, $data = [], Team $team = null)
    {
        $slug = null;

        if ($team) {
            $slug = $team->slug;
        } else {
            $device = \App\Support\Facades\ApiAuth::getDevice();
            $slug = $device->team->slug;
        }

        $uri = route($routeName, ['team' => $slug]);
        return $this->json('POST', $uri, $data);
    }

    /**
     * @param null $team
     *
     * @return Device
     */
    protected function createDevice($team = null)
    {
        if (is_null($team)) {
            $team = factory(Team::class, 'withBusiness')->create();
        }

        $device = factory(Device::class)->make();
        $device->team()->associate($team);
        $device->save();

        return $device;
    }

    protected function mockDevice()
    {
        $device = \Mockery::mock(Device::class)->makePartial();
        $device->shouldReceive('getNextRegisterNumberAttribute')->andReturn(1);
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
        $apiAuth = $this->mockApiAuth();
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $apiAuth->shouldReceive('getDevice')->andReturn($device);

        App::instance('apiauth', $apiAuth);

        return $apiAuth;
    }

    protected function logDevice($device)
    {
        $apiSession = factory(ApiSession::class)->make();
        $apiSession->device()->associate($device);
        $apiSession->save();

        \App\Support\Facades\ApiAuth::setApiSession($apiSession);

        return $apiSession;
    }

    protected function mockApiAuth($setCheck = true)
    {
        $apiAuth = m::mock(ApiAuth::class)->makePartial();
        if ($setCheck) {
            $apiAuth->shouldReceive('check')->andReturn(true);
        }

        App::instance('apiauth', $apiAuth);

        return $apiAuth;
    }

    /**
     * @param array $data
     * @param Team|null $team
     *
     * @return Request
     */
    protected function mockRequest($data = [], $team = null)
    {
        return $this->mockFormRequest(Request::class, $data, $team);
    }

    /**
     * @param string $class
     * @param array $data
     * @param Team|null $team
     *
     * @return \Illuminate\Foundation\Http\FormRequest
     */
    protected function mockFormRequest($class, $data, $team = null)
    {
        $content = json_encode($data);
        $mock = m::mock($class)->makePartial();

        if (method_exists($mock, 'setContainer')) {
            $mock->setContainer(\app());

        }

        $mock->shouldReceive('getContent')->andReturn($content);
        $mock->shouldReceive('expectsJson')->andReturn(true);
        $mock->shouldReceive('route')->with('team')->andReturn($team);

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $mock;
    }

    /**
     * @TODO should replace calls to this method to use assertValidatesRequestData instead
     */
    protected function assertValidatesData($routeName, $baseData, $attributeToValidate, $values, $testPresence = true)
    {
        if ($testPresence) {
            $data = $baseData;
            array_forget($data, "data.$attributeToValidate");

            $response = $this->queryAPI($routeName, $data);
            $response->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => ApiResponse::ERROR_CLIENT_ERROR,
                ],
            ]);
        }

        if (!$values) {
            return;
        }

        foreach ($values as $value) {
            $data = $baseData;
            array_set($data, "data.$attributeToValidate", $value);

            $response = $this->queryAPI($routeName, $data);
            $response->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => ApiResponse::ERROR_CLIENT_ERROR,
                ],
            ]);
        }
    }

    protected function assertValidatesRequestData(
        $callback,
        $baseData,
        $attributeToValidate,
        $values,
        $testPresence = true
    ) {
        if ($testPresence) {
            $data = $baseData;
            array_forget($data, $attributeToValidate);
            $request = $this->mockRequest($data);

            try {
                call_user_func($callback, $request);
                $this->fail('ValidationException not thrown when missing attribute `' . $attributeToValidate . '`');
            } catch (ValidationException $e) {
                // Do nothing
            }
        }

        if (!$values) {
            return;
        }

        foreach ($values as $value) {
            $data = $baseData;
            array_set($data, $attributeToValidate, $value);
            $request = $this->mockRequest($data);

            try {
                call_user_func($callback, $request);
                $this->fail('ValidationException not thrown with attribute `'
                    . $attributeToValidate . '` = ' . (is_null($value) ? 'NULL' : '`'.$value.'`'));
            } catch (ValidationException $e) {
                // Do nothing
            }
        }
    }
}
