<?php

namespace Tests\Feature\Api;

use App\Api\Http\ApiResponse;
use App\ApiSession;
use App\Business;
use App\Support\Facades\ApiAuth;
use App\Team;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequestTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var Business
     */
    protected $business;

    /**
     * @var \App\Team
     */
    protected $team;

    protected function setUp()
    {
        parent::setUp();

        $this->team = factory(Team::class, 'withBusiness')->create();
        $this->business = $this->team->business;

        Route::middleware(['api', 'api:request'])
            ->group(function () {
                Route::any('/api/test', function () {
                    return new ApiResponse();
                });

                Route::post('/api/auth/{team}', function () {
                    return new ApiResponse();
                })->middleware('api:auth');
            });
    }

    protected function createApiSession()
    {
        $apiSession = factory(ApiSession::class, 'withDevice')->create();
        $team = factory(Team::class)->make();
        $team->business()->associate($apiSession->device->team->business);
        $team->save();

        return $apiSession;
    }

    public function testReturnsJson()
    {
        $response = $this->json('GET', '/api/test', ['test' => true]);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJson(['status' => 'ok']);
    }

    public function testAcceptsEmptyBody()
    {
        $response = $this->json('POST', '/api/test');
        $response->assertStatus(200);
        $response->assertJSON([
            'status' => 'ok',
        ]);
    }

    public function testReturnsErrorIfInvalidRequest()
    {
        // We have a non-json request, so it is invalid
        $response = $this->post('/api/test');
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_CLIENT_ERROR,
            ]
        ]);
    }

    public function testReturnsErrorIfInvalidTeam()
    {
        $response = $this->json('POST', '/api/auth/XX-invalid-XX', ['test' => true]);
        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_NOT_FOUND,
            ]
        ]);
    }

    public function testAuthReturnsErrorWithInvalidToken()
    {
        $uri = '/api/auth/' . $this->team->slug;
        $response = $this->json('POST', $uri, ['token' => 'invalid']);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_AUTH_FAILED,
            ],
        ]);
    }

    public function testAuthWorksWithValidToken()
    {
        $apiSession = $this->createApiSession();

        $uri = '/api/auth/' . $apiSession->device->team->slug;
        $response = $this->json('POST', $uri, ['token' => $apiSession->token]);
        $response->assertJson([
            'status' => 'ok',
        ]);
        $this->assertTrue(ApiAuth::check());
    }

    public function testAuthGeneratesNewToken()
    {
        $apiSession = $this->createApiSession();
        $oldToken = $apiSession->token;

        $uri = '/api/auth/' . $apiSession->device->team->slug;
        $this->json('POST', $uri, ['token' => $oldToken]);
        $this->assertNotEquals(ApiAuth::getToken(), $oldToken);
    }

    public function testAddsTokenToResponse()
    {
        $apiSession = $this->createApiSession();
        $uri = '/api/auth/' . $apiSession->device->team->slug;
        $response = $this->json('POST', $uri, ['token' => $apiSession->token]);
        $response->assertJson([
            'token' => ApiAuth::getToken(),
        ]);
    }

    public function testHasDataVersion()
    {
        // We query an API route in the 'api' middleware to check it has the `dataVersion`attribute
        $apiSession = $this->createApiSession();
        $business = $apiSession->device->team->business;
        $business->bumpVersion();
        $uri = '/api/auth/' . $apiSession->device->team->slug;
        $response = $this->json('POST', $uri, ['token' => $apiSession->token]);
        $response->assertJson([
            'dataVersion' => $business->version,
        ]);
    }

    public function testHasUpdatedData()
    {
        // We query an API route in the 'api' middleware to check it will included updated data
        $apiSession = $this->createApiSession();
        $business = $apiSession->device->team->business;
        $business->bumpVersion();
        $oldVersion = $business->version;
        $business->bumpVersion([Business::MODIFICATION_REGISTER, Business::MODIFICATION_ROOMS]);
        $uri = '/api/auth/' . $apiSession->device->team->slug;

        $data = ['token' => $apiSession->token, 'dataVersion' => $oldVersion];
        $response = $this->json('POST', $uri, $data);
        $response->assertJsonStructure([
            'business' => ['rooms'],
            'device' => ['currentRegister'],
        ]);
    }
}
