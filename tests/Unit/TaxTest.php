<?php

namespace Tests\Unit;

use App\Business;
use App\Tax;
use Tests\TestCase;

class TaxTest extends TestCase
{
    public function testToArray()
    {
        $data = [
            'id' => 123,
            'name' => 'Test tax',
            'type' => 'percentage',
            'amount' => 12.34,
            'applies_to_all' => true,
        ];

        $business = new Business();
        $business->id = 789;

        $expected = array_only($data, ['id', 'name']);

        $tax = new Tax($data);
        $tax->id = $data['id'];
        $tax->business()->associate($business);

        $this->assertEquals($expected, $tax->toArray());
    }

    public function testCasts()
    {
        $tax = new Tax(['amount' => '1.23']);
        $this->assertInternalType('float', $tax->amount);
    }
}
