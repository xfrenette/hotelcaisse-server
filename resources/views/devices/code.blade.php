@extends('spark::layouts.app')

@section('content')
    <div class="container">
        <h1>{{ __('devices.code.title') }}</h1>
        <p>{{ __('devices.code.instructions') }}</p>
        <div class="row">
            <div class="col-sm-offset-3 col-sm-6 panel panel-primary text-center">
                <div class="lead">{{ $passcode }}</div>
            </div>
        </div>
        <a href="{{ route('devices.list') }}" class="btn btn-primary">{{ __('actions.back') }}</a>
    </div>
@endsection
