<?php

use Illuminate\Database\Seeder;

class HIRDLRoomsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $business = \App\Team::where('slug', 'hirdl')
            ->with('business')
            ->first()
            ->business;

        $rooms = [
            ['name' => '1 (dortoir)'],
            ['name' => '2 (dortoir)'],
            ['name' => '3 (dortoir)'],
            ['name' => '4 (dortoir)'],
            ['name' => '5 (privée)'],
            ['name' => '6 (privée)'],
            ['name' => '7.1 (privée)'],
            ['name' => '7.2 (dortoir)'],
            ['name' => '8 (privée)'],
            ['name' => '9 (privée)'],
            ['name' => '10 (privée)'],
            ['name' => '11 (privée)'],
            ['name' => '12 (privée)'],
            ['name' => '14 (privée)'],
            ['name' => '15 (privée)'],
            ['name' => '16 (privée)'],
            ['name' => '17 (privée)'],
            ['name' => 'Dortoir grange'],
            ['name' => 'Camping'],
        ];

        foreach ($rooms as $roomData) {
            $room = \App\Room::make($roomData);
            $room->business()->associate($business);
            $room->save();
        }
    }
}
