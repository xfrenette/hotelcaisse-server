<?php

namespace Tests\Unit;

use App\Field;
use Tests\TestCase;

class FieldTest extends TestCase
{
    public function testToArray()
    {
        $expected = [
            'id' => 456,
            'type' => 'NumberField',
            'label' => 'test-label',
            'role' => 'test-role',
            'required' => true,
            'defaultValue' => 'test-defaultValue',
            'values' => ['a', 'b'],
        ];

        $field = new Field($expected);
        $field->id = $expected['id'];

        $this->assertEquals($expected, $field->toArray());
    }
}
