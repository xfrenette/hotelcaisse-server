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
        $this->apiSession = factory(ApiSession::class, 'withDeviceAndBusiness')->create();
    }

    public function testScopeValid()
    {
        $query = ApiSession::valid();
        $prevCount = $query->count();

        $apiSession = factory(ApiSession::class, 'withDeviceAndBusiness')->make();
        $apiSession->expires_at = Carbon::tomorrow();
        $apiSession->save();

        $this->assertEquals($prevCount + 1, $query->count());

        $apiSession->expires_at = Carbon::yesterday();
        $apiSession->save();
        $this->assertEquals($prevCount, $query->count());
    }
}
