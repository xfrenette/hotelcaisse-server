<?php
/**
 * @var \App\ProductCategory $productCategory
 */
?>

@extends('spark::layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <h1>Catégorie "{{ $productCategory->name }}"</h1>
            <form method="post" action="{{route('productCategories.categories.update', ['category' => $productCategory])}}">
                {{csrf_field()}}
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="name" class="form-control" value="{{ $productCategory->name }}">
                </div>
                <div class="form-group">
                    <label>Catégorie parente</label>
                    <select class="form-control" name="parent">
                        @foreach($categoryOptions as $option)
                            @if ($option['value'] !== $productCategory->id)
                                <option value="{{ $option['value'] }}" {{ $option['value'] === $currentParent ? 'selected="selected"' : '' }}>{{ $option['label'] }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('productCategories.list') }}" class="btn btn-default">Annuler</a>
                @if($productCategory->products()->count() || $productCategory->categories()->count())
                    <span class="btn btn-danger" disabled="disabled" title="Cette catégorie ne peut pas être supprimée car elle n'est pas vide">Supprimer</span>
                    <div><small></small></div>
                @else
                    <a href="{{ route('productCategories.categories.delete', ['category' => $productCategory]) }}" class="btn btn-danger">Supprimer</a>
                @endif
            </form>
        </div>
    </div>
@endsection
