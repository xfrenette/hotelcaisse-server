<?php

use App\Business;
use App\Team;
use App\User;
use Illuminate\Database\Seeder;
use Laravel\Spark\Spark;

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
        $user = User::first();

        $team = new Team([
            'name' => 'Dev',
            'slug' => 'dev',
        ]);

        $team->business()->associate($business);
        $team->owner()->associate($user);
        $team->save();

        $team->users()->attach($user, ['role' => Spark::defaultRole()]);
    }
}
