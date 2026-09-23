{{-- Cards for the home "দেখতে থাকুন" grid (first page, and each "আরও দেখুন" page via store.discover). --}}
@foreach($products as $product)
    @include('storefront.partials.product-card', ['product' => $product, 'reviewWord' => true])
@endforeach
