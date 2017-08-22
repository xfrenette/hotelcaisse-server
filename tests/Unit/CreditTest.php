<?php

namespace Tests\Unit;

use App\Credit;
use App\Order;
use Carbon\Carbon;
use Tests\TestCase;

class CreditTest extends TestCase
{
    public function testToArray()
    {
        $date = Carbon::yesterday();

        $order = new Order();
        $order->uuid = 'test-order-uuid';
        $order->id = 123;

        $expected = [
            'uuid' => 'test-uuid',
            'note' => 'test-note',
            'amount' => 12.75,
            'createdAt' => $date->getTimestamp(),
        ];

        $credit = new Credit($expected);
        $credit->created_at = $date;
        $credit->order()->associate($order);

        $this->assertEquals($expected, $credit->toArray());
    }
}
