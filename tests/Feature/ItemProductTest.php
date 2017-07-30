<?php

namespace Tests\Feature;

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
        $this->itemProduct = factory(ItemProduct::class, 'withItem')->create();
    }

    public function testSetTaxesCreatesRows()
    {
        $business = $this->itemProduct->item->order->business;

        $tax1 = factory(Tax::class)->make();
        $tax1->business()->associate($business);
        $tax1->save();

        $tax2 = factory(Tax::class)->make();
        $tax2->business()->associate($business);
        $tax2->save();

        $taxes = [
            ['tax_id' => $tax1->id, 'amount' => 12.34],
            ['tax_id' => $tax2->id, 'amount' => 45.67],
        ];

        $this->itemProduct->setTaxes($taxes);

        $res = DB::table('applied_taxes')
            ->where([
                'type' => 'ItemProduct',
                'instance_id' => $this->itemProduct->id,
            ])->get();

        $this->assertEquals(count($taxes), $res->count());
        $this->assertEquals($taxes[1]['amount'], $res[1]->amount);
        $this->assertEquals($taxes[1]['tax_id'], $res[1]->tax_id);
    }
}
