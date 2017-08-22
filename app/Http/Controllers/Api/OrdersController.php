<?php

namespace App\Http\Controllers\Api;

use App\Api\Http\ApiResponse;
use App\Business;
use App\Credit;
use App\Customer;
use App\Exceptions\CrossBusinessAccessException;
use App\Item;
use App\ItemProduct;
use App\Order;
use App\Register;
use App\RoomSelection;
use App\Support\Facades\ApiAuth;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $this->validateNew($request);
        $data = $this->getRequestData($request);
        $this->validateRequiredRegisterStatus($data);

        $device = ApiAuth::getDevice();
        $business = ApiAuth::getBusiness();
        $this->createOrder($data, $business, $device->currentRegister);

        // Bump the version of Business
        $business->bumpVersion([Business::MODIFICATION_ORDERS]);

        return new ApiResponse();
    }

    /**
     * Controller method for /api/orders/edit(see docs/api.md)
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Api\Http\ApiResponse
     */
    public function edit(Request $request)
    {
        $this->validateEdit($request);
        $data = $this->getRequestData($request);
        $this->validateRequiredRegisterStatus($data);

        $device = ApiAuth::getDevice();
        $business = ApiAuth::getBusiness();
        $order = $business->orders()->where('uuid', array_get($data, 'uuid'))->firstOrFail();
        $this->updateOrder($order, $data, $device->currentRegister);

        return new ApiResponse();
    }

    /**
     * Controller method for /api/orders/list method (see docs/api.md)
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Api\Http\ApiResponse
     */
    public function list(Request $request)
    {
        $this->validateList($request);

        $business = ApiAuth::getBusiness();
        $quantity = $request->json('data.quantity');
        $uuid = $request->json('data.from', null);
        $from = null;

        if (!is_null($uuid)) {
            $from = $business->orders()->where('uuid', $uuid)->first();
        }

        $orders = $this->getOrders($business, $quantity, $from);

        $response = new ApiResponse();
        $response->setResponseData($orders);

        return $response;
    }

    /**
     * Gets the latest $quantity Orders of the $business. If $from is passed, Order are following $from. In the
     * returning Collection, the most recent Order is first. All relations of each Order are also loaded.
     *
     * @param \App\Business $business
     * @param integer $quantity
     * @param \App\Order|null $from
     *
     * @return Collection
     * @throws \App\Exceptions\CrossBusinessAccessException
     */
    public function getOrders(Business $business, $quantity, Order $from = null)
    {
        // Make sure $from has the same business as $business
        if (!is_null($from)) {
            if ($from->business_id !== $business->id) {
                throw new CrossBusinessAccessException('$from Order is not in $business.');
            }
        }

        $query = $business->orders()->with(Order::RELATIONS)->take($quantity);

        if (is_null($from)) {
            $query->orderBy('created_at', 'DESC');
        } else {
            $query->from($from)->orderBy('created_at', 'ASC');
        }

        $results = $query->get();

        if (!is_null($from)) {
            return $results->reverse();
        }

        return $results;
    }

    /**
     * Validates the parameters for the 'new' controller method. Throws exception in case of validation error.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @param \Illuminate\Http\Request $request
     */
    public function validateNew(Request $request)
    {
        $data = $this->getRequestData($request);
        $rules = $this->generateMutationValidationRules($data, 'new');
        $this->validate($request, $rules);
    }

    /**
     * Validates the parameters for the 'edit' controller method. Throws exception in case of validation error.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @param \Illuminate\Http\Request $request
     */
    public function validateEdit(Request $request)
    {
        $data = $this->getRequestData($request);
        $rules = $this->generateMutationValidationRules($data, 'edit');
        $this->validate($request, $rules);
    }

    /**
     * Validates the parameters for the 'list' controller method. Throws exception in case of validation error.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @param \Illuminate\Http\Request $request
     */
    public function validateList(Request $request)
    {
        $business = ApiAuth::getBusiness();
        $quantityMax = config('api.orders.list.quantity.max', 30);
        $rules = [
            'quantity' => 'required|numeric|min:0|max:' . $quantityMax . '|not_in:0',
            'from' => 'sometimes|string|filled|exists:orders,uuid,business_id,' . $business->id,
        ];

        $this->validate($request, $rules);
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
            $this->validateRegisterOpened();
        }
    }

    /**
     * Returns the validation rules when mutating an Order (creating or editing an Order). Rules can be generated for a
     * new Order or an existing Order with the $newOrEdit parameter. Some rules are conditionally made for some
     * attributes, so we need the Request.
     *
     * @param array $data
     * @param string $newOrEdit Must be 'new' or 'edit'
     * @return array
     */
    protected function generateMutationValidationRules($data, $newOrEdit)
    {
        $isNew = $newOrEdit === 'new';

        $rules = [
            'uuid' => 'bail|required|string' . ($isNew ? '|unique:orders' : '|exists:orders,uuid'),
            'note' => 'sometimes|string',
            'customer' => ($isNew ? 'bail|required' : 'sometimes') . '|array|min:1', // min:1 required
            'customer.fieldValues' => 'bail|required_with:customer|array|min:1',
            'customer.fieldValues.*.fieldId' => 'bail|required|exists:fields,id',
            'customer.fieldValues.*.value' => 'bail|required|string',
            'credits' => 'sometimes|array',
            'credits.*.uuid' => 'bail|required|string' . ($isNew ? '|unique:credits' : ''),
            'credits.*.note' => 'bail|required|string',
            'credits.*.amount' => 'bail|required|numeric|min:0|not_in:0',
            'credits.*.createdAt' => 'sometimes|required|numeric|min:0|not_in:0',
            'transactions' => 'sometimes|array',
            'transactions.*.uuid' => 'bail|required|string|unique:transactions',
            'transactions.*.amount' => 'bail|required|numeric|not_in:0',
            'transactions.*.createdAt' => 'sometimes|required|numeric|min:0|not_in:0',
            'transactions.*.transactionModeId' => 'bail|required|exists:transaction_modes,id',
            'items' => 'sometimes|array',
            'items.*.uuid' => 'bail|required|string|unique:items',
            'items.*.quantity' => 'bail|required|numeric|not_in:0',
            'items.*.createdAt' => 'sometimes|required|numeric|min:0|not_in:0',
            'items.*.product' => 'bail|required|array',
            'items.*.product.name' => 'bail|required|string',
            'items.*.product.price' => 'bail|required|numeric|min:0',
            'items.*.product.productId' => 'sometimes|nullable|exists:products,id',
            'items.*.product.taxes' => 'sometimes|array',
            'items.*.product.taxes.*.id' => 'bail|required|exists:taxes,id',
            'items.*.product.taxes.*.amount' => 'bail|required|numeric|min:0|not_in:0',
            'roomSelections' => 'sometimes|array',
            'roomSelections.*.uuid' => 'bail|required|string' . ($isNew ? '|unique:room_selections' : ''),
            'roomSelections.*.startDate' => 'bail|required|integer|min:0',
            // 'roomSelections.*.endDate' => // see special validation below
            'roomSelections.*.roomId' => 'bail|required|exists:rooms,id',
            'roomSelections.*.fieldValues' => 'bail|required|array|min:1',
            'roomSelections.*.fieldValues.*.fieldId' => 'bail|required|exists:fields,id',
            'roomSelections.*.fieldValues.*.value' => 'bail|required|string',
        ];

        if ($isNew) {
            $rules['createdAt'] = 'sometimes|required|numeric|min:0|not_in:0';
        }

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
            $createdAt = array_get($data, 'createdAt', false);
            if ($createdAt && $createdAt <= Carbon::now()->getTimestamp()) {
                $order->created_at = Carbon::createFromTimestamp($createdAt);
            }
            $order->save();

            $this->addOrderCredits($order, $data);
            $this->addOrderItems($order, $data);
            $this->addOrderRoomSelections($order, $data);
            $this->addOrderTransactions($order, $data, $register);
        });

        return $order;
    }

    /**
     * Updates the Order with the supplied $data array. $register is required only if the $data contains transactions,
     * else it can be null.
     *
     * @param \App\Order $order
     * @param $data
     * @param \App\Register|null $register
     *
     * @return \App\Order
     */
    public function updateOrder(Order $order, $data, Register $register = null)
    {
        $order->refresh();
        $this->updateOrderNote($order, $data);
        $this->updateOrderCustomer($order, $data);
        $this->updateOrderCredits($order, $data);
        $this->updateOrderItems($order, $data);
        $this->updateOrderRoomSelections($order, $data);

        if (!is_null($register)) {
            $this->updateOrderTransactions($order, $data, $register);
        }

        $order->save();

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
            $createdAt = array_get($creditData, 'createdAt', false);
            if ($createdAt && $createdAt <= Carbon::now()->getTimestamp()) {
                $credit->created_at = Carbon::createFromTimestamp($createdAt);
            }
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

            $productData = array_get($itemData, 'product');
            $itemProduct = new ItemProduct($productData);
            $itemProduct->save();

            $item->product()->associate($itemProduct);
            $createdAt = array_get($itemData, 'createdAt', false);
            if ($createdAt && $createdAt <= Carbon::now()->getTimestamp()) {
                $item->created_at = Carbon::createFromTimestamp($createdAt);
            }
            $item->save();

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
            $roomSelection->room_id = $roomSelectionData['roomId'];
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
            $transaction->transaction_mode_id = $transactionData['transactionModeId'];
            $transaction->register()->associate($register);
            $transaction->order()->associate($order);
            $createdAt = array_get($transactionData, 'createdAt', false);
            if ($createdAt && $createdAt <= Carbon::now()->getTimestamp()) {
                $transaction->created_at = Carbon::createFromTimestamp($createdAt);
            }
            $transaction->save();
        }
    }

    /**
     * Updates the note of the Order (only if $data has `note` attribute).
     *
     * @param \App\Order $order
     * @param $data
     */
    protected function updateOrderNote(Order &$order, $data)
    {
        if (array_has($data, 'note')) {
            $order->note = array_get($data, 'note');
        }
    }

    /**
     * Updates the fieldValues of a Customer (only if $data has `customer.fieldValues` attribute). We can add a new
     * value, modify or delete a value. For this reason, all fieldValues must be present, even if the ones that are not
     * modified, else they will be deleted.
     *
     * @param \App\Order $order
     * @param $data
     */
    protected function updateOrderCustomer(Order $order, $data)
    {
        if (array_has($data, 'customer.fieldValues')) {
            $order->customer->replaceFieldValues(array_get($data, 'customer.fieldValues'));
        }
    }

    /**
     * Updates the Credit list of the Order (only if $data has `credits` attribute). Credits can be created, modified or
     * deleted. For this reason, all Credit must be present in the `credits` attribute of $data, else the missing ones
     * will be deleted.
     *
     * @param \App\Order $order
     * @param $data
     */
    protected function updateOrderCredits(Order $order, $data)
    {
        if (!array_has($data, 'credits')) {
            return;
        }

        $creditsData = array_get($data, 'credits');
        $existingCredits = $order->credits;
        $dataUUIDs = array_pluck($creditsData, 'uuid');
        $existingUUIDs = $existingCredits->pluck('uuid');

        $existingCredits->each(function ($credit) use ($dataUUIDs, $creditsData) {
            // Delete missing
            if (!in_array($credit->uuid, $dataUUIDs)) {
                $credit->delete();
                return;
            }

            // Update existing
            $newData = array_first($creditsData, function ($creditData) use ($credit) {
                return $creditData['uuid'] === $credit->uuid;
            });

            $credit->note = $newData['note'];
            $credit->amount = $newData['amount'];
            $credit->save();
        });

        // Insert new
        $newUUIDs = with(new Collection($dataUUIDs))->diff($existingUUIDs);
        $newCreditsData = array_where($creditsData, function ($creditData) use ($newUUIDs) {
            return $newUUIDs->contains($creditData['uuid']);
        });

        if (count($newCreditsData)) {
            $this->addOrderCredits($order, ['credits' => $newCreditsData]);
        }
    }

    /**
     * Updates the list of Items (only if $data has `items` attribute). Items can only be added. It is not possible to
     * modify or delete existing items (for accounting reasons).
     *
     * @param \App\Order $order
     * @param $data
     */
    protected function updateOrderItems(Order $order, $data)
    {
        if (!array_has($data, 'items')) {
            return;
        }

        $this->addOrderItems($order, $data);
    }

    /**
     * Updates the RoomSelections of the Order (only if $data has `roomSelections` attribute). It is possible to create
     * new, modify or delete RoomSelection. For this reason, the roomSelections attribute must contain all the
     * RoomSelection, even the ones that are not modified (if a RoomSelection is not there, we assume it must be
     * deleted).
     *
     * @param \App\Order $order
     * @param $data
     */
    protected function updateOrderRoomSelections(Order $order, $data)
    {
        if (!array_has($data, 'roomSelections')) {
            return;
        }

        $roomSelectionsData = array_get($data, 'roomSelections');
        $existingRoomSelections = $order->roomSelections;
        $dataUUIDs = array_pluck($roomSelectionsData, 'uuid');
        $existingUUIDs = $existingRoomSelections->pluck('uuid');

        $existingRoomSelections->each(function ($roomSelection) use ($dataUUIDs, $roomSelectionsData) {
            // Delete missing
            if (!in_array($roomSelection->uuid, $dataUUIDs)) {
                $roomSelection->delete();
                return;
            }

            // Update existing
            $newData = array_first($roomSelectionsData, function ($roomSelectionData) use ($roomSelection) {
                return $roomSelectionData['uuid'] === $roomSelection->uuid;
            });

            $roomSelection->start_date = Carbon::createFromTimestamp($newData['startDate']);
            $roomSelection->end_date = Carbon::createFromTimestamp($newData['endDate']);
            $roomSelection->room_id = $newData['roomId'];
            $roomSelection->save();
            $roomSelection->replaceFieldValues($newData['fieldValues']);
        });

        // Insert new
        $newUUIDs = with(new Collection($dataUUIDs))->diff($existingUUIDs);
        $newRoomSelectionsData = array_where($roomSelectionsData, function ($roomSelectionData) use ($newUUIDs) {
            return $newUUIDs->contains($roomSelectionData['uuid']);
        });

        if (count($newRoomSelectionsData)) {
            $this->addOrderRoomSelections($order, ['roomSelections' => $newRoomSelectionsData]);
        }
    }

    /**
     * Updates the Transactions of the Order (only if $data has `transactions` attribute). We can only add new
     * transactions, previously created transactions cannot be modified or deleted (for accounting reasons).
     *
     * @param \App\Order $order
     * @param array $data
     * @param \App\Register $register
     */
    protected function updateOrderTransactions(Order $order, $data, Register $register)
    {
        if (!array_has($data, 'transactions')) {
            return;
        }

        $this->addOrderTransactions($order, $data, $register);
    }
}
