<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\UsesFilters;
use App\Product;
use App\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    use UsesFilters;

    public function list(Request $request)
    {
        $viewData = $this->getListViewData($request);
        return view('products.list', $viewData);
    }

    public function getListViewData(Request $request)
    {
        $business = Auth::user()->currentTeam->business;

        // From all the sold item products, get the products id, the total quantity and the total amount sold
        $query = DB::table('item_products')
            ->select(
                'products.id as product_id',
                DB::raw('SUM(item_products.price * items.quantity) as total_amount'),
                DB::raw('SUM(items.quantity) as total_quantity')
            )
            ->join('products', 'products.id', '=', 'item_products.product_id')
            ->join('items', 'items.item_product_id', '=', 'item_products.id')
            ->where('products.business_id', $business->id)
            ->groupBy('products.id');
        $item_products = $this->filterQuery($query, $request)->get();

        $special_items_query = DB::table('item_products')
            ->select(
                DB::raw('SUM(price) as total_amount'),
                DB::raw('COUNT(*) as total_quantity')
            )
            ->join('items', 'items.item_product_id', '=', 'item_products.id')
            ->join('orders', 'orders.id', '=', 'items.order_id')
            ->where('orders.business_id', $business->id)
            ->whereNull('product_id')
            ->groupBy('product_id');

        $special_items = $this->filterQuery($special_items_query, $request)->first();

        // Get Product instances
        $products = Product::with('parent')
            ->whereIn('id', $item_products->pluck('product_id'))
            ->get()
            ->keyBy('id');

        // Get taxes total for each product
        $product_taxes = DB::table('applied_taxes')
            ->select(
                'applied_taxes.tax_id as tax_id',
                DB::raw('SUM(applied_taxes.amount * items.quantity) as total'),
                'item_products.product_id as product_id'
            )
            ->join('item_products', 'item_products.id', '=', 'applied_taxes.instance_id')
            ->join('items', 'items.item_product_id', '=', 'item_products.id')
            ->where('applied_taxes.type', 'ItemProduct')
            ->whereIn('item_products.product_id', $item_products->pluck('product_id'))
            ->groupBy('applied_taxes.tax_id')
            ->groupBy('item_products.product_id')
            ->get();

        $taxes = Tax::whereIn('id', $product_taxes->pluck('tax_id'))->get();

        $product_taxes = $product_taxes->groupBy('product_id')
            ->mapWithKeys(function ($taxData) {
                return [$taxData[0]->product_id => $taxData->groupBy('tax_id')->map(function($data) {
                    return $data[0]->total;
                })];
            });

        $totals = $item_products->mapWithKeys(function ($item_product) use ($product_taxes) {
            $subTotal = $item_product->total_amount;
            $product_id = $item_product->product_id;
            $taxes = $product_taxes->get($product_id, new Collection())->reduce(function ($prev, $tax) {
                return bcadd($prev, $tax);
            }, 0);

            return [$product_id => bcadd($subTotal, $taxes)];
        });

        return [
            'products' => $products,
            'item_products' => $item_products,
            'product_taxes' => $product_taxes,
            'taxes' => $taxes,
            'totals' => $totals,
            'special_items' => $special_items,
            'startDate' => $this->getFormattedStartDate(),
            'endDate' => $this->getFormattedEndDate(),
        ];
    }

    protected function filterWithStartDate($query, $startDate)
    {
        return $query->where('item_products.created_at', '>=', $startDate);
    }

    protected function filterWithEndDate($query, $endDate)
    {
        return $query->where('item_products.created_at', '<=', $endDate);
    }
}
