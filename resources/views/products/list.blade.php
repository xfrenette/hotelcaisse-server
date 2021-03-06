@extends('spark::layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-sm-8">
                <h1>{{ __('products.list.title') }}</h1>
            </div>
            <div class="col-sm-4 text-right">
                <a class="btn btn-primary" href="{{ $exportURL }}">Télécharger cette liste</a>
            </div>
        </div>
        @include('partials.filters', [
            'filters' => [
                [
                    'label' => __('products.list.filters.saleDate'),
                    'type' => 'dateRange'
                ],
                [
                    'label' => __('products.list.filters.registerRange'),
                    'type' => 'registerRange'
                ],
            ],
        ])
        <hr>
        @if(!$item_products->count())
            <p>{{ __('products.list.empty') }}</p>
        @else
        <table class="table">
            <thead>
            <tr>
                <th>Nom du produit</th>
                <th>Qté vendue</th>
                <th>Sous-total</th>
                @foreach($taxes as $tax)
                    <th>{{ $tax->name }}</th>
                @endforeach
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($item_products as $item_product)
                <?php $total = $item_product->total_amount; ?>
                <tr>
                    <td>{{ $products[$item_product->product_id]->fullName }}</td>
                    <td>{{ intval($item_product->total_quantity) }}</td>
                    <td>{{ money_format('%(i', $item_product->total_amount) }}</td>
                    @foreach($taxes as $tax)
                        <?php
                        $amount = $product_taxes
                            ->get($item_product->product_id, collect())
                            ->get($tax->id, 0);
                        $total = bcadd($total, $amount);
                        ?>
                        <td>{{ money_format('%(i', $amount) }}</td>
                    @endforeach
                    <td>{{ money_format('%(i', $total) }}</td>
                </tr>
            @endforeach
            @if($special_items)
            <tr>
                <td><em>Produits spéciaux</em> <a href="{{ $customProductsListURL }}">(Voir la
                        liste)</a></td>
                <td>{{ intval($special_items->total_quantity) }}</td>
                <td>{{ money_format('%(i', $special_items->total_amount) }}</td>
                @foreach($taxes as $tax)
                    <td>{{ money_format('%(i', 0) }}</td>
                @endforeach
                <td>{{ money_format('%(i', $special_items->total_amount) }}</td>
            </tr>
            @endif
            </tbody>
        </table>
        @endif
    </div>
@endsection
