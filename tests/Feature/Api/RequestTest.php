<?php

namespace Tests\Feature\Api;

use App\Api\Http\ApiResponse;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequestTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        Route::any('/api/test', function () {
            return new ApiResponse();
        })->middleware('api');
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
}
