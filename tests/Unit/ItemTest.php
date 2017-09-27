<?php

namespace Tests\Unit;

use App\Item;
use App\ItemProduct;
use App\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery as m;
use Tests\TestCase;

class ItemTest extends TestCase
{
    public function testToArray()
    {
        $date = Carbon::yesterday();
        $itemProduct = new ItemProduct([
            'name' => 'test-name',
            'price' => 123.87,
        ]);
        $itemProduct->id = 456;

        $expected = [
            'uuid' => 'test-uuid',
            'quantity' => 3.5,
            'product' => $itemProduct->toArray(),
            'createdAt' => $date->getTimestamp(),
        ];

        $order = new Order();
        $order->id = 963;

        $item = new Item($expected);
        $item->id = 741;
        $item->product()->associate($itemProduct);
        $item->order()->associate($order);
        $item->created_at = $date;

        $this->assertEquals($expected, $item->toArray());
    }

    public function testCasts()
    {
        $item = new Item(['quantity' => '1.23']);
        $this->assertInternalType('float', $item->quantity);
    }

    public function testGetValuesAttribute()
    {
        $qty = -6;
        $unitPrice = 10.235;
        $expected = round($qty * $unitPrice, 4);
        $taxes = new Collection([
            ['taxId' => 1, 'name' => 'TPS', 'amount' => 0.33],
            ['taxId' => 2, 'name' => 'TVQ', 'amount' => 1.8701],
        ]);

        $item = new Item(['quantity' => $qty]);
        $itemProduct = m::mock(ItemProduct::class)->makePartial();
        $itemProduct->fill(['price' => $unitPrice]);
        $itemProduct->shouldReceive('getTaxesAttribute')->andReturn($taxes);
        $item->setRelation('product', $itemProduct);

        $this->assertEquals($expected, $item->subTotal);
        $this->assertInternalType('float', $item->subTotal);

        $expectedTaxes = $taxes->map(function ($tax) use ($qty) {
            return [
                'taxId' => $tax['taxId'],
                'name' => $tax['name'],
                'amount' => round($qty * $tax['amount'], 4),
            ];
        });

        $this->assertEquals($expectedTaxes, $item->taxes);
    }

}
