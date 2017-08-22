<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\CashMovementsController;
use Faker\Factory;
use Tests\InteractsWithAPI;
use Tests\TestCase;

class CashMovementsControllerTest extends TestCase
{
    use InteractsWithAPI;

    protected static $faker;

    /**
     * @var \App\Http\Controllers\Api\CashMovementsController
     */
    protected $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->controller = new CashMovementsController();
    }

    protected static function generateAddData()
    {
        if (!self::$faker) {
            self::$faker = Factory::create();
        }

        return [
            'data' => [
                'uuid' => self::$faker->uuid(),
                'note' => 'Test note',
                'amount' => -12.34,
                'createdAt' => 1503410614, // 08/22/2017 @ 2:03pm (UTC)
            ],
        ];
    }

    public function testValidateAddReturnsErrorIfInvalidUUID()
    {
        $values = [null, '', ' ', 12];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateAdd'],
            self::generateAddData(),
            'data.uuid',
            $values
        );
    }

    public function testValidateAddReturnsErrorIfInvalidNote()
    {
        $values = [null, '', ' ', 12];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateAdd'],
            self::generateAddData(),
            'data.note',
            $values
        );
    }

    public function testValidateAddReturnsErrorIfInvalidAmount()
    {
        $values = [null, '', ' ', false, 0];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateAdd'],
            self::generateAddData(),
            'data.amount',
            $values
        );
    }

    public function testValidateAddWorksWithValidData()
    {
        $request = $this->mockRequest(self::generateAddData());
        $this->controller->validateAdd($request);

        // Should not throw any error
    }

    // --------------------------

    public function testValidateDeleteReturnsErrorIfInvalidUUID()
    {
        $values = [null, '', ' ', 12];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateDelete'],
            ['data' => []],
            'data.uuid',
            $values
        );
    }
}
