@props(['type' => 'info'])

@php
    $styles = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
    ][$type] ?? 'bg-blue-50 border-blue-200 text-blue-800';
@endphp

<div x-data="{ show: true }" x-show="show" x-cloak
    {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-lg border px-4 py-3 text-sm $styles"]) }}>
    <div class="flex-1">{{ $slot }}</div>
    <button type="button" @click="show = false" class="opacity-60 hover:opacity-100">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
