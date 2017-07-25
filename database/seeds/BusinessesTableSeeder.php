<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Do nothing if a Business is already present
        if (DB::table('businesses')->count() > 0) {
            return;
        }

        factory(\App\Business::class)->create();
    }
}
