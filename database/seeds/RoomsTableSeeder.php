<?php

use Illuminate\Database\Seeder;

class RoomsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (\App\Room::count() > 0) {
            return;
        }

        $business = \App\Business::first();

        for ($i = 1; $i < 5; $i++) {
            $room = new \App\Room();
            $room->name = "Room #$i";
            $room->business()->associate($business);
            $room->save();
        }
    }
}
