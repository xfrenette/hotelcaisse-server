<?php

namespace App\Http\Controllers;

use App\Jobs\PreCalcOrderValues;
use App\Order;
use App\Tax;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    /**
     * Controller method for the orders.list route
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        $orders = Order
            ::with('calculatedValues')
            ->orderBy('created_at', 'desc')
            ->get();

        $customerNames = $this->getOrderCustomerNames($orders);

        $ordersData = $orders->map(function ($order) use ($customerNames) {
            return $this->extractOrderVariables($order, $customerNames);
        });

        $taxes = $this->getOrdersTaxes($ordersData);

        return view('orders.list', [
            'orders' => $ordersData,
            'taxes' => $taxes,
        ]);
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
