<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    const SEEDABLE_ENVIRONMENTS = ['testing', 'local'];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (!in_array(App::environment(), self::SEEDABLE_ENVIRONMENTS)) {
            throw new Exception('Seeding is not available for this environment. Add --env=[environment] if necessary.');
        }

        $this->call(UsersSeeder::class);
        $this->call(BusinessesTableSeeder::class);
        $this->call(TeamsSeeder::class);
        $this->call(SampleDeviceSeeder::class);
        $this->call(SampleRegistersSeeder::class);
        $this->call(RoomsTableSeeder::class);
        $this->call(TransactionModesTableSeeder::class);
        $this->call(ProductsTableSeeder::class);
        $this->call(BusinessCustomerFieldsSeeder::class);
        $this->call(BusinessRoomSelectionFieldsSeeder::class);
        $this->call(TaxesTableSeeder::class);
        $this->call(SampleOrderSeeder::class);
        $this->call(ProductCategoriesTableSeeder::class);
    }
}
