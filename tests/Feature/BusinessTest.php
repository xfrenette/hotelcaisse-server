<?php

namespace Tests\Feature;

use App\Business;
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

    public function testVersionReturnsNullIfNoVersion()
    {
        $this->assertNull($this->business->version);
    }

    public function testVersionReturnsLastVersion()
    {
        $expected = 8;
        $otherBusiness = factory(Business::class)->create();
        $this->business->insertVersion($expected - 1);
        $this->business->insertVersion($expected);
        // The next line is for another Business
        $otherBusiness->insertVersion($expected + 1);
        $this->assertEquals((string) $expected, $this->business->version);
    }

    public function testVersionReturnsString()
    {
        $this->business->insertVersion(100);
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
        $this->business->insertVersion($oldVersion);
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

    public function testGetVersionModificationsReturnsNullIfNotAVersion()
    {
        $version = '4';
        // We insert the version number for *another* business
        $otherBusiness = factory(Business::class)->create();
        $otherBusiness->insertVersion($version);
        $this->assertNull($this->business->getVersionModifications($version));
    }

    public function testGetVersionModificationsReturnsEmptyArrayIfNoModifications()
    {
        $version = '4';
        $this->business->insertVersion($version);
        $res = $this->business->getVersionModifications($version);
        $this->assertTrue(is_array($res));
        $this->assertCount(0, $res);
    }

    public function testGetVersionModificationsReturnsArrayOfModifications()
    {
        $version = '4';
        $modifications = ['c', 'a'];
        $this->business->insertVersion($version, $modifications);
        $res = $this->business->getVersionModifications($version);
        $this->assertTrue(is_array($res));
        $this->assertEquals($modifications, $res);
    }
}
