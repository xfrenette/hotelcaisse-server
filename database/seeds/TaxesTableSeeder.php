<?php

use App\Tax;
use Illuminate\Database\Seeder;

class TaxesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (\App\Tax::count() > 0) {
            return;
        }

        $faker = \Faker\Factory::create();
        $business = \App\Business::first();

        for ($i = 1; $i < 5; $i++) {
            $tax = new \App\Tax();
            $tax->name = $faker->word();
            $tax->amount = $faker->randomFloat(5, 0, 99);
            $tax->type = $i % 2 === 0 ? Tax::TYPE_PERCENTAGE : Tax::TYPE_PERCENTAGE;
            $tax->applies_to_all = $i % 2 === 1;
            $tax->business()->associate($business);
            $tax->save();
        }
    }
}
