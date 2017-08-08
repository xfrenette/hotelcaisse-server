<?php

namespace Tests\Unit;

use App\Business;
use App\Field;
use App\Product;
use App\Room;
use App\Tax;
use App\TransactionMode;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    public function testToArray()
    {
        $expected = [
            'rooms' => [
                ['id' => 256, 'name' => 'rm-1'],
                ['id' => 257, 'name' => 'rm-2'],
            ],
            'taxes' => [
                ['id' => 356, 'name' => 'tx-1'],
                ['id' => 357, 'name' => 'tx-2'],
            ],
            'transactionModes' => [
                ['id' => 456, 'name' => 'tm-1'],
                ['id' => 457, 'name' => 'tm-2'],
            ],
            'products' => [
                ['id' => 556, 'name' => 'pr-1'],
                ['id' => 557, 'name' => 'pr-2'],
            ],
            'customerFields' => [
                ['id' => 656, 'type' => 'NumberField'],
                ['id' => 657, 'type' => 'NumberField'],
            ],
            'roomSelectionFields' => [
                ['id' => 756, 'type' => 'NumberField'],
                ['id' => 757, 'type' => 'NumberField'],
            ],
            'rootProductCategory' => [
                'name' => 'root',
                'categories' => [
                    ['name' => 'sub 1', 'products' => []],
                    ['name' => 'sub 2', 'products' => [557]],
                ],
                'products' => [556]
            ],
        ];

        $rootCategory = $this->getMockBuilder(TransactionMode::class)
            ->setMethods(['toArray'])
            ->getMock();
        $rootCategory->method('toArray')
            ->willReturn($expected['rootProductCategory']);

        $business = $this->getMockBuilder(Business::class)
            ->setMethods(['getRootProductCategoryAttribute'])
            ->getMock();
        $business->method('getRootProductCategoryAttribute')
            ->willReturn($rootCategory);

        $business->id = 123;

        $rooms = collect([]);
        $taxes = collect([]);
        $transactionModes = collect([]);
        $products = collect([]);
        $customerFields = collect([]);
        $roomSelectionFields = collect([]);

        foreach ($expected['rooms'] as $roomData) {
            $field = $this->getMockBuilder(Room::class)
                ->setMethods(['toArray'])
                ->getMock();
            $field->method('toArray')
                ->willReturn($roomData);
            $rooms->push($field);
        }

        foreach ($expected['taxes'] as $taxData) {
            $tax = $this->getMockBuilder(Tax::class)
                ->setMethods(['toArray'])
                ->getMock();
            $tax->method('toArray')
                ->willReturn($taxData);
            $taxes->push($tax);
        }

        foreach ($expected['transactionModes'] as $transactionModeData) {
            $transactionMode = $this->getMockBuilder(TransactionMode::class)
                ->setMethods(['toArray'])
                ->getMock();
            $transactionMode->method('toArray')
                ->willReturn($transactionModeData);
            $transactionModes->push($transactionMode);
        }

        foreach ($expected['products'] as $productData) {
            $product = $this->getMockBuilder(Product::class)
                ->setMethods(['toArray'])
                ->getMock();
            $product->method('toArray')
                ->willReturn($productData);
            $products->push($product);
        }

        foreach ($expected['customerFields'] as $customerFieldData) {
            $field = $this->getMockBuilder(Field::class)
                ->setMethods(['toArray'])
                ->getMock();
            $field->method('toArray')
                ->willReturn($customerFieldData);
            $customerFields->push($field);
        }

        foreach ($expected['roomSelectionFields'] as $roomSelectionFieldData) {
            $field = $this->getMockBuilder(Field::class)
                ->setMethods(['toArray'])
                ->getMock();
            $field->method('toArray')
                ->willReturn($roomSelectionFieldData);
            $roomSelectionFields->push($field);
        }

        $business->setRelation('rooms', $rooms);
        $business->setRelation('taxes', $taxes);
        $business->setRelation('transaction_modes', $transactionModes);
        $business->setRelation('products', $products);
        $business->setRelation('customer_fields', $customerFields);
        $business->setRelation('room_selection_fields', $roomSelectionFields);

        $this->assertEquals($expected, $business->toArray());
    }
}
