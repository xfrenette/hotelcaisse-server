<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\CashMovement;
use App\Http\Controllers\Api\CashMovementsController;
use App\Register;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class CashMovementsControllerTest extends TestCase
{
    use DatabaseTransactions;
    use WithoutMiddleware;
    use InteractsWithAPI;

    protected static $faker;

    /**
     * @var \App\Http\Controllers\Api\CashMovementsController
     */
    protected $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->business = factory(Business::class)->create();
        $this->controller = new CashMovementsController();
    }

    protected static function generateAddData()
    {
        if (!self::$faker) {
            self::$faker = Factory::create();
        }

        return [
            'data' => [
                'uuid' => self::$faker->uuid(),
                'note' => 'Test note',
                'amount' => -12.34,
            ],
        ];
    }

    public function testAddReturnsErrorIfNoRegister()
    {
        $device = $this->createDevice();
        $this->logDevice($device);
        $response = $this->queryAPI('api.cashMovements.add', self::generateAddData());
        $response->assertJson([
            'status' => 'error',
            'error' => ['code' => ApiResponse::ERROR_CLIENT_ERROR],
        ]);
    }

    public function testAddReturnsErrorIfRegisterIsNotOpened()
    {
        $device = $this->createDeviceWithRegister();
        $this->logDevice($device);
        $response = $this->queryAPI('api.cashMovements.add', self::generateAddData());
        $response->assertJson([
            'status' => 'error',
            'error' => ['code' => ApiResponse::ERROR_CLIENT_ERROR],
        ]);
    }

    public function testAddAddsCashMovementToRegister()
    {
        $data = self::generateAddData();
        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);
        $this->queryAPI('api.cashMovements.add', $data);

        $cashMovement = CashMovement::where('register_id', $device->currentRegister->id)->first();
        $this->assertEquals($data['data']['note'], $cashMovement->note);
        $this->assertEquals($data['data']['amount'], $cashMovement->amount);
    }

    public function testAddBumpsBusinessVersionWithModifications()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);
        $business = $device->team->business;
        $oldVersion = $business->version;

        $this->queryAPI('api.cashMovements.add', self::generateAddData());
        $newVersion = $business->version;
        $this->assertNotEquals($oldVersion, $newVersion);
        $this->assertEquals(
            [Business::MODIFICATION_REGISTER],
            $business->getVersionModifications($newVersion)
        );
    }

    // --------------------------

    public function testValidateDeleteReturnsErrorIfNonExistentUUID()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);
        $data = ['data' => ['uuid' => 'non-existent']];
        $business = $device->team->business;
        $oldVersion = $business->version;

        $response = $this->queryAPI('api.cashMovements.delete', $data);
        $response->assertJson([
            'status' => 'error',
            'error' => ['code' => ApiResponse::ERROR_CLIENT_ERROR],
        ]);

        // Test team version is same
        $this->assertEquals($oldVersion, $business->version);
    }

    public function testValidateDeleteReturnsErrorIfUUIDOfOtherRegister()
    {
        $uuid = Factory::create()->uuid();
        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);
        $business = $device->team->business;
        $oldVersion = $business->version;

        // Create another register of same device and assign it a cash movement
        $otherRegister = \factory(Register::class)->make();
        $otherRegister->device()->associate($device);
        $otherRegister->save();

        $cashMovement = new CashMovement([
            'uuid' => $uuid,
            'amount' => 12.34,
            'note' => 'Test note',
        ]);
        $cashMovement->register()->associate($otherRegister);
        $cashMovement->save();

        // Call the api, then check to see if the CashMethod is still there
        $response = $this->queryAPI('api.cashMovements.delete', ['data' => ['uuid' => $uuid]]);
        $response->assertJson([
            'status' => 'error',
            'error' => ['code' => ApiResponse::ERROR_CLIENT_ERROR],
        ]);

        $query = CashMovement::where('uuid', $uuid);
        $this->assertEquals(1, $query->count());

        // Test team version is same
        $this->assertEquals($oldVersion, $business->version);
    }

    public function testDeleteDeletesCashMovementIfValid()
    {
        $uuid = Factory::create()->uuid();
        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);
        $cashMovement = new CashMovement([
            'uuid' => $uuid,
            'amount' => -12.34,
            'note' => 'Test note',
        ]);
        $cashMovement->register()->associate($device->currentRegister);
        $cashMovement->save();

        // Call the api, then check to see if the CashMethod is deleted
        $response = $this->queryAPI('api.cashMovements.delete', ['data' => ['uuid' => $uuid]]);
        $response->assertJson([
            'status' => 'ok',
        ]);
        $query = CashMovement::where('uuid', $uuid);
        $this->assertEquals(0, $query->count());
    }

    public function testDeleteBumpsVersionWithModifications()
    {
        $uuid = Factory::create()->uuid();
        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);
        $business = $device->team->business;

        $cashMovement = new CashMovement([
            'uuid' => $uuid,
            'amount' => -12.34,
            'note' => 'Test note',
        ]);
        $cashMovement->register()->associate($device->currentRegister);
        $cashMovement->save();

        $oldVersion = $business->version;

        $this->queryAPI('api.cashMovements.delete', ['data' => ['uuid' => $uuid]]);
        $newVersion = $business->version;
        $this->assertNotEquals($oldVersion, $newVersion);
        $this->assertEquals(
            [Business::MODIFICATION_REGISTER],
            $business->getVersionModifications($newVersion)
        );
    }
}
