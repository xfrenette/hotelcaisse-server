@extends('spark::layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-sm-8">
                <h1>{{ __('customProducts.list.title') }}</h1>
            </div>
            <div class="col-sm-4 text-right">
                <a class="btn btn-primary" href="{{ $exportURL }}">Télécharger cette liste</a>
            </div>
        </div>
        @include('partials.filters', [
            'filters' => [
                [
                    'label' => __('customProducts.list.filters.saleDate'),
                    'type' => 'dateRange'
                ],
                [
                    'label' => __('customProducts.list.filters.registerRange'),
                    'type' => 'registerRange'
                ],
            ],
        ])
        <hr>
        @if (!$items->count())
            <p>{{ __('customProducts.list.empty') }}</p>
        @else
        <table class="table">
            <thead>
            <tr>
                <th>Date de vente</th>
                <th>Nom du produit</th>
                <th>Prix unitaire</th>
                <th>Qté vendue</th>
                <th>Total</th>
                <th>Fiche associée</th>
            </tr>
            </thead>
            <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->product->created_at->formatLocalized(config('formats.dateTimeShort')) }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ money_format('%(i', $item->product->price) }}</td>
                    <td>{{ intval($item->quantity) }}</td>
                    <td>{{ money_format('%(i', bcmul($item->product->price, $item->quantity)) }}</td>
                    <td>
                        <a href="{{ route('orders.order.view', $item->order) }}">
                            Fiche #{{ $item->order->id }}
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $items->links() }}
        @endif
    </div>
@endsection
