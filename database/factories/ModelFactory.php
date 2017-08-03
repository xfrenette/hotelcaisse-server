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

$factory->define(App\Device::class, function () {
    return [
    ];
});

$factory->defineAs(App\Device::class, 'withBusiness', function () {
    return [
        'business_id' => function () {
            return factory(\App\Business::class)->create()->id;
        },
    ];
});

$factory->define(App\ApiSession::class, function (Faker\Generator $faker) {
    return [
        'token' => $faker->uuid,
        'expires_at' => \Carbon\Carbon::tomorrow(),
    ];
});

$factory->defineAs(App\ApiSession::class, 'withDeviceAndBusiness', function () {
    return [
        'token' => str_random(32),
        'expires_at' => \Carbon\Carbon::tomorrow(),
        'device_id' => function () {
            return factory(\App\Device::class, 'withBusiness')->create()->id;
        },
    ];
});

$factory->define(App\DeviceApproval::class, function () {
    return [
        'passcode' => Hash::make('1234'),
        'expires_at' => \Carbon\Carbon::tomorrow(),
    ];
});

$factory->defineAs(App\DeviceApproval::class, 'withDeviceAndBusiness', function () {
    return [
        'passcode' => Hash::make('1234'),
        'expires_at' => \Carbon\Carbon::tomorrow(),
        'device_id' => function () {
            return factory(\App\Device::class, 'withBusiness')->create()->id;
        },
    ];
});

$factory->define(App\Register::class, function () {
    return [
    ];
});

$factory->define(App\Field::class, function (\Faker\Generator $faker) {
    return [
        'type' => $faker->randomElement(['NumberField', 'EmailField', 'TextField']),
        'label' => $faker->word(),
        'role' => $faker->randomElement(['customer.name', null]),
        'required' => $faker->boolean(),
    ];
});

$factory->define(App\ProductCategory::class, function (\Faker\Generator $faker) {
    return [
        'name' => $faker->word,
    ];
});

$factory->defineAs(App\Product::class, 'withBusiness', function (\Faker\Generator $faker) {
    return [
        'name' => $faker->word,
        'description' => $faker->words(4, true),
        'price' => $faker->randomFloat(2, 0, 100),
        'business_id' => function () {
            return factory(\App\Business::class)->create()->id;
        },
    ];
});

$factory->define(App\Tax::class, function (\Faker\Generator $faker) {
    return [
        'name' => $faker->word,
        'amount' => $faker->randomFloat(2, 0, 10),
        'type' => $faker->randomElement(['percentage', 'absolute']),
        'applies_to_all' => $faker->boolean(),
    ];
});

$factory->defineAs(App\Customer::class, 'withBusiness', function () {
    return [
        'business_id' => function () {
            return factory(\App\Business::class)->create()->id;
        },
    ];
});

$factory->defineAs(App\Order::class, 'withCustomer', function (\Faker\Generator $faker) {
    $customer = factory(\App\Customer::class, 'withBusiness')->create();
    return [
        'uuid' => $faker->uuid(),
        'customer_id' => $customer->id,
        'business_id' => $customer->business_id,
    ];
});

$factory->defineAs(App\Item::class, 'withOrder', function (\Faker\Generator $faker) {
    return [
        'uuid' => $faker->uuid(),
        'quantity' => $faker->randomFloat(1, 0, 1000),
        'order_id' => function () {
            return factory(\App\Order::class, 'withCustomer')->create()->id;
        },
    ];
});

$factory->defineAs(App\ItemProduct::class, 'withItem', function (\Faker\Generator $faker) {
    return [
        'name' => $faker->word(),
        'price' => $faker->randomFloat(2, 0, 100),
        'product_id' => null,
        'item_id' => function () {
            return factory(\App\Item::class, 'withOrder')->create()->id;
        },
    ];
});
