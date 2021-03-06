<?php

namespace Tests\Unit;

use App\CashMovement;
use App\Register;
use Carbon\Carbon;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    /**
     * @var Register
     */
    protected $register;

    protected function setUp()
    {
        parent::setUp();
        $this->register = factory(Register::class)->make();
    }

    public function testOpenSetsAttributes()
    {
        $employee = 'test-employee';
        $cashAmount = 123.45;
        $this->register->opened_at = Carbon::yesterday();

        $this->register->open($employee, $cashAmount);

        $this->assertTrue($this->register->opened);
        $this->assertEquals($employee, $this->register->employee);
        $this->assertEquals($cashAmount, $this->register->opening_cash);
        $this->assertTrue(abs(Carbon::now()->diffInSeconds($this->register->opened_at)) <= 1);
    }

    public function testCloseSetsAttributes()
    {
        $POSTRef = 'post-ref';
        $POSTAmount = 789.12;
        $cashAmount = 123.45;
        $this->register->closed_at = Carbon::yesterday();

        $this->register->close($cashAmount, $POSTRef, $POSTAmount);

        $this->assertFalse($this->register->opened);
        $this->assertEquals($POSTRef, $this->register->post_ref);
        $this->assertEquals($POSTAmount, $this->register->post_amount);
        $this->assertEquals($cashAmount, $this->register->closing_cash);
        $this->assertTrue(abs(Carbon::now()->diffInSeconds($this->register->closed_at)) <= 1);
    }

    public function testToArray()
    {
        $cashMovements = collect([]);

        for ($i = 1; $i <= 2; $i++) {
            $cashMovement = new CashMovement(['uuid' => 'uuid-cashMovement-'.$i]);
            $cashMovements->push($cashMovement);
        }

        $expected = [
            'uuid' => 'test-uuid',
            'number' => 123,
            'state' => Register::STATE_OPENED,
            'cashMovements' => $cashMovements->toArray(),
            'employee' => 'Test Employee',
            'openingCash' => 100.36,
        ];

        $register = new Register($expected);
        $register->id = 456;
        $register->device_id = 123;
        $register->setRelation('cashMovements', $cashMovements);

        $register->open($expected['employee'], $expected['openingCash']);

        $expected['openedAt'] = $register->opened_at->getTimestamp();

        $this->assertEquals($expected, $register->toArray());
    }

    public function testCasts()
    {
        $register = new Register();
        $register->opening_cash = '1.23';
        $register->closing_cash = '1.23';
        $register->post_amount = '1.23';

        $this->assertInternalType('float', $register->opening_cash);
        $this->assertInternalType('float', $register->closing_cash);
        $this->assertInternalType('float', $register->post_amount);
    }
}
