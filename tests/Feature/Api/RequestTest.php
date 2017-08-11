<?php

namespace Tests\Feature\Api;

use App\Api\Http\ApiResponse;
use App\ApiSession;
use App\Business;
use App\Support\Facades\ApiAuth;
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

    protected function setUp()
    {
        parent::setUp();

        $this->business = factory(Business::class)->create();

        Route::middleware('api')
            ->group(function () {
                Route::any('/api/test', function () {
                    return new ApiResponse();
                });

                /** @noinspection PhpUnusedParameterInspection */
                Route::post('/api/businesstest/{business}', function (Business $business) {
                    return new ApiResponse();
                });

                /** @noinspection PhpUnusedParameterInspection */
                Route::post('/api/auth/{business}', function (Business $business) {
                    return new ApiResponse();
                })->middleware('apiauth');
            });
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

    public function testReturnsErrorIfInvalidBusiness()
    {
        $response = $this->json('POST', '/api/businesstest/invalid', ['test' => true]);
        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_NOT_FOUND,
            ]
        ]);
    }

    public function testWorksWithValidBusiness()
    {
        $uri = '/api/businesstest/' . $this->business->slug;
        $response = $this->json('POST', $uri, ['test' => true]);
        $response->assertJson([
            'status' => 'ok',
        ]);
    }

    public function testAuthReturnsErrorWithInvalidToken()
    {
        $uri = '/api/auth/' . $this->business->slug;
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
        $apiSession = factory(ApiSession::class, 'withDeviceAndBusiness')->create();

        $uri = '/api/auth/' . $apiSession->device->business->slug;
        $response = $this->json('POST', $uri, ['token' => $apiSession->token]);
        $response->assertJson([
            'status' => 'ok',
        ]);
        $this->assertTrue(ApiAuth::check());
    }

    public function testAuthGeneratesNewToken()
    {
        $apiSession = factory(ApiSession::class, 'withDeviceAndBusiness')->create();
        $oldToken = $apiSession->token;

        $uri = '/api/auth/' . $apiSession->device->business->slug;
        $this->json('POST', $uri, ['token' => $oldToken]);
        $this->assertNotEquals(ApiAuth::getToken(), $oldToken);
    }

    public function testAddsTokenToResponse()
    {
        $apiSession = factory(ApiSession::class, 'withDeviceAndBusiness')->create();
        $uri = '/api/auth/' . $apiSession->device->business->slug;
        $response = $this->json('POST', $uri, ['token' => $apiSession->token]);
        $response->assertJson([
            'token' => ApiAuth::getToken(),
        ]);
    }

    public function testHasDataVersion()
    {
        // We query an API route in the 'api' middleware to check it has the `dataVersion`attribute
        $apiSession = factory(ApiSession::class, 'withDeviceAndBusiness')->create();
        $business = $apiSession->device->business;
        $business->bumpVersion();
        $uri = '/api/auth/' . $business->slug;
        $response = $this->json('POST', $uri, ['token' => $apiSession->token]);
        $response->assertJson([
            'dataVersion' => $business->version,
        ]);
    }

    public function testHasUpdatedDataVersion()
    {
        // We query an API route in the 'api' middleware to check it will included updated data
        $apiSession = factory(ApiSession::class, 'withDeviceAndBusiness')->create();
        $business = $apiSession->device->business;
        $business->bumpVersion();
        $oldVersion = $business->version;
        $business->bumpVersion([Business::MODIFICATION_REGISTER, Business::MODIFICATION_ROOMS]);
        $uri = '/api/auth/' . $business->slug;

        $response = $this->json('POST', $uri, ['token' => $apiSession->token, 'dataVersion' => $oldVersion]);
        $response->assertJsonStructure([
            'business' => ['rooms'],
            'deviceRegister',
        ]);
    }
}
