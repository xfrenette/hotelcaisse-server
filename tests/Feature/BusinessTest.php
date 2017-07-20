<?php

namespace Tests\Feature;

use App\Business;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var Business
     */
    protected $business;

    protected function setUp()
    {
        parent::setUp();
        $this->business = factory(Business::class)->create();
    }

    protected function insertBusinessVersion($number, Business $business)
    {
        DB::table('business_versions')->insert([
            'created_at' => Carbon::now()->format('Y-m-d H:m:s'),
            'business_id' => $business->id,
            'version' => (string) $number,
            'modifications' => 'register',
        ]);
    }

    public function testVersionReturnsNullIfNoVersion()
    {
        $this->assertNull($this->business->version);
    }

    public function testVersionReturnsLastVersion()
    {
        $expected = 8;
        $otherBusiness = factory(Business::class)->create();
        $this->insertBusinessVersion($expected - 1, $this->business);
        $this->insertBusinessVersion($expected, $this->business);
        // The next line is for another Business
        $this->insertBusinessVersion($expected + 1, $otherBusiness);
        $this->assertEquals((string) $expected, $this->business->version);
    }

    public function testVersionReturnsString()
    {
        $this->insertBusinessVersion(100, $this->business);
        $this->assertTrue(is_string($this->business->version));
    }

    public function testBumpVersionSetsAInitialVersionIfNoVersionYet()
    {
        $newBusiness = factory(Business::class)->create();
        $version = $newBusiness->bumpVersion();
        $this->assertTrue(is_string($version));
        $this->assertFalse(empty($version));
    }

    public function testBumpVersionUpdatesVersion()
    {
        $oldVersion = 4;
        $this->insertBusinessVersion($oldVersion, $this->business);
        $newVersion = $this->business->bumpVersion();
        $this->assertNotEquals($oldVersion, $newVersion);
        // Ensure the new version is the one returned by `$business->version`
        $this->assertEquals($newVersion, $this->business->version);
    }

    public function testBumpVersionSavesModifications()
    {
        $modifications = ['a', 'b'];
        $newVersion = $this->business->bumpVersion($modifications);
        $savedModifications = DB::table('business_versions')
            ->where(['business_id' => $this->business->id, 'version' => $newVersion])
            ->value('modifications');
        $this->assertSame($modifications, explode(',', $savedModifications));
    }

    public function testBumpVersionUpdatesTheDB()
    {
        $newVersion = $this->business->bumpVersion();
        $this->assertEquals($newVersion, $this->business->version);
    }
}
