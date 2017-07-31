<?php

namespace App\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Credit;
use App\Customer;
use App\Exceptions\Api\InvalidRequestException;
use App\Item;
use App\ItemProduct;
use App\Order;
use App\Register;
use App\RoomSelection;
use App\Support\Facades\ApiAuth;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdersController extends ApiController
{
    /**
     * Controller method for /api/orders/new (see docs/api.md)
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Api\Http\ApiResponse
     */
    public function new(Request $request)
    {
        $data = $this->getRequestData($request);

        $rules = $this->generateValidationRules($data);
        $this->validate($request, $rules);
        $this->validateRequiredRegisterStatus($data);

        $device = ApiAuth::getDevice();
        $this->createOrder($data, $device->business, $device->currentRegister);

        return new ApiResponse();
    }

    /**
     * Validates that the currentRegister of the authenticated Device is opened if we have transactions in the request.
     * If not, throws an exception.
     *
     * @param array $data
     *
     * @throws \App\Exceptions\Api\InvalidRequestException
     */
    protected function validateRequiredRegisterStatus($data)
    {
        if (count(array_get($data, 'transactions', []))) {
            $device = ApiAuth::getDevice();
            if (!$device->isCurrentRegisterOpened) {
                $message = 'The register of the device must be opened to accept new transactions.';
                throw new InvalidRequestException($message);
            }
        }
    }

    /**
     * Returns the validation rules for the request's data. Some rules are conditionally made for some attributes, so we
     * need the Request.
     *
     * @param array $data
     * @return array
     */
    protected function generateValidationRules($data)
    {
        $rules = [
            'uuid' => 'bail|required|string|unique:orders',
            'note' => 'sometimes|string',
            'customer' => 'bail|required|array|min:1',
            'customer.fieldValues' => 'bail|required|array|min:1',
            'customer.fieldValues.*.field' => 'bail|required|exists:fields,id',
            'customer.fieldValues.*.value' => 'bail|required|string',
            'credits' => 'sometimes|array',
            'credits.*.uuid' => 'bail|required|string|unique:credits',
            'credits.*.note' => 'bail|required|string',
            'credits.*.amount' => 'bail|required|numeric|not_in:0',
            'transactions' => 'sometimes|array',
            'transactions.*.uuid' => 'bail|required|string|unique:transactions',
            'transactions.*.amount' => 'bail|required|numeric|not_in:0',
            'transactions.*.transactionMode' => 'bail|required|exists:transaction_modes,id',
            'items' => 'sometimes|array',
            'items.*.uuid' => 'bail|required|string|unique:items',
            'items.*.quantity' => 'bail|required|numeric|not_in:0',
            'items.*.product' => 'bail|required|array',
            'items.*.product.name' => 'bail|required|string',
            'items.*.product.price' => 'bail|required|numeric|min:0',
            'items.*.product.product_id' => 'sometimes|nullable|exists:products,id',
            'items.*.product.taxes' => 'sometimes|array',
            'items.*.product.taxes.*.tax_id' => 'bail|required|exists:taxes,id',
            'items.*.product.taxes.*.amount' => 'bail|required|numeric|min:0|not_in:0',
            'roomSelections' => 'sometimes|array',
            'roomSelections.*.uuid' => 'bail|required|string|unique:room_selections',
            'roomSelections.*.startDate' => 'bail|required|integer|min:0',
            // 'roomSelections.*.endDate' => // see special validation below
            'roomSelections.*.room' => 'bail|required|exists:rooms,id',
            'roomSelections.*.fieldValues' => 'bail|required|array|min:1',
            'roomSelections.*.fieldValues.*.field' => 'bail|required|exists:fields,id',
            'roomSelections.*.fieldValues.*.value' => 'bail|required|string',
        ];

        // Conditional validation for roomSelections.*.endDate to be 1 day after startDate
        $roomSelections = array_get($data, 'roomSelections', []);

        if (is_array($roomSelections)) {
            foreach ($roomSelections as $index => $roomSelection) {
                $startDate = array_key_exists('startDate', $roomSelection) ? $roomSelection['startDate'] : 0;
                $startDate = is_numeric($startDate) ? $startDate : 0;
                $dayAfter = $startDate + (24 * 60 * 60);

                $rules["roomSelections.$index.endDate"] = 'bail|required|integer|min:0' . $dayAfter;
            }
        }

        return $rules;
    }

    /**
     * Creates a new Order, fills it with the data supplied, saves it and returns it.
     *
     * @param array $data
     * @param \App\Business $business
     * @param \App\Register $register
     *
     * @return \App\Order
     */
    public function createOrder($data, Business $business, Register $register)
    {
        $order = new Order();

        // Since we will run a lot of INSERT queries, we prefer to put them all in a transaction so the DB is not
        // polluted if one query fails.
        DB::transaction(function () use (&$order, $data, $business, $register) {
            $order->uuid = array_get($data, 'uuid');
            $order->note = array_get($data, 'note', null);
            $order->business()->associate($business);

            $customer = $this->createCustomer($data, $business);
            $order->customer()->associate($customer);
            $order->save();

            $this->addOrderCredits($order, $data);
            $this->addOrderItems($order, $data);
            $this->addOrderRoomSelections($order, $data);
            $this->addOrderTransactions($order, $data, $register);
        });

        return $order;
    }

    /**
     * Creates a new Customer from the data, saves it and returns it.
     *
     * @param array $data
     * @param \App\Business $business
     *
     * @return \App\Customer
     */
    protected function createCustomer($data, Business $business)
    {
        $customer = new Customer();
        $customer->business()->associate($business);
        $customer->save();

        $customer->setFieldValues(array_get($data, 'customer.fieldValues', []), false);

        return $customer;
    }

    /**
     * Creates the Credits found in $data and adds them to $order.
     *
     * @param \App\Order $order
     * @param array $data
     */
    protected function addOrderCredits(Order $order, $data)
    {
        $credits = array_get($data, 'credits', []);

        foreach ($credits as $creditData) {
            $credit = new Credit($creditData);
            $credit->order()->associate($order);
            $credit->save();
        }
    }

    /**
     * Creates the Items found in $data and adds them to $order. If an Item has a custom Product, we create it.
     *
     * @param \App\Order $order
     * @param array $data
     */
    protected function addOrderItems(Order $order, $data)
    {
        $items = array_get($data, 'items', []);

        foreach ($items as $itemData) {
            $item = new Item($itemData);
            $item->order()->associate($order);
            $item->save();

            $productData = array_get($itemData, 'product');
            $itemProduct = new ItemProduct($productData);
            $itemProduct->item()->associate($item);
            $itemProduct->save();

            $taxes = array_get($productData, 'taxes', []);

            if (count($taxes)) {
                $itemProduct->setTaxes($taxes);
            }
        }
    }

    /**
     * Creates the RoomSelections found in $data and adds them to $order.
     *
     * @param \App\Order $order
     * @param array $data
     */
    protected function addOrderRoomSelections(Order $order, $data)
    {
        $roomSelections = array_get($data, 'roomSelections', []);

        foreach ($roomSelections as $roomSelectionData) {
            $roomSelection = new RoomSelection();
            $roomSelection->uuid = $roomSelectionData['uuid'];
            $roomSelection->start_date = Carbon::createFromTimestamp($roomSelectionData['startDate']);
            $roomSelection->end_date = Carbon::createFromTimestamp($roomSelectionData['endDate']);
            $roomSelection->room_id = $roomSelectionData['room'];
            $roomSelection->order()->associate($order);
            $roomSelection->save();

            $roomSelection->setFieldValues(array_get($roomSelectionData, 'fieldValues', []), false);
        }
    }

    /**
     * Creates the Transaction found in $data, associates them with the Register and adds them to $order.
     *
     * @param \App\Order $order
     * @param array $data
     * @param \App\Register $register
     */
    protected function addOrderTransactions(Order $order, $data, Register $register)
    {
        $transactions = array_get($data, 'transactions', []);

        foreach ($transactions as $transactionData) {
            $transaction = new Transaction($transactionData);
            $transaction->transaction_mode_id = $transactionData['transactionMode'];
            $transaction->register()->associate($register);
            $transaction->order()->associate($order);
            $transaction->save();
        }
    }
}
