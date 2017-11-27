<?php

use App\CashMovement;
use App\Device;
use App\Register;
use Faker\Factory;
use Illuminate\Database\Seeder;

class SampleRegistersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Register::count()) {
            return;
        }


        $faker = Factory::create();
        $device = Device::first();

        $number = $faker->randomNumber();

        $closedRegister = new Register(['uuid' => $faker->uuid(), 'number' => $number]);
        $closedRegister->device()->associate($device);
        $closedRegister->open('Test employee', 98.75);
        $closedRegister->close(365.89, 'POST-ref', 425.68);
        $closedRegister->save();
        $this->addRandomCashMovements($closedRegister, $faker);

        // ---

        $openRegister = new Register(['uuid' => $faker->uuid(), 'number' => $number + 1]);
        $openRegister->device()->associate($device);
        $openRegister->open('Test employee', 102.35);
        $openRegister->save();
        $this->addRandomCashMovements($openRegister, $faker);

        // Associate the opened register to the current device
        $device->currentRegister()->associate($openRegister);
        $device->save();
    }

    /**
     * @param \App\Register $register
     * @param \Faker\Generator $faker
     */
    protected function addRandomCashMovements(Register $register, $faker)
    {
        for ($i = 0; $i < 3; $i++) {
            $cashMovement = new CashMovement([
                'uuid' => $faker->uuid,
                'note' => $faker->words(5, true),
                'amount' => $faker->randomFloat(2, -10, 10),
            ]);
            $cashMovement->register()->associate($register);
            $cashMovement->save();
        }
    }
}
