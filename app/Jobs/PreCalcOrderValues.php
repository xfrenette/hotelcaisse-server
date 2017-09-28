<?php

namespace App\Jobs;

use App\Order;
use App\Repositories\CalculatedValueRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PreCalcOrderValues implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Order
     */
    protected $order;

    /**
     * @return \App\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CalculatedValueRepository $cache)
    {
        $cache->set($this->order, Order::PRE_CALC_CREDITS, $this->order->creditsTotal);
        $cache->set($this->order, Order::PRE_CALC_SUB_TOTAL, $this->order->subTotal);
        $cache->set($this->order, Order::PRE_CALC_TRANSACTIONS, $this->order->transactionsTotal);

        foreach ($this->order->taxes as $tax) {
            $key = Order::PRE_CALC_TAX . '.' . $tax['taxId'];
            $cache->set($this->order, $key, $tax['amount']);
        }
    }
}
