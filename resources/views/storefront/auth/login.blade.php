@extends('layouts.app')
@section('title', 'Sign in')

@section('content')
<div class="max-w-[420px] mx-auto px-6 pt-16 pb-24 nf-fade">
    <h1 class="text-[28px] font-semibold tracking-tight mb-1.5 text-center">Welcome back</h1>
    <p class="text-sm text-gray-500 text-center mb-8">Sign in to track orders and check out faster.</p>

    <form method="POST" action="{{ route('login') }}" class="bg-white border border-[#EADBC4] rounded-xl p-6">
        @csrf
        <div class="mb-4">
            <label class="block text-[13px] text-gray-500 mb-1.5">Email</label>
            <input name="email" value="{{ old('email') }}" class="w-full h-[44px] border rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a] {{ $errors->has('email') ? 'border-red-400' : 'border-[#EADBC4]' }}">
            @error('email')<div class="text-xs text-red-600 mt-1.5">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="block text-[13px] text-gray-500 mb-1.5">Password</label>
            <input type="password" name="password" class="w-full h-[44px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a]">
        </div>
        <label class="flex items-center gap-2 text-[13px] text-gray-500 mb-5"><input type="checkbox" name="remember" class="accent-[#691d2a]"> Remember me</label>
        <button class="w-full h-[46px] rounded-lg bg-[#691d2a] hover:bg-[#4d141e] text-white text-sm font-semibold">Sign in</button>
    </form>
    <p class="text-center text-sm text-gray-500 mt-5">New here? <a href="{{ route('register') }}" class="text-[#691d2a] font-semibold">Create an account</a></p>
</div>
@endsection
