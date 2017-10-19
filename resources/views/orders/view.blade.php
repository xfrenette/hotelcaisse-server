@extends('spark::layouts.app')

@section('content')
    <?php
    ?>
    <div class="container">
        <h1>
            {{ __('orders.view.title', ['number' => $order->id])}}
        </h1>
        <div class="row">
            <div class="col-sm-8">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h2 class="panel-title">{{ __('orders.view.customer.title') }}</h2>
                    </div>
                    <table class="table">
                        @foreach($customerFields as $field)
                            <tr>
                                <th>{{ $field['label'] }}</th>
                                <td>{{ $field['value'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h2 class="panel-title">{{ __('orders.view.roomSelections.title') }}</h2>
                    </div>
                    @if($checkIn)
                    <div class="panel-body">
                        <dl>
                            <dt>{{ __('roomSelections.fields.startDate') }}</dt>
                            <dd>
                                {{ $checkIn->formatLocalized(config('formats.dateFullCompact')) }}
                            </dd>
                            <dt>{{ __('roomSelections.fields.endDate') }}</dt>
                            <dd>
                                {{ $checkOut->formatLocalized(config('formats.dateFullCompact')) }}
                            </dd>
                        </dl>
                    </div>
                    <table class="table">
                        <thead>
                        <tr>
                            <th></th>
                            @foreach($roomSelections[0]['fields'] as $field)
                            <th>{{ $field['label'] }}</th>
                            @endforeach
                        </tr>
                        @foreach($roomSelections as $roomSelection)
                        <tr>
                            <td>{{ $roomSelection['room'] }}</td>
                            @foreach($roomSelection['fields'] as $field)
                                <td>{{ $field['value'] }}</td>
                            @endforeach
                        </tr>
                        @endforeach
                        </thead>
                    </table>
                    @else
                    <div class="panel-body">
                        <p>{{ __('orders.view.roomSelections.empty') }}</p>
                    </div>
                    @endif
                </div>
            </div>
            <div class="col-sm-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h2 class="panel-title">{{ __('orders.view.summary.title') }}</h2>
                    </div>
                    <table class="table">
                        <tbody>
                            <tr>
                                <th>{{ __('orders.fields.subTotal') }}</th>
                                <td>{{ money_format('%(i', $subTotal) }}</td>
                            </tr>
                            @foreach($taxes as $tax)
                            <tr>
                                <th>{{ $tax['name'] }}</th>
                                <td>{{ money_format('%(i', $tax['amount']) }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <th>{{ __('orders.fields.credits') }}</th>
                                <td>{{ money_format('%(i', -1 * $creditsTotal) }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('orders.fields.total') }}</th>
                                <td>{{ money_format('%(i', $total) }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('orders.fields.transactions') }}</th>
                                <td>{{ money_format('%(i', -1 * $transactionsTotal) }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('orders.fields.balance') }}</th>
                                <td>{{ money_format('%(i', $balance) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">{{ __('orders.view.items.title') }}</h2>
            </div>
            @if(!$order->items->count())
                <div class="panel-body">
                    <p>{{ __('orders.view.items.empty') }}</p>
                </div>
            @else
                <table class="table">
                    <thead>
                    <tr>
                        <th>{{ __('items.fields.quantity') }}</th>
                        <th>{{ __('items.fields.productName') }}</th>
                        <th>{{ __('items.fields.total') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ money_format('%(i', $item->total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">{{ __('orders.view.credits.title') }}</h2>
            </div>
            @if(!$order->credits->count())
                <div class="panel-body">
                    <p>{{ __('orders.view.credits.empty') }}</p>
                </div>
            @else
                <table class="table">
                    <thead>
                    <tr>
                        <th>{{ __('credits.fields.note') }}</th>
                        <th>{{ __('credits.fields.amount') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($order->credits as $credit)
                        <tr>
                            <td>{{ $credit->note}}</td>
                            <td>{{ money_format('%(i', -1 * $credit->amount) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">{{ __('orders.view.transactions.title') }}</h2>
            </div>
            @if(!$transactions->count())
                <div class="panel-body">
                    <p>{{ __('orders.view.transactions.empty') }}</p>
                </div>
            @else
                <table class="table">
                    <thead>
                    <tr>
                        <th>{{ __('transactions.fields.createdAt') }}</th>
                        <th>{{ __('transactions.fields.type') }}</th>
                        <th>{{ __('transactions.fields.transactionMode') }}</th>
                        <th>{{ __('transactions.fields.amount') }}</th>
                        <th>{{ __('transactions.fields.register') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($transactions as $transaction)
                        <tr>
                            <td>
                                {{ $transaction['createdAt']->formatLocalized(config('formats.dateTimeShortSec')) }}
                            </td>
                            <td>{{ __('transactions.types.' . $transaction['type']) }}</td>
                            <td>{{ $transaction['mode']}}</td>
                            <td>{{ money_format('%(i', -1 * $transaction['amount']) }}</td>
                            <td>
                                <a href="{{
                                    route('registers.register.view', ['register' => $transaction['registerId']])
                                }}"># {{ $transaction['registerNumber'] }}</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
