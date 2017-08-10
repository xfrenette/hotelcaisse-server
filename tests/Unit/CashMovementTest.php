<?php

namespace Tests\Unit;

use App\CashMovement;
use App\Register;
use Tests\TestCase;

class CashMovementTest extends TestCase
{
    public function testToArray()
    {
        $expected = [
            'uuid' => 'Test-uuid',
            'note' => 'Test note',
            'amount' => -12.34,
        ];

        $register = new Register();
        $register->id = 123;

        $cashMovement = new CashMovement($expected);
        $cashMovement->id = 456;
        $cashMovement->register()->associate($register);

        $this->assertEquals($expected, $cashMovement->toArray());
    }
}
