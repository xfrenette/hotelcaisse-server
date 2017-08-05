<?php

namespace Tests\Unit;

use App\Customer;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    public function testToArray()
    {
        $fields = [
            ['field' => 123, 'value' => 'oldValue'],
            ['field' => 345, 'value' => 'test3'],
        ];

        $expected = [
            'fieldValues' => $fields,
        ];

        $customer = $this->getMockBuilder(Customer::class)
            ->setMethods(['getFieldValuesAttribute'])
            ->getMock();
        $customer->method('getFieldValuesAttribute')
            ->willReturn($fields);
        $customer->id = 789;

        $this->assertEquals($expected, $customer->toArray());
    }
}
