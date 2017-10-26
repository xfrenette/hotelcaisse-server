@extends('spark::layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-sm-8">
                <h1>{{ __('orders.list.title') }}</h1>
            </div>
            <div class="col-sm-4 text-right">
            </div>
        </div>
        @include('partials.filters', [
            'filters' => [
                [
                    'label' => __('orders.list.filters.creationDate'),
                    'type' => 'dateRange'
                ],
            ],
        ])
        <hr>
        @if(!$orders->count())
            <p>{{ __('orders.list.empty') }}</p>
        @else
            <table class="table table-striped table-hover table-responsive">
                <thead>
                <tr>
                    <th></th>
                    <th>Date</th>
                    <th>Total</th>
                    @foreach($customerFields as $field)
                    <th>{{ $field->label }}</th>
                    @endforeach
                    @foreach($roomSelectionsNumericFields as $field)
                    <th>{{ $field->label }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr
                            style="cursor: pointer;"
                            onclick="document.location = '{{route('orders.order.view', ['order' => $order['id']])}}'"
                        >
                            <td>
                                <a href="{{ route('orders.order.view', ['order' => $order['id']]) }}">
                                    #&nbsp;{{ $order['id'] }}
                                </a>
                            </td>
                            <td>
                                {{ $order['createdAt']->formatLocalized(config('formats.dateFullCompact')) }}
                                <br>
                                {{ $order['createdAt']->formatLocalized(config('formats.time')) }}
                            </td>
                            <td>{{ money_format('%(i', $order['total']) }}</td>
                            @foreach($customerFields as $field)
                                <td>
                                @if($order['customerFieldValues']->has($field->id))
                                    {{ $order['customerFieldValues'][$field->id] }}
                                @endif
                                </td>
                            @endforeach
                            @foreach($roomSelectionsNumericFields as $field)
                                <td>
                                @if($order['roomSelectionsNumericFieldValues']->has($field->id))
                                    {{ $order['roomSelectionsNumericFieldValues'][$field->id] }}
                                @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $paginator->links() }}
        @endif
    </div>
@endsection
