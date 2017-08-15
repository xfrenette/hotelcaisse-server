<?php

namespace Tests\Feature;

use App\ProductCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use DatabaseTransactions;

    // This test requires the test seeded data
    public function testToArray()
    {
        $rootCategory = ProductCategory::whereNull('parent_id')->first();
        $array = $rootCategory->toArray();

        $this->assertCategory($rootCategory, $array);
    }

    protected function assertCategory($category, $data)
    {
        // Check all products are there
        $this->assertCount($category->products()->count(), $data['products']);

        // Check all categories are there
        $this->assertCount($category->categories()->count(), $data['categories']);

        // For each sub-category, validate
        $category->categories->map(function ($subCategory) use ($data) {
            $subData = array_first($data['categories'], function ($catData) use ($subCategory) {
                return $catData['id'] === $subCategory->id;
            });
            $this->assertCategory($subCategory, $subData);
        });
    }
}
