<?php

namespace Tests\Unit;

use App\CashMovement;
use App\Register;
use Carbon\Carbon;
use Tests\TestCase;

class CashMovementTest extends TestCase
{
    public function testToArray()
    {
        $date = Carbon::yesterday();

        $expected = [
            'uuid' => 'Test-uuid',
            'note' => 'Test note',
            'amount' => -12.34,
            'createdAt' => $date->getTimestamp(),
        ];

        $register = new Register();
        $register->id = 123;

        $cashMovement = new CashMovement($expected);
        $cashMovement->id = 456;
        $cashMovement->register()->associate($register);
        $cashMovement->created_at = $date;

        $this->assertEquals($expected, $cashMovement->toArray());
    }

    public function testCasts()
    {
        $cm = new CashMovement(['amount' => '1.23']);
        $this->assertInternalType('float', $cm->amount);
    }
}
