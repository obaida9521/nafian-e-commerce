@props(['title' => null, 'action' => null])
<div {{ $attributes->merge(['class' => 'bg-white rounded-[20px] p-5 sm:p-6 nf-shadow-soft']) }}>
    @if($title || $action)
        <div class="flex justify-between items-center gap-3 flex-wrap">
            @if($title)<div class="text-[17px] font-semibold">{{ $title }}</div>@endif
            @if($action)<div>{{ $action }}</div>@endif
        </div>
    @endif
    {{ $slot }}
</div>
