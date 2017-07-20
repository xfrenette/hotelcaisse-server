<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\CashMovement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class CashMovementsControllerTest extends TestCase
{
    use DatabaseTransactions;
    use WithoutMiddleware;
    use InteractsWithAPI;

    const ADD_DATA = [
        'data' => [
            'note' => 'Test note',
            'amount' => -12.34,
        ],
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->business = factory(Business::class)->create();
    }

    public function testAddReturnsErrorIfInvalidNote()
    {
        $values = [null, '', ' ', 12];
        $this->assertValidatesData('api.cashMovements.add', self::ADD_DATA, 'note', $values);
    }

    public function testAddReturnsErrorIfInvalidAmount()
    {
        $values = [null, '', ' ', false, 0];
        $this->assertValidatesData('api.cashMovements.add', self::ADD_DATA, 'amount', $values);
    }

    public function testAddWorksWithValidData()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);

        $response = $this->queryAPI('api.cashMovements.add', self::ADD_DATA);
        $response->assertJson([
            'status' => 'ok'
        ]);
    }

    public function testAddReturnsErrorIfNoRegister()
    {
        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);
        $response = $this->queryAPI('api.cashMovements.add', self::ADD_DATA);
        $response->assertJson([
            'status' => 'error',
            'error' => ['code' => ApiResponse::ERROR_CLIENT_ERROR],
        ]);
    }

    public function testAddReturnsErrorIfRegisterIsNotOpened()
    {
        $device = $this->createDeviceWithRegister();
        $this->mockApiAuthDevice($device);
        $response = $this->queryAPI('api.cashMovements.add', self::ADD_DATA);
        $response->assertJson([
            'status' => 'error',
            'error' => ['code' => ApiResponse::ERROR_CLIENT_ERROR],
        ]);
    }

    public function testAddAddsCashMovementToRegister()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);
        $this->queryAPI('api.cashMovements.add', self::ADD_DATA);

        $cashMovement = CashMovement::where('register_id', $device->currentRegister->id)->first();
        $this->assertEquals(self::ADD_DATA['data']['note'], $cashMovement->note);
        $this->assertEquals(self::ADD_DATA['data']['amount'], $cashMovement->amount);
    }

    public function testAddBumpsBusinessVersionWithModifications()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);

        $oldVersion = $this->business->version;

        $this->queryAPI('api.cashMovements.add', self::ADD_DATA);
        $newVersion = $this->business->version;
        $this->assertNotEquals($oldVersion, $newVersion);
        $this->assertEquals(
            [Business::MODIFICATION_REGISTER],
            $this->business->getVersionModifications($newVersion)
        );
    }
}
