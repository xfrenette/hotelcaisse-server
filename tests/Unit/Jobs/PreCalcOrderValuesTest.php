<?php

namespace Tests\Feature\Jobs;

use App\Jobs\PreCalcOrderValues;
use App\Order;
use App\Repositories\CalculatedValueRepository;
use Illuminate\Support\Collection;
use Mockery as m;
use Tests\TestCase;

class PreCalcOrderValuesTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testHandle()
    {
        $subTotal = 123.45;
        $tax1 = 21.36;
        $tax2 = 11.37;
        $creditsTotal = 24.68;
        $transactionsTotal = 100.13;
        $taxes = new Collection([
            ['taxId' => 1, 'name' => 'Tax 1', 'amount' => $tax1],
            ['taxId' => 2, 'name' => 'Tax 2', 'amount' => $tax2],
        ]);

        $repo = m::mock(CalculatedValueRepository::class);
        $order = m::mock(Order::class)->makePartial();
        $order->id = 4;
        $order->shouldReceive('getSubTotalAttribute')->andReturn($subTotal);
        $order->shouldReceive('getTaxesAttribute')->andReturn($taxes);
        $order->shouldReceive('getCreditsTotalAttribute')->andReturn($creditsTotal);
        $order->shouldReceive('getTransactionsTotalAttribute')->andReturn($transactionsTotal);

        $repo->shouldReceive('set')->once()->with($order, Order::PRE_CALC_CREDITS, $creditsTotal);
        $repo->shouldReceive('set')->once()->with($order, Order::PRE_CALC_TAX . '.1', $tax1);
        $repo->shouldReceive('set')->once()->with($order, Order::PRE_CALC_TAX . '.2', $tax2);
        $repo->shouldReceive('set')->once()->with($order, Order::PRE_CALC_SUB_TOTAL, $subTotal);
        $repo->shouldReceive('set')->once()->with($order, Order::PRE_CALC_TRANSACTIONS, $transactionsTotal);

        $job = new PreCalcOrderValues($order);
        $job->handle($repo);
    }
}
