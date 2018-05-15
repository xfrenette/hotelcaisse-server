<li>
    @if ($category['isRoot'])
        <span class="glyphicon glyphicon-folder-open"></span>
        <strong>Racine</strong>
    @else
        <a href="{{ route('productCategories.categories.edit', ['category' => $category['category']]) }}">
            <span class="glyphicon glyphicon-folder-open"></span>
            <strong>{{ $category['category']->name }}</strong>
        </a>
    @endif

    <ul>
        @foreach($category['category']->products as $product)
            <li><a href="{{ route('productCategories.products.edit', ['product' => $product]) }}">
                <span class="glyphicon glyphicon-unchecked"></span>
                {{ $product->name }}
            </a></li>
        @endforeach

        @if($category['children'] !== null)
            @foreach($category['children'] as $subCategory)
                @include('partials.productCategory', ['category' => $subCategory])
            @endforeach
        @endif
    </ul>
</li>
