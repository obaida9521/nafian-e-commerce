@props(['status' => null, 'color' => null, 'label' => ''])
@php
    /** @var \App\Enums\OrderStatus|null $status */
    $pill = $status instanceof \App\Enums\OrderStatus
        ? $status->pill()
        : match ($color) {
            'green' => ['bg' => '#F1F4F1', 'color' => '#3F5A42'],
            'red' => ['bg' => '#F7EFEE', 'color' => '#8A5A52'],
            'blue', 'indigo' => ['bg' => '#EDF2F7', 'color' => '#3C5A78'],
            default => ['bg' => '#F3EFEC', 'color' => '#3E3532'],
        };
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-3 py-1.5 text-[13px] font-semibold']) }}
      style="background:{{ $pill['bg'] }};color:{{ $pill['color'] }};">
    {{ $status?->labelBn() ?: ($label ?: $slot) }}
</span>
