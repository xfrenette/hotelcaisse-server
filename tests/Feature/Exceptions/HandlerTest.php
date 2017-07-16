<?php

namespace Tests\Feature\Exceptions;

use App\Business;
use Tests\TestCase;
use Illuminate\Support\Facades\Route;
use App\Exceptions\Api\InvalidTokenException;
use App\Exceptions\Api\InvalidRequestException;
use Illuminate\Http\Request;
use App\Api\Http\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExceptionsTest extends TestCase
{
    use DatabaseTransactions;

    protected $business;

    protected function setUp()
    {
        parent::setUp();
        $this->business = factory(Business::class)->create();
    }

    public function testApiInvalidTokenException()
    {
        Route::get('/api/test', function () {
            throw new InvalidTokenException();
        });

        $response = $this->json('GET', '/api/test');

        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_INVALID_TOKEN,
            ],
        ]);
    }

    public function testApiInvalidRequestException()
    {
        Route::get('/api/test', function () {
            throw new InvalidRequestException();
        });

        $response = $this->json('GET', '/api/test');

        $response->assertStatus(400);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_INVALID_REQUEST,
            ],
        ]);
    }

    public function testApiNotFoundException()
    {
        $response = $this->json('GET', '/api/non-existent-url');

        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_NOT_FOUND,
            ],
        ]);
    }

    public function testApi400HttpException()
    {
        Route::get('/api/test', function () {
            throw new HttpException(418);
        });

        $response = $this->json('GET', '/api/test');

        $response->assertStatus(418);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_CLIENT_ERROR,
            ],
        ]);
    }

    public function testApiGeneralException()
    {
        Route::get('/api/test', function () {
            throw new \Exception();
        });

        $response = $this->json('GET', '/api/test');

        $response->assertStatus(500);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_SERVER_ERROR,
            ],
        ]);
    }
}
