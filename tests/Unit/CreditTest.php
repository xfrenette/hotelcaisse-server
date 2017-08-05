<?php

namespace Tests\Unit;

use App\Credit;
use App\Order;
use Tests\TestCase;

class CreditTest extends TestCase
{
    public function testToArray()
    {
        $order = new Order();
        $order->uuid = 'test-order-uuid';
        $order->id = 123;

        $expected = [
            'uuid' => 'test-uuid',
            'note' => 'test-note',
            'amount' => 12.75,
        ];

        $credit = new Credit($expected);
        $credit->order()->associate($order);

        $this->assertEquals($expected, $credit->toArray());
    }
}
