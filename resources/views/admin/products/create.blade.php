@extends('layouts.admin')

@section('title', 'নতুন পণ্য')

@section('content')
<div class="max-w-[1080px] mx-auto">
    <a href="{{ route('admin.products.index') }}" class="text-[13px] text-gray-500 mb-3.5 inline-flex items-center gap-1.5 hover:text-ink"><x-ui.icon name="arrow-left" :size="14" />Back to products</a>
    <h2 class="text-2xl font-semibold mb-6">New product</h2>
    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.products._form')
    </form>
</div>
@endsection
