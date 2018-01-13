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
        p.image {
            text-align: center;
            border: 1px solid #636b6f;
            padding: 15px;
            margin: 30px auto;
            width: 75%;
        }

        p.image img {
            display: inline-block;
            max-width: 100%;
            height: auto;
        }
        .toc ul {
            list-style-type: none;
            font-size: 14px;
            line-height: 1.2;
            padding-left: 0;
        }
        .toc ul ul {
            padding-left: 20px;
        }
        .toc li {
            margin-bottom: 10px;
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
