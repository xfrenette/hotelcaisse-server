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
use App\Jobs\PreCalcRegisterTransactions;
use App\Order;
use App\Room;
use App\RoomSelection;
use App\Team;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
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
        $this->controller = new OrdersController();

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
        $business = Business::first();

        $data = [
            'uuid' => $faker->uuid(),
            'createdAt' => 1503410614, // 08/22/2017 @ 2:03pm (UTC)
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
        $customerFields = $business->customerFields;
        $customerFields->each(function ($field) use (&$data, $faker) {
            $data['customer']['fieldValues'][] = [
                'fieldId' => $field->id,
                'value' => $faker->randomElement([1, 'text']),
            ];
        });

        // credits
        for ($i = 0; $i < 2; $i++) {
            $amount = $faker->randomFloat(2, 0, 10);

            $data['credits'][] = [
                'uuid' => $faker->uuid(),
                'note' => $faker->words(5, true),
                'amount' => $amount == 0 ? 1 : $amount,
                'createdAt' => 1503410614, // 08/22/2017 @ 2:03pm (UTC)
            ];
        }

        // items
        $products = $business->products;
        for ($i = 0; $i < 3; $i++) {
            $isCustom = $i === 2;
            $productId = $isCustom ? null : $products->random()->id;

            $itemData = [
                'uuid' => $faker->uuid(),
                'quantity' => $isCustom ? -2 : 2,
                'createdAt' => 1503410614, // 08/22/2017 @ 2:03pm (UTC)
                'product' => [
                    'name' => $faker->words(2, true),
                    'price' => $faker->randomFloat(2, 0.1, 10),
                    'productId' => $productId,
                ],
            ];

            if (!$isCustom) {
                $taxes = $business->taxes()
                    ->inRandomOrder()
                    ->take(2)
                    ->get()
                    ->map(function ($tax) use ($faker) {
                        return [
                            'taxId' => $tax->id,
                            'amount' => $faker->randomFloat(4, 0, 20),
                        ];
                    })->toArray();
                $itemData['product']['taxes'] = $taxes;
            }

            $data['items'][] = $itemData;
        }

        // roomSelections
        $rooms = $business->rooms;
        $roomSelectionFields = $business->roomSelectionFields;
        for ($i = 0; $i < 2; $i++) {
            $endDate = $faker->dateTimeThisMonth();
            $startDate = clone $endDate;
            $startDate->sub(new \DateInterval('PT' . $faker->numberBetween(25, 200) . 'H'));
            $fieldValues = [];
            $roomSelectionFields->each(function ($field) use (&$fieldValues, $faker) {
                $fieldValues[] = [
                    'fieldId' => $field->id,
                    'value' => $faker->randomElement([1, 'text value']),
                ];
            });

            $data['roomSelections'][] = [
                'uuid' => $faker->uuid(),
                'startDate' => $startDate->getTimestamp(),
                'endDate' => $endDate->getTimestamp(),
                'roomId' => $rooms->random()->id,
                'fieldValues' => $fieldValues,
            ];
        }

        // transactions
        $transactionModes = $business->transactionModes;
        for ($i = 0; $i < 2; $i++) {
            $amount = $faker->randomFloat(2, -10, 10);

            $data['transactions'][] = [
                'uuid' => $faker->uuid(),
                'amount' => $amount == 0 ? 1 : $amount,
                'transactionModeId' => $transactionModes->random()->id,
                'createdAt' => 1503410614, // 08/22/2017 @ 2:03pm (UTC)
            ];
        }

        return ['data' => $data];
    }

    protected function generateEditData($device = null)
    {
        $data = $this->generateNewData();

        if (is_null($device)) {
            $order = Order::first();
        } else {
            /** @noinspection PhpUndefinedFieldInspection */
            $order = $device->team->business->orders()->first();
        }

        if (is_null($order)) {
            throw new \Exception('This test requires test data. Run the seeder.');
        }

        array_set($data, 'data.uuid', $order->uuid);

        return $data;
    }

    protected function createOtherOrder()
    {
        $business = Business::first();
        $existingOrder = factory(Order::class, 'withCustomer')->make();
        $existingOrder->business()->associate($business);
        $existingOrder->save();

        return $existingOrder;
    }

    public function testNewWorksWithValidData()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);

        $data = $this->generateNewData();
        $response = $this->queryAPI('api.orders.new', $data);
        $response->assertJson([
            'status' => 'ok'
        ]);
    }

    public function testNewPushesPreCalcRegisterTransactionsJob()
    {
        Queue::fake();

        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);

        $data = $this->generateNewData();
        $this->queryAPI('api.orders.new', $data);

        Queue::assertPushed(PreCalcRegisterTransactions::class, function ($job) use ($device) {
            return $job->getRegister()->id === $device->currentRegister->id;
        });
    }

    public function testNewWorksWithMissingOptionalAttributes()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);

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

    public function testValidateNewReturnsErrorWithInvalidUUID()
    {
        $existingOrder = $this->createOtherOrder();

        $data = $this->generateNewData();
        $values = [null, 123, '', ' ', $existingOrder->uuid];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.uuid',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidNote()
    {
        $data = $this->generateNewData();
        $values = [123];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.note',
            $values,
            false
        );
    }

    public function testValidateNewReturnsErrorWithMissingCustomer()
    {
        $data = $this->generateNewData();
        $values = [null, false, 'test'];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.customer',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidCustomerFields()
    {
        // test customer.fieldValues
        $data = $this->generateNewData();
        $values = [null, 1, []];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.customer.fieldValues',
            $values
        );

        // test customer.fieldValues.*.field
        $data = $this->generateNewData();
        $values = [null, false, 0];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.customer.fieldValues.0.fieldId',
            $values
        );

        // test customer.fieldValues.*.value
        $data = $this->generateNewData();
        $values = [];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.customer.fieldValues.0.value',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidCredits()
    {
        $data = $this->generateNewData();
        $values = [null, 1];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.credits',
            $values,
            false
        );
    }

    public function testValidateNewReturnsErrorWithInvalidCreditsDataUUID()
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
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.credits.1.uuid',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidCreditsDataNote()
    {
        $data = $this->generateNewData();
        $values = [null, 1, '', ' '];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.credits.1.note',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidCreditsDataAmount()
    {
        $data = $this->generateNewData();
        $values = [null, 0, 'test', -5];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.credits.1.amount',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidTransactions()
    {
        $data = $this->generateNewData();
        $values = [null, 1];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.transactions',
            $values,
            false
        );
    }

    public function testValidateNewReturnsErrorWithInvalidTransactionsDataUUID()
    {
        // TODO: test for existing id
        $data = $this->generateNewData();
        $values = [null, 1];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.transactions.1.uuid',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidTransactionsDataAmount()
    {
        $data = $this->generateNewData();
        $values = [null, 0, 'test'];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.transactions.1.amount',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidTransactionsDataTransactionMode()
    {
        $data = $this->generateNewData();
        $values = [null, 0, 'test'];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.transactions.1.transactionModeId',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidItems()
    {
        $data = $this->generateNewData();
        $values = [null, 1];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.items',
            $values,
            false
        );
    }

    public function testValidateNewReturnsErrorWithInvalidItemsDataQuantity()
    {
        $data = $this->generateNewData();
        $values = [null, 0, 'test'];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.items.1.quantity',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidItemsDataUUID()
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
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.items.1.uuid',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidItemsDataProduct()
    {
        $data = $this->generateNewData();
        $values = [null, 0, 'test'];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.items.1.product',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidItemsDataProductName()
    {
        $data = $this->generateNewData();
        $values = [null, 0];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.items.1.product.name',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidItemsDataProductPrice()
    {
        $data = $this->generateNewData();
        $values = [null, -5, 'test'];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.items.1.product.price',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidItemsDataProductID()
    {
        $data = $this->generateNewData();
        $values = [-1, 'test'];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.items.1.product.productId',
            $values,
            false
        );
    }

    public function testValidateNewReturnsErrorWithInvalidItemsDataTaxes()
    {
        $data = $this->generateNewData();
        $values = [1, 'test'];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.items.1.product.taxes',
            $values,
            false
        );
    }

    public function testValidateNewReturnsErrorWithInvalidItemsDataTaxesId()
    {
        $data = $this->generateNewData();
        $values = [null, -1, 'test'];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.items.1.product.taxes.1.taxId',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidItemsDataTaxesAmount()
    {
        $data = $this->generateNewData();
        $values = [null, -1, 0, 'test'];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.items.1.product.taxes.1.amount',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidRoomSelections()
    {
        $data = $this->generateNewData();
        $values = [null, 1];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.roomSelections',
            $values,
            false
        );
    }

    public function testValidateNewReturnsErrorWithInvalidRoomSelectionsDataUUID()
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
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.roomSelections.1.uuid',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidRoomSelectionsDataStartDate()
    {
        $data = $this->generateNewData();
        $values = [null, 'test', -1];
        $this->assertValidatesRequestData(
            [$this->controller, 'validateNew'],
            $data,
            'data.roomSelections.1.startDate',
            $values
        );
    }

    public function testValidateNewReturnsErrorWithInvalidRoomSelectionsDataEndDate()
    {
        $data = $this->generateNewData();
        $values = [null, 'test', -2];
        $this->assertValidatesRequestData([$this->controller, 'validateNew'], $data, 'data.roomSelections.1.endDate', $values);
    }

    public function testValidateNewReturnsErrorWithInvalidRoomSelectionsDataStartDateBeforeEnd()
    {
        $data = $this->generateNewData();
        $start = array_get($data, 'data.roomSelections.1.startDate');
        $end = array_get($data, 'data.roomSelections.1.endDate');

        array_set($data, 'data.roomSelections.1.endDate', $start);
        array_set($data, 'data.roomSelections.1.startDate', $end);

        $request = $this->mockRequest($data);

        $this->expectException(ValidationException::class);
        $this->controller->validateNew($request);
    }

    public function testValidateNewReturnsErrorWithInvalidRoomSelectionsDataRoomId()
    {
        $data = $this->generateNewData();
        $values = [null, 'test', 0];
        $this->assertValidatesRequestData([$this->controller, 'validateNew'], $data, 'data.roomSelections.1.roomId', $values);
    }

    public function testValidateNewReturnsErrorWithInvalidRoomSelectionFields()
    {
        // test roomSelections.*.fieldValues
        $data = $this->generateNewData();
        $values = [null, 1, []];
        $this->assertValidatesRequestData([$this->controller, 'validateNew'], $data, 'data.roomSelections.1.fieldValues', $values);

        // test roomSelections.*.fieldValues.*.field
        $data = $this->generateNewData();
        $values = [null, false, 0];
        $this->assertValidatesRequestData([$this->controller, 'validateNew'], $data, 'data.roomSelections.1.fieldValues.0.fieldId', $values);

        // test customer.fieldValues.*.value
        $data = $this->generateNewData();
        $values = [];
        $this->assertValidatesRequestData([$this->controller, 'validateNew'], $data, 'data.roomSelections.1.fieldValues.0.value', $values);
    }

    // --------------------

    public function testNewFailsIfTransactionsWithClosedRegister()
    {
        $data = $this->generateNewData();
        $device = $this->createDevice();
        $this->logDevice($device);
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
        $business = $device->team->business;
        $this->logDevice($device);
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
        $business = $device->team->business;
        $this->logDevice($device);
        $order = $this->controller->createOrder($data['data'], $business, $device->currentRegister);

        $expected = count(array_get($data, 'data.customer.fieldValues'));
        $this->assertEquals($expected, $order->customer->fieldValues->count());
    }

    public function testCreateOrderCreatesCredits()
    {
        $data = $this->generateNewData();
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->team->business;
        $this->logDevice($device);
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
        $business = $device->team->business;
        $this->logDevice($device);
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
        $this->assertEquals(array_get($data, 'data.items.1.product.productId'), $product->product_id);

        // Check productId null for last item (custom item)
        $this->assertNull($order->items[2]->product->productId);
    }

    public function testCreateOrderCreatesItemsProductTaxes()
    {
        $data = $this->generateNewData();
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->team->business;
        $this->logDevice($device);
        $order = $this->controller->createOrder($data['data'], $business, $device->currentRegister);

        $product = $order->items[1]->product;
        $taxes = $product->taxes;
        $testId = array_get($data, 'data.items.1.product.taxes.1.taxId');
        $testTax = $taxes->first(function ($tax) use ($testId) {
            return $tax['taxId'] === $testId;
        });
        $this->assertEquals(count(array_get($data, 'data.items.1.product.taxes')), $taxes->count());
        $this->assertEquals(array_get($data, 'data.items.1.product.taxes.1.amount'), $testTax['amount']);
    }

    public function testCreateOrderCreatesRoomSelections()
    {
        $data = $this->generateNewData();
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->team->business;
        $this->logDevice($device);
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
        $this->assertEquals(array_get($data, 'data.roomSelections.1.roomId'), $roomSelection->room->id);

        // Field values
        $count = count(array_get($data, 'data.roomSelections.1.fieldValues'));
        $this->assertEquals($count, $roomSelection->fieldValues->count());
    }

    public function testCreateOrderCreatesTransactions()
    {
        $data = $this->generateNewData();
        $device = $this->createDeviceWithOpenedRegister();
        $business = $device->team->business;
        $this->logDevice($device);
        $order = $this->controller->createOrder($data['data'], $business, $device->currentRegister);

        $count = count(array_get($data, 'data.transactions'));
        $this->assertEquals($count, $order->transactions->count());
        $transaction = $order->transactions[1];
        $this->assertEquals(array_get($data, 'data.transactions.1.uuid'), $transaction->uuid);
        $this->assertEquals(array_get($data, 'data.transactions.1.amount'), $transaction->amount);
        $this->assertEquals(array_get($data, 'data.transactions.1.transactionModeId'), $transaction->transactionMode->id);
        $this->assertEquals($device->currentRegister->id, $transaction->register->id);
    }

    // -------------------------

    public function testNewBumpsBusinessVersionWithModifications()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);
        $data = $this->generateNewData();
        $business = $device->team->business;
        $oldVersion = $business->version;

        $this->queryAPI('api.orders.new', $data);

        $newVersion = $business->version;
        $this->assertNotEquals($oldVersion, $newVersion);
        $this->assertEquals(
            [Business::MODIFICATION_ORDERS],
            $business->getVersionModifications($newVersion)
        );
    }

    ////////////////////////////////////////
    // ====================================
    ////////////////////////////////////////

    public function testEditWorksWithValidData()
    {
        $device = Team::first()->devices()->first();
        $this->logDevice($device);

        $data = $this->generateEditData();
        $response = $this->queryAPI('api.orders.edit', $data, $device->team);
        $response->assertJson([
            'status' => 'ok'
        ]);
    }

    public function testEditPushesPreCalcRegisterTransactionsJob()
    {
        Queue::fake();

        $device = Team::first()->devices()->first();
        $this->logDevice($device);

        $data = $this->generateEditData();
        $this->queryAPI('api.orders.edit', $data, $device->team);

        Queue::assertPushed(PreCalcRegisterTransactions::class, function ($job) use ($device) {
            return $job->getRegister()->id === $device->currentRegister->id;
        });
    }

    public function testEditWorksWithMissingOptionalAttributes()
    {
        $device = Team::first()->devices()->first();
        $this->logDevice($device);

        $data = $this->generateEditData($device);

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

    public function testValidateEditReturnsErrorWithInvalidUUID()
    {
        $device = $this->createDeviceWithOpenedRegister();
        $this->logDevice($device);

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

    public function testValidateEditReturnsErrorWithInvalidNote()
    {
        $data = $this->generateEditData();
        $values = [123];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.note', $values, false);
    }

    public function testValidateEditReturnsErrorWithInvalidCustomer()
    {
        $data = $this->generateEditData();
        $values = [null, false, 'test'];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.customer', $values, false);
    }

    public function testValidateEditReturnsErrorWithInvalidCustomerFields()
    {
        // test customer.fieldValues
        $data = $this->generateEditData();
        $values = [null, 1, []];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.customer.fieldValues', $values);

        // test customer.fieldValues.*.field
        $data = $this->generateEditData();
        $values = [null, false, 0];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.customer.fieldValues.0.fieldId', $values);

        // test customer.fieldValues.*.value
        $data = $this->generateEditData();
        $values = [];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.customer.fieldValues.0.value', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidCredits()
    {
        $data = $this->generateEditData();
        $values = [null, 1];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.credits', $values, false);
    }

    public function testValidateEditReturnsErrorWithInvalidCreditsDataUUID()
    {
        $data = $this->generateEditData();
        $values = [null, 1];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.credits.1.uuid', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidCreditsDataNote()
    {
        $data = $this->generateEditData();
        $values = [null, 1, '', ' '];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.credits.1.note', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidCreditsDataAmount()
    {
        $data = $this->generateEditData();
        $values = [null, 0, 'test', -5];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.credits.1.amount', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidTransactions()
    {
        $data = $this->generateEditData();
        $values = [null, 1];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.transactions', $values, false);
    }

    public function testValidateEditReturnsErrorWithInvalidTransactionsDataUUID()
    {
        // TODO: test for existing id
        $data = $this->generateEditData();
        $values = [null, 1];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.transactions.1.uuid', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidTransactionsDataAmount()
    {
        $data = $this->generateEditData();
        $values = [null, 0, 'test'];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.transactions.1.amount', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidTransactionsDataTransactionMode()
    {
        $data = $this->generateEditData();
        $values = [null, 0, 'test'];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.transactions.1.transactionModeId', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidItems()
    {
        $data = $this->generateEditData();
        $values = [null, 1];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.items', $values, false);
    }

    public function testValidateEditReturnsErrorWithInvalidItemsDataQuantity()
    {
        $data = $this->generateEditData();
        $values = [null, 0, 'test'];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.items.1.quantity', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidItemsDataUUID()
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
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.items.1.uuid', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidItemsDataProduct()
    {
        $data = $this->generateEditData();
        $values = [null, 0, 'test'];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.items.1.product', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidItemsDataProductName()
    {
        $data = $this->generateEditData();
        $values = [null, 0];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.items.1.product.name', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidItemsDataProductPrice()
    {
        $data = $this->generateEditData();
        $values = [null, -5, 'test'];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.items.1.product.price', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidItemsDataProductID()
    {
        $data = $this->generateEditData();
        $values = [-1, 'test'];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.items.1.product.productId', $values, false);
    }

    public function testValidateEditReturnsErrorWithInvalidItemsDataTaxes()
    {
        $data = $this->generateEditData();
        $values = [1, 'test'];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.items.1.product.taxes', $values, false);
    }

    public function testValidateEditReturnsErrorWithInvalidItemsDataTaxesId()
    {
        $data = $this->generateEditData();
        $values = [null, -1, 'test'];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.items.1.product.taxes.1.taxId', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidItemsDataTaxesAmount()
    {
        $data = $this->generateEditData();
        $values = [null, -1, 0, 'test'];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.items.1.product.taxes.1.amount', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidRoomSelections()
    {
        $data = $this->generateEditData();
        $values = [null, 1];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.roomSelections', $values, false);
    }

    public function testValidateEditReturnsErrorWithInvalidRoomSelectionsDataUUID()
    {
        $data = $this->generateEditData();

        $values = [null, 1];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.roomSelections.1.uuid', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidRoomSelectionsDataStartDate()
    {
        $data = $this->generateEditData();
        $values = [null, 'test', -1];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.roomSelections.1.startDate', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidRoomSelectionsDataEndDate()
    {
        $data = $this->generateEditData();
        $values = [null, 'test', -2];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.roomSelections.1.endDate', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidRoomSelectionsDataStartDateBeforeEnd()
    {
        $data = $this->generateEditData();
        $start = array_get($data, 'data.roomSelections.1.startDate');
        $end = array_get($data, 'data.roomSelections.1.endDate');

        array_set($data, 'data.roomSelections.1.endDate', $start);
        array_set($data, 'data.roomSelections.1.startDate', $end);

        $request = $this->mockRequest($data);

        $this->expectException(ValidationException::class);
        $this->controller->validateEdit($request);
    }

    public function testValidateEditReturnsErrorWithInvalidRoomSelectionsDataRoomId()
    {
        $data = $this->generateEditData();
        $values = [null, 'test', 0];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.roomSelections.1.roomId', $values);
    }

    public function testValidateEditReturnsErrorWithInvalidRoomSelectionFields()
    {
        // test roomSelections.*.fieldValues
        $data = $this->generateEditData();
        $values = [null, 1, []];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.roomSelections.1.fieldValues', $values);

        // test roomSelections.*.fieldValues.*.field
        $data = $this->generateEditData();
        $values = [null, false, 0];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.roomSelections.1.fieldValues.0.fieldId', $values);

        // test customer.fieldValues.*.value
        $data = $this->generateEditData();
        $values = [];
        $this->assertValidatesRequestData([$this->controller, 'validateEdit'], $data, 'data.roomSelections.1.fieldValues.0.value', $values);
    }

    // --------------------

    public function testEditFailsIfTransactionsWithClosedRegister()
    {
        $baseData = $this->generateEditData();
        $device = $this->createDevice();
        $this->logDevice($device);
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
        $device = Team::first()->devices()->first();
        $baseData = $this->generateEditData($device);
        $this->logDevice($device);
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
        $editedElement['value'] = is_string($editedElement['value'])
            ? $editedElement['value'] . 'new'
            : $editedElement['value'] + 1;
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
                'roomId' => $roomSelection->room_id,
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
            'roomId' => $new[0]['roomId'],
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
        $business = Business::first();

        return [
            'data' => [
                'quantity' => 4,
                'from' => $business->orders()->first()->uuid,
            ],
        ];
    }

    public function testValidateList()
    {
        $device = Business::first()->team->devices()->first();
        $this->logDevice($device);
        $data = $this->generateListData();

        // Quantity
        $max = config('api.orders.list.quantity.max');
        $values = [-1, 0, '', ' ', null, $max + 1];
        $this->assertValidatesRequestData([$this->controller, 'validateList'], $data, 'data.quantity', $values);

        // from (UUID)
        // Create a Order that will be saved, but that is assigned to another $team (so should fail validation, thus
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
        $this->logDevice($device);
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
        $business = Business::first();
        $ordersResult = collect([
            'test' => true,
        ]);

        $device = $this->createDevice();
        $device->team->business()->associate($business);
        $device->team->save();
        $this->logDevice($device);
        $quantity = 4;
        $from = $business->orders()->first();

        $request = $this->mockRequest(['data' => ['quantity' => $quantity, 'from' => $from->uuid]]);
        $controller = m::mock(OrdersController::class);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $controller->shouldReceive('validateList')->andReturnNull();
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $controller->shouldReceive('getOrders')
            ->withArgs([$device->team->business, $quantity, m::on(function ($arg) use ($from) {
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
        $this->logDevice($device);
        $quantity = 1;
        $orders = $this->controller->getOrders($device->team->business, $quantity);
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

        $customer = \factory(Customer::class, 'withBusiness')->create();
        $business = $customer->business;

        $otherOrder = null;

        foreach ($orders as $index => $orderData) {
            $order = new Order($orderData);
            $order->created_at = $orderData['created_at'];
            $order->business()->associate($business);
            $order->customer()->associate($customer);
            $order->save();

            if ($index === count($orders) - 1) {
                $otherOrder = $order;
            }
        }

        // Move last Order to another team
        $otherCustomer = \factory(Customer::class, 'withBusiness')->create();
        $otherOrder->customer()->associate($otherCustomer);
        $otherOrder->business()->associate($otherCustomer->business);
        $otherOrder->save();

        // No $from specified
        $res = $this->controller->getOrders($business, 2);
        $uuids = $res->pluck('uuid')->toArray();
        $this->assertEquals(['test-5', 'test-4'], $uuids);

        // With $from specified
        $from = Order::where('uuid', 'test-2')->first();
        $res = $this->controller->getOrders($business, 2, $from);
        $uuids = $res->pluck('uuid')->toArray();
        $this->assertEquals(['test-4', 'test-3'], $uuids);

        // With $quantity larger than available
        $res = $this->controller->getOrders($business, 10, $from);
        $uuids = $res->pluck('uuid')->toArray();
        $this->assertEquals(['test-5', 'test-4', 'test-3'], $uuids);

        // Ask the last one of the team
        $from = Order::where('uuid', 'test-5')->first();
        $res = $this->controller->getOrders($business, 5, $from);
        $this->assertEmpty($res);

        // Throws error if $from is not of the same team
        try {
            $this->controller->getOrders($business, 5, $otherOrder);
            $this->fail('Did not throw exception with $from an Order for another Business');
        } catch (CrossBusinessAccessException $e) {
            // Do nothing
        }
    }

    public function testGetOrdersLoadsAllRelations()
    {
        $business = Business::first();
        $orders = $this->controller->getOrders($business, 3);

        $order = $orders->first();

        $this->assertTrue($order->relationLoaded('items'));
        $this->assertTrue($order->relationLoaded('transactions'));
        $this->assertTrue($order->relationLoaded('customer'));
        $this->assertTrue($order->relationLoaded('credits'));
        $this->assertTrue($order->relationLoaded('roomSelections'));

        // Check that sub-relations are loaded
        $this->assertTrue($order->items->first()->relationLoaded('product'));
    }
}
