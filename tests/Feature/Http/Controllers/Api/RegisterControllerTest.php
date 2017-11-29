<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Requests\Api\RegisterOpen;
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
            'number' => 123,
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
            'closedAt' => 1503410614, // 08/22/2017 @ 2:03pm (UTC)
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

    public function testOpenWithoutOpenedAt()
    {
        $device = $this->createDeviceWithRegister();
        $this->mockApiAuthDevice($device);
        $oldRegisterId = $device->currentRegister->id;
        $data = self::OPEN_DATA;
        unset($data['data']['openedAt']);
        $request = $this->mockFormRequest(RegisterOpen::class, $data);
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
        $request = $this->mockFormRequest(RegisterOpen::class, $data);
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
        $request = $this->mockFormRequest(RegisterOpen::class, $data);
        $now = Carbon::now();
        $this->controller->open($request);
        $device->currentRegister->refresh();
        $this->assertTrue($device->currentRegister->opened);
        $this->assertEquals($nbSeconds, $now->diffInSeconds($device->currentRegister->opened_at));
    }

    // ---------------------

    public function testValidateCloseReturnsErrorIfInvalidUUID()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);
        $data = $this->generateCloseData($device);
        $values = [null, '', ' ', 12];
        $this->assertValidatesRequestData([$this->controller, 'validateClose'], $data, 'data.uuid', $values);
    }

    public function testValidateCloseReturnsErrorIfInvalidCashAmount()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);
        $data = $this->generateCloseData($device);
        $values = [null, ''];
        $this->assertValidatesRequestData([$this->controller, 'validateClose'], $data, 'data.cashAmount', $values);
    }

    public function testValidateCloseReturnsErrorIfInvalidPOSTAmount()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);
        $data = $this->generateCloseData($device);
        $values = [null, ''];
        $this->assertValidatesRequestData([$this->controller, 'validateClose'], $data, 'data.POSTAmount', $values);
    }

    public function testValidateCloseReturnsErrorIfInvalidPOSTRef()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);
        $data = $this->generateCloseData($device);
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
