@extends('layouts.auth')

@section('title', 'অ্যাডমিন লগইন')

@section('content')
<div class="min-h-screen grid desk:grid-cols-2">
    {{-- Brand panel --}}
    <div class="hidden desk:flex flex-col justify-between p-12 relative overflow-hidden text-[#EDE6E1]" style="background:linear-gradient(140deg,#3B2F2D,#33444F);">
        <div class="absolute inset-0 nf-drift" style="background:linear-gradient(120deg,#3B2F2D 0%,#5A4842 45%,#2F3B47 100%);opacity:.6;"></div>
        <div class="relative">
            <div class="font-display text-[26px] tracking-[0.32em] text-white">NAFIAN</div>
            <div class="mt-1.5 text-[13px] text-[#A8C2DA] tracking-[0.2em]">অ্যাডমিন প্যানেল</div>
        </div>

        <div class="relative max-w-md">
            <h1 class="font-display text-[42px] leading-[1.2] text-white">স্টোর চালান এক জায়গা থেকে</h1>
            <p class="mt-4 text-[16.5px] leading-[1.85] text-[#C3B9B4]">অর্ডার, ইনভেন্টরি, কুপন আর রিপোর্ট — সবকিছু একসাথে।</p>
        </div>

        <div class="relative text-[13.5px] text-[#C3B9B4]">© {{ bn_digits(date('Y')) }} {{ config('shop.name') }}. সর্বস্বত্ব সংরক্ষিত।</div>
    </div>

    {{-- Form panel --}}
    <div class="flex items-center justify-center p-6 sm:p-12 bg-canvas">
        <div class="w-full max-w-sm">
            <div class="desk:hidden mb-8">
                <div class="font-display text-[22px] tracking-[0.3em] text-espresso">NAFIAN</div>
                <div class="mt-1 text-[12.5px] text-muted">অ্যাডমিন প্যানেল</div>
            </div>

            <h2 class="font-display text-[30px]">আবার স্বাগতম</h2>
            <p class="mt-1.5 text-[15px] text-muted">অ্যাডমিন অ্যাকাউন্টে লগইন করুন।</p>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl bg-rose-soft px-5 py-3.5 text-[14.5px] text-rose">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="mt-6 flex flex-col gap-4">
                @csrf

                <div>
                    <label class="nf-label" for="email">ইমেইল</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="nf-input" placeholder="admin@shop.com">
                </div>

                <div x-data="{ show: false }">
                    <label class="nf-label" for="password">পাসওয়ার্ড</label>
                    <div class="relative">
                        <input id="password" name="password" :type="show ? 'text' : 'password'" required class="nf-input pr-16" placeholder="••••••••">
                        <button type="button" @click="show = ! show" tabindex="-1" class="absolute inset-y-0 right-0 px-4 text-[13px] text-muted hover:text-espresso">
                            <span x-show="! show">দেখুন</span>
                            <span x-show="show" x-cloak>লুকান</span>
                        </button>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-[14px] text-cocoa">
                    <input type="checkbox" name="remember" class="accent-espresso"> মনে রাখুন
                </label>

                <button type="submit" class="w-full bg-mocha hover:bg-ink text-white rounded-full py-3.5 text-[15px] font-semibold">লগইন করুন</button>
            </form>

            <p class="mt-6 text-[12.5px] text-muted text-center">ডেমো: admin@shop.com / password</p>
        </div>
    </div>
</div>
@endsection
