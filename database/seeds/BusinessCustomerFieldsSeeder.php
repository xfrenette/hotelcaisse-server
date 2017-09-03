<?php

use App\Business;
use App\Field;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessCustomerFieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if ($this->alreadyHasFields()) {
            return;
        }

        $business = Business::first();

        // Customer name
        $field = factory(Field::class)->make();
        $field->type = 'NameField';
        $field->role = 'customer.name';
        $field->required = true;
        $field->save();
        $business->customerFields()->attach($field);

        // Other fields
        for ($i = 0; $i < 4; $i++) {
            $field = factory(Field::class)->create();
            $business->customerFields()->attach($field);
        }
    }

    protected function alreadyHasFields()
    {
        return DB::table('business_fields')
            ->where('type', 'customer')
            ->count() > 0;
    }
}
