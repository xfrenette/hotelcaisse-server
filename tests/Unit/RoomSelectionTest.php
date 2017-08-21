<?php

namespace Tests\Unit;

use App\Order;
use App\Room;
use App\RoomSelection;
use Carbon\Carbon;
use Tests\TestCase;

class RoomSelectionTest extends TestCase
{
    public function testToArray()
    {
        $fields = [
            ['field' => 3, 'value' => 'test1'],
            ['field' => 4, 'value' => 'test2'],
        ];
        $room = new Room([
            'name' => 'test room',
        ]);
        $room->id = 2;

        $expected = [
            'fieldValues' => $fields,
            'uuid' => 'test-uuid',
            'startDate' => Carbon::now()->getTimestamp(),
            'endDate' => Carbon::now()->getTimestamp(),
            'room' => $room->toArray(),
        ];

        $order = new Order();
        $order->id = 456;

        $roomSelection = $this->getMockBuilder(RoomSelection::class)
            ->setMethods(['getFieldValuesAttribute'])
            ->getMock();
        $roomSelection->method('getFieldValuesAttribute')
            ->willReturn($fields);

        $roomSelection->fill($expected);
        $roomSelection->start_date = $expected['startDate'];
        $roomSelection->end_date = $expected['endDate'];
        $roomSelection->room()->associate($room);
        $roomSelection->order()->associate($order);
        $this->assertEquals($expected, $roomSelection->toArray());
    }

    public function testGetFieldsClass()
    {
        $roomSelection = new RoomSelection();
        $this->assertEquals('RoomSelection', $roomSelection->getFieldsClass());
    }
}
