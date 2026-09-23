@extends('layouts.app')
@section('title', 'অ্যাকাউন্ট খুলুন')

@section('content')
<div class="max-w-[420px] mx-auto px-6 pt-16 pb-24 nf-fade">
    <h1 class="text-[28px] font-semibold tracking-tight mb-1.5 text-center">অ্যাকাউন্ট খুলুন</h1>
    <p class="text-sm text-gray-500 text-center mb-8">Join the list for early access and faster checkout.</p>

    <form method="POST" action="{{ route('register') }}" class="bg-panel rounded-[22px] p-6">
        @csrf
        <div class="mb-4">
            <label class="nf-label">Full name</label>
            <input name="name" value="{{ old('name') }}" class="w-full h-[44px] border rounded-[7px] px-3.5 text-sm outline-none focus:border-[#3B2F2D] {{ $errors->has('name') ? 'border-red-400' : 'border-[#E9E4E0]' }}">
            @error('name')<div class="text-[13px] text-rose mt-1.5">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="nf-label">ইমেইল</label>
            <input name="email" value="{{ old('email') }}" class="nf-input nf-input-soft">
            @error('email')<div class="text-[13px] text-rose mt-1.5">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="nf-label">Phone <span class="text-gray-300">(optional)</span></label>
            <input name="phone" value="{{ old('phone') }}" class="nf-input nf-input-soft">
        </div>
        <div class="mb-4">
            <label class="nf-label">পাসওয়ার্ড</label>
            <input type="password" name="password" class="w-full h-[44px] border rounded-[7px] px-3.5 text-sm outline-none focus:border-[#3B2F2D] {{ $errors->has('password') ? 'border-red-400' : 'border-[#E9E4E0]' }}">
            @error('password')<div class="text-[13px] text-rose mt-1.5">{{ $message }}</div>@enderror
        </div>
        <div class="mb-5">
            <label class="nf-label">পাসওয়ার্ড নিশ্চিত করুন</label>
            <input type="password" name="password_confirmation" class="nf-input nf-input-soft">
        </div>
        <button class="w-full bg-espresso hover:bg-ink text-white rounded-full py-3.5 text-[15px] font-semibold">অ্যাকাউন্ট খুলুন</button>
    </form>
    <p class="text-center text-sm text-gray-500 mt-5">অ্যাকাউন্ট আছে? <a href="{{ route('login') }}" class="text-espresso font-semibold">লগইন করুন</a></p>
</div>
@endsection
