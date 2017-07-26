<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Credit;
use App\Item;
use App\Order;
use App\Product;
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

    protected function setUp()
    {
        parent::setUp();
        $this->business = Business::first();

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

            $productData = [
                'uuid' => $faker->uuid(),
                'quantity' => $isCustom ? -2 : 2,
                'product' => $products->random()->id,
            ];

            if ($isCustom) {
                $productData['product'] = [
                    'uuid' => $faker->uuid,
                    'name' => $faker->word,
                    'price' => $faker->randomFloat(2, 0.1, 10),
                ];
            }

            $data['items'][] = $productData;
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
        $data = $this->generateNewData();
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
        $existingItem->product()->associate(Product::first());
        $existingItem->quantity = 1;
        $existingItem->save();

        $values = [null, 1, $existingItem->uuid];
        $this->assertValidatesData('api.orders.new', $data, 'items.1.uuid', $values);
    }

    public function testNewReturnsErrorWithInvalidItemsDataProductId()
    {
        $data = $this->generateNewData();
        $values = [null, 0, 'test'];
        $this->assertValidatesData('api.orders.new', $data, 'items.1.product', $values);
    }

    public function testNewReturnsErrorWithInvalidItemsDataProductObject()
    {
        $existingUUID = Product::whereNotNull('uuid')->first()->uuid;

        // uuid
        $data = $this->generateNewData();
        $values = [null, 0, $existingUUID];
        $this->assertValidatesData('api.orders.new', $data, 'items.2.product.uuid', $values);

        // name
        $data = $this->generateNewData();
        $values = [null, 2, '', ' '];
        $this->assertValidatesData('api.orders.new', $data, 'items.2.product.name', $values);

        // price
        $data = $this->generateNewData();
        $values = [null, 0, 'x', -1];
        $this->assertValidatesData('api.orders.new', $data, 'items.2.product.price', $values);
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
}
