<?php

namespace Tests\Unit;

use App\Business;
use App\Product;
use App\ProductCategory;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    public function testToArray()
    {
        $expected = [
            'id' => 123,
            'name' => 'Test category',
            'categories' => [
                ['id' => 223, 'name' => 'Sub 1', 'products' => [], 'categories' => []],
                ['id' => 323, 'name' => 'Sub 2', 'products' => [], 'categories' => []],
            ],
            'products' => [123, 465],
        ];

        $business = new Business();
        $business->id = 963;

        $products = collect([]);

        foreach ($expected['products'] as $productId) {
            $product = new Product();
            $product->id = $productId;
            $products->push($product);
        }

        // Create a mock to simulate recursive categories
        $category = $this->getMockBuilder(ProductCategory::class)
            ->setMethods(['getCategoriesRecursiveAttribute'])
            ->getMock();
        $category->method('getCategoriesRecursiveAttribute')
            ->willReturn(collect($expected['categories']));

        $category->id = $expected['id'];
        $category->fill($expected);
        $category->business()->associate($business);
        $category->setRelation('products', $products);

        $this->assertEquals($expected, $category->toArray());
    }


    protected function makeMockedCategoryWithProducts($products = [])
    {
        $category = new ProductCategory();
        $category->setRelation('products', $products);

        return $category;
    }
}
