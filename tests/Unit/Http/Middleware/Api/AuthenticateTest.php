<?php

namespace Tests\Unit\Http\Middleware\Api;

use App\Api\Auth\ApiAuth;
use App\Business;
use App\Exceptions\Api\InvalidRequestException;
use App\Exceptions\Api\InvalidTokenException;
use App\Http\Middleware\Api\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class AuthenticateTest extends TestCase
{
    /**
     * @var Authenticate
     */
    protected $middleware;
    /**
     * @var Business
     */
    protected $business;

    protected function setUp()
    {
        parent::setUp();
        $this->middleware = new Authenticate();
        $this->business = factory(Business::class)->create();
    }

    protected function mockRequest($json, $business = null)
    {
        $stub = $this->createMock(Request::class);

        $jsonMap = [
            ['token', null, array_key_exists('token', $json) ? $json['token'] : null],
        ];

        $routeMap = [
            ['business', $business],
        ];

        $stub->expects($this->any())
            ->method('json')
            ->will($this->returnValueMap($jsonMap));

        $stub->expects($this->any())
            ->method('route')
            ->will($this->returnValueMap($routeMap));

        return $stub;
    }

    protected function mockApiAuth()
    {
        $stub = $this->createMock(ApiAuth::class);
        $stub->method('loadSession')
            ->will($this->returnValue(true));
        App::instance('apiauth', $stub);
        return $stub;
    }

    public function testThrowsIfNoToken()
    {
        $request = $this->mockRequest(['a' => 'b']);
        $this->expectException(InvalidTokenException::class);
        $this->middleware->handle($request, function () {
            //
        });
    }

    public function testThrowsIfNoBusiness()
    {
        $request = $this->mockRequest(['token' => 'test']);
        $this->expectException(InvalidRequestException::class);
        $this->middleware->handle($request, function () {
            //
        });
    }

    public function testThrowsIfInvalidToken()
    {
        $request = $this->mockRequest(['token' => 'invalid'], $this->business);
        $this->expectException(InvalidTokenException::class);
        $this->middleware->handle($request, function () {
            //
        });
    }

    public function testCallsClosureIfValid()
    {
        $this->mockApiAuth();
        $called = false;
        $request = $this->mockRequest(['token' => 'test'], $this->business);
        $this->middleware->handle($request, function () use (&$called) {
            $called = true;
        });
        $this->assertTrue($called);
    }

    public function testRegeneratesTokenIfValid()
    {
        $apiAuth = $this->mockApiAuth();
        $request = $this->mockRequest(['token' => 'test'], $this->business);
        $apiAuth->expects($this->once())->method('regenerateToken');

        $this->middleware->handle($request, function () {
            //
        });
    }
}
