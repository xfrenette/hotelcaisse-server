<?php

namespace Tests\Unit;

use App\Business;
use App\Product;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function testToArray()
    {
        $expected = [
            'id' => 789,
            'name' => 'Test name',
            'description' => 'test description',
            'price' => 12.34,
            'taxes' => [
                ['tax' => 7878, 'amount' => 12.34],
                ['tax' => 2121, 'amount' => 14.68],
            ],
            'variants' => [963, 852],
        ];

        $variants = collect();
        foreach ($expected['variants'] as $variantId) {
            $variant = $this->makeMockedProductWithTaxes();
            $variant->fill(['name' => 'Test name #'.$variantId]);
            $variant->id = $variantId;
            $variants->push($variant);
        }

        $business = new Business();
        $business->id = 456;

        $product = $this->makeMockedProductWithTaxes(collect($expected['taxes']));

        $product->fill($expected);
        $product->id = $expected['id'];

        // Simulate relation
        $product->setRelation('variants', $variants);
        $product->business()->associate($business);

        $this->assertEquals($expected, $product->toArray());
    }

    protected function makeMockedProductWithTaxes($taxes = [])
    {
        $product = $this->getMockBuilder(Product::class)
            ->setMethods(['getAppliedTaxesAttribute'])
            ->getMock();
        $product->method('getAppliedTaxesAttribute')
            ->willReturn($taxes);

        return $product;
    }
}
