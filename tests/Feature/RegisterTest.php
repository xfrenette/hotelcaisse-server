<?php

namespace Tests\Feature;

use App\Register;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use DatabaseTransactions;

    // This test requires the seeded test data
    public function testToArray()
    {
        $register = Register::with('cashMovements')->first();
        $array = $register->toArray();

        $expected = [
            'uuid' => $register->uuid,
            'cashMovements' => $register->cashMovements->map(function ($cashMovement) {
                return $cashMovement->toArray();
            })->toArray(),
        ];

        $this->assertEquals($expected, $array);
    }
}
