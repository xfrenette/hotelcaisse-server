<?php

namespace Tests\Unit\Http\Middleware\Api;

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
    /**
     * @var \Mockery\Mock
     */
    protected $apiAuth;

    protected function setUp()
    {
        parent::setUp();
        $this->middleware = new Authenticate();
        $team = \Mockery::mock(Team::class)->makePartial();
        $this->team = $team;

        $this->apiAuth = $this->mockApiAuth(false);
    }

    protected function tearDown()
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testNoTokenOrTeam()
    {
        $this->apiAuth->shouldNotHaveReceived('loadSession');
        $this->apiAuth->shouldNotReceive('regenerateToken');

        // Missing team and token
        $request = $this->mockRequest(['a' => 'b']);
        $called = false;
        $this->middleware->handle($request, function () use (&$called) {
            // Should not throw
            $called = true;
        });
        $this->assertTrue($called);
        $this->assertFalse($this->apiAuth->check());

        // Missing team
        $request = $this->mockRequest(['token' => '85421']);
        $called = false;
        $this->middleware->handle($request, function () use (&$called) {
            // Should not throw
            $called = true;
        });
        $this->assertTrue($called);
        $this->assertFalse($this->apiAuth->check());

        // Missing team
        $request = $this->mockRequest(['token' => '85421']);
        $called = false;
        $this->middleware->handle($request, function () use (&$called) {
            // Should not throw
            $called = true;
        });
        $this->assertTrue($called);
        $this->assertFalse($this->apiAuth->check());

        // Missing token
        $request = $this->mockRequest(['a' => 'b'], new Team());
        $this->middleware->handle($request, function () {
            // Should not throw
        });
        $this->assertFalse($this->apiAuth->check());
    }

    public function testInvalidToken()
    {
        $token = 'invalid-token';
        $team = new Team();
        $request = $this->mockRequest(['token' => $token], $team);

        $this->apiAuth
            ->shouldReceive('loadSession')
            ->once()
            ->with($token, $team)
            ->andReturn(false);

        $this->apiAuth->shouldNotReceive('regenerateToken');

        $this->middleware->handle($request, function () {
            //
        });
        $this->assertFalse($this->apiAuth->check());
    }

    public function testRegeneratesToken()
    {
        $this->apiAuth->shouldReceive('loadSession')->andReturn(true);
        $this->apiAuth->shouldReceive('regenerateToken')->once();
        $request = $this->mockRequest(['token' => 'test'], new Team());

        $this->middleware->handle($request, function () {
            //
        });
    }
}
