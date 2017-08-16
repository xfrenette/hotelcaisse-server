<?php

namespace Tests\Unit\Http\Middleware\Api;

use App\Exceptions\Api\AuthenticationException;
use App\Exceptions\Api\InvalidRequestException;
use App\Http\Middleware\Api\Authenticate;
use App\Team;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class AuthenticateTest extends TestCase
{
    use InteractsWithAPI;

    /**
     * @var Authenticate
     */
    protected $middleware;
    /**
     * @var Team
     */
    protected $team;
    protected $apiAuth;

    protected function setUp()
    {
        parent::setUp();
        $this->middleware = new Authenticate();
        $team = \Mockery::mock(Team::class)->makePartial();
        $team->shouldReceive('canAccessApi')->andReturn(true);
        $this->team = $team;

        $this->apiAuth = $this->mockApiAuth();
        $this->apiAuth->shouldReceive('regenerateToken')->andReturn();
    }

    protected function tearDown()
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testThrowsIfNoToken()
    {
        $request = $this->mockRequest(['a' => 'b']);
        $this->expectException(AuthenticationException::class);
        $this->middleware->handle($request, function () {
            //
        });
    }

    public function testThrowsIfNoTeam()
    {
        $request = $this->mockRequest(['token' => 'test']);
        $this->expectException(InvalidRequestException::class);
        $this->middleware->handle($request, function () {
            //
        });
    }

    public function testThrowsIfLoadSessionFails()
    {
        $this->apiAuth->shouldReceive('loadSession')->andReturn(false);
        $request = $this->mockRequest(['token' => 'invalid'], new Team());
        $this->expectException(AuthenticationException::class);
        $this->middleware->handle($request, function () {
            //
        });
    }

    public function testThrowsIfTeamDoesntHaveAccessToApi()
    {
        $team = \Mockery::mock(Team::class)->makePartial();
        $team->shouldReceive('canAccessApi')->andReturn(false);
        $request = $this->mockRequest(['token' => 'test'], $team);

        $this->expectException(AuthenticationException::class);
        $this->middleware->handle($request, function () {
            //
        });
    }

    public function testCallsClosureIfValid()
    {
        $called = false;
        $this->apiAuth->shouldReceive('loadSession')->andReturn(true);
        $request = $this->mockRequest(['token' => 'test'], $this->team);
        $this->middleware->handle($request, function () use (&$called) {
            $called = true;
        });
        $this->assertTrue($called);
    }

    public function testRegeneratesTokenIfValid()
    {
        $this->apiAuth->shouldReceive('loadSession')->andReturn(true);
        $request = $this->mockRequest(['token' => 'test'], $this->team);
        $this->apiAuth->shouldReceive('regenerateToken')->byDefault()->once()->andReturn();

        $this->middleware->handle($request, function () {
            //
        });
    }
}
