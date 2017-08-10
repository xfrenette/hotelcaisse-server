<?php

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
        if (\App\Device::count() > 0) {
            return;
        }

        $business = \App\Business::first();
        $device = new \App\Device();

        $device->business()->associate($business);
        $device->save();
    }
}
