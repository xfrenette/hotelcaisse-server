<?php

namespace Unit;

use App\ApiSession;
use Carbon\Carbon;
use Tests\TestCase;

class ApiSessionTest extends TestCase
{
    protected $apiSession;

    protected function setUp()
    {
        parent::setUp();
        $this->apiSession = factory(ApiSession::class)->make();
    }

    public function testExpired()
    {
        $this->apiSession->expires_at = Carbon::now()->addSecond(1);
        $this->assertFalse($this->apiSession->expired());

        $this->apiSession->expires_at = Carbon::now();
        $this->assertTrue($this->apiSession->expired());

        $this->apiSession->expires_at = Carbon::now()->subSecond(1);
        $this->assertTrue($this->apiSession->expired());
    }

    public function testExpire()
    {
        // First make sure it is not expired
        $this->apiSession->expires_at = Carbon::tomorrow();
        $this->apiSession->expire();
        $this->assertTrue($this->apiSession->expired());
    }
}
