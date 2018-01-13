@extends('spark::layouts.app')

@push('styles')
    <style>
        a[name] {
            transform: translateY(-100px);
            display: inline-block;
        }
        .help-container {
            font-weight: normal;
            font-size: 16px;
        }
        .alert {
            font-weight: normal;
        }
    </style>
@endpush

@section('content')
    <div class="container help-container">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                @yield('help:content')
            </div>
        </div>
    </div>
@endsection
