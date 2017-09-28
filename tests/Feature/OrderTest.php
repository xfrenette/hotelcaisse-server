<?php

namespace Tests\Feature;

use App\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseTransactions;

    public function testScopeFrom()
    {
        $baseDate = Carbon::yesterday();

        $orders = [
            ['uuid' => 'test-1', 'created_at' => $baseDate->copy()->subHours(9)],
            // created_at is after all others, but inserted before
            ['uuid' => 'test-8', 'created_at' => $baseDate->copy()->subHours(4)],
            ['uuid' => 'test-2', 'created_at' => $baseDate->copy()->subHours(8)],
            // Same created_at as 'test-2'
            ['uuid' => 'test-3', 'created_at' => $baseDate->copy()->subHours(8)],
            // Same created_at as 'test-2 and 3'
            ['uuid' => 'test-4', 'created_at' => $baseDate->copy()->subHours(8)],
            // 'test-6' inserted before test-5, but has later created_at
            ['uuid' => 'test-6', 'created_at' => $baseDate->copy()->subHours(6)],
            ['uuid' => 'test-5', 'created_at' => $baseDate->copy()->subHours(7)],
            ['uuid' => 'test-7', 'created_at' => $baseDate->copy()->subHours(5)],
        ];

        $customer = factory(\App\Customer::class, 'withBusiness')->create();

        foreach ($orders as $orderData) {
            $order = new Order($orderData);
            $order->created_at = $orderData['created_at'];
            $order->business()->associate($customer->business);
            $order->customer()->associate($customer);
            $order->save();
        }

        $from = Order::where('uuid', 'test-3')->first();
        $res = Order::where('business_id', $customer->business->id)->from($from)->orderBy('created_at')->get();
        $uuids = $res->pluck('uuid')->toArray();
        $this->assertEquals(['test-4', 'test-5', 'test-6', 'test-7', 'test-8'], $uuids);
    }

    public function testLoadAllRelations()
    {
        $order = Order::first();
        $order->loadAllRelations();

        $this->assertTrue($order->relationLoaded('items'));
        $this->assertTrue($order->relationLoaded('transactions'));
        $this->assertTrue($order->relationLoaded('customer'));
        $this->assertTrue($order->relationLoaded('credits'));
        $this->assertTrue($order->relationLoaded('roomSelections'));

        // Check that sub-relations are loaded
        $this->assertTrue($order->items->first()->relationLoaded('product'));
        $this->assertTrue($order->transactions->first()->relationLoaded('transactionMode'));
        $this->assertTrue($order->roomSelections->first()->relationLoaded('room'));
    }

    public function testValuesAttribute()
    {
        // Requires test data
        $order = Order::with('items.product')->first();
        $subTotal = 0;
        $taxes = [];
        $taxesTotal = 0;

        $order->items->each(function($item) use(&$subTotal, &$taxes, &$taxesTotal) {
            $subTotal += round($item->product->price * $item->quantity, 2);
            $item->taxes->each(function($tax) use (&$taxes, &$taxesTotal) {
                if (array_key_exists($tax['taxId'], $taxes)) {
                    $currentAmount = $taxes[$tax['taxId']]['amount'];
                    $taxes[$tax['taxId']]['amount'] = floatval(bcadd($currentAmount, $tax['amount']));
                } else {
                    $taxes[$tax['taxId']] = $tax;
                }
                $taxesTotal = round($taxesTotal + $tax['amount'], 4);
            });
        });

        $creditsTotal = $order->credits->reduce(function ($total, $credit) {
            return round($total + $credit->amount, 2);
        });

        $totalPayments = $order->transactions->reduce(function ($total, $transaction) {
            return $total + $transaction->amount;
        }, 0);

        $balance = $subTotal + $taxesTotal - $totalPayments;

        $this->assertEquals($subTotal, $order->subTotal);
        $this->assertEquals(array_values($taxes), $order->taxes->toArray());
        $this->assertEquals($creditsTotal, $order->creditsTotal);
        $this->assertInternalType('float', $order->creditsTotal);
        $this->assertEquals($balance, $order->balance);
    }

    public function testValuesAttributeEmpty()
    {
        $order = new Order();

        $this->assertEquals(0, $order->subTotal);
        $this->assertEquals(0, $order->taxes->count());
        $this->assertEquals(0, $order->creditsTotal);
        $this->assertEquals(0, $order->balance);
    }
}
