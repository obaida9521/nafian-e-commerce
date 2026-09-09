@extends('layouts.app')
@section('title', 'My account')

@section('content')
<div class="max-w-[1080px] mx-auto px-4 sm:px-6 pt-7 pb-20 nf-fade">
    <h1 class="text-[28px] font-semibold tracking-tight mb-6">My account</h1>
    <div class="grid lg:grid-cols-[200px_1fr] gap-8 items-start">
        <aside class="lg:sticky lg:top-[84px] flex lg:flex-col gap-1 flex-wrap">
            @php
                $tabs = [
                    'orders' => ['My orders', route('store.account.orders')],
                    'profile' => ['Profile', route('store.account.profile')],
                    'addresses' => ['Address book', route('store.account.addresses')],
                ];
            @endphp
            @foreach($tabs as $key => [$label, $url])
                <a href="{{ $url }}" class="px-3.5 py-2.5 rounded-lg text-sm font-medium {{ ($tab ?? '') === $key ? 'bg-[#F8EAD6] text-[#691d2a]' : 'text-gray-700 hover:bg-[#F8EAD6]/60' }}">{{ $label }}</a>
            @endforeach
            <form method="POST" action="{{ route('logout') }}" class="lg:mt-2">
                @csrf
                <button class="px-3.5 py-2.5 rounded-lg text-sm font-medium text-gray-500 hover:text-red-600">Sign out</button>
            </form>
        </aside>
        <div>
            @yield('account')
        </div>
    </div>
</div>
@endsection
