<?php

namespace Tests\Unit;

use App\Business;
use App\Room;
use Tests\TestCase;

class RoomTest extends TestCase
{
    public function testToArray()
    {
        $business = new Business();
        $business->slug = 'test-slug';
        $business->id = 123;

        $expected = [
            'id' => 456,
            'name' => 'test-note',
        ];

        $room = new Room($expected);
        $room->id = $expected['id'];
        $room->business()->associate($business);

        $this->assertEquals($expected, $room->toArray());
    }
}
