<?php

use Illuminate\Database\Seeder;

class HIRDLBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $business = new \App\Business();
        $business->save();

        $user = new \App\User([
            'name' => 'Xavier Frenette',
            'email' => 'xavier@xavierfrenette.com',
        ]);
        $user->password = \Illuminate\Support\Facades\Hash::make('password');
        $user->save();

        $otherUser = new \App\User([
            'name' => 'Aubergiste',
            'email' => 'info@aubergerdl.ca',
        ]);
        $otherUser->password = \Illuminate\Support\Facades\Hash::make('password');
        $otherUser->save();

        $team = new \App\Team();
        $team->name = 'Auberge Internationale de Rivière-du-Loup';
        $team->slug = 'hirdl';
        $team->business()->associate($business);
        $team->owner()->associate($user);
        $team->save();

        $user->teams()->attach($team, ['role' => 'admin']);
        $otherUser->teams()->attach($team, ['role' => 'member']);
    }
}
