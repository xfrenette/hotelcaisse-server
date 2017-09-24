@extends('spark::layouts.app')

@section('content')
    <div class="container">
        <h1>{{ __('registers.list.title') }}</h1>
        @if(count($registers))
            <table class="table">
            <thead>
                <tr>
                    <th>{{ __('registers.fields.numberShort') }}</th>
                    <th>{{ __('registers.fields.employee') }}</th>
                    <th>{{ __('registers.fields.state') }}</th>
                    <th>{{ __('registers.fields.openedAt') }}</th>
                    <th>{{ __('registers.fields.openingCash') }}</th>
                    <th>{{ __('registers.fields.closedAt') }}</th>
                    <th>{{ __('registers.fields.closingCash') }}</th>
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
                    ?>
                    <tr onclick="document.location = '{{ route('registers.view', ['register' => $register]) }}'">
                        <td>{{ $register->number }}</td>
                        <td>{{ $register->employee }}</td>
                        <td>
                            @if($opened)
                                {{ __('registers.states.opened') }}
                            @else
                                {{ __('registers.states.closed') }}
                            @endif
                        </td>
                        <td>{{ $openedAt->toDateTimeString() }}</td>
                        <td>{{ money_format('%(i', $register->opening_cash) }}</td>
                        <td>
                            @if($closed)
                                {{ $closedAt->toDateTimeString() }}
                            @endif
                        </td>
                        <td>
                            @if($closed)
                                {{ money_format('%(i', $register->closing_cash) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            </table>
        @else
            <p>{{ __('registers.list.empty') }}</p>
        @endif
    </div>
@endsection
