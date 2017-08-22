<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Http\Controllers\Api\RegisterController;
use App\Register;
use Carbon\Carbon;
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
            'openedAt' => 1503410614, // 08/22/2017 @ 2:03pm (UTC)
        ],
    ];

    const CLOSE_DATA = [
        'data' => [
            'cashAmount' => 12.34,
            'POSTRef' => 'Test POST ref',
            'POSTAmount' => 45.67,
        ],
    ];

    /**
     * @var RegisterController
     */
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
        $this->assertValidatesRequestData(
            [$this->controller, 'validateOpen'],
            self::OPEN_DATA,
            'data.employee',
            $values
        );
    }

    public function testValidateOpenReturnsErrorIfInvalidCashAmount()
    {
        $values = [null, '', -5];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateOpen'],
            self::OPEN_DATA,
            'data.cashAmount',
            $values
        );
    }

    public function testValidateOpenReturnsErrorIfInvalidOpenedAt()
    {
        $values = [null, '', ' ', 0, -6];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateOpen'],
            self::OPEN_DATA,
            'data.openedAt',
            $values,
            false
        );
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

    public function testValidateOpenWorksWithValidData()
    {
        $data = self::OPEN_DATA;
        $request = $this->mockRequest($data);
        // Should not throw
        $this->controller->validateOpen($request);

        // Remove optional attributes
        unset($data['openedAt']);
        $request = $this->mockRequest($data);
        // Should not throw
        $this->controller->validateOpen($request);
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

    public function testOpenWithoutOpenedAt()
    {
        $device = $this->createDeviceWithRegister();
        $this->mockApiAuthDevice($device);
        $oldRegisterId = $device->currentRegister->id;
        $data = self::OPEN_DATA;
        unset($data['data']['openedAt']);
        $request = $this->mockRequest($data);
        $now = Carbon::now();
        $this->controller->open($request);
        $device->currentRegister->refresh();
        $this->assertTrue($device->currentRegister->opened);
        $this->assertNotEquals($oldRegisterId, $device->currentRegister->id);
        $this->assertEquals(0, $now->diffInSeconds($device->currentRegister->opened_at));
    }

    public function testOpenWithFutureOpenedAt()
    {
        $device = $this->createDeviceWithRegister();
        $this->mockApiAuthDevice($device);
        $data = self::OPEN_DATA;
        $data['data']['openedAt'] = Carbon::now()->addSeconds(5)->getTimestamp();
        $request = $this->mockRequest($data);
        $now = Carbon::now();
        $this->controller->open($request);
        $device->currentRegister->refresh();
        $this->assertTrue($device->currentRegister->opened);
        $this->assertEquals(0, $now->diffInSeconds($device->currentRegister->opened_at));
    }

    public function testOpenWithPastOpenedAt()
    {
        $nbSeconds = 55;
        $device = $this->createDeviceWithRegister();
        $this->mockApiAuthDevice($device);
        $data = self::OPEN_DATA;
        $data['data']['openedAt'] = Carbon::now()->subSeconds($nbSeconds)->getTimestamp();
        $request = $this->mockRequest($data);
        $now = Carbon::now();
        $this->controller->open($request);
        $device->currentRegister->refresh();
        $this->assertTrue($device->currentRegister->opened);
        $this->assertEquals($nbSeconds, $now->diffInSeconds($device->currentRegister->opened_at));
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
