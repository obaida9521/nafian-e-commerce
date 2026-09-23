@extends('layouts.app')
@section('title', 'লগইন')

@section('content')
<div class="max-w-[420px] mx-auto px-6 pt-16 pb-24 nf-fade">
    <h1 class="font-display text-[32px] mb-1.5 text-center">আবার স্বাগতম</h1>
    <p class="text-[15px] text-muted text-center mb-8">লগইন করলে অর্ডার ট্র্যাক করা ও দ্রুত চেকআউট করা যায়।</p>

    <form method="POST" action="{{ route('login') }}" class="bg-panel rounded-[22px] p-6">
        @csrf
        <div class="mb-4">
            <label class="nf-label">ইমেইল</label>
            <input name="email" value="{{ old('email') }}" class="nf-input nf-input-soft">
            @error('email')<div class="text-[13px] text-rose mt-1.5">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="nf-label">পাসওয়ার্ড</label>
            <input type="password" name="password" class="nf-input nf-input-soft">
        </div>
        <label class="flex items-center gap-2 text-[13.5px] text-muted mb-5"><input type="checkbox" name="remember" class="accent-espresso"> মনে রাখুন</label>
        <button class="w-full bg-espresso hover:bg-ink text-white rounded-full py-3.5 text-[15px] font-semibold">লগইন করুন</button>
    </form>
    <p class="text-center text-[14.5px] text-muted mt-5">নতুন? <a href="{{ route('register') }}" class="text-espresso font-semibold">অ্যাকাউন্ট খুলুন</a></p>
</div>
@endsection
