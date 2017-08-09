<?php

use App\Business;
use App\Product;
use App\ProductCategory;
use Faker\Factory;
use Illuminate\Database\Seeder;

class ProductCategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (ProductCategory::count() > 0) {
            return;
        }

        $faker = Factory::create();
        $business = Business::first();

        $rootCategory = null;
        $parentCategory = null;

        for ($i = 0; $i < 10; $i++) {
            $category = new ProductCategory();
            $category->name = $faker->word();
            $category->business()->associate($business);

            if ($i === 0) {
                $rootCategory = $category;
            } elseif ($i <= 6) {
                $category->parent()->associate($rootCategory);
                $parentCategory = $category; // redefined at each loop until we reach the following else
            } else {
                $category->parent()->associate($parentCategory);
            }

            $category->save();

            // Assign random products
            $products = Product::whereNull('parent_id')->inRandomOrder()->take(2)->get();
            $category->products()->saveMany($products);
        }
    }
}
