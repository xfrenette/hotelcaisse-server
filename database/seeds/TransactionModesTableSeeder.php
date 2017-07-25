<?php

use App\Business;
use App\TransactionMode;
use Illuminate\Database\Seeder;

class TransactionModesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (TransactionMode::count() > 0) {
            return;
        }

        $business = Business::first();
        $modes = ['Cash', 'Mastercard', 'Visa', 'Debit'];

        foreach ($modes as $modeName) {
            $mode = new TransactionMode();
            $mode->name = $modeName;
            $mode->business()->associate($business);
            $mode->save();
        }
    }
}
