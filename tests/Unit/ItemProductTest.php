<?php

namespace Tests\Unit;

use App\ItemProduct;
use App\Product;
use Tests\TestCase;

class ItemProductTest extends TestCase
{
    public function testToArray()
    {
        $taxes = [
            ['taxId' => 123, 'amount' => 12.34, 'name' => 'Tax 1'],
            ['taxId' => 345, 'amount' => 45.5687, 'name' => 'Tax 2'],
        ];

        $expected = [
            'id' => 963,
            'taxes' => $taxes,
            'name' => 'test-name',
            'price' => 123.87,
        ];

        $product = new Product();
        $product->id = $expected['id'];

        $itemProduct = $this->getMockBuilder(ItemProduct::class)
            ->setMethods(['getTaxesAttribute'])
            ->getMock();
        $itemProduct->method('getTaxesAttribute')
            ->willReturn($taxes);
        $itemProduct->id = 789;
        $itemProduct->product()->associate($product);
        $itemProduct->fill($expected);

        $this->assertEquals($expected, $itemProduct->toArray());
    }

    public function testCasts()
    {
        $itemProduct = new ItemProduct(['price' => '1.23']);
        $this->assertInternalType('float', $itemProduct->price);
    }
}
