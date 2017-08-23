<?php

namespace Tests\Unit;

use App\Business;
use App\Product;
use Mockery as m;
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
                ['taxId' => 7878, 'amount' => 12.34, 'name' => 'Tax 1'],
                ['taxId' => 2121, 'amount' => 14.68, 'name' => 'Tax 2'],
            ],
            'variants' => [
                ['id' => 963, 'name' => 'Test variant 1'],
                ['id' => 852, 'name' => 'Test variant 2'],
            ],
        ];

        $variants = collect();
        foreach ($expected['variants'] as $variantData) {
            $variant = $this->makeMockedProductWithTaxes();
            $variant->shouldReceive('toArray')->andReturn($variantData);
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
        $product = m::mock(Product::class)->makePartial();
        $product->shouldReceive('getAppliedTaxesAttribute')->andReturn($taxes);

        return $product;
    }
}
