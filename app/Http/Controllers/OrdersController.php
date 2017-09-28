<?php

namespace App\Http\Controllers;

use App\Jobs\PreCalcOrderValues;
use App\Order;
use App\Tax;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class OrdersController extends Controller
{
    public function list()
    {
        $orders = Order
            ::with('customer', 'calculatedValues')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return $this->extractOrderVariables($order);
            });

        $taxes = $this->getOrdersTaxes($orders);

        return view('orders.list', [
            'orders' => $orders,
            'taxes' => $taxes,
        ]);
    }

    protected function extractOrderVariables(Order $order)
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
            'customerName' => 'TODO',
            'subTotal' => $subTotal,
            'taxes' => $taxes,
            'creditsTotal' => $creditsTotal,
            'total' => $total,
            'balance' => $balance,
        ];
    }

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

    public function recalculate(Order $order)
    {
        dispatch(new PreCalcOrderValues($order));
        return redirect(route('orders.list'));
    }
}
