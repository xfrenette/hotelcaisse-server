<?php

namespace Tests\Feature\Jobs;

use App\Jobs\PreCalcRegisterCashMovements;
use App\Register;
use App\Repositories\CalculatedValueRepository;
use Mockery as m;
use Tests\TestCase;

class PreCalcRegisterCashMovementsTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testHandle()
    {
        $totalCashMovements = -86.85;

        $repo = m::mock(CalculatedValueRepository::class);
        $register = m::mock(Register::class)->makePartial();
        $register->id = 4;
        $register->shouldReceive('getCashMovementsTotalAttribute')->andReturn($totalCashMovements);

        $repo->shouldReceive('set')->once()->with($register, Register::PRE_CALC_CASH_MV_TOTAL, $totalCashMovements);

        $job = new PreCalcRegisterCashMovements($register);
        $job->handle($repo);
    }
}
