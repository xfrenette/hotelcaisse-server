<?php

use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Business::class, function (Faker\Generator $faker) {
    return [
        'slug' => $faker->slug,
    ];
});

$factory->define(App\Device::class, function (Faker\Generator $faker) {
    return [
        'uuid' => $faker->uuid,
    ];
});

$factory->define(App\ApiSession::class, function (Faker\Generator $faker) {
    return [
        'token' => $faker->uuid,
        'expires_at' => \Carbon\Carbon::tomorrow(),
    ];
});

$factory->defineAs(App\ApiSession::class, 'withBusinessAndDevice', function () {
    return [
        'token' => str_random(32),
        'expires_at' => \Carbon\Carbon::tomorrow(),
        'business_id' => function () {
            return factory(\App\Business::class)->create()->id;
        },
        'device_id' => function () {
            return factory(\App\Device::class)->create()->id;
        },
    ];
});

$factory->define(App\DeviceApproval::class, function () {
    return [
        'passcode' => Hash::make('1234'),
        'expires_at' => \Carbon\Carbon::tomorrow(),
    ];
});

$factory->defineAs(App\DeviceApproval::class, 'withBusiness', function () {
    return [
        'passcode' => Hash::make('1234'),
        'expires_at' => \Carbon\Carbon::tomorrow(),
        'business_id' => function () {
            return factory(\App\Business::class)->create()->id;
        },
    ];
});
