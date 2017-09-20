<?php

namespace Tests\Feature;

use App\Business;
use App\Product;
use App\Tax;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var Product
     */
    protected $product;
    /**
     * @var Business
     */
    protected $business;

    protected function setUp()
    {
        parent::setUp();
        $this->product = factory(Product::class, 'withBusiness')->create();
        $this->business = $this->product->business;
    }

    protected function createTax($appliesToAll = true)
    {
        $defaultTax = factory(Tax::class)->make();
        $defaultTax->applies_to_all = $appliesToAll;
        $defaultTax->business()->associate($this->business);
        $defaultTax->save();

        return $defaultTax;
    }

    protected function insertRedefinedTax($product, $baseTax, $amount)
    {
        DB::table('product_tax')->insert([
            'amount' => $amount,
            'type' => $baseTax->type,
            'product_id' => $product->id,
            'tax_id' => $baseTax->id,
        ]);
    }

    public function testAppliedTaxesReturnsEmptyArrayIfNoTaxes()
    {
        $this->assertEquals(0, $this->product->appliedTaxes->count());
    }

    public function testAppliedTaxesAppliesDefaultPercentage()
    {
        $tax = $this->createTax();
        $tax->type = 'percentage';
        $tax->save();

        $expectedAmount = round(($tax->amount / 100) * $this->product->price, 2);
        $res = $this->product->appliedTaxes;
        $this->assertEquals(1, $res->count());
        $this->assertEquals(
            $res[0],
            ['taxId' => $tax->id, 'name' => $tax->name, 'amount' => $expectedAmount]
        );
        $this->assertInternalType('float', $res[0]['amount']);
    }

    public function testAppliedTaxesAppliesDefaultAbsolute()
    {
        $tax = $this->createTax();
        $tax->type = 'absolute';
        $tax->save();

        $expectedAmount = $tax->amount;
        $res = $this->product->appliedTaxes;
        $this->assertEquals(1, $res->count());
        $this->assertEquals(
            $res[0],
            ['taxId' => $tax->id, 'name' => $tax->name, 'amount' => $expectedAmount]
        );
        $this->assertInternalType('float', $res[0]['amount']);
    }

    public function testAppliedTaxesAppliesRedefinedPercentage()
    {
        $tax = $this->createTax();
        $tax->type = 'percentage';
        $tax->save();

        $newAmount = $tax->amount + 1;
        $this->insertRedefinedTax($this->product, $tax, $newAmount);

        $expectedAmount = round(($newAmount / 100) * $this->product->price, 2);
        $res = $this->product->appliedTaxes;
        $this->assertEquals(1, $res->count());
        $this->assertEquals(
            $res[0],
            ['taxId' => $tax->id, 'name' => $tax->name, 'amount' => $expectedAmount]
        );
        $this->assertInternalType('float', $res[0]['amount']);
    }

    public function testAppliedTaxesAppliesRedefinedAbsolute()
    {
        $tax = $this->createTax();
        $tax->type = 'absolute';
        $tax->save();

        $newAmount = $tax->amount + 1;
        $this->insertRedefinedTax($this->product, $tax, $newAmount);

        $expectedAmount = $newAmount;
        $res = $this->product->appliedTaxes;
        $this->assertEquals(1, $res->count());
        $this->assertEquals(
            $res[0],
            ['taxId' => $tax->id, 'name' => $tax->name, 'amount' => $expectedAmount]
        );
        $this->assertInternalType('float', $res[0]['amount']);
    }

    public function testAppliedTaxesIncludesDefaultTaxes()
    {
        $tax = $this->createTax();
        $tax->type = 'absolute';
        $tax->save();

        $res = $this->product->appliedTaxes;
        $this->assertEquals(
            $res[0],
            ['taxId' => $tax->id, 'name' => $tax->name, 'amount' => $tax->amount]
        );
        $this->assertInternalType('float', $res[0]['amount']);
    }

    public function testAppliedTaxesIncludesRedefinedNonDefaultTaxes()
    {
        $tax = $this->createTax(false);
        $tax->type = 'absolute';
        $tax->save();

        $amount = $tax->amount + 1;
        $this->insertRedefinedTax($this->product, $tax, $amount);

        $res = $this->product->appliedTaxes;
        $this->assertEquals(
            $res[0],
            ['taxId' => $tax->id, 'name' => $tax->name, 'amount' => $amount]
        );
        $this->assertInternalType('float', $res[0]['amount']);
    }

    public function testAppliedTaxesDoesNotIncludeNonRedefinedNonDefault()
    {
        $tax = $this->createTax(false);
        $tax->type = 'absolute';
        $tax->save();

        $res = $this->product->appliedTaxes;
        $this->assertEquals(0, $res->count());
    }

    public function testAppliedTaxesDoesNotIncludeRedefinedZero()
    {
        $tax = $this->createTax();
        $tax->type = 'percentage';
        $tax->save();

        $this->insertRedefinedTax($this->product, $tax, 0);
        $res = $this->product->appliedTaxes;

        $this->assertEquals(0, $res->count());
    }

    /**
     * Test for a bug we had where if a Product redefines all taxes, another product that was not
     * returning thoses taxes if it didn't redefine them
     */
    public function testAppliedTaxesWorkIfOtherProductRedefinesAllTaxes()
    {
        $tax1 = $this->createTax();
        $tax1->type = 'absolute';
        $tax1->save();

        $tax2 = $this->createTax(false);
        $tax2->type = 'absolute';
        $tax2->save();

        // Other business tax
        $otherBusiness = factory(Business::class)->create();
        $tax3 = factory(Tax::class)->make();
        $tax3->business()->associate($otherBusiness);
        $tax3->save();

        $otherProduct = Product::make([ 'name' => 'test' ]);
        $otherProduct->business()->associate($this->business);
        $otherProduct->save();

        $this->insertRedefinedTax($otherProduct, $tax1, 1);
        $this->insertRedefinedTax($otherProduct, $tax2, 2);

        $taxes = $this->product->appliedTaxes;
        $this->assertEquals($taxes->count(), 1);

        $otherTaxes = $otherProduct->appliedTaxes;
        $this->assertEquals($otherTaxes->count(), 2);
    }
}
