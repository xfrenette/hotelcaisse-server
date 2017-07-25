<?php

use App\Business;
use App\Field;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessRoomSelectionFieldsSeeder extends Seeder
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

        for ($i = 0; $i < 4; $i++) {
            $field = factory(Field::class)->make();
            $field->save();

            $business->roomSelectionFields()->attach($field, ['type' => 'roomSelection']);
        }
    }

    protected function alreadyHasFields()
    {
        return DB::table('business_fields')
            ->where('type', 'roomSelection')
            ->count() > 0;
    }
}
