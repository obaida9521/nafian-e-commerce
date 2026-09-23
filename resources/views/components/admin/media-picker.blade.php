{{-- Media picker modal (driven by the `mediaPicker` Alpine store in resources/js/media-picker.js). --}}
<div id="media-picker" data-index-url="{{ route('admin.media.index') }}" data-store-url="{{ route('admin.media.store') }}"
     x-data x-show="$store.mediaPicker.open" x-cloak
     @keydown.escape.window="$store.mediaPicker.open && $store.mediaPicker.cancel()"
     class="fixed inset-0 z-[110] flex items-end sm:items-center justify-center sm:p-5">
    <div class="absolute inset-0 bg-[#1A1413]/50" @click="$store.mediaPicker.cancel()" x-show="$store.mediaPicker.open" x-transition.opacity></div>

    <div x-show="$store.mediaPicker.open"
         x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="relative w-full sm:w-[min(980px,100%)] h-[88vh] sm:h-[min(720px,90vh)] bg-white rounded-t-[22px] sm:rounded-[20px] shadow-2xl flex flex-col overflow-hidden"
         role="dialog" aria-modal="true" :aria-label="$store.mediaPicker.title">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-5 pt-4 pb-3 border-b border-hair">
            <div class="min-w-0 flex-1">
                <div class="text-[17px] font-semibold truncate" x-text="$store.mediaPicker.title"></div>
                <div class="text-[12.5px] text-muted" x-text="$store.mediaPicker.multiple ? 'একাধিক ছবি বেছে নেওয়া যাবে' : 'একটি ছবি বেছে নিন'"></div>
            </div>
            <label class="flex-none inline-flex items-center gap-1.5 h-10 px-4 rounded-full bg-sand text-espresso text-[13.5px] font-semibold cursor-pointer hover:bg-sand-2"
                   :class="$store.mediaPicker.uploading && 'opacity-60 pointer-events-none'">
                <x-ui.icon name="plus" :size="15" />
                <span x-text="$store.mediaPicker.uploading ? 'আপলোড হচ্ছে…' : 'নতুন আপলোড'"></span>
                <input type="file" accept="image/*" multiple class="hidden" data-image-editor data-max-width="1600"
                       @change="$store.mediaPicker.upload($event.target)">
            </label>
            <button type="button" @click="$store.mediaPicker.cancel()" class="flex-none w-10 h-10 rounded-full grid place-items-center text-muted hover:bg-sand" aria-label="বন্ধ করুন">
                <x-ui.icon name="x" :size="18" />
            </button>
        </div>

        {{-- Search + source filter --}}
        <div class="px-5 py-3 flex flex-col sm:flex-row gap-2.5 sm:items-center">
            <div class="relative sm:w-64">
                <x-ui.icon name="search" :size="16" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted" />
                <input type="search" x-model="$store.mediaPicker.search" @input.debounce.350ms="$store.mediaPicker.load(true)" placeholder="নাম বা পণ্য দিয়ে খুঁজুন…"
                       class="w-full h-10 rounded-full bg-canvas pl-10 pr-4 text-[14px] outline-none focus:shadow-[inset_0_0_0_2px_#3B2F2D]">
            </div>
            <div class="flex gap-1.5 overflow-x-auto [scrollbar-width:none] -mx-5 px-5 sm:mx-0 sm:px-0">
                @foreach(['' => 'সব'] + \App\Services\MediaLibraryService::SOURCES as $key => $label)
                    <button type="button" @click="$store.mediaPicker.filter(@js($key))"
                            class="flex-none rounded-full px-3.5 py-2 text-[13px] font-medium"
                            :class="$store.mediaPicker.source === @js($key) ? 'bg-mocha text-white' : 'bg-hair text-ink'">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- Grid --}}
        <div class="flex-1 min-h-0 overflow-y-auto px-5 pb-4">
            <p x-show="$store.mediaPicker.error" x-cloak class="mb-3 rounded-xl bg-rose-soft text-rose px-4 py-2.5 text-[13.5px]" x-text="$store.mediaPicker.error"></p>

            <div class="grid grid-cols-3 sm:grid-cols-5 lg:grid-cols-6 gap-2.5">
                <template x-for="item in $store.mediaPicker.items" :key="item.key">
                    <button type="button" @click="$store.mediaPicker.toggle(item)" @dblclick="if (! $store.mediaPicker.isSelected(item)) $store.mediaPicker.toggle(item); $store.mediaPicker.confirm()"
                            class="group relative aspect-square rounded-xl overflow-hidden bg-sand text-left transition"
                            :class="$store.mediaPicker.isSelected(item) ? 'ring-[3px] ring-accent ring-offset-2' : 'hover:opacity-90'"
                            :title="item.name">
                        <img :src="item.thumb" :alt="item.name" loading="lazy" class="w-full h-full object-cover">
                        <span class="absolute left-1.5 top-1.5 rounded-full bg-white/90 px-2 py-0.5 text-[10.5px] font-semibold text-cocoa" x-text="item.source_label"></span>
                        <span x-show="$store.mediaPicker.isSelected(item)" class="absolute right-1.5 top-1.5 w-6 h-6 rounded-full bg-accent text-white grid place-items-center shadow">
                            <x-ui.icon name="check" :size="14" :stroke="2.6" />
                        </span>
                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent px-2 pb-1.5 pt-4 text-[11px] text-white truncate" x-text="item.owner"></span>
                    </button>
                </template>
            </div>

            <div x-show="! $store.mediaPicker.loading && ! $store.mediaPicker.items.length" class="py-16 text-center text-muted text-[14px]">
                কোনো ছবি পাওয়া যায়নি। উপরে “নতুন আপলোড” দিয়ে যোগ করুন।
            </div>
            <div x-show="$store.mediaPicker.loading" class="py-6 text-center text-muted text-[13.5px]">লোড হচ্ছে…</div>
            <div x-show="! $store.mediaPicker.loading && $store.mediaPicker.page < $store.mediaPicker.lastPage" class="pt-4 text-center">
                <button type="button" @click="$store.mediaPicker.load()" class="rounded-full bg-sand px-5 py-2.5 text-[13.5px] font-semibold text-espresso">আরও দেখুন</button>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center gap-3 px-5 py-3.5 pb-[max(14px,env(safe-area-inset-bottom))] border-t border-hair">
            <div class="flex-1 min-w-0 text-[13.5px] text-muted">
                <span x-text="bnNumber($store.mediaPicker.selected.length)"></span>টি বেছে নেওয়া হয়েছে
            </div>
            <button type="button" @click="$store.mediaPicker.cancel()" class="h-11 px-5 rounded-full bg-hair text-[14px] font-semibold inline-flex items-center gap-1.5"><x-ui.icon name="x" :size="15" />বাতিল</button>
            <button type="button" @click="$store.mediaPicker.confirm()" :disabled="! $store.mediaPicker.selected.length"
                    class="h-11 px-6 rounded-full bg-mocha text-white text-[14px] font-semibold inline-flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed"><x-ui.icon name="check" :size="15" />বেছে নিন</button>
        </div>
    </div>
</div>
