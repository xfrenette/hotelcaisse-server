<?php

namespace Tests\Unit;

use App\Item;
use App\ItemProduct;
use App\Order;
use Tests\TestCase;

class ItemTest extends TestCase
{
    public function testToArray()
    {
        $itemProduct = new ItemProduct([
            'name' => 'test-name',
            'price' => 123.87,
        ]);
        $itemProduct->id = 456;

        $expected = [
            'uuid' => 'test-uuid',
            'quantity' => 3.5,
            'product' => $itemProduct->toArray(),
        ];

        $order = new Order();
        $order->id = 963;

        $item = new Item($expected);
        $item->id = 741;
        $item->product()->associate($itemProduct);
        $item->order()->associate($order);

        $this->assertEquals($expected, $item->toArray());
    }
}
