<?php

namespace Tests\Unit;

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
        $this->register->openedAt = Carbon::yesterday();

        $this->register->open($employee, $cashAmount);

        $this->assertTrue($this->register->opened);
        $this->assertEquals($employee, $this->register->employee);
        $this->assertEquals($cashAmount, $this->register->openingCash);
        $this->assertTrue(abs(Carbon::now()->diffInSeconds($this->register->openedAt)) <= 1);
    }

    public function testCloseSetsAttributes()
    {
        $POSTRef = 'post-ref';
        $POSTAmount = 789.12;
        $cashAmount = 123.45;
        $this->register->closedAt = Carbon::yesterday();

        $this->register->close($cashAmount, $POSTRef, $POSTAmount);

        $this->assertFalse($this->register->opened);
        $this->assertEquals($POSTRef, $this->register->POSTRef);
        $this->assertEquals($POSTAmount, $this->register->POSTAmount);
        $this->assertEquals($cashAmount, $this->register->closingCash);
        $this->assertTrue(abs(Carbon::now()->diffInSeconds($this->register->closedAt)) <= 1);
    }
}
