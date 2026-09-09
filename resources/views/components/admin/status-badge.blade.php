@props(['color' => 'gray', 'label' => ''])

@php
    $styles = [
        'amber' => 'bg-amber-100 text-amber-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'indigo' => 'bg-indigo-100 text-indigo-800',
        'purple' => 'bg-purple-100 text-purple-800',
        'green' => 'bg-green-100 text-green-800',
        'red' => 'bg-red-100 text-red-800',
        'gray' => 'bg-gray-100 text-gray-700',
    ][$color] ?? 'bg-gray-100 text-gray-700';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium $styles"]) }}>
    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
    {{ $label ?: $slot }}
</span>
