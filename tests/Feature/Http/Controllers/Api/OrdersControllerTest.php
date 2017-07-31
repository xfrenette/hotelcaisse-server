<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Credit;
use App\Http\Controllers\Api\OrdersController;
use App\Item;
use App\Order;
use App\Room;
use App\RoomSelection;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\InteractsWithAPI;
use Tests\TestCase;

// Those tests require the seeded test data
class OrdersControllerTest extends TestCase
{
    use DatabaseTransactions;
    use WithoutMiddleware;
    use InteractsWithAPI;

    /**
     * @var \Faker\Generator
     */
    protected static $faker;
    /**
     * @var OrdersController
     */
    protected $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->business = Business::first();
        $this->controller = new OrdersController();

        if (!self::$faker) {
            self::$faker = Factory::create();
        }
    }

    protected function generateNewData()
    {
        $faker = self::$faker;

        $data = [
            'uuid' => $faker->uuid(),
            'note' => $faker->words(5, true),
            'customer' => [
                'fieldValues' => [
                    // See below
                ],
            ],
            'credits' => [
                // See below
            ],
            'items' => [
                // See below
            ],
            'roomSelections' => [
                // See below
            ],
            'transactions' => [
                // See below
            ],
        ];

        // customer.fieldValues
        $customerFields = $this->business->customerFields;
        $customerFields->each(function ($field) use (&$data, $faker) {
            $data['customer']['fieldValues'][] = [
                'field' => $field->id,
                'value' => $faker->word(),
            ];
        });

        // credits
        for ($i = 0; $i < 2; $i++) {
            $amount = $faker->randomFloat(2, -10, 10);

            $data['credits'][] = [
                'uuid' => $faker->uuid(),
                'note' => $faker->words(5, true),
                'amount' => $amount == 0 ? 1 : $amount,
            ];
        }

        // items
        $products = $this->business->products;
        for ($i = 0; $i < 3; $i++) {
            $isCustom = $i === 2;

            $itemData = [
                'uuid' => $faker->uuid(),
                'quantity' => $isCustom ? -2 : 2,
                'product' => [
                    'name' => $faker->words(2, true),
                    'price' => $faker->randomFloat(2, 0.1, 10),
                    'product_id' => $isCustom ? null : $products->random()->id,
                ],
            ];

            if (!$isCustom) {
                $taxes = $this->business->taxes()
                    ->inRandomOrder()
                    ->take(2)
                    ->get()
                    ->map(function ($tax) use ($faker) {
                        return [
                            'tax_id' => $tax->id,
                            'amount' => $faker->randomFloat(4, 0, 20),
                        ];
                    })->toArray();
                $itemData['product']['taxes'] = $taxes;
            }

            $data['items'][] = $itemData;
        }

        // roomSelections
        $rooms = $this->business->rooms;
        $roomSelectionFields = $this->business->roomSelectionFields;
        for ($i = 0; $i < 2; $i++) {
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

            $data['roomSelections'][] = [
                'uuid' => $faker->uuid(),
                'startDate' => $startDate->getTimestamp(),
                'endDate' => $endDate->getTimestamp(),
                'room' => $rooms->random()->id,
                'fieldValues' => $fieldValues,
            ];
        }

        // transactions
        $transactionModes = $this->business->transactionModes;
        for ($i = 0; $i < 2; $i++) {
            $amount = $faker->randomFloat(2, -10, 10);

            $data['transactions'][] = [
                'uuid' => $faker->uuid(),
                'amount' => $amount == 0 ? 1 : $amount,
                'transactionMode' => $transactionModes->random()->id,
            ];
        }

        return ['data' => $data];
    }

    protected function createOtherOrder()
    {
        $existingOrder = factory(Order::class, 'withCustomer')->make();
        $existingOrder->business()->associate($this->business);
        $existingOrder->save();

        return $existingOrder;
    }

    public function testNewWorksWithValidData()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);

        $data = $this->generateNewData();
        $response = $this->queryAPI('api.orders.new', $data);
        $response->assertJson([
            'status' => 'ok'
        ]);
    }

    public function testNewWorksWithMissingOptionalAttributes()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);

        $data = $this->generateNewData();
        array_forget($data, 'data.credits');
        array_forget($data, 'data.items');
        array_forget($data, 'data.roomSelections');
        array_forget($data, 'data.transactions');

        $response = $this->queryAPI('api.orders.new', $data);
        $response->assertJson([
            'status' => 'ok'
        ]);
    }

    public function testNewReturnsErrorWithInvalidUUID()
    {
        $existingOrder = $this->createOtherOrder();

        $data = $this->generateNewData();
        $values = [null, 123, '', ' ', $existingOrder->uuid];
        $this->assertValidatesData('api.orders.new', $data, 'uuid', $values);
    }

    public function testNewReturnsErrorWithInvalidNote()
    {
        $data = $this->generateNewData();
        $values = [123];
        $this->assertValidatesData('api.orders.new', $data, 'note', $values, false);
    }

    public function testNewReturnsErrorWithMissingCustomer()
    {
        $data = $this->generateNewData();
        $values = [null, false, 'test'];
        $this->assertValidatesData('api.orders.new', $data, 'customer', $values);
    }

    public function testNewReturnsErrorWithInvalidCustomerFields()
    {
        // test customer.fieldValues
        $data = $this->generateNewData();
        $values = [null, 1, []];
        $this->assertValidatesData('api.orders.new', $data, 'customer.fieldValues', $values);

        // test customer.fieldValues.*.field
        $data = $this->generateNewData();
        $values = [null, false, 0];
        $this->assertValidatesData('api.orders.new', $data, "customer.fieldValues.0.field", $values);

        // test customer.fieldValues.*.value
        $data = $this->generateNewData();
        $values = [null, false, 2];
        $this->assertValidatesData('api.orders.new', $data, "customer.fieldValues.0.value", $values);
    }

    public function testNewReturnsErrorWithInvalidCredits()
    {
        $data = $this->generateNewData();
        $values = [null, 1];
        $this->assertValidatesData('api.orders.new', $data, 'credits', $values, false);
    }

    public function testNewReturnsErrorWithInvalidCreditsDataUUID()
    {
        $existingOrder = $this->createOtherOrder();
        $existingCredit = new Credit();
        $existingCredit->uuid = self::$faker->uuid();
        $existingCredit->order()->associate($existingOrder);
        $existingCredit->note = 'test';
        $existingCredit->amount = 1;
        $existingCredit->save();

        $data = $this->generateNewData();
        $values = [null, 1, $existingCredit->uuid];
        $this->assertValidatesData('api.orders.new', $data, 'credits.1.uuid', $values);
    }

    public function testNewReturnsErrorWithInvalidCreditsDataNote()
    {
        $data = $this->generateNewData();
        $values = [null, 1, '', ' '];
        $this->assertValidatesData('api.orders.new', $data, 'credits.1.note', $values);
    }

    public function testNewReturnsErrorWithInvalidCreditsDataAmount()
    {
        $data = $this->generateNewData();
        $values = [null, 0, 'test'];
        $this->assertValidatesData('api.orders.new', $data, 'credits.1.amount', $values);
    }

    public function testNewReturnsErrorWithInvalidTransactions()
    {
        $data = $this->generateNewData();
        $values = [null, 1];
        $this->assertValidatesData('api.orders.new', $data, 'transactions', $values, false);
    }

    public function testNewReturnsErrorWithInvalidTransactionsDataUUID()
    {
        // TODO: test for existing id
        $data = $this->generateNewData();
        $values = [null, 1];
        $this->assertValidatesData('api.orders.new', $data, 'transactions.1.uuid', $values);
    }

    public function testNewReturnsErrorWithInvalidTransactionsDataAmount()
    {
        $data = $this->generateNewData();
        $values = [null, 0, 'test'];
        $this->assertValidatesData('api.orders.new', $data, 'transactions.1.amount', $values);
    }

    public function testNewReturnsErrorWithInvalidTransactionsDataTransactionMode()
    {
        $data = $this->generateNewData();
        $values = [null, 0, 'test'];
        $this->assertValidatesData('api.orders.new', $data, 'transactions.1.transactionMode', $values);
    }

    public function testNewReturnsErrorWithInvalidItems()
    {
        $data = $this->generateNewData();
        $values = [null, 1];
        $this->assertValidatesData('api.orders.new', $data, 'items', $values, false);
    }

    public function testNewReturnsErrorWithInvalidItemsDataQuantity()
    {
        $data = $this->generateNewData();
        $values = [null, 0, 'test'];
        $this->assertValidatesData('api.orders.new', $data, 'items.1.quantity', $values);
    }

    public function testNewReturnsErrorWithInvalidItemsDataUUID()
    {
        $data = $this->generateNewData();

        $existingOrder = $this->createOtherOrder();
        $existingItem = new Item();
        $existingItem->uuid = self::$faker->uuid();
        $existingItem->order()->associate($existingOrder);
        $existingItem->quantity = 1;
        $existingItem->save();

        $values = [null, 1, $existingItem->uuid];
        $this->assertValidatesData('api.orders.new', $data, 'items.1.uuid', $values);
    }

    public function testNewReturnsErrorWithInvalidItemsDataProduct()
    {
        $data = $this->generateNewData();
        $values = [null, 0, 'test'];
        $this->assertValidatesData('api.orders.new', $data, 'items.1.product', $values);
    }

    public function testNewReturnsErrorWithInvalidItemsDataProductName()
    {
        $data = $this->generateNewData();
        $values = [null, 0];
        $this->assertValidatesData('api.orders.new', $data, 'items.1.product.name', $values);
    }

    public function testNewReturnsErrorWithInvalidItemsDataProductPrice()
    {
        $data = $this->generateNewData();
        $values = [null, -5, 'test'];
        $this->assertValidatesData('api.orders.new', $data, 'items.1.product.price', $values);
    }

    public function testNewReturnsErrorWithInvalidItemsDataProductID()
    {
        $data = $this->generateNewData();
        $values = [-1, 'test'];
        $this->assertValidatesData('api.orders.new', $data, 'items.1.product.product_id', $values, false);
    }

    public function testNewReturnsErrorWithInvalidItemsDataTaxes()
    {
        $data = $this->generateNewData();
        $values = [1, 'test'];
        $this->assertValidatesData('api.orders.new', $data, 'items.1.product.taxes', $values, false);
    }

    public function testNewReturnsErrorWithInvalidItemsDataTaxesId()
    {
        $data = $this->generateNewData();
        $values = [null, -1, 'test'];
        $this->assertValidatesData('api.orders.new', $data, 'items.1.product.taxes.1.tax_id', $values);
    }

    public function testNewReturnsErrorWithInvalidItemsDataTaxesAmount()
    {
        $data = $this->generateNewData();
        $values = [null, -1, 0, 'test'];
        $this->assertValidatesData('api.orders.new', $data, 'items.1.product.taxes.1.amount', $values);
    }

    public function testNewReturnsErrorWithInvalidRoomSelections()
    {
        $data = $this->generateNewData();
        $values = [null, 1];
        $this->assertValidatesData('api.orders.new', $data, 'roomSelections', $values, false);
    }

    public function testNewReturnsErrorWithInvalidRoomSelectionsDataUUID()
    {
        $data = $this->generateNewData();

        $existingOrder = $this->createOtherOrder();
        $existingRoomSelection = new RoomSelection();
        $existingRoomSelection->uuid = self::$faker->uuid();
        $existingRoomSelection->start_date = Carbon::yesterday();
        $existingRoomSelection->end_date = Carbon::today();
        $existingRoomSelection->order()->associate($existingOrder);
        $existingRoomSelection->room()->associate(Room::first());
        $existingRoomSelection->save();

        $values = [null, 1, $existingRoomSelection->uuid];
        $this->assertValidatesData('api.orders.new', $data, 'roomSelections.1.uuid', $values);
    }

    public function testNewReturnsErrorWithInvalidRoomSelectionsDataStartDate()
    {
        $data = $this->generateNewData();
        $values = [null, 'test', -1];
        $this->assertValidatesData('api.orders.new', $data, 'roomSelections.1.startDate', $values);
    }

    public function testNewReturnsErrorWithInvalidRoomSelectionsDataEndDate()
    {
        $data = $this->generateNewData();
        $values = [null, 'test', -2];
        $this->assertValidatesData('api.orders.new', $data, 'roomSelections.1.endDate', $values);
    }

    public function testNewReturnsErrorWithInvalidRoomSelectionsDataStartDateBeforeEnd()
    {
        $data = $this->generateNewData();
        $start = array_get($data, 'data.roomSelections.1.startDate');
        $end = array_get($data, 'data.roomSelections.1.endDate');

        array_set($data, 'data.roomSelections.1.endDate', $start);
        array_set($data, 'data.roomSelections.1.startDate', $end);

        $response = $this->queryAPI('api.orders.new', $data);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_CLIENT_ERROR,
            ],
        ]);
    }

    public function testNewReturnsErrorWithInvalidRoomSelectionsDataRoom()
    {
        $data = $this->generateNewData();
        $values = [null, 'test', 0];
        $this->assertValidatesData('api.orders.new', $data, 'roomSelections.1.room', $values);
    }

    public function testNewReturnsErrorWithInvalidRoomSelectionFields()
    {
        // test roomSelections.*.fieldValues
        $data = $this->generateNewData();
        $values = [null, 1, []];
        $this->assertValidatesData('api.orders.new', $data, 'roomSelections.1.fieldValues', $values);

        // test roomSelections.*.fieldValues.*.field
        $data = $this->generateNewData();
        $values = [null, false, 0];
        $this->assertValidatesData('api.orders.new', $data, "roomSelections.1.fieldValues.0.field", $values);

        // test customer.fieldValues.*.value
        $data = $this->generateNewData();
        $values = [null, false, 2];
        $this->assertValidatesData('api.orders.new', $data, "roomSelections.1.fieldValues.0.value", $values);
    }

    // --------------------

    public function testNewFailsIfTransactionsWithClosedRegister()
    {
        $data = $this->generateNewData();
        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);
        $response = $this->queryAPI('api.orders.new', $data);

        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_CLIENT_ERROR,
            ],
        ]);
    }

    // ---------------------

    public function testCreateOrderCreatesOrder()
    {
        $data = $this->generateNewData();
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->business;
        $this->mockApiAuthDevice($device);
        $order = $this->controller->createOrder($data['data'], $business, $device->currentRegister);

        $uuid = array_get($data, 'data.uuid');
        $note = array_get($data, 'data.note');
        $this->assertEquals($uuid, $order->uuid);
        $this->assertEquals($note, $order->note);
        $this->assertEquals($business->id, $order->business->id);
    }

    public function testCreateOrderCreatesCustomer()
    {
        $data = $this->generateNewData();
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->business;
        $this->mockApiAuthDevice($device);
        $order = $this->controller->createOrder($data['data'], $business, $device->currentRegister);

        $expected = count(array_get($data, 'data.customer.fieldValues'));
        $this->assertEquals($expected, $order->customer->fieldValues->count());
    }

    public function testCreateOrderCreatesCredits()
    {
        $data = $this->generateNewData();
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->business;
        $this->mockApiAuthDevice($device);
        $order = $this->controller->createOrder($data['data'], $business, $device->currentRegister);

        $count = count(array_get($data, 'data.credits'));
        $this->assertEquals($count, $order->credits->count());
        $credit = $order->credits[1];
        $this->assertEquals(array_get($data, 'data.credits.1.uuid'), $credit->uuid);
        $this->assertEquals(array_get($data, 'data.credits.1.note'), $credit->note);
        $this->assertEquals(array_get($data, 'data.credits.1.amount'), $credit->amount);
    }

    public function testCreateOrderCreatesItems()
    {
        $data = $this->generateNewData();
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->business;
        $this->mockApiAuthDevice($device);
        $order = $this->controller->createOrder($data['data'], $business, $device->currentRegister);

        $count = count(array_get($data, 'data.items'));
        $this->assertEquals($count, $order->items->count());
        $item = $order->items[1];
        $this->assertEquals(array_get($data, 'data.items.1.uuid'), $item->uuid);
        $this->assertEquals(array_get($data, 'data.items.1.quantity'), $item->quantity);

        // Check that an ItemProduct has been created
        $product = $item->product;
        $this->assertEquals(array_get($data, 'data.items.1.product.name'), $product->name);
        $this->assertEquals(array_get($data, 'data.items.1.product.price'), $product->price);
        $this->assertEquals(array_get($data, 'data.items.1.product.product_id'), $product->product_id);

        // Check product_id null for last item (custom item)
        $this->assertNull($order->items[2]->product->product_id);
    }

    public function testCreateOrderCreatesItemsProductTaxes()
    {
        $data = $this->generateNewData();
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->business;
        $this->mockApiAuthDevice($device);
        $order = $this->controller->createOrder($data['data'], $business, $device->currentRegister);

        $product = $order->items[1]->product;
        $taxes = $product->taxes;
        $this->assertEquals(count(array_get($data, 'data.items.1.product.taxes')), $taxes->count());
        $this->assertEquals(array_get($data, 'data.items.1.product.taxes.1.tax_id'), $taxes[1]->tax_id);
        $this->assertEquals(array_get($data, 'data.items.1.product.taxes.1.amount'), $taxes[1]->amount);
    }

    public function testCreateOrderCreatesRoomSelections()
    {
        $data = $this->generateNewData();
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->business;
        $this->mockApiAuthDevice($device);
        $order = $this->controller->createOrder($data['data'], $business, $device->currentRegister);

        $count = count(array_get($data, 'data.roomSelections'));
        $this->assertEquals($count, $order->roomSelections->count());
        $roomSelection = $order->roomSelections[1];
        $this->assertEquals(array_get($data, 'data.roomSelections.1.uuid'), $roomSelection->uuid);
        $this->assertEquals(
            array_get($data, 'data.roomSelections.1.startDate'),
            $roomSelection->start_date->getTimestamp()
        );
        $this->assertEquals(
            array_get($data, 'data.roomSelections.1.endDate'),
            $roomSelection->end_date->getTimestamp()
        );
        $this->assertEquals(array_get($data, 'data.roomSelections.1.room'), $roomSelection->room->id);

        // Field values
        $count = count(array_get($data, 'data.roomSelections.1.fieldValues'));
        $this->assertEquals($count, $roomSelection->fieldValues->count());
    }

    public function testCreateOrderCreatesTransactions()
    {
        $data = $this->generateNewData();
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->business;
        $this->mockApiAuthDevice($device);
        $order = $this->controller->createOrder($data['data'], $business, $device->currentRegister);

        $count = count(array_get($data, 'data.transactions'));
        $this->assertEquals($count, $order->transactions->count());
        $transaction = $order->transactions[1];
        $this->assertEquals(array_get($data, 'data.transactions.1.uuid'), $transaction->uuid);
        $this->assertEquals(array_get($data, 'data.transactions.1.amount'), $transaction->amount);
        $this->assertEquals(array_get($data, 'data.transactions.1.transactionMode'), $transaction->transactionMode->id);
        $this->assertEquals($device->currentRegister->id, $transaction->register->id);
    }

    // -------------------------

    public function testNewBumpsBusinessVersionWithModifications()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);
        $data = $this->generateNewData();

        $oldVersion = $this->business->version;

        $this->queryAPI('api.orders.new', $data);

        $newVersion = $this->business->version;
        $this->assertNotEquals($oldVersion, $newVersion);
        $this->assertEquals(
            [Business::MODIFICATION_ORDERS],
            $this->business->getVersionModifications($newVersion)
        );
    }
}
