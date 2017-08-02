<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (App::environment() !== 'testing') {
            throw new Exception('Seeding is available only for "testing" environment. Add --env=testing.');
        }

        $this->call(BusinessesTableSeeder::class);
        $this->call(SampleDeviceSeeder::class);
        $this->call(RoomsTableSeeder::class);
        $this->call(TransactionModesTableSeeder::class);
        $this->call(ProductsTableSeeder::class);
        $this->call(BusinessCustomerFieldsSeeder::class);
        $this->call(BusinessRoomSelectionFieldsSeeder::class);
        $this->call(TaxesTableSeeder::class);
        $this->call(SampleOrderSeeder::class);
    }
}
