@extends('layouts.app')
@section('title', 'আমার অ্যাকাউন্ট')

@section('content')
<div class="max-w-[1080px] mx-auto px-4 sm:px-6 pt-7 pb-20 nf-fade">
    <h1 class="font-display text-[32px] mb-6">আমার অ্যাকাউন্ট</h1>
    <div class="grid lg:grid-cols-[200px_1fr] gap-8 items-start">
        <aside class="lg:sticky lg:top-[84px] flex lg:flex-col gap-1 flex-wrap">
            @php
                $tabs = [
                    'orders' => ['আমার অর্ডার', route('store.account.orders')],
                    'profile' => ['প্রোফাইল', route('store.account.profile')],
                    'addresses' => ['ঠিকানা', route('store.account.addresses')],
                ];
            @endphp
            @foreach($tabs as $key => [$label, $url])
                <a href="{{ $url }}" class="px-3.5 py-2.5 rounded-lg text-sm font-medium {{ ($tab ?? '') === $key ? 'bg-[#F3EFEC] text-espresso' : 'text-gray-700 hover:bg-[#F3EFEC]/60' }}">{{ $label }}</a>
            @endforeach
            <form method="POST" action="{{ route('logout') }}" class="lg:mt-2">
                @csrf
                <button class="px-3.5 py-2.5 rounded-lg text-sm font-medium text-gray-500 hover:text-red-600">সাইন আউট</button>
            </form>
        </aside>
        <div>
            @yield('account')
        </div>
    </div>
</div>
@endsection
