@extends('spark::layouts.app')

<?php
function amountError($amount) {
    if (!$amount) {
        return '';
    }

    $formatted = money_format('%i', $amount);

    if ($amount > 0) {
        $formatted = '+' . $formatted;
    }

    $html = '<small class="text-danger">';
    $html .= $formatted;
    $html .= '</small>';
    return $html;
}

$notAvailable = '<em title="';
$notAvailable .= __('registers.list.naDefinition');
$notAvailable .= '"><span style="text-muted">-N/A-</span><span style="text-primary">*</span></em>';
?>

@push('styles')
    <style>
        .table > thead > tr > .tableGroupHead {
            border-bottom: 0;
            text-transform: uppercase;
        }
        .table > thead > tr >th {
            white-space: nowrap;
            border-top: 0;
        }
        .tableGroup {
            border-right: 2px solid #ddd;
        }
        .tableGroup:last-child {
            border-right: 0;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-sm-8">
                <h1>{{ __('registers.list.title') }}</h1>
            </div>
            <div class="col-sm-4 text-right">
                <a class="btn btn-primary" href="{{ $exportURL }}">Télécharger cette liste</a>
            </div>
        </div>
        @include('partials.filters', [
            'filters' => [
                [
                    'label' => __('registers.list.filters.openingDate'),
                    'type' => 'dateRange',
                ],
                [
                    'label' => __('registers.list.filters.registerRange'),
                    'type' => 'registerRange',
                ],
            ],
        ])
        <hr>
        @if(count($registers))
        <table class="table table-hover">
            <thead>
            <tr>
                @foreach(__('registers.list.columns') as $column)
                    <th class="tableGroupHead tableGroup" colspan={{ count($column) - 1 }}>
                        {!! $column['title'] !!}
                    </th>
                @endforeach
            </tr>
            <tr>
                @foreach(__('registers.list.columns') as $columnGroup)
                    @foreach($columnGroup as $column)
                        @if(!$loop->first)
                            <th class={{ $loop->last ? 'tableGroup' : '' }}>
                                {!! $column !!}
                            </th>
                        @endif
                    @endforeach
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($registers as $register)
                <?php
                $opened = $register->state === \App\Register::STATE_OPENED;
                $closed = !$opened;
                $openedAt = $register->opened_at->timezone(Auth::user()->timezone);
                $closedAt = $opened
                    ? null
                    : $register->closed_at->timezone(Auth::user()->timezone);
                $paymentsTotal = $register->getCalculatedValue(
                    \App\Register::PRE_CALC_PAYMENTS_TOTAL
                );
                $refundsTotal = -1 * $register->getCalculatedValue(
                    \App\Register::PRE_CALC_REFUNDS_TOTAL
                );
                $transactionsTotal = $paymentsTotal + $refundsTotal;
                $cashTransactionsTotal = $register->getCalculatedValue(
                    \App\Register::PRE_CALC_CASH_TX_TOTAL
                );
                $cashMovementsTotal = $register->getCalculatedValue(
                    \App\Register::PRE_CALC_CASH_MV_TOTAL
                );
                $openingCashError = $register->opening_cash - $cashFloat;
                $cashExpected = $cashTransactionsTotal + $cashMovementsTotal + $openingCashError;
                $cashDeclared = $opened ? null : $register->closing_cash;
                $expectedPOSTAmount = $transactionsTotal - $cashTransactionsTotal;
                ?>
                <tr
                    style="cursor: pointer;"
                    onclick="document.location = '{{ route('registers.register.view', ['register' => $register]) }}'"
                >
                    <td>{{ $register->number }}</td>
                    <td>
                        {{ $openedAt->formatLocalized(config('formats.dateShort')) }}
                        <br>
                        {{ $openedAt->formatLocalized(config('formats.time')) }}
                    </td>
                    <td>{{ $register->employee }}</td>
                    <td>
                        {{ money_format('%(i', $register->opening_cash) }}
                        <br>
                        {!! amountError($openingCashError) !!}
                        <br>
                    </td>
                    <td>
                        {{ money_format('%(i', $paymentsTotal) }}
                    </td>
                    <td>
                        {{ money_format('%(i', $refundsTotal) }}
                    </td>
                    <td>
                        {{ money_format('%(i', $transactionsTotal) }}
                    </td>
                    <td>
                        {{ money_format('%(i', $cashTransactionsTotal) }}
                    </td>
                    <td>
                        {{ money_format('%(i', $cashMovementsTotal) }}
                    </td>
                    <td>
                        {{ money_format('%(i', $openingCashError) }}
                    </td>
                    <td>
                        {{ money_format('%(i', $cashExpected) }}
                    </td>
                    <td>
                        @if ($closed)
                            {{ money_format('%(i', $cashDeclared) }}
                            <br>
                            {!! amountError($cashDeclared - $cashExpected) !!}
                        @else
                            {!! $notAvailable !!}
                        @endif
                    </td>
                    <td>
                        @if($opened)
                            {!! $notAvailable !!}
                        @else
                            {{ $register->post_ref }}
                        @endif
                    <td>{{ money_format('%(i', $expectedPOSTAmount) }}</td>
                    <td>
                        @if($opened)
                            {!! $notAvailable !!}
                        @else
                            {{ money_format('%(i', $register->post_amount) }}
                            <br>
                            {!! amountError($register->post_amount - $expectedPOSTAmount) !!}
                        @endif
                    </td>
                    <td>
                        @if($opened)
                            {!! $notAvailable !!}
                        @else
                            {{ $closedAt->formatLocalized(config('formats.dateShort')) }}
                            <br>
                            {{ $closedAt->formatLocalized(config('formats.time')) }}
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p>{!! __('registers.list.naMessage', ['na' => $notAvailable]) !!}</p>
        {{ $registers->links() }}
        @else
        <p>{{ __('registers.list.empty') }}</p>
        @endif
    </div>
@endsection
