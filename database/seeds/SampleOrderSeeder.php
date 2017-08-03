<?php

use Illuminate\Database\Seeder;

class SampleOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (\App\Order::count() > 0) {
            return;
        }

        $faker = \Faker\Factory::create();
        $business = \App\Business::first();

        $customer = new \App\Customer();

        $customerFields = $business->customerFields;
        $customerFields->each(function ($field) use (&$data, $faker) {
            $data['customer']['fieldValues'][] = [
                'field' => $field->id,
                'value' => $faker->word(),
            ];
        });

        $customer->business()->associate($business);
        $customer->save();

        $order = new \App\Order();
        $order->uuid = $faker->uuid();
        $order->note = $faker->words(10, true);
        $order->customer()->associate($customer);
        $order->business()->associate($business);
        $order->save();

        // credits
        for ($i = 0; $i < 4; $i++) {
            $amount = $faker->randomFloat(2, 0, 10);

            $credit = new \App\Credit([
                'uuid' => $faker->uuid(),
                'note' => $faker->words(5, true),
                'amount' => $amount == 0 ? 1 : $amount,
            ]);

            $credit->order()->associate($order);
            $credit->save();
        }

        // items
        $products = $business->products;
        for ($i = 0; $i < 4; $i++) {
            $isCustom = $i === 2;

            $item = new \App\Item([
                'uuid' => $faker->uuid(),
                'quantity' => $isCustom ? -2 : 2,
            ]);
            $item->order()->associate($order);
            $item->save();

            $itemProduct = new \App\ItemProduct([
                'name' => $faker->words(2, true),
                'price' => $faker->randomFloat(2, 0.1, 10),
                'product_id' => $isCustom ? null : $products->random()->id,
            ]);
            $itemProduct->item()->associate($item);
            $itemProduct->save();

            if (!$isCustom) {
                $taxes = $business->taxes()
                    ->inRandomOrder()
                    ->take(2)
                    ->get()
                    ->map(function ($tax) use ($faker) {
                        return [
                            'tax_id' => $tax->id,
                            'amount' => $faker->randomFloat(4, 0, 20),
                        ];
                    })->toArray();
                $itemProduct->setTaxes($taxes);
            }
        }

        // room selections
        $rooms = $business->rooms;
        $roomSelectionFields = $business->roomSelectionFields;
        for ($i = 0; $i < 4; $i++) {
            $endDate = $faker->dateTimeThisMonth();
            $startDate = clone $endDate;
            $startDate->sub(new \DateInterval('PT' . $faker->numberBetween(25, 200) . 'H'));
            $fieldValues = [];
            $roomSelectionFields->each(function ($field) use (&$fieldValues, $faker) {
                $fieldValues[] = [
                    'field' => $field->id,
                    'value' => $faker->word(),
                ];
            });

            $roomSelection = new \App\RoomSelection([
                'uuid' => $faker->uuid(),
                'start_date' => $startDate->getTimestamp(),
                'end_date' => $endDate->getTimestamp(),
            ]);

            $roomSelection->room()->associate($rooms->random());
            $roomSelection->order()->associate($order);
            $roomSelection->save();

            $roomSelection->setFieldValues($fieldValues);
        }

        // transactions
        $transactionModes = $business->transactionModes;
        $register = \App\Register::first();
        for ($i = 0; $i < 4; $i++) {
            $amount = $faker->randomFloat(2, -10, 10);

            $transaction = new \App\Transaction([
                'uuid' => $faker->uuid(),
                'amount' => $amount == 0 ? 1 : $amount,
            ]);
            $transaction->transactionMode()->associate($transactionModes->random());
            $transaction->order()->associate($order);
            $transaction->register()->associate($register);
            $transaction->save();
        }
    }
}
