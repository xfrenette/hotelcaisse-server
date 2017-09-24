@extends('spark::layouts.app')

@section('content')
    <div class="container">
        <h1>{{ __('devices.add.title') }}</h1>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        {!! Form::open(['route' => 'devices.store', 'method' => 'put']) !!}
            <div class="form-group">
                <label for="field-name">{{ __('devices.add.fields.name') }}</label>
                <input id="field-name" class="form-control" name="name" value="{{ old('name') }}">
            </div>
            <div class="form-group">
                <label for="field-initialNumber">
                    {{ __('devices.add.fields.initialRegisterNumber') }}
                </label>
                <input
                    id="field-name"
                    class="form-control"
                    name="initial_register_number"
                    value="{{ old('initial_register_number', '1') }}"
                >
            </div>
            <button class="btn btn-primary">{{ __('actions.save') }}</button>
            <a href="{{ route('devices.list') }}" class="btn btn-default">{{ __('actions.cancel') }}</a>
        {!! Form::close() !!}
    </div>
@endsection
