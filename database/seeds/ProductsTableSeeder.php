<?php

use App\Business;
use App\Product;
use Faker\Factory;
use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Product::count() > 0) {
            return;
        }

        $faker = Factory::create();
        $business = Business::first();
        $parentProduct = null;

        for ($i = 0; $i < 10; $i++) {
            $product = new Product();
            $product->name = $faker->word();
            $product->description = $faker->words(5, true);
            $product->price = $faker->randomFloat(2, 2, 70);
            $product->business()->associate($business);

            if ($i === 6) {
                $parentProduct = $product;
            }

            if ($i > 6) {
                $product->parent()->associate($parentProduct);
            }

            if ($i === 9) {
                $product->uuid = $faker->uuid;
            }

            $product->save();
        }
    }
}
