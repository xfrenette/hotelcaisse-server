<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Http\Controllers\Api\RegisterController;
use App\Register;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use DatabaseTransactions;
    use WithoutMiddleware;
    use InteractsWithAPI;

    const OPEN_DATA = [
        'data' => [
            'uuid' => 'test-uuid',
            'employee' => 'Test Employee',
            'cashAmount' => 12.34,
        ],
    ];

    const CLOSE_DATA = [
        'data' => [
            'cashAmount' => 12.34,
            'POSTRef' => 'Test POST ref',
            'POSTAmount' => 45.67,
        ],
    ];

    protected $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->business = factory(Business::class)->create();
        $this->controller = new RegisterController();
    }

    /**
     * @param \App\Device $device
     *
     * @return array
     */
    protected function generateCloseData($device = null)
    {
        $data = self::CLOSE_DATA;

        if ($device && $device->currentRegister) {
            $data['data']['uuid'] = $device->currentRegister->uuid;
        }

        return $data;
    }

    // Uses seeded test data
    public function testValidateOpenReturnsErrorIfInvalidUUID()
    {
        $existingRegister = Register::first();
        $values = [null, '', ' ', 12, $existingRegister->uuid];
        $this->assertValidatesRequestData([$this->controller, 'validateOpen'], self::OPEN_DATA, 'data.uuid', $values);
    }

    public function testValidateOpenReturnsErrorIfInvalidEmployee()
    {
        $values = [null, '', ' ', 12];
        $this->assertValidatesRequestData([$this->controller, 'validateOpen'], self::OPEN_DATA, 'data.employee', $values);
    }

    public function testValidateOpenReturnsErrorIfInvalidCashAmount()
    {
        $values = [null, '', -5];
        $this->assertValidatesRequestData([$this->controller, 'validateOpen'], self::OPEN_DATA, 'data.cashAmount', $values);
    }

    public function testValidateOpenReturnsErrorIfRegisterAlreadyOpened()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);

        $response = $this->queryAPI('api.register.open', self::OPEN_DATA);
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
        $this->logDevice($device);
        $business = $device->team->business;

        $oldVersion = $business->version;

        $this->queryAPI('api.register.open', self::OPEN_DATA);
        $newVersion = $business->version;
        $this->assertNotEquals($oldVersion, $newVersion);
        $this->assertEquals(
            [Business::MODIFICATION_REGISTER],
            $business->getVersionModifications($newVersion)
        );
    }

    public function testOpenAssignsNewOpenedRegisterToDevice()
    {
        $device = $this->createDeviceWithRegister();
        $this->mockApiAuthDevice($device);

        $oldRegisterId = $device->currentRegister->id;

        $this->queryAPI('api.register.open', self::OPEN_DATA);
        $device->currentRegister->refresh();
        $this->assertTrue($device->currentRegister->opened);
        $this->assertNotEquals($oldRegisterId, $device->currentRegister->id);
    }

    public function testOpenWorksWithValidData()
    {
        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);

        $response = $this->queryAPI('api.register.open', self::OPEN_DATA);
        $response->assertJson([
            'status' => 'ok',
        ]);
    }

    // ---------------------

    public function testValidateCloseReturnsErrorIfInvalidUUID()
    {
        $data = $this->generateCloseData();
        $values = [null, '', ' ', 12];
        $this->assertValidatesRequestData([$this->controller, 'validateClose'], $data, 'data.uuid', $values);
    }

    public function testValidateCloseReturnsErrorIfInvalidCashAmount()
    {
        $data = $this->generateCloseData();
        $values = [null, '', -5];
        $this->assertValidatesRequestData([$this->controller, 'validateClose'], $data, 'data.cashAmount', $values);
    }

    public function testValidateCloseReturnsErrorIfInvalidPOSTAmount()
    {
        $data = $this->generateCloseData();
        $values = [null, '', -5];
        $this->assertValidatesRequestData([$this->controller, 'validateClose'], $data, 'data.POSTAmount', $values);
    }

    public function testValidateCloseReturnsErrorIfInvalidPOSTRef()
    {
        $data = $this->generateCloseData();
        $values = [null, '', '  ', 5];
        $this->assertValidatesRequestData([$this->controller, 'validateClose'], $data, 'data.POSTRef', $values);
    }

    public function testValidateCloseReturnsErrorIfNoRegister()
    {
        $device = $this->createDevice(); // No register assigned
        $this->mockApiAuthDevice($device);
        $data = $this->generateCloseData($device);

        $response = $this->queryAPI('api.register.close', $data);
        $response->assertJson([
            'status' => 'error',
            'error' => ['code' => ApiResponse::ERROR_CLIENT_ERROR],
        ]);
    }

    public function testValidateCloseReturnsErrorIfUUIDIsNotDeviceCurrentRegister()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);
        $data = $this->generateCloseData();
        $data['data']['uuid'] = $device->currentRegister->uuid . '-other';

        $response = $this->queryAPI('api.register.close', $data);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_CLIENT_ERROR,
            ],
        ]);
    }

    public function testValidateCloseReturnsErrorIfNoOpenedRegister()
    {
        $device = $this->createDeviceWithRegister(); // With a closed register
        $this->mockApiAuthDevice($device);
        $data = $this->generateCloseData($device);

        $response = $this->queryAPI('api.register.close', $data);
        $response->assertJson([
            'status' => 'error',
            'error' => ['code' => ApiResponse::ERROR_CLIENT_ERROR],
        ]);
    }

    public function testCloseWorksWithValidData()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);
        $data = $this->generateCloseData($device);

        $response = $this->queryAPI('api.register.close', $data);
        $response->assertJson([
            'status' => 'ok',
        ]);
    }

    public function testCloseClosesTheCurrentRegister()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);
        $data = $this->generateCloseData($device);

        $this->queryAPI('api.register.close', $data);
        $device->currentRegister->refresh();
        $this->assertFalse($device->currentRegister->opened);
    }

    public function testCloseBumpsBusinessVersionWithModifications()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->team->business;
        $this->mockApiAuthDevice($device);
        $data = $this->generateCloseData($device);

        $oldVersion = $business->version;

        $this->queryAPI('api.register.close', $data);
        $newVersion = $business->version;
        $this->assertNotEquals($oldVersion, $newVersion);
        $this->assertEquals(
            [Business::MODIFICATION_REGISTER],
            $business->getVersionModifications($newVersion)
        );
    }
}
