@extends('layouts.admin')

@section('title', 'Edit Category')

@section('content')
<div class="space-y-5">
    <x-ui.breadcrumb :items="['Categories' => route('admin.categories.index'), $category->name => null]" />
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.categories._form')
    </form>
</div>
@endsection
