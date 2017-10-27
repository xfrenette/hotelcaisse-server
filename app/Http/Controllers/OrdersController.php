<?php

namespace App\Http\Controllers;

use App\Field;
use App\Http\Controllers\Traits\Exports;
use App\Http\Controllers\Traits\UsesFilters;
use App\Jobs\PreCalcOrderValues;
use App\Order;
use App\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    use UsesFilters;
    use Exports;

    /**
     * Number of items per page in the paginated list screen
     * @type integer
     */
    const LIST_NB_PER_PAGE = 20;

    /**
     * Controller method for the orders.list route
     * @param $request Request
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $query = $this->buildListQuery($request);
        $orders = $query->simplePaginate(self::LIST_NB_PER_PAGE)
            ->appends($_GET);
        $viewData = $this->getListViewData($orders);
        $viewData['paginator'] = $orders;
        $viewData['exportURL'] = route('orders.export', $_GET);

        return view('orders.list', $viewData);
    }

    public function export(Request $request)
    {
        $query = $this->buildListQuery($request);
        $orders = $query->get();
        $viewData = $this->getListViewData($orders);

        $titles = ['No.', 'Date', 'Total'];
        foreach ($viewData['customerFields'] as $field) {
            $titles[] = $field->label;
        }
        foreach ($viewData['roomSelectionsNumericFields'] as $field) {
            $titles[] = $field->label;
        }
        $titles[] = 'URL détails';

        $data = [$titles];

        foreach ($viewData['orders'] as $order) {
            $row = [
                $order['id'],
                $order['createdAt']->toDateTimeString(),
                $order['total'],
            ];

            foreach ($viewData['customerFields'] as $field) {
                if ($order['customerFieldValues']->has($field->id)) {
                    $row[] = $order['customerFieldValues'][$field->id];
                } else {
                    $row[] = '';
                }
            }

            foreach ($viewData['roomSelectionsNumericFields'] as $field) {
                if ($order['roomSelectionsNumericFieldValues']->has($field->id)) {
                    $row[] = $order['roomSelectionsNumericFieldValues'][$field->id];
                } else {
                    $row[] = '';
                }
            }

            $row[] = route('orders.order.view', ['order' => $order['id']]);

            $data[] = $row;
        }


        return $this->downloadableCSV($data, 'fiches');
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Query\Builder
     */
    protected function buildListQuery(Request $request)
    {
        $business = Auth::user()->currentTeam->business;
        $query = $business->orders()
            ->with('calculatedValues')
            ->orderBy('created_at', 'desc');

        return $this->filterQuery($query, $request);
    }

    protected function filterWithStartDate($query, $startDate)
    {
        return $query->where('created_at', '>=', $startDate);
    }

    protected function filterWithEndDate($query, $endDate)
    {
        return $query->where('created_at', '<=', $endDate);
    }

    protected function getListViewData($orders)
    {
        $customerFieldValues = $this->getCustomersFieldValues($orders);
        $roomSelectionsNumericFieldValues = $this->getRoomSelectionsNumericFieldValues($orders);

        $ordersData = $orders->map(function ($order) use ($customerFieldValues, $roomSelectionsNumericFieldValues) {
            return $this->extractOrderVariables($order, $customerFieldValues, $roomSelectionsNumericFieldValues);
        });

        $business = Auth::user()->currentTeam->business;
        $roomSelectionFields = $business->roomSelectionFields;
        $roomSelectionNumericFields = $roomSelectionFields->filter(function ($field) {
            return $field->type === 'NumberField';
        });

        return [
            'orders' => $ordersData,
            'customerFields' => $business->customerFields,
            'roomSelectionsNumericFields' => $roomSelectionNumericFields,
            'startDate' => $this->getFormattedStartDate(),
            'endDate' => $this->getFormattedEndDate(),
        ];
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
    protected function extractOrderVariables(Order $order, $customerFieldValues, $roomSelectionsNumericFieldValues)
    {
        $subTotal = $order->getCalculatedValue(\App\Order::PRE_CALC_SUB_TOTAL);
        $creditsTotal = $order->getCalculatedValue(\App\Order::PRE_CALC_CREDITS);
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

        return [
            'id' => $order->id,
            'createdAt' => $order->created_at->timezone(Auth::user()->timezone),
            'customerFieldValues' => $customerFieldValues->get($order->id, new Collection()),
            'roomSelectionsNumericFieldValues' => $roomSelectionsNumericFieldValues->get($order->id, new Collection()),
            'total' => $total,
        ];
    }

    protected function getCustomersFieldValues($orders)
    {
        $ft = 'field_values';
        $rawFields = DB::table($ft)
            ->select('orders.id as order_id', "fields.type as type", "$ft.field_id as field_id", "$ft.value as value")
            ->join('customers', 'customers.id', '=', "$ft.instance_id")
            ->join('orders', 'orders.customer_id', '=', 'customers.id')
            ->join('fields', 'fields.id', '=', "$ft.field_id")
            ->whereIn('orders.id', $orders->pluck('id'))
            ->get()
            ->groupBy('order_id');

        $fields = new Collection();
        $rawFields->each(function ($data, $orderId) use (&$fields) {
            $fields[$orderId] = $data->mapWithKeys(function ($item) {
                $value = $item->value;

                if ($item->type === 'YesNoField') {
                    $value = __('actions.' . ($value == '1' ? 'yes' : 'no'));
                }

                return [$item->field_id => $value];
            });
        });

        return $fields;
    }

    protected function getRoomSelectionsNumericFieldValues($orders)
    {
        $ft = 'field_values';
        $valuesQuery = DB::table($ft)
            ->select('orders.id as order_id', "$ft.field_id as field_id", "$ft.value as value")
            ->join('room_selections', 'room_selections.id', '=', "$ft.instance_id")
            ->join('orders', 'orders.id', '=', 'room_selections.order_id')
            ->join('fields', 'fields.id', '=', "$ft.field_id")
            ->whereIn('orders.id', $orders->pluck('id'))
            ->where('fields.type', 'NumberField');

        $rawFields = DB::table(DB::raw("({$valuesQuery->toSql()}) as sub"))
            ->select('order_id', 'field_id', DB::raw("SUM(value) as value"))
            ->mergeBindings($valuesQuery)
            ->groupBy(['field_id', 'order_id'])
            ->get()
            ->groupBy('order_id');

        $fields = new Collection();
        $rawFields->each(function ($data, $orderId) use (&$fields) {
            $fields[$orderId] = $data->mapWithKeys(function ($item) {
                return [$item->field_id => $item->value];
            });
        });

        return $fields;
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
