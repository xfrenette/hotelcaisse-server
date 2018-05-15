<?php
/**
 * @var \App\Product $product
 */
?>

@extends('spark::layouts.app')

@section('content')
    <script>
        function confirmDelete(event) {
        	if (!confirm("Voulez-vous vraiment supprimer ce produit (irréversible) ?")) {
        	    event.preventDefault();
            }
        }
    </script>
    <div class="container">
        <div class="row">
            <h1>
                @if ($product->exists)
                    Produit "{{ $product->fullName }}"
                @else
                    {{ $product->parent ? 'Nouvelle variante pour "' . $product->parent->name . '"' : 'Nouveau produit' }}
                @endif
            </h1>
            <form method="post" action="{{ $postUrl }}">
                {{csrf_field()}}
                <div class="form-group">
                    <label>
                        {{ $product->parent ? 'Nom de la variante' : 'Nom du produit' }}
                    </label>
                    <input type="text" name="name" class="form-control" value="{{ $product->name }}">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control" value="{{ $product->description}}">
                </div>
                @if ($product->variants()->count())
                    <div class="form-group">
                        <label>Variantes</label>
                        <ul>
                            @foreach($product->variants as $variant)
                                <li><a href="{{ route('productCategories.products.edit', ['product' => $variant]) }}">{{ $variant->name }} ({{$variant->price}}&nbsp;$ + taxes)</a></li>
                            @endforeach
                            <li>
                                <a href="{{ route('productCategories.products.create', ['parent' => $product->id]) }}" class="btn btn-default btn-sm">
                                    Ajouter une variante
                                </a>
                            </li>
                        </ul>
                    </div>
                @else
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Prix avant taxes</label>
                                <div class="input-group">
                                    <input type="text" name="price" class="form-control" value="{{ $product->price }}">
                                    <div class="input-group-addon">$</div>
                                </div>
                            </div>
                        </div>
                        @foreach($taxes as $tax)
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>{{ $tax['tax']->name }} *</label>
                                    <div class="input-group">
                                        <input type="text" name="taxes[<?= $tax['tax']->id ?>]" class="form-control" value="{{ is_null($tax['amount']) ? '' : number_format($tax['amount'], 2) }}">
                                        <div class="input-group-addon">$</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="col-md-3">
                            * Laisser vide pour une valeur de taxe par défaut. Mettre "0" si aucune taxe.
                        </div>
                    </div>
                    @if (!$product->parent)
                        <div class="form-group">
                            @if ($product->exists)
                                <a href="{{ route('productCategories.products.create', ['parent' => $product->id]) }}" class="btn btn-default">
                                    Définir une variante
                                </a>
                            @else
                                <span class="btn btn-default" disabled="disabled">
                                    Définir une variante
                                </span>
                                <div>
                                    <small>Enregistrez le produit avant de pouvoir créer des variantes</small>
                                </div>
                            @endif
                        </div>
                    @endif
                @endif
                <div class="form-group">

                </div>
                @if (!$product->parent)
                    <div class="form-group">
                        <label>Catégories où afficher ce produit</label>
                        @foreach($categoryOptions as $option)
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="categories[]" value="{{ $option['value'] }}" {{ $categoryIds->contains($option['value']) ? 'checked="checked"' : '' }}>
                                    {{ $option['label'] }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                @if($product->parent)
                    <a href="{{ route('productCategories.products.edit', ['product' => $product->parent]) }}" type="submit" class="btn btn-default">Annuler</a>
                @else
                    <a href="{{ route('productCategories.list') }}" class="btn btn-default">Annuler</a>
                @endif
                @if ($product->exists)
                    <a href="{{ route('productCategories.products.delete', ['product' => $product]) }}" class="btn btn-danger" onclick="confirmDelete(event)">Supprimer</a>
                @endif
            </form>
        </div>
    </div>
@endsection
