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
            <button class="btn btn-primary">{{ __('actions.save') }}</button>
            <a href="{{ route('devices.list') }}" class="btn btn-default">{{ __('actions.cancel') }}</a>
        {!! Form::close() !!}
    </div>
@endsection
