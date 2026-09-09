@props([
    'title' => '',
    'value' => '',
    'change' => null,
    'icon' => null,
    'color' => 'wine',
])

@php
    $iconBg = [
        'wine' => 'bg-wine-100 text-wine-700',
        'green' => 'bg-green-100 text-green-700',
        'blue' => 'bg-blue-100 text-blue-700',
        'amber' => 'bg-amber-100 text-amber-700',
    ][$color] ?? 'bg-wine-100 text-wine-700';
@endphp

<div class="rounded-xl bg-white border border-cream-300/60 p-5 shadow-sm">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-lg grid place-items-center shrink-0 {{ $iconBg }}">
            @if ($icon)
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                </svg>
            @endif
        </div>
        <div class="min-w-0">
            <p class="text-sm text-gray-500">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-900 truncate">{{ $value }}</p>
        </div>
    </div>
    @if ($change)
        <p class="mt-3 text-xs font-medium {{ str_starts_with($change, '-') ? 'text-red-600' : 'text-green-600' }}">
            {{ $change }}
        </p>
    @endif
</div>
