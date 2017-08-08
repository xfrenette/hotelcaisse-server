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
            'name' => 'Test category',
            'categories' => [
                ['name' => 'Sub 1', 'products' => []],
                ['name' => 'Sub 2', 'products' => []],
            ],
            'products' => [123, 465],
        ];

        $business = new Business();
        $business->id = 963;

        $categories = collect([]);
        $products = collect([]);

        foreach ($expected['categories'] as $categoryData) {
            $category = new ProductCategory($categoryData);
            $category->setRelation('products', collect([]));
            $categories->push($category);
        }

        foreach ($expected['products'] as $productId) {
            $product = new Product();
            $product->id = $productId;
            $products->push($product);
        }

        $category = new ProductCategory($expected);
        $category->business()->associate($business);
        $category->setRelation('categories', $categories);
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
