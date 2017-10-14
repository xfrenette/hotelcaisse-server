<?php

namespace App\Http\Controllers;

use App\Field;
use App\Jobs\PreCalcOrderValues;
use App\Order;
use App\Tax;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    /**
     * Number of items per page in the paginated list screen
     * @type integer
     */
    const LIST_NB_PER_PAGE = 20;

    /**
     * Controller method for the orders.list route
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        $orders = Order
            ::with('calculatedValues')
            ->orderBy('created_at', 'desc')
            ->simplePaginate(self::LIST_NB_PER_PAGE);

        $customerNames = $this->getOrderCustomerNames($orders);

        $ordersData = $orders->map(function ($order) use ($customerNames) {
            return $this->extractOrderVariables($order, $customerNames);
        });

        $taxes = $this->getOrdersTaxes($ordersData);

        return view('orders.list', [
            'orders' => $ordersData,
            'taxes' => $taxes,
            'paginator' => $orders,
        ]);
    }

    /**
     * Controller method for the orders.order.view
     * @return \Illuminate\Http\Response
     */
    public function view(Order $order)
    {
        $order->load([
            'roomSelections.room',
            'transactions.transactionMode',
            'transactions.register',
        ]);

        $firstRoomSelection = $order->roomSelections->first();
        $checkInDate = $firstRoomSelection ? $firstRoomSelection->start_date->timezone(Auth::user()->timezone) : null;
        $checkOutDate = $firstRoomSelection ? $firstRoomSelection->end_date->timezone(Auth::user()->timezone) : null;

        $roomSelections = $order->roomSelections->map(function ($roomSelection) {
            return [
                'room' => $roomSelection->room->name,
                'fields' => $this->getFields($roomSelection),
            ];
        });

        return view('orders.view', [
            'order' => $order,
            'transactions' => $order->transactions->map(function ($transaction) {
                return [
                    'type' => $transaction->amount > 0 ? 'payment' : 'refund',
                    'mode' => $transaction->transactionMode->name,
                    'amount' => $transaction->amount,
                    'createdAt' => $transaction->created_at->timezone(Auth::user()->timezone),
                    'registerId' => $transaction->register->id,
                    'registerNumber' => $transaction->register->number,
                ];
            }),
            'customerFields' => $this->getFields($order->customer),
            'createdAt' => $order->created_at->timezone(Auth::user()->timezone),
            'subTotal' => $order->subTotal,
            'taxes' => $order->taxes,
            'creditsTotal' => $order->creditsTotal,
            'transactionsTotal' => $order->transactionsTotal,
            'total' => $order->total,
            'balance' => $order->balance,
            'checkIn' => $checkInDate,
            'checkOut' => $checkOutDate,
            'roomSelections' => $roomSelections,
        ]);
    }

    /**
     * Returns a collection of arrays with keys `label` and `value`
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return Collection
     */
    protected function getFields($model)
    {
        $values = $model->fieldValues;
        $ids = $values->pluck('fieldId');
        $fields = Field::whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return $values->map(function ($value) use ($fields) {
            $field = $fields[$value['fieldId']];
            $type = $field->type;
            $value = $value['value'];

            switch ($type) {
                case 'YesNoField':
                    $value = __($value ? 'actions.yes' : 'actions.no');
                    break;
                case 'SelectField':
                    $value = $field->values[$value];
                    break;
            }

            return [
                'label' => $field->label,
                'value' => $value,
            ];
        });
    }

    /**
     * Controller method for the orders.order.recalculate route
     * @param \App\Order $order
     *
     * @return \Illuminate\Http\Response
     */
    public function recalculate(Order $order)
    {
        dispatch(new PreCalcOrderValues($order));
        return redirect(route('orders.list'));
    }

    /**
     * From an Order instance, returns an array of different values needed for the orders.list route
     * @param \App\Order $order
     * @param Collection $customerNames
     *
     * @return array
     */
    protected function extractOrderVariables(Order $order, $customerNames)
    {
        $subTotal = $order->getCalculatedValue(\App\Order::PRE_CALC_SUB_TOTAL);
        $creditsTotal = $order->getCalculatedValue(\App\Order::PRE_CALC_CREDITS);
        $transactionsTotal = $order->getCalculatedValue(\App\Order::PRE_CALC_TRANSACTIONS);
        $taxes = [];
        $taxesTotal = 0;

        foreach ($order->calculatedValues as $value) {
            if (strpos($value['key'], Order::PRE_CALC_TAX) === 0) {
                $taxId = substr($value['key'], strlen(Order::PRE_CALC_TAX) + 1);
                $taxes[$taxId] = $value['value'];
                $taxesTotal = bcadd($taxesTotal, $value['value']);
            }
        }

        $total = bcsub(
            bcadd($subTotal, $taxesTotal),
            $creditsTotal
        );

        $balance = bcsub($total, $transactionsTotal);

        return [
            'id' => $order->id,
            'createdAt' => $order->created_at->timezone(Auth::user()->timezone),
            'customerName' => $customerNames->has($order->id) ? $customerNames[$order->id]->customer_name : null,
            'subTotal' => $subTotal,
            'taxes' => $taxes,
            'creditsTotal' => $creditsTotal,
            'total' => $total,
            'balance' => $balance,
        ];
    }

    protected function getOrderCustomerNames($orders)
    {
        $business = Auth::user()->currentTeam->business;
        $customerNameField = $business->customerFields()
            ->where('role', 'customer.name')
            ->first();

        $ft = 'field_values';
        return DB::table($ft)
            ->select('orders.id as order_id', "$ft.value as customer_name")
            ->join('customers', 'customers.id', '=', "$ft.instance_id")
            ->join('orders', 'orders.customer_id', '=', 'customers.id')
            ->whereIn('orders.id', $orders->pluck('id'))
            ->where("$ft.field_id", $customerNameField->id)
            ->get()
            ->keyBy('order_id');
    }

    /**
     * From the array returned by `extractOrderVariables`, returns a Collection of all the Tax objects
     * @param array $orders
     *
     * @return Collection
     */
    protected function getOrdersTaxes($orders)
    {
        $taxesId = [];

        foreach ($orders as $order) {
            $taxesId = array_merge($taxesId, array_keys($order['taxes']));
        }

        $taxesId = array_unique($taxesId);

        if (count($taxesId)) {
            return Tax::whereIn('id', $taxesId)->get();
        }

        return new Collection([]);
    }
}
