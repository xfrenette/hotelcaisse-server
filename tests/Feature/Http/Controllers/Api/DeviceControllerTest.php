<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\DeviceApproval;
use App\Support\Facades\ApiAuth;
use App\Team;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class DeviceControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAPI;

    protected function setUp()
    {
        parent::setUp();
        $this->business = factory(Business::class)->create();
    }

    public function testLinkReturnsErrorWithMissingCredentials()
    {
        $team = factory(Team::class, 'withBusiness')->create();
        $response = $this->queryAPI('api.device.link', [], $team);
        $response->assertJson([
            'error' => [
                'code' => ApiResponse::ERROR_CLIENT_ERROR,
            ],
        ]);
    }

    public function testLinkReturnsErrorWithInvalidCredentials()
    {
        $data = [
            'data' => [
                'passcode' => 'invalid',
            ],
        ];
        $team = factory(Team::class, 'withBusiness')->create();
        $response = $this->queryAPI('api.device.link', $data, $team);
        $response->assertJson([
            'error' => [
                'code' => ApiResponse::ERROR_AUTH_FAILED,
            ],
        ]);
        $this->assertNotContains('token', $response->json());
    }

    public function testLinkReturnsExpectedResponseWithValidCredentials()
    {
        $passcode = '4321';
        $deviceApproval = factory(DeviceApproval::class, 'withDevice')->make();
        $deviceApproval->passcode = $passcode;
        $deviceApproval->save();

        $data = [
            'data' => [
                'passcode' => $passcode,
            ],
        ];

        $response = $this->queryAPI('api.device.link', $data, $deviceApproval->device->team);
        $response->assertJson([
            'status' => 'ok',
        ]);
    }

    public function testLinkHasValidTokenWithValidCredentials()
    {
        $passcode = '4321';
        $deviceApproval = factory(DeviceApproval::class, 'withDevice')->make();
        $deviceApproval->passcode = $passcode;
        $deviceApproval->save();

        $data = [
            'data' => [
                'passcode' => $passcode,
            ],
        ];

        $response = $this->queryAPI('api.device.link', $data, $deviceApproval->device->team);
        $token = $response->json()['token'];
        ApiAuth::loadSession($token, $deviceApproval->device->team);
        $this->assertTrue(ApiAuth::check());
    }
}
