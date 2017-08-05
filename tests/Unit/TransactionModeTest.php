<?php

namespace Tests\Unit;

use App\Business;
use App\TransactionMode;
use Tests\TestCase;

class TransactionModeTest extends TestCase
{
    public function testToArray()
    {
        $business = new Business();
        $business->slug = 'test-slug';
        $business->id = 123;

        $expected = [
            'id' => 456,
            'name' => 'test-note',
        ];

        $transactionMode = new TransactionMode($expected);
        $transactionMode->id = $expected['id'];
        $transactionMode->business()->associate($business);

        $this->assertEquals($expected, $transactionMode->toArray());
    }
}
