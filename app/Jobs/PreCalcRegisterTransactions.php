<?php

namespace App\Jobs;

use App\Register;
use App\Repositories\CalculatedValueRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PreCalcRegisterTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Register
     */
    protected $register;

    /**
     * @return \App\Register
     */
    public function getRegister()
    {
        return $this->register;
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Register $register)
    {
        $this->register = $register;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CalculatedValueRepository $cache)
    {
        $cache->set($this->register, Register::PRE_CALC_PAYMENTS_TOTAL, $this->register->paymentsTotal);
        $cache->set($this->register, Register::PRE_CALC_REFUNDS_TOTAL, $this->register->refundsTotal);
        $cache->set($this->register, Register::PRE_CALC_CASH_TX_TOTAL, $this->register->cashTransactionsTotal);
    }
}
