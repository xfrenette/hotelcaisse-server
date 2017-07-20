<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\CashMovement;
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

    protected function setUp()
    {
        parent::setUp();
        $this->business = factory(Business::class)->create();
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

    public function testAddReturnsErrorIfInvalidUUID()
    {
        $values = [null, '', ' ', 12];
        $this->assertValidatesData('api.cashMovements.add', self::generateAddData(), 'uuid', $values);
    }

    public function testAddReturnsErrorIfInvalidNote()
    {
        $values = [null, '', ' ', 12];
        $this->assertValidatesData('api.cashMovements.add', self::generateAddData(), 'note', $values);
    }

    public function testAddReturnsErrorIfInvalidAmount()
    {
        $values = [null, '', ' ', false, 0];
        $this->assertValidatesData('api.cashMovements.add', self::generateAddData(), 'amount', $values);
    }

    public function testAddWorksWithValidData()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);

        $response = $this->queryAPI('api.cashMovements.add', self::generateAddData());
        $response->assertJson([
            'status' => 'ok'
        ]);
    }

    public function testAddReturnsErrorIfNoRegister()
    {
        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);
        $response = $this->queryAPI('api.cashMovements.add', self::generateAddData());
        $response->assertJson([
            'status' => 'error',
            'error' => ['code' => ApiResponse::ERROR_CLIENT_ERROR],
        ]);
    }

    public function testAddReturnsErrorIfRegisterIsNotOpened()
    {
        $device = $this->createDeviceWithRegister();
        $this->mockApiAuthDevice($device);
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
        $this->mockApiAuthDevice($device);
        $this->queryAPI('api.cashMovements.add', $data);

        $cashMovement = CashMovement::where('register_id', $device->currentRegister->id)->first();
        $this->assertEquals($data['data']['note'], $cashMovement->note);
        $this->assertEquals($data['data']['amount'], $cashMovement->amount);
    }

    public function testAddBumpsBusinessVersionWithModifications()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);

        $oldVersion = $this->business->version;

        $this->queryAPI('api.cashMovements.add', self::generateAddData());
        $newVersion = $this->business->version;
        $this->assertNotEquals($oldVersion, $newVersion);
        $this->assertEquals(
            [Business::MODIFICATION_REGISTER],
            $this->business->getVersionModifications($newVersion)
        );
    }

    // --------------------------

    public function testDeleteReturnsErrorIfInvalidUUID()
    {
        $values = [null, '', ' ', 12];
        $this->assertValidatesData('api.cashMovements.delete', ['data' => []], 'uuid', $values);
    }

    public function testDeleteReturnsErrorIfNonExistentUUID()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);
        $data = ['data' => ['uuid' => 'non-existent']];
        $oldVersion = $device->business->version;

        $response = $this->queryAPI('api.cashMovements.delete', $data);
        $response->assertJson([
            'status' => 'error',
            'error' => ['code' => ApiResponse::ERROR_CLIENT_ERROR],
        ]);

        // Test business version is same
        $this->assertEquals($oldVersion, $device->business->version);
    }

    public function testDeleteReturnsErrorIfUUIDOfOtherRegister()
    {
        $uuid = Factory::create()->uuid();
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);
        $oldVersion = $device->business->version;

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

        // Test business version is same
        $this->assertEquals($oldVersion, $device->business->version);
    }

    public function testDeleteDeletesCashMovementIfValid()
    {
        $uuid = Factory::create()->uuid();
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);
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
        $this->mockApiAuthDevice($device);
        $cashMovement = new CashMovement([
            'uuid' => $uuid,
            'amount' => -12.34,
            'note' => 'Test note',
        ]);
        $cashMovement->register()->associate($device->currentRegister);
        $cashMovement->save();

        $oldVersion = $this->business->version;

        $this->queryAPI('api.cashMovements.delete', ['data' => ['uuid' => $uuid]]);
        $newVersion = $this->business->version;
        $this->assertNotEquals($oldVersion, $newVersion);
        $this->assertEquals(
            [Business::MODIFICATION_REGISTER],
            $this->business->getVersionModifications($newVersion)
        );
    }
}
