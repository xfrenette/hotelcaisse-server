<?php

namespace Tests\Feature;

use App\Customer;
use App\Field;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var Customer
     */
    protected $customer;
    /**
     * @var \App\Business
     */
    protected $business;
    protected $field1;
    protected $field2;

    protected function setUp()
    {
        parent::setUp();
        $this->customer = factory(Customer::class, 'withBusiness')->create();
        $this->business = $this->customer->business;
        $this->field1 = factory(Field::class)->create();
        $this->field2 = factory(Field::class)->create();
    }

    protected function getFieldValues()
    {
        return DB::table('field_values')
            ->select(['field_id', 'value'])
            ->where([
                'instance_id' => $this->customer->id,
                'class' => 'Customer',
            ])
            ->get();
    }

    public function testSetFieldValuesCreatesFields()
    {
        $fields = [
            ['fieldId' => $this->field1->id, 'value' => 'test1'],
            ['fieldId' => $this->field2->id, 'value' => 'test2'],
        ];

        $this->customer->setFieldValues($fields);

        $res = $this->getFieldValues();

        $expected = array_map(function ($field) {
            $obj = new \stdClass();
            $obj->field_id = $field['fieldId'];
            $obj->value = $field['value'];
            return $obj;
        }, $fields);

        $this->assertEquals($expected, $res->toArray());
    }

    public function testSetFieldValuesUpdatesExistingFields()
    {
        $newValue = 'newValue';

        $this->customer->setFieldValues([['fieldId' => $this->field1->id, 'value' => 'oldValue']]);

        $fields = [
            ['fieldId' => $this->field1->id, 'value' => $newValue],
            ['fieldId' => $this->field2->id, 'value' => 'test3'],
        ];

        $this->customer->setFieldValues($fields);

        $res = $this->getFieldValues();

        $expected = array_map(function ($field) {
            $obj = new \stdClass();
            $obj->field_id = $field['fieldId'];
            $obj->value = $field['value'];
            return $obj;
        }, $fields);

        $this->assertEquals($expected, $res->toArray());
    }

    public function testSetFieldValuesDoesNotUpdateWithSecondParam()
    {
        $newValue = 'newValue';

        $this->customer->setFieldValues([['fieldId' => $this->field1->id, 'value' => 'oldValue']]);

        $fields = [
            ['fieldId' => $this->field1->id, 'value' => $newValue],
            ['fieldId' => $this->field2->id, 'value' => 'test3'],
        ];

        $this->customer->setFieldValues($fields, false);

        $res = $this->getFieldValues();

        $this->assertEquals(3, $res->count());
    }

    public function testReplaceFieldValuesClearsOldValues()
    {
        $fields = [
            ['fieldId' => $this->field1->id, 'value' => 'oldValue'],
            ['fieldId' => $this->field2->id, 'value' => 'test3'],
        ];

        $this->customer->setFieldValues($fields);

        $fields = [
            ['fieldId' => $this->field1->id, 'value' => 'newValue'],
        ];

        $this->customer->replaceFieldValues($fields);

        $res = $this->getFieldValues();

        $this->assertEquals(1, $res->count());
    }
}
