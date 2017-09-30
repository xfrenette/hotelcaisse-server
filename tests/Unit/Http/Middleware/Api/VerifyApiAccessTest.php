<?php

namespace Tests\Unit\Http\Middleware\Api;

use App\Exceptions\Api\AuthenticationException;
use App\Http\Middleware\Api\Authenticate;
use App\Http\Middleware\Api\VerifyApiAccess;
use App\Team;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class VerifyApiAccessTest extends TestCase
{
    use InteractsWithAPI;

    /**
     * @var Authenticate
     */
    protected $middleware;
    /**
     * @var \Mockery\Mock
     */
    protected $apiAuth;

    protected function setUp()
    {
        parent::setUp();
        $this->middleware = new VerifyApiAccess();
        $this->apiAuth = $this->mockApiAuth(false);
    }

    protected function tearDown()
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testThrowsIfNotAuthenticated()
    {
        $this->apiAuth->shouldReceive('check')->andReturn(false);
        $request = $this->mockRequest();
        $this->expectException(AuthenticationException::class);
        $this->middleware->handle($request, function () {
            //
        });
    }

    public function testThrowsIfTeamDoesntHaveAccessToApi()
    {
        $team = \Mockery::mock(Team::class)->makePartial();
        $team->shouldReceive('canAccessApi')->andReturn(false);
        $this->apiAuth->shouldReceive('check')->andReturn(true);
        $this->apiAuth->shouldReceive('getTeam')->andReturn($team);
        $request = $this->mockRequest();

        $this->expectException(AuthenticationException::class);
        $this->middleware->handle($request, function () {
            //
        });
    }

    public function testCallsClosureIfValid()
    {
        $team = \Mockery::mock(Team::class)->makePartial();
        $team->shouldReceive('canAccessApi')->andReturn(true);
        $this->apiAuth->shouldReceive('getTeam')->andReturn($team);
        $this->apiAuth->shouldReceive('check')->andReturn(true);
        $request = $this->mockRequest();

        $called = false;
        $this->middleware->handle($request, function () use (&$called) {
            $called = true;
        });
        $this->assertTrue($called);
    }
}
