@extends('spark::layouts.app')

@section('content')
    <div class="container">
        <h1>{{ __('devices.list.title') }}</h1>
        <p class="text-right">
            <a href="{{ route('devices.add') }}" class="btn btn-primary">{{ __('devices.actions.add_device') }}</a>
        </p>
        @if(!$devices->count())
            <p>{{ __('devices.list.empty') }}</p>
        @else
            <table class="table table-striped table-hover table-responsive">
                <colgroup>
                    <col class="col-xs-6">
                    <col class="col-xs-6">
                </colgroup>
                <thead>
                <tr>
                    <th>{{ __('devices.list.cols.name') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($devices as $device)
                    <tr>
                        <td>{{ $device->name }}</td>
                        <td class="text-right">
                            <a href="{{ route('devices.device.code', ['device' => $device]) }}" class="btn btn-default">
                                {{ __('devices.list.actions.get_passcode') }}
                            </a>
                            <a href="{{ route('devices.device.revoke', ['device' => $device]) }}" class="btn btn-danger">
                                {{ __('devices.list.actions.revoke') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
