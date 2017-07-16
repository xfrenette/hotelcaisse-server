<?php

namespace Feature;

use App\ApiSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiSessionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var ApiSession
     */
    protected $apiSession;

    protected function setUp()
    {
        parent::setUp();
        $this->apiSession = factory(ApiSession::class, 'withBusinessAndDevice')->create();
    }

    public function testScopeValid()
    {
        $this->apiSession->expires_at = Carbon::tomorrow();
        $this->apiSession->save();
        $results = ApiSession::valid()->get();
        $this->assertEquals(1, $results->count());

        $this->apiSession->expires_at = Carbon::yesterday();
        $this->apiSession->save();
        $results = ApiSession::valid()->get();
        $this->assertEquals(0, $results->count());
    }
}
