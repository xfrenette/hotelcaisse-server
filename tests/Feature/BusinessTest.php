<?php

namespace Tests\Feature;

use App\Business;
use App\Field;
use App\Product;
use App\ProductCategory;
use App\Room;
use App\Tax;
use App\TransactionMode;
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

    public function testCustomerFieldsReturnsFields()
    {
        $business = factory(Business::class)->create();
        $field1 = factory(Field::class)->create();
        $field2 = factory(Field::class)->create();
        $field3 = factory(Field::class)->create();

        DB::table('business_fields')->insert([
            ['type' => 'customer', 'business_id' => $business->id, 'field_id' => $field1->id],
            ['type' => 'customer', 'business_id' => $business->id, 'field_id' => $field2->id],
            ['type' => 'roomSelection', 'business_id' => $business->id, 'field_id' => $field3->id],
        ]);

        $fields = $business->customerFields;
        $this->assertEquals(2, $fields->count());
        $this->assertEquals($field1->id, $fields[0]->id);
        $this->assertEquals($field2->id, $fields[1]->id);
    }

    public function testRoomSelectionFieldsReturnsFields()
    {
        $field1 = factory(Field::class)->create();
        $field2 = factory(Field::class)->create();
        $field3 = factory(Field::class)->create();

        DB::table('business_fields')->insert([
            ['type' => 'roomSelection', 'business_id' => $this->business->id, 'field_id' => $field1->id],
            ['type' => 'roomSelection', 'business_id' => $this->business->id, 'field_id' => $field2->id],
            ['type' => 'customer', 'business_id' => $this->business->id, 'field_id' => $field3->id],
        ]);

        $fields = $this->business->roomSelectionFields;
        $this->assertEquals(2, $fields->count());
        $this->assertEquals($field1->id, $fields[0]->id);
        $this->assertEquals($field2->id, $fields[1]->id);
    }

    public function testRootProductCategoryReturnsCorrectCategory()
    {
        $otherBusiness = factory(Business::class)->create();

        $rootCategory = factory(ProductCategory::class)->make();
        $rootCategory->business()->associate($this->business);
        $rootCategory->save();

        $category1 = factory(ProductCategory::class)->make();
        $category1->business()->associate($this->business);
        $category1->parent()->associate($rootCategory);
        $category1->save();

        $category2 = factory(ProductCategory::class)->make();
        $category2->business()->associate($otherBusiness);
        $category2->save();

        $res = $this->business->rootProductCategory;

        $this->assertInstanceOf(ProductCategory::class, $res);
        $this->assertEquals($rootCategory->id, $res->id);
    }

    // The following test requires the seeded test data
    public function testToArray()
    {
        $business = Business::first();
        $business->loadAllRelations();

        $array = $business->toArray();

        // Multiple related models all have an `id` and a `name` attribute we can check to see if present
        $simpleRelations = [
            'rooms' => Room::class,
            'taxes' => Tax::class,
            'transactionModes' => TransactionMode::class,
            'products' => Product::class,
        ];

        // For each $simpleRelations, check the count is the correct and that at least one has the same `name` attribute
        foreach ($simpleRelations as $key => $className) {
            $models = call_user_func($className . '::where', 'business_id', $business->id);
            $this->assertCount($models->count(), $array[$key]);

            $sampleModel = $models->first();
            $sampleData = array_first($array[$key], function ($data) use ($sampleModel) {
                return $sampleModel->id === $data['id'];
            }, false);
            $this->assertEquals($sampleData['name'], $sampleModel->name);
        }

        // Check the products are present with taxes and variants, where applicable
        $this->assertNotEmpty($array['products'][0]['taxes']);
        $this->assertNotFalse(array_first($array['products'], function ($product) {
            return count($product['variants']) > 0;
        }, false));

        // Check customerFields
        $this->assertCount($business->customerFields->count(), $array['customerFields']);
        $sampleField = Field::find($array['customerFields'][0]['id']);
        $this->assertEquals($sampleField->type, $array['customerFields'][0]['type']);

        // Check roomSelectionFields
        $this->assertCount($business->roomSelectionFields->count(), $array['roomSelectionFields']);
        $sampleField = Field::find($array['roomSelectionFields'][0]['id']);
        $this->assertEquals($sampleField->type, $array['roomSelectionFields'][0]['type']);

        // Check rootProductCategory is present
        $this->assertEquals($business->rootProductCategory->id, $array['rootProductCategory']['id']);
    }
}
