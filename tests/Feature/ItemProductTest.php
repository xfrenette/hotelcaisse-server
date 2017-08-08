<?php

namespace Tests\Feature;

use App\Business;
use App\ItemProduct;
use App\Tax;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ItemProductTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var ItemProduct
     */
    protected $itemProduct;

    protected function setUp()
    {
        parent::setUp();
        $this->itemProduct = factory(ItemProduct::class)->create();
    }

    public function testSetTaxesCreatesRows()
    {
        $business = factory(Business::class)->create();

        $tax1 = factory(Tax::class)->make();
        $tax1->business()->associate($business);
        $tax1->save();

        $tax2 = factory(Tax::class)->make();
        $tax2->business()->associate($business);
        $tax2->save();

        $taxes = [
            ['tax' => $tax1->id, 'amount' => 12.34],
            ['tax' => $tax2->id, 'amount' => 45.67],
        ];

        $this->itemProduct->setTaxes($taxes);

        $res = DB::table('applied_taxes')
            ->where([
                'type' => 'ItemProduct',
                'instance_id' => $this->itemProduct->id,
            ])->get();

        $this->assertEquals(count($taxes), $res->count());
        $this->assertEquals($taxes[1]['amount'], $res[1]->amount);
        $this->assertEquals($taxes[1]['tax'], $res[1]->tax_id);
    }
}
