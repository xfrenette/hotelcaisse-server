<?php

use Illuminate\Database\Seeder;

class HIRDLTaxesSeeder extends Seeder
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

        $taxesDef = [
            'TPS' => 5,
            'TVQ' => 9.975,
        ];

        foreach ($taxesDef as $name => $amount) {
            $tax = new \App\Tax();
            $tax->name = $name;
            $tax->amount = $amount;
            $tax->type = \App\Tax::TYPE_PERCENTAGE;
            $tax->applies_to_all = true;
            $tax->business()->associate($business);
            $tax->save();
        }
    }
}
