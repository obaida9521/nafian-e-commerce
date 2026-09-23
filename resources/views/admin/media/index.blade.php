@extends('layouts.admin')
@section('title', 'মিডিয়া')

@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $items */
    $formatSize = fn (int $bytes) => $bytes >= 1048576 ? number_format($bytes / 1048576, 1).' MB' : max(1, (int) round($bytes / 1024)).' KB';
    $query = fn (array $merge) => array_filter(array_merge(['source' => $source, 'search' => $search], $merge), fn ($v) => filled($v));
@endphp

@section('content')
<div x-data="{
        active: null,
        dims: '',
        open(item) { this.active = item; this.dims = ''; },
        copy(text) { navigator.clipboard?.writeText(text).then(() => window.adminToast?.('success', 'লিংক কপি হয়েছে।')); },
     }"
     @keydown.escape.window="active = null">

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-[22px] font-semibold">মিডিয়া</h1>
            <p class="text-[13.5px] text-muted">স্টোরের সব ছবি এক জায়গায় — মোট {{ bn_digits($counts['all']) }}টি</p>
        </div>
        @adminCan('media')
            <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="w-full sm:w-auto">
                @csrf
                <label class="h-[42px] sm:h-10 w-full sm:w-auto px-4.5 rounded-lg bg-wine-700 hover:bg-[#2A2220] text-white text-sm font-semibold inline-flex items-center justify-center gap-2 cursor-pointer">
                    <x-ui.icon name="plus" :size="16" />ছবি আপলোড
                    <input type="file" name="files[]" multiple accept="image/*" class="hidden" data-image-editor data-max-width="1600" @change="$el.form.submit()">
                </label>
            </form>
        @endadminCan
    </div>

    <div class="flex flex-col sm:flex-row gap-2.5 sm:items-center mb-4">
        <form method="GET" class="relative sm:w-72">
            @if($source)<input type="hidden" name="source" value="{{ $source }}">@endif
            <x-ui.icon name="search" :size="16" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted" />
            <input type="search" name="search" value="{{ $search }}" placeholder="নাম বা পণ্য দিয়ে খুঁজুন…"
                   class="w-full h-[42px] sm:h-10 rounded-lg border border-[#E9E4E0] bg-white pl-10 pr-3 text-sm outline-none focus:border-wine-700">
        </form>
        <div class="flex gap-1.5 overflow-x-auto [scrollbar-width:none] -mx-5 px-5 sm:mx-0 sm:px-0">
            @foreach(['' => 'সব'] + $sources as $key => $label)
                <a href="{{ route('admin.media.index', $query(['source' => $key ?: null, 'page' => null])) }}"
                   @class(['flex-none inline-flex items-center gap-1.5 rounded-full px-3.5 py-2 text-[13px] font-medium',
                           'bg-mocha text-white' => ($source ?? '') === $key, 'bg-white border border-[#E9E4E0] text-ink' => ($source ?? '') !== $key])>
                    {{ $label }} <span class="opacity-60">{{ bn_digits($counts[$key ?: 'all'] ?? 0) }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Grid --}}
    @if($items->isEmpty())
        <div class="bg-white border border-[#E9E4E0] rounded-2xl px-5 py-16 text-center text-muted">
            কোনো ছবি পাওয়া যায়নি।
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            @foreach($items as $item)
                <button type="button" @click="open(@js($item))"
                        class="group text-left bg-white border border-[#E9E4E0] rounded-2xl overflow-hidden hover:border-wine-700 hover:shadow-md transition">
                    <div class="relative aspect-square bg-sand">
                        <img src="{{ $item['thumb'] }}" alt="{{ $item['name'] }}" loading="lazy" onerror="this.remove()" class="w-full h-full object-cover">
                        <span class="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-[10.5px] font-semibold text-cocoa">{{ $item['source_label'] }}</span>
                    </div>
                    <div class="px-3 py-2.5">
                        <div class="text-[13px] font-semibold truncate">{{ $item['owner'] }}</div>
                        <div class="text-[11.5px] text-muted truncate">{{ $item['name'] }} · {{ $formatSize($item['size']) }}</div>
                    </div>
                </button>
            @endforeach
        </div>
        <div class="mt-5">{{ $items->links() }}</div>
    @endif

    {{-- Details drawer --}}
    <div x-show="active" x-cloak class="fixed inset-0 z-[80]">
        <div class="absolute inset-0 bg-[#1A1413]/40" @click="active = null" x-show="active" x-transition.opacity></div>
        <aside x-show="active"
               x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full sm:translate-y-0 sm:translate-x-full" x-transition:enter-end="translate-y-0 sm:translate-x-0"
               x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 sm:translate-x-0" x-transition:leave-end="translate-y-full sm:translate-y-0 sm:translate-x-full"
               class="absolute inset-x-0 bottom-0 sm:inset-y-0 sm:left-auto sm:right-0 sm:w-[440px] max-h-[92vh] sm:max-h-none bg-white rounded-t-[22px] sm:rounded-none shadow-2xl flex flex-col">
            <template x-if="active">
                <div class="flex flex-col min-h-0 h-full">
                    <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-hair">
                        <div class="min-w-0">
                            <div class="text-[16px] font-semibold truncate" x-text="active.owner"></div>
                            <div class="text-[12.5px] text-muted" x-text="active.source_label"></div>
                        </div>
                        <button type="button" @click="active = null" class="w-9 h-9 rounded-full grid place-items-center text-muted hover:bg-sand" aria-label="বন্ধ করুন"><x-ui.icon name="x" :size="17" /></button>
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto p-5">
                        <div class="rounded-2xl overflow-hidden bg-[repeating-conic-gradient(#efebe8_0%_25%,#f8f6f4_0%_50%)] bg-[length:18px_18px] grid place-items-center">
                            <img :src="active.url" alt="" class="max-h-[46vh] w-auto object-contain" @load="dims = $el.naturalWidth + ' × ' + $el.naturalHeight + ' px'">
                        </div>
                        <dl class="mt-4 grid grid-cols-[96px_1fr] gap-y-2 text-[13.5px]">
                            <dt class="text-muted">ফাইল</dt><dd class="break-all" x-text="active.name"></dd>
                            <dt class="text-muted">মাপ</dt><dd x-text="dims || '—'"></dd>
                            <dt class="text-muted">সাইজ</dt><dd x-text="active.size >= 1048576 ? (active.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(active.size / 1024)) + ' KB'"></dd>
                            <dt class="text-muted">ধরন</dt><dd x-text="active.mime || '—'"></dd>
                            <dt class="text-muted">তারিখ</dt><dd x-text="active.created_at ? new Date(active.created_at).toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' }) : '—'"></dd>
                        </dl>
                    </div>
                    <div class="px-5 py-4 pb-[max(16px,env(safe-area-inset-bottom))] border-t border-hair flex flex-wrap gap-2">
                        <button type="button" @click="copy(active.url)" class="flex-1 h-10 rounded-xl border border-[#E9E4E0] text-[13.5px] font-semibold inline-flex items-center justify-center gap-1.5 hover:border-wine-700">
                            <x-ui.icon name="external" :size="15" />লিংক কপি
                        </button>
                        <template x-if="active.owner_url">
                            <a :href="active.owner_url" class="flex-1 h-10 rounded-xl border border-[#E9E4E0] text-[13.5px] font-semibold inline-flex items-center justify-center gap-1.5 hover:border-wine-700">
                                <x-ui.icon name="edit" :size="15" />যেখানে ব্যবহৃত
                            </a>
                        </template>
                        @adminCan('media')
                            <template x-if="active.deletable">
                                <form method="POST" :action="@js(route('admin.media.destroy', ['key' => '__KEY__'])).replace('__KEY__', active.key)"
                                      class="w-full" @submit="if (! confirm('ছবিটি মিডিয়া থেকে মুছে ফেলবেন?')) $event.preventDefault()">
                                    @csrf @method('DELETE')
                                    <button class="w-full h-10 rounded-xl bg-red-50 text-red-600 text-[13.5px] font-semibold inline-flex items-center justify-center gap-1.5 hover:bg-red-100">
                                        <x-ui.icon name="trash" :size="15" />মুছে ফেলুন
                                    </button>
                                </form>
                            </template>
                        @endadminCan
                        <p x-show="! active.deletable" class="w-full text-[12.5px] text-muted">এই ছবিটি একটি <span x-text="active.source_label"></span>-এ ব্যবহৃত — সেখান থেকে বদলান বা সরান।</p>
                    </div>
                </div>
            </template>
        </aside>
    </div>
</div>
@endsection
