@extends('layouts.admin')

@section('title', 'Edit Product')

@section('content')
<div class="max-w-[1080px] mx-auto">
    <a href="{{ route('admin.products.index') }}" class="text-[13px] text-gray-500 mb-3.5 inline-block">← Back to products</a>
    <h2 class="text-2xl font-semibold mb-6">Edit product</h2>
    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.products._form')
    </form>
</div>
@endsection
