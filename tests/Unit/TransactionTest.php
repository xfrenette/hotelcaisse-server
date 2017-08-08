<?php

namespace Tests\Unit;

use App\Order;
use App\Register;
use App\Transaction;
use App\TransactionMode;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    public function testToArray()
    {
        $expected = [
            'uuid' => 'test-uuid',
            'amount' =>12.32,
            'transactionMode' => 2,
        ];

        $order = new Order();
        $order->id = 123;

        $register = new Register();
        $register->id = 456;

        $transactionMode = new TransactionMode();
        $transactionMode->id = $expected['transactionMode'];

        $transaction = new Transaction($expected);
        $transaction->id = 789;
        $transaction->transactionMode()->associate($transactionMode);
        $transaction->order()->associate($order);
        $transaction->register()->associate($register);

        $this->assertEquals($expected, $transaction->toArray());
    }
}
