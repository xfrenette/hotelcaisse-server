<?php

namespace Tests\Feature;

use App\CashMovement;
use App\Device;
use App\Order;
use App\Register;
use App\Transaction;
use App\TransactionMode;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use DatabaseTransactions;

    protected function createRegister()
    {
        $device = Device::first();
        $register = factory(Register::class)->make();
        $register->device()->associate($device);
        $register->save();

        return $register;
    }

    public function testTransactionsTotalsAttributes()
    {
        $register = $this->createRegister();
        $order = Order::first();
        $totalPayments = 0;
        $totalRefunds = 0;
        $totalCashTransactions = 0;
        $faker = Factory::create();

        $cashTransactionMode = TransactionMode::where('type', TransactionMode::TYPE_CASH)->first();
        $nonCashTransactionMode = TransactionMode::whereNull('type')->first();

        // Payments
        for ($i = 0; $i < 6; $i++) {
            $isCash = $i >= 3;
            $amount = $faker->randomFloat(2, 0, 100);
            $tm = $isCash ? $cashTransactionMode : $nonCashTransactionMode;
            Transaction::forceCreate([
                'uuid' => 'ncp' . $i,
                'amount' => $amount,
                'register_id' => $register->id,
                'order_id' => $order->id,
                'transaction_mode_id' => $tm->id,
            ]);
            $totalPayments += $amount;

            if ($isCash) {
                $totalCashTransactions += $amount;
            }
        }

        // Refunds
        for ($i = 0; $i < 6; $i++) {
            $isCash = $i >= 3;
            $amount = $faker->randomFloat(2, 0.01, 100);
            $tm = $isCash ? $cashTransactionMode : $nonCashTransactionMode;
            Transaction::forceCreate([
                'uuid' => 'ncr' . $i,
                'amount' => -1 * $amount,
                'register_id' => $register->id,
                'order_id' => $order->id,
                'transaction_mode_id' => $tm->id,
            ]);
            $totalRefunds += $amount;

            if ($isCash) {
                $totalCashTransactions -= $amount;
            }
        }

        $this->assertEquals($totalPayments, $register->paymentsTotal);
        $this->assertEquals($totalRefunds, $register->refundsTotal);
        $this->assertEquals($totalCashTransactions, $register->cashTransactionsTotal);
        $this->assertEquals($totalPayments - $totalRefunds, $register->transactionsTotal);
    }

    public function testTotalsAttributesOnEmptyRegister()
    {
        $register = $this->createRegister();

        $this->assertEquals(0, $register->paymentsTotal);
        $this->assertEquals(0, $register->refundsTotal);
        $this->assertEquals(0, $register->cashTransactionsTotal);
        $this->assertEquals(0, $register->transactionsTotal);
        $this->assertEquals(0, $register->cashMovementsTotal);
    }

    public function testCashMovementsTotalsAttributes()
    {
        $register = $this->createRegister();
        $totalCashMovements = 0;
        $faker = Factory::create();

        for($i = 0; $i < 6; $i++) {
            $isIn = $i < 3;
            $amount = $faker->randomFloat(2, 0.01, 100);
            $amount *= ($isIn ? 1 : -1);

            CashMovement::forceCreate([
                'uuid' => 'cm' . $i,
                'amount' => $amount,
                'register_id' => $register->id,
                'note' => 'test',
            ]);

            $totalCashMovements += $amount;
        }

        $this->assertEquals($totalCashMovements, $register->cashMovementsTotal);
    }
}
