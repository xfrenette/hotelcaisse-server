<?php

namespace Tests\Feature\Jobs;

use App\Jobs\PreCalcRegisterTransactions;
use App\Register;
use App\Repositories\CalculatedValueRepository;
use Mockery as m;
use Tests\TestCase;

class PreCalcRegisterTransactionsTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testHandle()
    {
        $totalPayments = 123.45;
        $totalRefunds = 456.78;
        $totalCashTransactions = 86.85;

        $repo = m::mock(CalculatedValueRepository::class);
        $register = m::mock(Register::class)->makePartial();
        $register->id = 4;
        $register->shouldReceive('getPaymentsTotalAttribute')->andReturn($totalPayments);
        $register->shouldReceive('getRefundsTotalAttribute')->andReturn($totalRefunds);
        $register->shouldReceive('getCashTransactionsTotalAttribute')->andReturn($totalCashTransactions);

        $repo->shouldReceive('set')->once()->with($register, Register::PRE_CALC_PAYMENTS_TOTAL, $totalPayments);
        $repo->shouldReceive('set')->once()->with($register, Register::PRE_CALC_REFUNDS_TOTAL, $totalRefunds);
        $repo->shouldReceive('set')->once()->with($register, Register::PRE_CALC_CASH_TX_TOTAL, $totalCashTransactions);

        $job = new PreCalcRegisterTransactions($register);
        $job->handle($repo);
    }
}
