<?php

use Illuminate\Database\Seeder;

class HIRDLSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(HIRDLBaseSeeder::class);
        $this->call(HIRDLTaxesSeeder::class);
        $this->call(HIRDLProductsSeeder::class);
        $this->call(HIRDLFieldsSeeder::class);
        $this->call(HIRDLTransactionModesSeeder::class);
        $this->call(HIRDLRoomsSeeder::class);
    }
}
