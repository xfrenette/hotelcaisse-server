<?php

namespace Feature\Api\Auth;

use App\Api\Auth\ApiAuth;
use App\ApiSession;
use App\Business;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var ApiAuth
     */
    protected $apiAuth;
    /**
     * @var Business
     */
    protected $business;
    /**
     * @var ApiSession
     */
    protected $apiSession;

    protected function setUp()
    {
        parent::setUp();
        $this->apiAuth = new ApiAuth();
        $this->apiSession = factory(ApiSession::class, 'withBusinessAndDevice')->create();
        $this->business = $this->apiSession->business;
    }

    public function testLoadSessionReturnsFalseIfInvalidToken()
    {
        $this->assertFalse($this->apiAuth->loadSession('false-token', $this->business));
    }

    public function testLoadSessionReturnsFalseIfValidTokenButInvalidBusiness()
    {
        $newBusiness = factory(Business::class)->create();
        $this->assertFalse($this->apiAuth->loadSession($this->apiSession->token, $newBusiness));
    }

    public function testLoadSessionReturnsFalseIfExpiredSession()
    {
        $this->apiSession->expire();
        $this->apiSession->save();
        $this->assertFalse($this->apiAuth->loadSession($this->apiSession->token, $this->business));
    }

    public function testLoadSessionReturnsTrueIfValid()
    {
        $this->assertTrue($this->loadValidSession());
    }

    public function testLoadSessionLoadsApiSessionIfValid()
    {
        $this->loadValidSession();
        $this->assertEquals($this->apiSession->id, $this->apiAuth->apiSession->id);
    }

    public function testLoadSessionClearsCurrentSession()
    {
        $this->loadValidSession();
        $this->apiAuth->loadSession('invalid-token', $this->business);
        $this->assertFalse($this->apiAuth->check());
    }

    public function testCheck()
    {
        $this->loadValidSession();
        $this->assertTrue($this->apiAuth->check());

        $this->apiAuth->logout();
        $this->assertFalse($this->apiAuth->check());
    }

    protected function loadValidSession()
    {
        return $this->apiAuth->loadSession($this->apiSession->token, $this->business);
    }
}
