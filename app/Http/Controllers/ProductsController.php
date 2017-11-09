<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\Exports;
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
    use Exports;

    public function list(Request $request)
    {
        $viewData = $this->getListViewData($request);
        $viewData['exportURL'] = route('products.export', $_GET);
        $viewData['customProductsListURL'] = route('customProducts.list', $_GET);
        return view('products.list', $viewData);
    }

    public function export(Request $request)
    {
        $viewData = $this->getListViewData($request);
        $data = [];

        // Titles
        $titles = [
            'Nom produit',
            'Qté vendue',
            'Sous-total',
        ];
        foreach($viewData['taxes'] as $tax) {
            $titles[] = $tax->name;
        }
        $titles[] = 'Total';

        $data[] = $titles;

        // Rows
        foreach ($viewData['item_products'] as $item_product) {
            $total = $item_product->total_amount;

            $row = [
                $viewData['products'][$item_product->product_id]->fullName,
                $item_product->total_quantity,
                round($item_product->total_amount, 2),
            ];

            foreach($viewData['taxes'] as $tax) {
                $amount = $viewData['product_taxes']
                    ->get($item_product->product_id, collect())
                    ->get($tax->id, 0);
                $total = bcadd($total, $amount);
                $row[] = round($amount, 2);
            }

            $row[] = round($total, 2);

            $data[] = $row;
        }

        // Special items
        if ($viewData['special_items']) {
            $special_item_rows = [
                'Produits spéciaux',
                $viewData['special_items']->total_quantity,
                round($viewData['special_items']->total_amount, 2),
            ];

            foreach($viewData['taxes'] as $tax) {
                $special_item_rows[] = 0;
            }

            $special_item_rows[] = round($viewData['special_items']->total_amount, 2);

            $data[] = $special_item_rows;
        }

        return $this->downloadableCSV($data, 'produits');
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
                DB::raw('SUM(item_products.price * items.quantity) as total_amount'),
                DB::raw('SUM(items.quantity) as total_quantity')
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
        $product_taxes_query = DB::table('applied_taxes')
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
            ->groupBy('item_products.product_id');

        $product_taxes = $this->filterQuery($product_taxes_query, $request)->get();

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
