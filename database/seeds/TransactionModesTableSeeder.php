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
        $modes = [
            'Cash' => TransactionMode::TYPE_CASH,
            'Mastercard',
            'Visa',
            'Debit'
        ];

        foreach ($modes as $modeName => $type) {
            if (is_int($modeName)) {
                $modeName = $type;
                $type = null;
            }

            $mode = new TransactionMode();
            $mode->name = $modeName;
            $mode->business()->associate($business);

            if (!is_null($type)) {
                $mode->type = $type;
            }

            $mode->save();
        }
    }
}
