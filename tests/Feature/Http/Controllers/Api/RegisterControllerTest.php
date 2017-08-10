<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
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

    protected function setUp()
    {
        parent::setUp();
        $this->business = factory(Business::class)->create();
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
    public function testOpenReturnsErrorIfInvalidUUID()
    {
        $existingRegister = Register::first();
        $values = [null, '', ' ', 12, $existingRegister->uuid];
        $this->assertValidatesData('api.register.open', self::OPEN_DATA, 'uuid', $values);
    }

    public function testOpenReturnsErrorIfInvalidEmployee()
    {
        $values = [null, '', ' ', 12];
        $this->assertValidatesData('api.register.open', self::OPEN_DATA, 'employee', $values);
    }

    public function testOpenReturnsErrorIfInvalidCashAmount()
    {
        $values = [null, '', -5];
        $this->assertValidatesData('api.register.open', self::OPEN_DATA, 'cashAmount', $values);
    }

    public function testOpenReturnsErrorIfRegisterAlreadyOpened()
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
        $this->mockApiAuthDevice($device);

        $oldVersion = $this->business->version;

        $this->queryAPI('api.register.open', self::OPEN_DATA);
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

    public function testCloseReturnsErrorIfInvalidUUID()
    {
        $data = $this->generateCloseData();
        $values = [null, '', ' ', 12];
        $this->assertValidatesData('api.register.close', $data, 'uuid', $values);
    }

    public function testCloseReturnsErrorIfInvalidCashAmount()
    {
        $values = [null, '', -5];

        foreach ($values as $value) {
            $data = $this->generateCloseData();

            if (is_null($value)) {
                unset($data['data']['cashAmount']);
            } else {
                $data['data']['cashAmount'] = $value;
            }

            $response = $this->queryAPI('api.register.close', $data);
            $response->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => ApiResponse::ERROR_CLIENT_ERROR,
                ],
            ]);
        }
    }

    public function testCloseReturnsErrorIfInvalidPOSTAmount()
    {
        $values = [null, '', -5];

        foreach ($values as $value) {
            $data = $this->generateCloseData();

            if (is_null($value)) {
                unset($data['data']['POSTAmount']);
            } else {
                $data['data']['POSTAmount'] = $value;
            }

            $response = $this->queryAPI('api.register.close', $data);
            $response->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => ApiResponse::ERROR_CLIENT_ERROR,
                ],
            ]);
        }
    }

    public function testCloseReturnsErrorIfInvalidPOSTRef()
    {
        $values = [null, '', '  ', 5];

        foreach ($values as $value) {
            $data = $this->generateCloseData();

            if (is_null($value)) {
                unset($data['data']['POSTRef']);
            } else {
                $data['data']['POSTRef'] = $value;
            }

            $response = $this->queryAPI('api.register.close', $data);
            $response->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => ApiResponse::ERROR_CLIENT_ERROR,
                ],
            ]);
        }
    }

    public function testCloseReturnsErrorIfNoRegister()
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

    public function testCloseReturnsErrorIfUUIDIsNotDeviceCurrentRegister()
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

    public function testCloseReturnsErrorIfNoOpenedRegister()
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
        $this->mockApiAuthDevice($device);
        $data = $this->generateCloseData($device);

        $oldVersion = $this->business->version;

        $this->queryAPI('api.register.close', $data);
        $newVersion = $this->business->version;
        $this->assertNotEquals($oldVersion, $newVersion);
        $this->assertEquals(
            [Business::MODIFICATION_REGISTER],
            $this->business->getVersionModifications($newVersion)
        );
    }
}
