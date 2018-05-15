@extends('spark::layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-sm-8">
                <h1>Gestion des produits</h1>
            </div>
            <div class="col-sm-4 text-right">
                <a class="btn btn-default" href="{{ route('productCategories.products.new') }}">Nouveau produit</a>
                <a class="btn btn-default" href="{{ route('productCategories.categories.new') }}">Nouvelle catégorie</a>
            </div>
        </div>
        <div class="panel panel-default">
            <ul>
                @foreach($productCategories as $category)
                    @include('partials.productCategory', ['category' => $category])
                @endforeach
            </ul>
        </div>
    </div>
@endsection
