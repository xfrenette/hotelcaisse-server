<?php

namespace Tests\Unit;

use App\Order;
use App\Register;
use App\Transaction;
use App\TransactionMode;
use Carbon\Carbon;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    public function testToArray()
    {
        $date = Carbon::yesterday();

        $transactionMode = new TransactionMode([
            'name' => 'test-transaction-mode',
            'type' => TransactionMode::TYPE_CASH,
        ]);
        $transactionMode->id = 2;

        $expected = [
            'uuid' => 'test-uuid',
            'amount' =>12.32,
            'transactionMode' => $transactionMode->toArray(),
            'createdAt' => $date->getTimestamp(),
        ];

        $order = new Order();
        $order->id = 123;

        $register = new Register();
        $register->id = 456;

        $transaction = new Transaction($expected);
        $transaction->id = 789;
        $transaction->created_at = $date;
        $transaction->transactionMode()->associate($transactionMode);
        $transaction->order()->associate($order);
        $transaction->register()->associate($register);

        $this->assertEquals($expected, $transaction->toArray());
    }
}
