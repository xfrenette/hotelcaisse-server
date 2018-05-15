@extends('spark::layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <h1>Nouvelle catégorie</h1>
            <form method="post" action="{{route('productCategories.categories.create')}}">
                {{csrf_field()}}
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Catégorie parente</label>
                    <select class="form-control" name="parent">
                        @foreach($categoryOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Créer</button>
            </form>
        </div>
    </div>
@endsection
