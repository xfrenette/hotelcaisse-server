@extends('spark::layouts.app')

@section('content')
    <div class="container">
        <h1>{{ __('orders.list.title') }}</h1>
        @if(!$orders->count())
            <p>{{ __('orders.list.empty') }}</p>
        @else
            <table class="table table-striped table-hover table-responsive">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Client</th>
                    <th>Sous-total</th>
                    @foreach($taxes as $tax)
                    <th>{{ $tax['name'] }}</th>
                    @endforeach
                    <th>Crédits</th>
                    <th>Total</th>
                    <th>À percevoir (à remb.)</th>
                </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr
                            style="cursor: pointer;"
                            onclick="document.location = '{{route('orders.order.view', ['order' => $order['id']])}}'"
                        >
                            <td>
                                {{ $order['createdAt']->formatLocalized(config('formats.dateFullCompact')) }}
                            </td>
                            <td>
                                {{ $order['createdAt']->formatLocalized(config('formats.time')) }}
                            </td>
                            <td>{{ $order['customerName'] }}</td>
                            <td>{{ money_format('%(i', $order['subTotal']) }}</td>
                            @foreach($taxes as $tax)
                                <td>
                                    @if(array_key_exists($tax['id'], $order['taxes']))
                                        {{ money_format('%(i', $order['taxes'][$tax['id']]) }}
                                    @endif
                                </td>
                            @endforeach
                            <td>{{ money_format('%(i', $order['creditsTotal']) }}</td>
                            <td>{{ money_format('%(i', $order['total']) }}</td>
                            <td>{{ money_format('%(i', $order['balance']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $paginator->links() }}
        @endif
    </div>
@endsection
