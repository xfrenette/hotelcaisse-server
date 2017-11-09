<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\Exports;
use App\Http\Controllers\Traits\UsesFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomProductsController extends Controller
{
    use UsesFilters;
    use Exports;

    const LIST_NB_PER_PAGE = 20;

    public function list(Request $request)
    {
        $query = $this->buildListQuery($request);
        $items = $query->simplePaginate(self::LIST_NB_PER_PAGE)
            ->appends($_GET);
        $viewData = $this->getListViewData($items);
        $viewData['exportURL'] = route('customProducts.export', $_GET);
        return view('customProducts.list', $viewData);
    }

    public function export(Request $request)
    {
        $items = $this->buildListQuery($request)->get();
        $viewData = $this->getListViewData($items);
        $data = [];

        // Titles
        $titles = [
            'Date de vente',
            'Nom du produit',
            'Prix unitaire',
            'Qté vendue',
            'Total',
            'Fiche associée',
        ];

        $data[] = $titles;

        // Rows
        foreach ($viewData['items'] as $item) {
            $row = [
                $item->product->created_at->toDateTimeString(),
                $item->product->name,
                $item->product->price,
                intval($item->quantity),
                bcmul($item->product->price, $item->quantity),
                route('orders.order.view', $item->order),
            ];

            $data[] = $row;
        }

        return $this->downloadableCSV($data, 'produits-speciaux');
    }

    protected function buildListQuery(Request $request)
    {
        $business = Auth::user()->currentTeam->business;

        // Get the sold items where the item product id is null
        $query = $business->items()
            ->with('product')
            ->with('order')
            ->join('item_products', 'item_products.id', '=', 'items.item_product_id')
            ->whereNull('item_products.product_id')
            ->orderBy('items.created_at', 'DESC');

        return $this->filterQuery($query, $request);
    }

    protected function getListViewData($items)
    {
        return [
            'items' => $items,
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
