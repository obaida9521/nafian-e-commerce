@extends('layouts.admin')

@section('title', 'New Product')

@section('content')
<div class="max-w-[1080px] mx-auto">
    <a href="{{ route('admin.products.index') }}" class="text-[13px] text-gray-500 mb-3.5 inline-block">← Back to products</a>
    <h2 class="text-2xl font-semibold mb-6">New product</h2>
    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.products._form')
    </form>
</div>
@endsection
