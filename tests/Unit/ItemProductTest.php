<?php

namespace Tests\Unit;

use App\Item;
use App\ItemProduct;
use App\Product;
use Tests\TestCase;

class ItemProductTest extends TestCase
{
    public function testToArray()
    {
        $taxes = [
            ['tax_id' => 123, 'amount' => 12.34],
            ['tax_id' => 345, 'amount' => 45.5687],
        ];

        $expected = [
            'taxes' => $taxes,
            'name' => 'test-name',
            'price' => 123.87,
        ];

        $product = new Product();
        $product->id = 963;
        $item = new Item();
        $item->id = 741;

        $itemProduct = $this->getMockBuilder(ItemProduct::class)
            ->setMethods(['getTaxesAttribute'])
            ->getMock();
        $itemProduct->method('getTaxesAttribute')
            ->willReturn($taxes);
        $itemProduct->id = 789;
        $itemProduct->product()->associate($product);
        $itemProduct->item()->associate($item);
        $itemProduct->fill($expected);

        $this->assertEquals($expected, $itemProduct->toArray());
    }
}
