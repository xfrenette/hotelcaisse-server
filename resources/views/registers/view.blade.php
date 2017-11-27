@php
function amountError($amount) {
    if (!$amount) {
        return '';
    }

    $formatted = money_format('%i', $amount);

    if ($amount > 0) {
        $formatted = '+' . $formatted;
    }

    $html = '<small class="text-danger">(Erreur: ';
    $html .= $formatted;
    $html .= ')</small>';
    return $html;
}

$notAvailable = '<em title="';
$notAvailable .= __('registers.list.naDefinition');
$notAvailable .= '"><span style="text-muted">N/A: la caisse est ouverte</span></em>';

@endphp

@extends('spark::layouts.app')

@section('content')
    <?php
    $closed = $register->state === \App\Register::STATE_CLOSED;
    $openedAt = $register->opened_at->timezone(Auth::user()->timezone);
    $closedAt = $closed ? $register->closed_at->timezone(Auth::user()->timezone) : null;
    ?>
    <div class="container">
        <h1>
            {{ __('registers.view.title', ['number' => $register->number])}}
        </h1>
        <div class="row">
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">{{ __('registers.view.meta.general') }}</div>
                    <div class="panel-body">
                        <dl>
                            <dt>{{ __('registers.fields.number') }}</dt>
                            <dd>{{ $register->number }}</dd>
                            <dt>{{ __('registers.fields.state') }}</dt>
                            <dd>
                                @if($closed)
                                    {{ __('registers.states.closed') }}
                                @else
                                    {{ __('registers.states.opened') }}
                                @endif
                            </dd>

                            @if($closed)
                                <dt>{{ __('registers.fields.paymentsTotal') }}</dt>
                                <dd>{{ money_format('%(i', $paymentsTotal) }}</dd>
                                <dt>{{ __('registers.fields.refundsTotal') }}</dt>
                                <dd>{{ money_format('%(i', $refundsTotal) }}</dd>
                                <dt>{{ __('registers.fields.cashMovementsTotal') }}</dt>
                                <dd>{{ money_format('%(i', $cashMovementsTotal) }}</dd>
                                <dt>{{ __('registers.fields.netTotal') }}</dt>
                                <dd>{{ money_format('%(i', $netTotal) }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">{{ __('registers.view.meta.opening') }}</div>
                    <div class="panel-body">
                        <dl>
                            <dt>{{ __('registers.fields.openedAt') }}</dt>
                            <dd>{{ $openedAt->toDateTimeString() }}</dd>
                            <dt>{{ __('registers.fields.employee') }}</dt>
                            <dd>{{ $register->employee }}</dd>
                        </dl>
                    </div>
                </div>
                @if($closed)
                <div class="panel panel-default">
                    <div class="panel-heading">{{ __('registers.view.meta.closing') }}</div>
                    <div class="panel-body">
                        <dl>
                            <dt>{{ __('registers.fields.closedAt') }}</dt>
                            <dd>{{ $closedAt->toDateTimeString() }}</dd>
                        </dl>
                    </div>
                </div>
                @endif
            </div>
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">{{ __('registers.view.meta.cashDetails') }}</div>
                    <div class="panel-body">
                        <dl>
                            <dt>{{ __('registers.fields.openingCash') }}</dt>
                            <dd>
                                {{ money_format('%(i', $register->opening_cash) }}
                                {!! amountError($register->opening_cash - $cashFloat)  !!}
                            </dd>
                            <dt>{{ __('registers.fields.expectedClosingCash') }}</dt>
                            <dd>{{ money_format('%(i', $cashTotal) }}</dd>
                            <dt>{{ __('registers.fields.closingCash') }}</dt>
                            <dd>
                                @if($closed)
                                {{ money_format('%(i', $register->closing_cash) }}
                                {!! amountError($register->closing_cash - $cashTotal)  !!}
                                @else
                                    {!! $notAvailable !!}
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">{{ __('registers.view.meta.postDetails') }}</div>
                    <div class="panel-body">
                        <dl>
                            <dt>{{ __('registers.fields.POSTRef') }}</dt>
                            <dd>
                                @if($closed)
                                {{ $register->post_ref }}
                                @else
                                {!! $notAvailable !!}
                                @endif
                            </dd>
                            <dt>{{ __('registers.fields.expectedPOSTAmount') }}</dt>
                            <dd>{{ money_format('%(i', $postTotal) }}</dd>
                            <dt>{{ __('registers.fields.declaredPOSTAmount') }}</dt>
                            <dd>
                                @if($closed)
                                {{ money_format('%(i', $register->post_amount) }}
                                {!! amountError($register->post_amount - $postTotal) !!}
                                @else
                                {!! $notAvailable !!}
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">{{ __('registers.view.transactions.title') }}</h2>
            </div>
            @if(!$register->transactions->count())
                <div class="panel-body">
                    <p>{{ __('registers.view.transactions.empty') }}</p>
                </div>
            @else
                <table class="table">
                    <thead>
                    <tr>
                        <th>{{ __('transactions.fields.createdAt') }}</th>
                        <th>{{ __('transactions.fields.type') }}</th>
                        <th>{{ __('transactions.fields.mode') }}</th>
                        <th>{{ __('transactions.fields.amount') }}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($register->transactions as $transaction)
                        <?php
                        $createdAt = $transaction->created_at->timezone(Auth::user()->timezone);
                        ?>
                        <tr>
                            <td>{{ $createdAt->toDateTimeString() }}</td>
                            <td>
                                @if($transaction->amount < 0)
                                    {{ __('transactions.types.refund') }}
                                @else
                                    {{ __('transactions.types.payment') }}
                                @endif
                            </td>
                            <td>{{ $transaction->transactionMode->name }}</td>
                            <td>{{ money_format('%(i', $transaction->amount) }}</td>
                            <td>
                                <a href="{{ route('orders.order.view', ['order' =>
                                $transaction->order_id]) }}">{{ __('transactions.fields.order')
                                }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">{{ __('transactions.list.total') }}</th>
                        <th>{{ money_format('%(i', $transactionsTotal) }}</th>
                        <th></th>
                    </tr>
                    </tfoot>
                </table>
            @endif
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">{{ __('registers.view.cashMovements.title') }}</h2>
            </div>
            @if(!$register->cashMovements->count())
                <div class="panel-body">
                    <p>{{ __('registers.view.cashMovements.empty') }}</p>
                </div>
            @else
                <table class="table">
                    <thead>
                    <tr>
                        <th>{{ __('cashMovements.fields.createdAt') }}</th>
                        <th>{{ __('cashMovements.fields.type') }}</th>
                        <th>{{ __('cashMovements.fields.note') }}</th>
                        <th>{{ __('cashMovements.fields.amount') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($register->cashMovements as $cashMovement)
                        <?php
                        $createdAt = $cashMovement->created_at->timezone(Auth::user()->timezone);
                        ?>
                        <tr>
                            <td>{{ $createdAt->toDateTimeString() }}</td>
                            <td>
                                @if($cashMovement->amount < 0)
                                    {{ __('cashMovements.types.out') }}
                                @else
                                    {{ __('cashMovements.types.in') }}
                                @endif
                            </td>
                            <td>{{ $cashMovement->note }}</td>
                            <td>{{ money_format('%(i', $cashMovement->amount) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">
                            {{ __('cashMovements.list.total') }}
                        </th>
                        <th>{{ money_format('%(i', $cashMovementsTotal) }}</th>
                    </tr>
                    </tfoot>
                </table>
            @endif
        </div>
    </div>
@endsection
