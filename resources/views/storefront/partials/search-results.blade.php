{{-- Shared body of the search dropdown (desktop) and search screen (mobile); expects an nfSearch scope. --}}
<template x-if="q.trim() && suggestions.length">
    <div class="mb-6">
        <div class="text-[12px] font-medium tracking-[0.14em] text-muted">সাজেশন</div>
        <div class="mt-3 flex flex-col gap-[13px] text-[15.5px]">
            <template x-for="s in suggestions" :key="s">
                <button type="button" @click="go(s)" class="text-left" x-html="highlight(s)"></button>
            </template>
        </div>
    </div>
</template>

<template x-if="q.trim() && products.length">
    <div class="mb-6">
        <div class="text-[12px] font-medium tracking-[0.14em] text-muted">পণ্য</div>
        <div class="mt-3.5 flex flex-col gap-3.5">
            <template x-for="p in products" :key="p.url">
                <a :href="p.url" @click="remember(q)" class="flex gap-[13px] items-center">
                    <span class="w-14 h-14 rounded-xl flex-none overflow-hidden"
                          :style="`background:linear-gradient(160deg, color-mix(in srgb, ${p.tone} 12%, #F6F3F1), color-mix(in srgb, ${p.tone} 30%, #ECE7E3))`">
                        <template x-if="p.image"><img :src="p.image" alt="" class="w-full h-full object-cover" onerror="this.remove()"></template>
                    </span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-[15.5px] font-semibold truncate" x-text="p.name"></span>
                        <span class="block mt-0.5 text-[13px] text-muted" x-text="p.meta"></span>
                    </span>
                </a>
            </template>
        </div>
    </div>
</template>

<template x-if="q.trim() && !loading && !products.length">
    <div class="mb-6 text-[14.5px] text-muted">“<span x-text="q"></span>” এর সাথে মেলে এমন পণ্য পাওয়া যায়নি।</div>
</template>

<template x-if="recent.length">
    <div>
        <div class="text-[12px] font-medium tracking-[0.14em] text-muted">সাম্প্রতিক</div>
        <div class="mt-3 flex gap-2 flex-wrap">
            <template x-for="r in recent" :key="r">
                <button type="button" @click="go(r)" class="bg-sand-3 rounded-full px-4 py-[9px] text-[13.5px]" x-text="r"></button>
            </template>
        </div>
    </div>
</template>
