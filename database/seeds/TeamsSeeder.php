<?php

use App\Business;
use App\Team;
use Illuminate\Database\Seeder;

class TeamsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Team::count() > 0) {
            return;
        }

        $business = Business::first();
        $team = factory(\App\Team::class)->make();
        $team->business()->associate($business);
        $team->save();
    }
}
