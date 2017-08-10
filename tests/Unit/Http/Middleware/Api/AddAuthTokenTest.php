<?php

namespace Tests\Http\Middleware\Api;

use App\Api\Auth\ApiAuth;
use App\Api\Http\ApiResponse;
use App\Http\Middleware\Api\AddAuthToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class AddAuthTokenTest extends TestCase
{
    /**
     * @var AddAuthToken
     */
    protected $middleware;

    protected function setUp()
    {
        parent::setUp();
        $this->middleware = new AddAuthToken();
    }

    protected function makeRequest()
    {
        return Request::create('/test');
    }

    protected function mockApiAuthGetToken($token)
    {
        $stub = $this->createMock(ApiAuth::class);
        $stub->method('getToken')
            ->will($this->returnValue($token));
        $stub->method('check')
            ->will($this->returnValue(true));
        App::instance('apiauth', $stub);
        return $stub;
    }

    public function testWorksIfNotApiResponse()
    {
        $request = $this->makeRequest();
        $testContent = 'string content';
        $res = $this->middleware->handle($request, function () use ($testContent) {
            return $testContent;
        });
        $this->assertEquals($testContent, $res);
    }

    public function testSetTokenOnApiResponse()
    {
        $token = 'test-token';
        $request = $this->makeRequest();
        $this->mockApiAuthGetToken($token);
        $res = $this->middleware->handle($request, function () {
            return new ApiResponse();
        });
        $this->assertEquals($token, $res->getToken());
    }
}
