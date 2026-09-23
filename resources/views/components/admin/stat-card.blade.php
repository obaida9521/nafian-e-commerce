@props(['title' => '', 'value' => '', 'change' => null, 'hint' => null])
@php
    // $change is a signed percentage (null when there is nothing to compare with).
    $up = $change !== null && $change >= 0;
@endphp
<div {{ $attributes->merge(['class' => 'bg-white rounded-[20px] p-[22px] nf-shadow-soft']) }}>
    <div class="text-[13.5px] font-medium text-muted">{{ $title }}</div>
    <div class="mt-2 text-[26px] sm:text-[30px] font-semibold text-ink">{{ $value }}</div>
    @if($change !== null)
        <div @class(['mt-1.5 text-[13.5px] font-medium', 'text-accent' => $up, 'text-rose' => ! $up])>
            {{ $up ? '↑' : '↓' }} {{ bn_digits(number_format(abs($change), 1)) }}%{{ $hint ? ' '.$hint : '' }}
        </div>
    @elseif($hint)
        <div class="mt-1.5 text-[13.5px] font-medium text-muted">{{ $hint }}</div>
    @endif
</div>
