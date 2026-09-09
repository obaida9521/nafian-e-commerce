@extends('layouts.app')
@section('title', 'Create account')

@section('content')
<div class="max-w-[420px] mx-auto px-6 pt-16 pb-24 nf-fade">
    <h1 class="text-[28px] font-semibold tracking-tight mb-1.5 text-center">Create your account</h1>
    <p class="text-sm text-gray-500 text-center mb-8">Join the list for early access and faster checkout.</p>

    <form method="POST" action="{{ route('register') }}" class="bg-white border border-[#EADBC4] rounded-xl p-6">
        @csrf
        <div class="mb-4">
            <label class="block text-[13px] text-gray-500 mb-1.5">Full name</label>
            <input name="name" value="{{ old('name') }}" class="w-full h-[44px] border rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a] {{ $errors->has('name') ? 'border-red-400' : 'border-[#EADBC4]' }}">
            @error('name')<div class="text-xs text-red-600 mt-1.5">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="block text-[13px] text-gray-500 mb-1.5">Email</label>
            <input name="email" value="{{ old('email') }}" class="w-full h-[44px] border rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a] {{ $errors->has('email') ? 'border-red-400' : 'border-[#EADBC4]' }}">
            @error('email')<div class="text-xs text-red-600 mt-1.5">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="block text-[13px] text-gray-500 mb-1.5">Phone <span class="text-gray-300">(optional)</span></label>
            <input name="phone" value="{{ old('phone') }}" class="w-full h-[44px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a]">
        </div>
        <div class="mb-4">
            <label class="block text-[13px] text-gray-500 mb-1.5">Password</label>
            <input type="password" name="password" class="w-full h-[44px] border rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a] {{ $errors->has('password') ? 'border-red-400' : 'border-[#EADBC4]' }}">
            @error('password')<div class="text-xs text-red-600 mt-1.5">{{ $message }}</div>@enderror
        </div>
        <div class="mb-5">
            <label class="block text-[13px] text-gray-500 mb-1.5">Confirm password</label>
            <input type="password" name="password_confirmation" class="w-full h-[44px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a]">
        </div>
        <button class="w-full h-[46px] rounded-lg bg-[#691d2a] hover:bg-[#4d141e] text-white text-sm font-semibold">Create account</button>
    </form>
    <p class="text-center text-sm text-gray-500 mt-5">Already have an account? <a href="{{ route('login') }}" class="text-[#691d2a] font-semibold">Sign in</a></p>
</div>
@endsection
