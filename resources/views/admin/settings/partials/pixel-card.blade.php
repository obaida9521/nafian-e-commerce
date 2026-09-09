{{-- props: $state, $enabledName, $bg, $title, $desc, $icon, $fields[[name,label,placeholder,value]] --}}
@php
    $field = 'w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 font-mono text-[13px] outline-none focus:border-wine-700 focus:ring-2 focus:ring-wine-700/10';
@endphp
<div class="bg-white border border-[#EADBC4] rounded-xl p-6">
    <div class="flex items-start justify-between gap-4 mb-4.5">
        <div class="flex gap-3.5">
            <div class="w-10 h-10 rounded-[9px] grid place-items-center flex-none" style="background:{{ $bg }};">{!! $icon !!}</div>
            <div><div class="text-[15px] font-semibold">{{ $title }}</div><div class="text-[13px] text-gray-400">{{ $desc }}</div></div>
        </div>
        @include('admin.settings.partials.toggle', ['state' => $state, 'name' => $enabledName])
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        @foreach($fields as [$name, $label, $placeholder, $value])
            <div><label class="block text-[13px] text-gray-500 mb-1.5">{{ $label }}</label><input name="{{ $name }}" value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}" class="{{ $field }}"></div>
        @endforeach
    </div>
</div>
