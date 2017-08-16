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

        $expectedAmount = ($tax->amount / 100) * $this->product->price;
        $res = $this->product->appliedTaxes;
        $this->assertEquals(1, $res->count());
        $this->assertEquals($res[0], ['id' => $tax->id, 'amount' => $expectedAmount]);
    }

    public function testAppliedTaxesAppliesDefaultAbsolute()
    {
        $tax = $this->createTax();
        $tax->type = 'absolute';
        $tax->save();

        $expectedAmount = $tax->amount;
        $res = $this->product->appliedTaxes;
        $this->assertEquals(1, $res->count());
        $this->assertEquals($res[0], ['id' => $tax->id, 'amount' => $expectedAmount]);
    }

    public function testAppliedTaxesAppliesRedefinedPercentage()
    {
        $tax = $this->createTax();
        $tax->type = 'percentage';
        $tax->save();

        $newAmount = $tax->amount + 1;
        $this->insertRedefinedTax($this->product, $tax, $newAmount);

        $expectedAmount = ($newAmount / 100) * $this->product->price;
        $res = $this->product->appliedTaxes;
        $this->assertEquals(1, $res->count());
        $this->assertEquals($res[0], ['id' => $tax->id, 'amount' => $expectedAmount]);
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
        $this->assertEquals($res[0], ['id' => $tax->id, 'amount' => $expectedAmount]);
    }

    public function testAppliedTaxesIncludesDefaultTaxes()
    {
        $tax = $this->createTax();
        $tax->type = 'absolute';
        $tax->save();

        $res = $this->product->appliedTaxes;
        $this->assertEquals($res[0], ['id' => $tax->id, 'amount' => $tax->amount]);
    }

    public function testAppliedTaxesIncludesRedefinedNonDefaultTaxes()
    {
        $tax = $this->createTax(false);
        $tax->type = 'absolute';
        $tax->save();

        $amount = $tax->amount + 1;
        $this->insertRedefinedTax($this->product, $tax, $amount);

        $res = $this->product->appliedTaxes;
        $this->assertEquals($res[0], ['id' => $tax->id, 'amount' => $amount]);
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
}
