<?php

use Illuminate\Database\Seeder;

class HIRDLTransactionModesSeeder extends Seeder
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

        $modes = [
            ['name' => 'Carte (crédit/débit)'],
            ['name' => 'Argent', 'type' => \App\TransactionMode::TYPE_CASH],
        ];

        foreach ($modes as $mode) {
            $transactionMode = \App\TransactionMode::make($mode);
            $transactionMode->business()->associate($business);
            $transactionMode->save();
        }
    }
}
