<?php

namespace Tests\Feature\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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

                Route::post('/api/businesstest/{business}', function (Business $business) {
                    return new ApiResponse();
                });
            });
    }

    public function testReturnsJson()
    {
        $response = $this->json('GET', '/api/test', ['test' => true]);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJson(['status' => 'ok']);
    }

    public function testReturnsErrorIfInvalidRequest()
    {
        // We have an empty, non-json request, so it is invalid
        $response = $this->post('/api/test');
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_INVALID_REQUEST,
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
}
