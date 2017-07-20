<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Auth\ApiAuth;
use App\Api\Http\ApiResponse;
use App\Business;
use App\Device;
use App\Register;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use DatabaseTransactions;
    use WithoutMiddleware;

    /**
     * @var \App\Business
     */
    protected $business;

    const OPEN_DATA = [
        'data' => [
            'employee' => 'Test Employee',
            'cashAmount' => 12.34,
        ],
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->business = factory(Business::class)->create();
    }

    protected function queryRoute($routeName, $data = [])
    {
        $uri = route($routeName, ['business' => $this->business->slug]);
        return $this->json('POST', $uri, $data);
    }

    protected function mockApiAuth($device)
    {
        $stub = $this->createMock(ApiAuth::class);
        $stub->method('getDevice')
            ->will($this->returnValue($device));
        App::instance('apiauth', $stub);
        return $stub;
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

    public function testOpenReturnsErrorIfInvalidEmployee()
    {
        $values = [null, '', ' ', 12];

        foreach ($values as $value) {
            $data = [
                'data' => [
                    'cashAmount' => 12.34,
                ],
            ];

            if (!is_null($value)) {
                $data['data']['employee'] = $value;
            }

            $response = $this->queryRoute('api.register.open', $data);
            $response->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => ApiResponse::ERROR_CLIENT_ERROR,
                ],
            ]);
        }
    }

    public function testOpenReturnsErrorIfInvalidCashAmount()
    {
        $values = [null, '', -5];

        foreach ($values as $value) {
            $data = [
                'data' => [
                    'employee' => 'Test Employee',
                ],
            ];

            if (!is_null($value)) {
                $data['data']['cashAmount'] = $value;
            }

            $response = $this->queryRoute('api.register.open', $data);
            $response->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => ApiResponse::ERROR_CLIENT_ERROR,
                ],
            ]);
        }
    }

    public function testOpenReturnsErrorIfRegisterAlreadyOpened()
    {
        $device = $this->createDeviceWithRegister();
        $device->currentRegister->open('test employee', 12.34);
        $device->currentRegister->save();
        $this->mockApiAuth($device);

        $response = $this->queryRoute('api.register.open', self::OPEN_DATA);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_CLIENT_ERROR,
            ],
        ]);
    }

    public function testOpenBumpsBusinessVersionWithModifications()
    {
        $device = $this->createDeviceWithRegister();
        $this->mockApiAuth($device);

        $oldVersion = $this->business->version;

        $this->queryRoute('api.register.open', self::OPEN_DATA);
        $newVersion = $this->business->version;
        $this->assertNotEquals($oldVersion, $newVersion);
        $this->assertEquals(
            [Business::MODIFICATION_REGISTER],
            $this->business->getVersionModifications($newVersion)
        );
    }

    public function testOpenAssignsNewOpenedRegisterToDevice()
    {
        $device = $this->createDeviceWithRegister();
        $this->mockApiAuth($device);

        $oldRegisterId = $device->currentRegister->id;

        $this->queryRoute('api.register.open', self::OPEN_DATA);
        $this->assertTrue($device->currentRegister->opened);
        $this->assertNotEquals($oldRegisterId, $device->currentRegister->id);
    }

    public function testOpenWorksWithValidData()
    {
        $device = $this->createDevice();
        $this->mockApiAuth($device);

        $response = $this->queryRoute('api.register.open', self::OPEN_DATA);
        $response->assertJson([
            'status' => 'ok',
        ]);
    }
}
