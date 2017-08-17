<?php

use App\Device;
use App\Team;
use Illuminate\Database\Seeder;

class SampleDeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Device::count() > 0) {
            return;
        }

        $team = Team::first();
        $device = new Device([
            'name' => 'Test device',
        ]);

        $device->team()->associate($team);
        $device->save();
    }
}
