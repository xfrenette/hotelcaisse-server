<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\DeviceApproval;
use App\Support\Facades\ApiAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeviceControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var Business
     */
    protected $business;

    protected function setUp()
    {
        parent::setUp();
        $this->business = factory(Business::class)->create();
    }

    protected function getUri($routeName)
    {
        return route($routeName, ['business' => $this->business]);
    }

    public function testRegisterReturnsErrorWithMissingCredentials()
    {
        $response = $this->json('POST', $this->getUri('api.device.register'));
        $response->assertJson([
            'error' => [
                'code' => ApiResponse::ERROR_INVALID_REQUEST,
            ],
        ]);
    }

    public function testRegisterReturnsErrorWithInvalidCredentials()
    {
        $data = [
            'data' => [
                'passcode' => 'invalid',
            ],
        ];
        $response = $this->json('POST', $this->getUri('api.device.register'), $data);
        $response->assertJson([
            'error' => [
                'code' => ApiResponse::ERROR_AUTH_FAILED,
            ],
        ]);
        $this->assertNotContains('token', $response->json());
    }

    public function testRegisterReturnsExpectedResponseWithValidCredentials()
    {
        $passcode = '4321';
        $deviceApproval = factory(DeviceApproval::class, 'withDeviceAndBusiness')->make();
        $deviceApproval->device->business()->associate($this->business);
        $deviceApproval->device->save();
        $deviceApproval->passcode = $passcode;
        $deviceApproval->save();

        $data = [
            'data' => [
                'passcode' => $passcode,
            ],
        ];

        $response = $this->json('POST', $this->getUri('api.device.register'), $data);
        $response->assertJson([
            'status' => 'ok',
        ]);
    }

    public function testRegisterHasValidTokenWithValidCredentials()
    {
        $passcode = '4321';
        $deviceApproval = factory(DeviceApproval::class, 'withDeviceAndBusiness')->make();
        $deviceApproval->device->business()->associate($this->business);
        $deviceApproval->device->save();
        $deviceApproval->passcode = $passcode;
        $deviceApproval->save();

        $data = [
            'data' => [
                'passcode' => $passcode,
            ],
        ];

        $response = $this->json('POST', $this->getUri('api.device.register'), $data);
        $token = $response->json()['token'];
        ApiAuth::loadSession($token, $this->business);
        $this->assertTrue(ApiAuth::check());
    }
}
