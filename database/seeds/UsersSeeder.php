<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (User::count() > 0) {
            return;
        }

        $user = new User([
            'name' => 'Xavier Frenette',
            'email' => 'xavier@xavierfrenette.com',
        ]);

        $user->password = Hash::make('password');

        $user->save();
    }
}
