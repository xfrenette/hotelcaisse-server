<?php

namespace Tests\Unit;

use App\Credit;
use App\Customer;
use App\Item;
use App\Order;
use App\RoomSelection;
use App\Transaction;
use Carbon\Carbon;
use Tests\TestCase;

class OrderTest extends TestCase
{
    public function testToArray()
    {
        $date = Carbon::yesterday();
        $items = collect([]);
        $transactions = collect([]);
        $credits = collect([]);
        $roomSelections = collect([]);

        // Create 3 dummy of each Item, Transaction, Credit and RoomSelection
        for ($i = 1; $i <= 3; $i++) {
            $items->push(new Item(['uuid' => 'uuid-item-'.$i]));
            $transactions->push(new Transaction(['uuid' => 'uuid-transaction-'.$i]));
            $credits->push(new Credit(['uuid' => 'uuid-credit-'.$i]));
            $roomSelections->push(new RoomSelection([
                'uuid' => 'uuid-roomSelection-'.$i,
                'start_date' => Carbon::yesterday(),
                'end_date' => Carbon::tomorrow(),
            ]));
        }

        $expected = [
            'uuid' => 'test-order-uuid',
            'createdAt' => $date->getTimestamp(),
            'note' => 'test-note',
            'items' => $items->toArray(),
            'transactions' => $transactions->toArray(),
            'credits' => $credits->toArray(),
            'roomSelections' => $roomSelections->toArray(),
            'customer' => [
                'fieldValues' => [
                    ['field' => 12, 'value' => 'test-1'],
                    ['field' => 14, 'value' => 'test-2'],
                ],
            ],
        ];

        $customer = $this->getMockBuilder(Customer::class)
            ->setMethods(['getFieldValuesAttribute'])
            ->getMock();
        $customer->method('getFieldValuesAttribute')
            ->willReturn(array_get($expected, 'customer.fieldValues'));
        $customer->id = 896;

        $order = new Order($expected);
        $order->id = 123;
        $order->created_at = $date;
        $order->customer()->associate($customer);
        // Simulate loaded collections
        $order->setRelation('items', $items);
        $order->setRelation('transactions', $transactions);
        $order->setRelation('roomSelections', $roomSelections);
        $order->setRelation('credits', $credits);

        $this->assertEquals($expected, $order->toArray());
    }
}
