<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Credit;
use App\Customer;
use App\Exceptions\CrossBusinessAccessException;
use App\Http\Controllers\Api\OrdersController;
use App\Item;
use App\ItemProduct;
use App\Order;
use App\Room;
use App\RoomSelection;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery as m;
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

        if (is_null($this->business)) {
            throw new \Exception('This test class requires test data. Run the seeder.');
        }

        if (!self::$faker) {
            self::$faker = Factory::create();
        }
    }

    protected function tearDown()
    {
        parent::tearDown();
        m::close();
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
            $amount = $faker->randomFloat(2, 0, 10);

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
                            'tax' => $tax->id,
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

    protected function generateEditData()
    {
        $data = $this->generateNewData();
        $order = Order::first();

        if (is_null($order)) {
            throw new \Exception('This test requires test data. Run the seeder.');
        }

        array_set($data, 'data.uuid', $order->uuid);

        return $data;
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
        $values = [null, 0, 'test', -5];
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
        $existingItem->product()->associate(factory(ItemProduct::class)->create());
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
        $this->assertValidatesData('api.orders.new', $data, 'items.1.product.taxes.1.tax', $values);
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
        $this->assertEquals(array_get($data, 'data.items.1.product.taxes.1.tax'), $taxes[1]['tax_id']);
        $this->assertEquals(array_get($data, 'data.items.1.product.taxes.1.amount'), $taxes[1]['amount']);
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

    ////////////////////////////////////////
    // ====================================
    ////////////////////////////////////////

    public function testEditWorksWithValidData()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);

        $data = $this->generateEditData();
        $response = $this->queryAPI('api.orders.edit', $data);
        $response->assertJson([
            'status' => 'ok'
        ]);
    }

    public function testEditWorksWithMissingOptionalAttributes()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);

        $data = $this->generateEditData();

        array_forget($data, 'data.customer');
        array_forget($data, 'data.note');
        array_forget($data, 'data.credits');
        array_forget($data, 'data.items');
        array_forget($data, 'data.roomSelections');
        array_forget($data, 'data.transactions');

        $response = $this->queryAPI('api.orders.edit', $data);
        $response->assertJson([
            'status' => 'ok'
        ]);
    }

    public function testEditReturnsErrorWithInvalidUUID()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->mockApiAuthDevice($device);

        $data = $this->generateEditData();
        $data['data']['uuid'] = 'non-existent';
        $response = $this->queryAPI('api.orders.edit', $data);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_CLIENT_ERROR,
            ],
        ]);
    }

    public function testEditReturnsErrorWithInvalidNote()
    {
        $data = $this->generateEditData();
        $values = [123];
        $this->assertValidatesData('api.orders.edit', $data, 'note', $values, false);
    }

    public function testEditReturnsErrorWithInvalidCustomer()
    {
        $data = $this->generateEditData();
        $values = [null, false, 'test'];
        $this->assertValidatesData('api.orders.edit', $data, 'customer', $values, false);
    }

    public function testEditReturnsErrorWithInvalidCustomerFields()
    {
        // test customer.fieldValues
        $data = $this->generateEditData();
        $values = [null, 1, []];
        $this->assertValidatesData('api.orders.edit', $data, 'customer.fieldValues', $values);

        // test customer.fieldValues.*.field
        $data = $this->generateEditData();
        $values = [null, false, 0];
        $this->assertValidatesData('api.orders.edit', $data, "customer.fieldValues.0.field", $values);

        // test customer.fieldValues.*.value
        $data = $this->generateEditData();
        $values = [null, false, 2];
        $this->assertValidatesData('api.orders.edit', $data, "customer.fieldValues.0.value", $values);
    }

    public function testEditReturnsErrorWithInvalidCredits()
    {
        $data = $this->generateEditData();
        $values = [null, 1];
        $this->assertValidatesData('api.orders.edit', $data, 'credits', $values, false);
    }

    public function testEditReturnsErrorWithInvalidCreditsDataUUID()
    {
        $data = $this->generateEditData();
        $values = [null, 1];
        $this->assertValidatesData('api.orders.edit', $data, 'credits.1.uuid', $values);
    }

    public function testEditReturnsErrorWithInvalidCreditsDataNote()
    {
        $data = $this->generateEditData();
        $values = [null, 1, '', ' '];
        $this->assertValidatesData('api.orders.edit', $data, 'credits.1.note', $values);
    }

    public function testEditReturnsErrorWithInvalidCreditsDataAmount()
    {
        $data = $this->generateEditData();
        $values = [null, 0, 'test', -5];
        $this->assertValidatesData('api.orders.edit', $data, 'credits.1.amount', $values);
    }

    public function testEditReturnsErrorWithInvalidTransactions()
    {
        $data = $this->generateEditData();
        $values = [null, 1];
        $this->assertValidatesData('api.orders.edit', $data, 'transactions', $values, false);
    }

    public function testEditReturnsErrorWithInvalidTransactionsDataUUID()
    {
        // TODO: test for existing id
        $data = $this->generateEditData();
        $values = [null, 1];
        $this->assertValidatesData('api.orders.edit', $data, 'transactions.1.uuid', $values);
    }

    public function testEditReturnsErrorWithInvalidTransactionsDataAmount()
    {
        $data = $this->generateEditData();
        $values = [null, 0, 'test'];
        $this->assertValidatesData('api.orders.edit', $data, 'transactions.1.amount', $values);
    }

    public function testEditReturnsErrorWithInvalidTransactionsDataTransactionMode()
    {
        $data = $this->generateEditData();
        $values = [null, 0, 'test'];
        $this->assertValidatesData('api.orders.edit', $data, 'transactions.1.transactionMode', $values);
    }

    public function testEditReturnsErrorWithInvalidItems()
    {
        $data = $this->generateEditData();
        $values = [null, 1];
        $this->assertValidatesData('api.orders.edit', $data, 'items', $values, false);
    }

    public function testEditReturnsErrorWithInvalidItemsDataQuantity()
    {
        $data = $this->generateEditData();
        $values = [null, 0, 'test'];
        $this->assertValidatesData('api.orders.edit', $data, 'items.1.quantity', $values);
    }

    public function testEditReturnsErrorWithInvalidItemsDataUUID()
    {
        $data = $this->generateEditData();

        $existingOrder = $this->createOtherOrder();
        $existingItem = new Item();
        $existingItem->uuid = self::$faker->uuid();
        $existingItem->order()->associate($existingOrder);
        $existingItem->quantity = 1;
        $existingItem->product()->associate(factory(ItemProduct::class)->create());
        $existingItem->save();

        $values = [null, 1, $existingItem->uuid];
        $this->assertValidatesData('api.orders.edit', $data, 'items.1.uuid', $values);
    }

    public function testEditReturnsErrorWithInvalidItemsDataProduct()
    {
        $data = $this->generateEditData();
        $values = [null, 0, 'test'];
        $this->assertValidatesData('api.orders.edit', $data, 'items.1.product', $values);
    }

    public function testEditReturnsErrorWithInvalidItemsDataProductName()
    {
        $data = $this->generateEditData();
        $values = [null, 0];
        $this->assertValidatesData('api.orders.edit', $data, 'items.1.product.name', $values);
    }

    public function testEditReturnsErrorWithInvalidItemsDataProductPrice()
    {
        $data = $this->generateEditData();
        $values = [null, -5, 'test'];
        $this->assertValidatesData('api.orders.edit', $data, 'items.1.product.price', $values);
    }

    public function testEditReturnsErrorWithInvalidItemsDataProductID()
    {
        $data = $this->generateEditData();
        $values = [-1, 'test'];
        $this->assertValidatesData('api.orders.edit', $data, 'items.1.product.product_id', $values, false);
    }

    public function testEditReturnsErrorWithInvalidItemsDataTaxes()
    {
        $data = $this->generateEditData();
        $values = [1, 'test'];
        $this->assertValidatesData('api.orders.edit', $data, 'items.1.product.taxes', $values, false);
    }

    public function testEditReturnsErrorWithInvalidItemsDataTaxesId()
    {
        $data = $this->generateEditData();
        $values = [null, -1, 'test'];
        $this->assertValidatesData('api.orders.edit', $data, 'items.1.product.taxes.1.tax', $values);
    }

    public function testEditReturnsErrorWithInvalidItemsDataTaxesAmount()
    {
        $data = $this->generateEditData();
        $values = [null, -1, 0, 'test'];
        $this->assertValidatesData('api.orders.edit', $data, 'items.1.product.taxes.1.amount', $values);
    }

    public function testEditReturnsErrorWithInvalidRoomSelections()
    {
        $data = $this->generateEditData();
        $values = [null, 1];
        $this->assertValidatesData('api.orders.edit', $data, 'roomSelections', $values, false);
    }

    public function testEditReturnsErrorWithInvalidRoomSelectionsDataUUID()
    {
        $data = $this->generateEditData();

        $values = [null, 1];
        $this->assertValidatesData('api.orders.edit', $data, 'roomSelections.1.uuid', $values);
    }

    public function testEditReturnsErrorWithInvalidRoomSelectionsDataStartDate()
    {
        $data = $this->generateEditData();
        $values = [null, 'test', -1];
        $this->assertValidatesData('api.orders.edit', $data, 'roomSelections.1.startDate', $values);
    }

    public function testEditReturnsErrorWithInvalidRoomSelectionsDataEndDate()
    {
        $data = $this->generateEditData();
        $values = [null, 'test', -2];
        $this->assertValidatesData('api.orders.edit', $data, 'roomSelections.1.endDate', $values);
    }

    public function testEditReturnsErrorWithInvalidRoomSelectionsDataStartDateBeforeEnd()
    {
        $data = $this->generateEditData();
        $start = array_get($data, 'data.roomSelections.1.startDate');
        $end = array_get($data, 'data.roomSelections.1.endDate');

        array_set($data, 'data.roomSelections.1.endDate', $start);
        array_set($data, 'data.roomSelections.1.startDate', $end);

        $response = $this->queryAPI('api.orders.edit', $data);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_CLIENT_ERROR,
            ],
        ]);
    }

    public function testEditReturnsErrorWithInvalidRoomSelectionsDataRoom()
    {
        $data = $this->generateEditData();
        $values = [null, 'test', 0];
        $this->assertValidatesData('api.orders.edit', $data, 'roomSelections.1.room', $values);
    }

    public function testEditReturnsErrorWithInvalidRoomSelectionFields()
    {
        // test roomSelections.*.fieldValues
        $data = $this->generateEditData();
        $values = [null, 1, []];
        $this->assertValidatesData('api.orders.edit', $data, 'roomSelections.1.fieldValues', $values);

        // test roomSelections.*.fieldValues.*.field
        $data = $this->generateEditData();
        $values = [null, false, 0];
        $this->assertValidatesData('api.orders.edit', $data, "roomSelections.1.fieldValues.0.field", $values);

        // test customer.fieldValues.*.value
        $data = $this->generateEditData();
        $values = [null, false, 2];
        $this->assertValidatesData('api.orders.edit', $data, "roomSelections.1.fieldValues.0.value", $values);
    }

    // --------------------

    public function testEditFailsIfTransactionsWithClosedRegister()
    {
        $baseData = $this->generateEditData();
        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);
        $data = [
            'data' => array_only($baseData['data'], ['uuid', 'transactions']),
        ];

        $response = $this->queryAPI('api.orders.edit', $data);
        $response->assertJson([
            'status' => 'error',
            'error' => [
                'code' => ApiResponse::ERROR_CLIENT_ERROR,
            ],
        ]);
    }

    public function testEditIgnoresOpenedRegisterIfNoTransactions()
    {
        $baseData = $this->generateEditData();
        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);
        $data = [
            'data' => array_only($baseData['data'], ['uuid', 'items']),
        ];
        $response = $this->queryAPI('api.orders.edit', $data);

        $response->assertJson([
            'status' => 'ok',
        ]);
    }

    // -------------------------

    public function testUpdateOrderUpdatesNote()
    {
        $order = Order::first();
        $oldNote = $order->note;
        $device = $this->createDeviceWithOpenedRegister();

        // Test not changed if attribute not there
        $this->controller->updateOrder($order, [], $device->currentRegister);
        $order->refresh();
        $this->assertEquals($oldNote, $order->note);

        // Test new value is saved
        $values = [$oldNote . 'new', null];
        foreach ($values as $value) {
            $this->controller->updateOrder($order, ['note' => $value], $device->currentRegister);
            $order->refresh();
            $this->assertEquals($value, $order->note);
        }
    }

    public function testUpdateOrderUpdatesCustomerFields()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $order = Order::first();
        $old = $order->customer->fieldValues;

        // Test not changed if attribute not there
        $this->controller->updateOrder($order, ['note' => 'test'], $device->currentRegister);
        $order->refresh();
        $this->assertEquals($old, $order->customer->fieldValues);

        $new = $old->slice(1, 2)->values();
        $editedElement = $new[0];
        $editedElement['value'] = 'new' . $editedElement['value'];
        $new[0] = $editedElement;
        $data = [
            'customer' => [
                'fieldValues' => $new->toArray(),
            ],
        ];
        $this->controller->updateOrder($order, $data, $device->currentRegister);
        $this->assertEquals($new->toArray(), $order->customer->fieldValues->toArray());
    }

    public function testUpdateOrderUpdatesCredits()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $order = Order::first();
        $old = $order->credits;

        // Does nothing if not present
        $data = ['note' => 'test'];
        $this->controller->updateOrder($order, $data, $device->currentRegister);
        $order->refresh();
        $this->assertEquals($old->count(), $order->credits->count());

        // Remove one
        $new = $old->slice(1)->values()->map(function ($credit, $index) {
            $data = [
                'uuid' => $credit->uuid,
                'note' => $credit->note,
                'amount' => $credit->amount,
            ];

            // Modify one
            if ($index === 1) {
                $data['note'] = 'new' . $data['note'];
            }

            return $data;
        });

        // Add one
        $new->prepend([
            'uuid' => 'test-uuid',
            'note' => 'test',
            'amount' => 3
        ]);

        $this->controller->updateOrder($order, ['credits' => $new->toArray()], $device->currentRegister);
        $order->refresh();
        $this->assertEquals($new->count(), $order->credits->count());

        $new->each(function ($newData) use ($order) {
            $credit = $order->credits->first(function ($credit) use ($newData) {
                return $credit->uuid === $newData['uuid'];
            });
            $this->assertEquals($newData['note'], $credit->note);
        });
    }

    public function testUpdateOrderAddsTransactions()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $order = Order::first();
        $old = $order->transactions;

        // Does nothing if not present
        $data = ['note' => 'test'];
        $this->controller->updateOrder($order, $data, $device->currentRegister);
        $order->refresh();
        $this->assertEquals($old->count(), $order->transactions->count());

        $baseData = $this->generateEditData();
        $new = $baseData['data']['transactions'];

        $this->controller->updateOrder($order, ['transactions' => $new], $device->currentRegister);
        $order->refresh();
        $this->assertEquals(count($new) + $old->count(), $order->transactions->count());

        $UUIDs = $order->transactions->pluck('uuid');
        $newUUIDs = array_pluck($new, 'uuid');
        // Test all elements are present by removing new UUIDs
        $this->assertEquals($old->count(), $UUIDs->diff($newUUIDs)->count());
        // Test all elements are present by removing old UUIDs
        $this->assertEquals(count($newUUIDs), $UUIDs->diff($old->pluck('uuid'))->count());
    }

    public function testUpdateOrderAddsItems()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $order = Order::first();
        $old = $order->items;

        // Does nothing if not present
        $data = ['note' => 'test'];
        $this->controller->updateOrder($order, $data, $device->currentRegister);
        $order->refresh();
        $this->assertEquals($old->count(), $order->items->count());

        $baseData = $this->generateEditData();
        $new = $baseData['data']['items'];

        $this->controller->updateOrder($order, ['items' => $new], $device->currentRegister);
        $order->refresh();
        $this->assertEquals(count($new) + $old->count(), $order->items->count());

        $UUIDs = $order->items->pluck('uuid');
        $newUUIDs = array_pluck($new, 'uuid');
        // Test all elements are present by removing new UUIDs
        $this->assertEquals($old->count(), $UUIDs->diff($newUUIDs)->count());
        // Test all elements are present by removing old UUIDs
        $this->assertEquals(count($newUUIDs), $UUIDs->diff($old->pluck('uuid'))->count());
    }

    public function testUpdateOrderUpdatesRoomSelections()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $order = Order::first();
        $old = $order->roomSelections;

        // Does nothing if not present
        $data = ['note' => 'test'];
        $this->controller->updateOrder($order, $data, $device->currentRegister);
        $order->refresh();
        $this->assertEquals($old->count(), $order->roomSelections->count());

        // Remove one
        $new = $old->slice(1)->values()->map(function ($roomSelection, $index) {
            $fieldValues = $roomSelection->fieldValues->toArray();

            $data = [
                'uuid' => $roomSelection->uuid,
                'startDate' => $roomSelection->start_date->getTimestamp(),
                'endDate' => $roomSelection->end_date->getTimestamp(),
                'room' => $roomSelection->room_id,
                'fieldValues' => $fieldValues,
            ];

            // Modify one
            if ($index === 1) {
                $data['startDate'] = 4;
            }

            return $data;
        });

        // Add one
        $new->prepend([
            'uuid' => 'test-uuid',
            'startDate' => $new[0]['startDate'] - 1000,
            'endDate' => $new[0]['endDate'] - 1000,
            'room' => $new[0]['room'],
            'fieldValues' => $new[0]['fieldValues'], // just a simple copy if the first
        ]);

        $this->controller->updateOrder($order, ['roomSelections' => $new->toArray()], $device->currentRegister);
        $order->refresh();
        $this->assertEquals($new->count(), $order->items->count());

        $new->each(function ($newData) use ($order) {
            $roomSelection = $order->roomSelections->first(function ($instance) use ($newData) {
                return $instance->uuid === $newData['uuid'];
            });

            $this->assertEquals($newData['startDate'], $roomSelection->start_date->getTimestamp());
        });
    }

    // ----------------------------

    protected function generateListData()
    {
        return [
            'data' => [
                'quantity' => 4,
                'from' => $this->business->orders()->first()->uuid,
            ],
        ];
    }

    public function testValidateList()
    {
        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);
        $data = $this->generateListData();

        // Quantity
        $max = config('api.orders.list.quantity.max');
        $values = [-1, 0, '', ' ', null, $max + 1];
        $this->assertValidatesRequestData([$this->controller, 'validateList'], $data, 'data.quantity', $values);

        // from (UUID)
        // Create a Order that will be saved, but that is assigned to another $business (so should fail validation, thus
        // passing the test)
        $otherOrder = \factory(Order::class, 'withCustomer')->create();
        $values = ['non-existent', false, '', ' ', null, $otherOrder->uuid];
        $this->assertValidatesRequestData([$this->controller, 'validateList'], $data, 'data.from', $values, false);

        // Works without from
        $testData = $data;
        array_forget($testData, 'data.from');
        $request = $this->mockRequest($testData);
        $this->controller->validateList($request); // No exception should be thrown

        // Works with valid data
        $request = $this->mockRequest($data);
        $this->controller->validateList($request); // No exception should be thrown
    }

    public function testListCallsValidateList()
    {
        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);
        $request = $this->mockRequest();
        $controller = m::mock(OrdersController::class)->makePartial();
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $controller->shouldReceive('validateList')
            ->andReturnNull()
            ->atLeast()
            ->once();

        $controller->list($request);
    }

    public function testListReturnsGetOrdersResult()
    {
        $ordersResult = collect([
            'test' => true,
        ]);

        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);
        $quantity = 4;
        $from = $this->business->orders()->first();

        $request = $this->mockRequest(['data' => ['quantity' => $quantity, 'from' => $from->uuid]]);
        $controller = m::mock(OrdersController::class);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $controller->shouldReceive('validateList')->andReturnNull();
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $controller->shouldReceive('getOrders')
            ->withArgs([$device->business, $quantity, m::on(function ($arg) use ($from) {
                return $arg->uuid === $from->uuid;
            })])
            ->once()
            ->andReturn($ordersResult);

        $res = $controller->list($request);
        $this->assertEquals($ordersResult, $res->getResponseData());
    }

    public function testListRoute()
    {
        $device = $this->createDevice();
        $this->mockApiAuthDevice($device);
        $quantity = 1;
        $orders = $this->controller->getOrders($device->business, $quantity);
        $data = [
            'data' => ['quantity' => $quantity],
        ];

        $response = $this->queryAPI('api.orders.list', $data);
        $response->assertJson([
            'data' => $orders->toArray(),
        ]);
    }

    // ------------------------

    public function testGetOrders()
    {
        $baseDate = Carbon::yesterday();

        $orders = [
            ['uuid' => 'test-1', 'created_at' => $baseDate->copy()->subHours(9)],
            ['uuid' => 'test-2', 'created_at' => $baseDate->copy()->subHours(8)],
            ['uuid' => 'test-3', 'created_at' => $baseDate->copy()->subHours(7)],
            // 'test-5' inserted before 'test-4', but with later created_at
            ['uuid' => 'test-5', 'created_at' => $baseDate->copy()->subHours(5)],
            ['uuid' => 'test-4', 'created_at' => $baseDate->copy()->subHours(6)],
            ['uuid' => 'other-business', 'created_at' => $baseDate->copy()->subHours(4)],
        ];

        $customer = new Customer();
        $customer->business()->associate($this->business);
        $customer->save();

        $otherOrder = null;

        foreach ($orders as $index => $orderData) {
            $order = new Order($orderData);
            $order->created_at = $orderData['created_at'];
            $order->business()->associate($this->business);
            $order->customer()->associate($customer);
            $order->save();

            if ($index === count($orders) - 1) {
                $otherOrder = $order;
            }
        }

        // Move last Order to another business
        $otherCustomer = \factory(Customer::class, 'withBusiness')->create();
        $otherOrder->customer()->associate($otherCustomer);
        $otherOrder->business()->associate($otherCustomer->business);
        $otherOrder->save();

        // No $from specified
        $res = $this->controller->getOrders($this->business, 2);
        $uuids = $res->pluck('uuid')->toArray();
        $this->assertEquals(['test-5', 'test-4'], $uuids);

        // With $from specified
        $from = Order::where('uuid', 'test-2')->first();
        $res = $this->controller->getOrders($this->business, 2, $from);
        $uuids = $res->pluck('uuid')->toArray();
        $this->assertEquals(['test-4', 'test-3'], $uuids);

        // With $quantity larger than available
        $res = $this->controller->getOrders($this->business, 10, $from);
        $uuids = $res->pluck('uuid')->toArray();
        $this->assertEquals(['test-5', 'test-4', 'test-3'], $uuids);

        // Ask the last one of the business
        $from = Order::where('uuid', 'test-5')->first();
        $res = $this->controller->getOrders($this->business, 5, $from);
        $this->assertEmpty($res);

        // Throws error if $from is not of the same business
        try {
            $this->controller->getOrders($this->business, 5, $otherOrder);
            $this->fail('Did not throw exception with $from an Order for another Business');
        } catch (CrossBusinessAccessException $e) {
            // Do nothing
        }
    }
}
