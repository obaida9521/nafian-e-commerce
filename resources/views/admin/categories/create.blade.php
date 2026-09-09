@extends('layouts.admin')

@section('title', 'New Category')

@section('content')
<div class="space-y-5">
    <x-ui.breadcrumb :items="['Categories' => route('admin.categories.index'), 'New' => null]" />
    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.categories._form')
    </form>
</div>
@endsection
