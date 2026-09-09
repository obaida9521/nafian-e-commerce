@extends('layouts.auth')

@section('title', 'Admin Login')

@section('content')
<div class="min-h-screen grid lg:grid-cols-2">
    {{-- Brand panel --}}
    <div class="hidden lg:flex flex-col justify-between bg-wine-700 text-cream-100 p-12 relative overflow-hidden">
        <div class="absolute -right-24 -top-24 w-96 h-96 rounded-full bg-wine-600/40"></div>
        <div class="absolute -left-16 bottom-10 w-72 h-72 rounded-full bg-wine-800/50"></div>

        <div class="relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-cream-100 text-wine-700 grid place-items-center font-bold text-xl">N</div>
                <span class="text-2xl font-semibold tracking-tight">{{ config('shop.name') }}</span>
            </div>
        </div>

        <div class="relative z-10 max-w-md">
            <h1 class="text-4xl font-bold leading-tight">Admin Control Center</h1>
            <p class="mt-4 text-cream-200/90 text-lg">Manage products, orders, inventory and reports — all in one elegant back office.</p>
        </div>

        <div class="relative z-10 text-cream-200/70 text-sm">
            &copy; {{ date('Y') }} {{ config('shop.name') }}. All rights reserved.
        </div>
    </div>

    {{-- Form panel --}}
    <div class="flex items-center justify-center p-6 sm:p-12 bg-cream-100">
        <div class="w-full max-w-sm">
            <div class="lg:hidden flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-wine-700 text-cream-100 grid place-items-center font-bold text-lg">N</div>
                <span class="text-xl font-semibold text-wine-700">{{ config('shop.name') }}</span>
            </div>

            <h2 class="text-2xl font-bold text-gray-900">Welcome back</h2>
            <p class="mt-1 text-gray-500 text-sm">Sign in to your admin account.</p>

            @if ($errors->any())
                <div class="mt-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none transition"
                        placeholder="admin@shop.com">
                </div>

                <div x-data="{ show: false }">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="mt-1.5 relative">
                        <input id="password" name="password" :type="show ? 'text' : 'password'" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 pr-11 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none transition"
                            placeholder="••••••••">
                        <button type="button" @click="show = !show" tabindex="-1"
                            class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-600">
                            <span x-show="!show">Show</span>
                            <span x-show="show" x-cloak>Hide</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-wine-700 focus:ring-wine-500/30">
                        Remember me
                    </label>
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-wine-700 px-4 py-2.5 text-sm font-semibold text-cream-100 hover:bg-wine-800 focus:ring-2 focus:ring-wine-500/40 transition">
                    Sign in
                </button>
            </form>

            <p class="mt-6 text-xs text-gray-400 text-center">
                Demo: admin@shop.com / password
            </p>
        </div>
    </div>
</div>
@endsection
