@extends('layouts.admin')
@section('title', 'সেটিংস')

@php
    $field = 'nf-input nf-input-soft';
    $symbol = config('shop.currency_symbol');
@endphp

@section('content')
<div x-data="{ tab: @js($tab) }">
    <h1 class="font-display text-[28px]">সেটিংস</h1>

    <div class="mt-4 flex gap-2 flex-wrap">
        @foreach(['general' => 'সাধারণ', 'campaign' => 'ক্যাম্পেইন', 'pixels' => 'মার্কেটিং পিক্সেল', 'courier' => 'কুরিয়ার'] as $key => $label)
            <button @click="tab = @js($key)" :class="tab === @js($key) ? 'bg-mocha text-white' : 'bg-white text-ink'"
                    class="rounded-full px-5 py-2.5 text-[14px] font-medium">{{ $label }}</button>
        @endforeach
    </div>

    {{-- GENERAL --}}
    <div x-show="tab === 'general'" class="mt-[18px]">
        <form method="POST" action="{{ route('admin.settings.general') }}" class="grid desk:grid-cols-2 gap-[18px] items-start" enctype="multipart/form-data"
              x-data="{ preview: @js(brand_logo()), remove: false, library: '',
                        pick(e) { const f = e.target.files[0]; if (f) { this.preview = URL.createObjectURL(f); this.remove = false; this.library = ''; } },
                        async fromLibrary() { const [item] = await window.openMediaPicker({ title: 'লোগো বেছে নিন' }); if (! item) return; this.$refs.logoFile.value = ''; this.preview = item.url; this.library = item.key; this.remove = false; } }">
            @csrf @method('PUT')

            <x-admin.card>
                <div class="text-[16px] font-semibold">ডেলিভারি চার্জ</div>
                <div class="mt-4 flex flex-col gap-3.5">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="nf-label" for="s-inside">ঢাকার ভেতরে</label>
                            <input id="s-inside" name="delivery_inside" value="{{ old('delivery_inside', $general['delivery_inside']) }}" class="{{ $field }}">
                        </div>
                        <div>
                            <label class="nf-label" for="s-outside">ঢাকার বাইরে</label>
                            <input id="s-outside" name="delivery_outside" value="{{ old('delivery_outside', $general['delivery_outside']) }}" class="{{ $field }}">
                        </div>
                    </div>
                    <div>
                        <label class="nf-label" for="s-free">ফ্রি ডেলিভারির সীমা</label>
                        <input id="s-free" name="free_delivery_threshold" value="{{ old('free_delivery_threshold', $general['free_delivery_threshold']) }}" class="{{ $field }}">
                        <div class="mt-1.5 text-[12.5px] text-muted">০ দিলে ফ্রি ডেলিভারি বন্ধ থাকবে।</div>
                    </div>
                </div>

                <div class="nf-line my-[18px]"></div>
                <div class="flex flex-col gap-3 text-[14.5px]">
                    @foreach([
                        ['cod_enabled', 'ক্যাশ অন ডেলিভারি চালু'],
                        ['mobile_banking_enabled', 'বিকাশ / নগদ চালু'],
                        ['card_enabled', 'কার্ড পেমেন্ট চালু'],
                    ] as [$name, $label])
                        <label class="flex justify-between items-center gap-3 cursor-pointer">
                            <span>{{ $label }}</span>
                            <input type="hidden" name="{{ $name }}" value="0">
                            <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked(old($name, $general[$name]))>
                            <span class="nf-switch"><span></span></span>
                        </label>
                    @endforeach
                </div>
            </x-admin.card>

            <x-admin.card>
                <div class="text-[16px] font-semibold">স্টোরের তথ্য</div>
                <div class="mt-4 flex flex-col gap-3.5">
                    <div>
                        <label class="nf-label" for="s-name">স্টোরের নাম</label>
                        <input id="s-name" name="store_name" value="{{ old('store_name', $general['store_name']) }}" class="{{ $field }}">
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="nf-label" for="s-phone">যোগাযোগ নম্বর</label>
                            <input id="s-phone" name="support_phone" value="{{ old('support_phone', $general['support_phone']) }}" class="{{ $field }}">
                        </div>
                        <div>
                            <label class="nf-label" for="s-email">সাপোর্ট ইমেইল</label>
                            <input id="s-email" name="support_email" value="{{ old('support_email', $general['support_email']) }}" class="{{ $field }}">
                        </div>
                    </div>
                    <div>
                        <label class="nf-label" for="s-address">ঠিকানা</label>
                        <input id="s-address" name="store_address" value="{{ old('store_address', $general['store_address']) }}" class="{{ $field }}">
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="nf-label" for="s-currency">মুদ্রা</label>
                            <select id="s-currency" name="currency" class="{{ $field }}">
                                <option value="BDT" @selected($general['currency'] === 'BDT')>BDT — টাকা (৳)</option>
                                <option value="USD" @selected($general['currency'] === 'USD')>USD — ডলার ($)</option>
                            </select>
                        </div>
                        <div>
                            <label class="nf-label" for="s-instagram">ইনস্টাগ্রাম</label>
                            <input id="s-instagram" name="instagram" value="{{ old('instagram', $general['instagram']) }}" placeholder="nafian" class="{{ $field }}">
                        </div>
                    </div>
                </div>

                <div class="nf-line my-[18px]"></div>
                <div class="flex flex-col gap-3 text-[14.5px]">
                    @foreach([
                        ['order_sms', 'অর্ডার এসএমএস পাঠান'],
                        ['low_stock_alert', 'স্টক কম হলে সতর্কতা'],
                    ] as [$name, $label])
                        <label class="flex justify-between items-center gap-3 cursor-pointer">
                            <span>{{ $label }}</span>
                            <input type="hidden" name="{{ $name }}" value="0">
                            <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked(old($name, $general[$name]))>
                            <span class="nf-switch"><span></span></span>
                        </label>
                    @endforeach
                </div>
            </x-admin.card>

            <x-admin.card>
                <div class="text-[16px] font-semibold">ব্র্যান্ড লোগো</div>
                <div class="mt-1.5 text-[13px] text-muted">স্টোরফ্রন্ট হেডার ও ইনভয়েসে দেখানো হয়। PNG, JPG, SVG বা WEBP, সর্বোচ্চ ১ MB।</div>
                <div class="mt-4 flex items-center gap-5 flex-wrap">
                    <div class="w-[120px] h-[56px] rounded-xl bg-canvas grid place-items-center overflow-hidden flex-none">
                        <template x-if="preview && ! remove"><img :src="preview" alt="লোগো" class="max-w-full max-h-full object-contain"></template>
                        <template x-if="! preview || remove"><span class="text-[12.5px] text-muted">লোগো নেই</span></template>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="bg-clay-soft text-espresso rounded-full px-5 py-2.5 text-[14px] font-semibold cursor-pointer w-fit">
                            লোগো আপলোড
                            <input x-ref="logoFile" type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden" @change="pick($event)" data-image-editor data-max-width="800">
                        </label>
                        <input type="hidden" name="library_logo" :value="library">
                        <button type="button" @click="fromLibrary()" class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-accent hover:underline"><x-ui.icon name="image" :size="15" />মিডিয়া থেকে বেছে নিন</button>
                        <label class="flex items-center gap-2 text-[13px] text-muted" x-show="preview">
                            <input type="checkbox" name="remove_logo" value="1" x-model="remove" class="accent-mocha"> বর্তমান লোগো সরান
                        </label>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card>
                <div class="text-[16px] font-semibold">চ্যাট বাটন</div>
                <div class="mt-1.5 text-[13px] text-muted">স্টোরফ্রন্টে ভাসমান হোয়াটসঅ্যাপ ও মেসেঞ্জার বাটন। খালি রাখলে বাটনটি দেখানো হবে না।</div>
                <div class="mt-4 grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="nf-label" for="s-whatsapp">হোয়াটসঅ্যাপ নম্বর</label>
                        <input id="s-whatsapp" name="whatsapp" value="{{ old('whatsapp', $general['whatsapp']) }}" placeholder="8801700000000" class="{{ $field }}">
                        <div class="mt-1.5 text-[12px] text-muted">কান্ট্রি কোডসহ, + বা স্পেস ছাড়া।</div>
                    </div>
                    <div>
                        <label class="nf-label" for="s-messenger">মেসেঞ্জার ইউজারনেম</label>
                        <input id="s-messenger" name="messenger" value="{{ old('messenger', $general['messenger']) }}" placeholder="nafian" class="{{ $field }}">
                        <div class="mt-1.5 text-[12px] text-muted">m.me/username — শুধু ইউজারনেমটি লিখুন।</div>
                    </div>
                </div>
            </x-admin.card>

            <div class="desk:col-span-2 flex justify-end">
                <button class="inline-flex items-center gap-1.5 bg-mocha text-white rounded-full px-7 py-3 text-[14.5px] font-semibold"><x-ui.icon name="check" :size="15" />সংরক্ষণ করুন</button>
            </div>
        </form>
    </div>

    {{-- CAMPAIGN --}}
    <div x-show="tab === 'campaign'" x-cloak class="mt-[18px]">
        <form method="POST" action="{{ route('admin.settings.campaign') }}" class="max-w-[640px]">
            @csrf @method('PUT')
            <x-admin.card>
                <div class="text-[16px] font-semibold">অফার ক্যাম্পেইন</div>
                <div class="mt-1.5 text-[13px] text-muted">হোমপেজের অ্যানাউন্সমেন্ট বার ও অফার পেজে দেখানো হয়।</div>
                <div class="mt-4 flex flex-col gap-3.5">
                    <label class="flex justify-between items-center gap-3 cursor-pointer text-[14.5px]">
                        <span>ক্যাম্পেইন চালু</span>
                        <input type="checkbox" name="enabled" value="1" class="peer sr-only" @checked(old('enabled', $campaign['enabled']))>
                        <span class="nf-switch"><span></span></span>
                    </label>
                    <div>
                        <label class="nf-label" for="cp-eyebrow">ছোট শিরোনাম</label>
                        <input id="cp-eyebrow" name="eyebrow" value="{{ old('eyebrow', $campaign['eyebrow']) }}" class="{{ $field }}" placeholder="সেপ্টেম্বর ক্যাম্পেইন">
                    </div>
                    <div>
                        <label class="nf-label" for="cp-title">শিরোনাম</label>
                        <input id="cp-title" name="title" value="{{ old('title', $campaign['title']) }}" required class="{{ $field }}">
                    </div>
                    <div>
                        <label class="nf-label" for="cp-body">বিবরণ</label>
                        <textarea id="cp-body" name="body" rows="3" class="{{ $field }}">{{ old('body', $campaign['body']) }}</textarea>
                    </div>
                    <div>
                        <label class="nf-label" for="cp-coupon">কুপন কোড</label>
                        <input id="cp-coupon" name="coupon_code" value="{{ old('coupon_code', $campaign['coupon_code']) }}" class="{{ $field }} uppercase" placeholder="COMBO30">
                        <div class="mt-1.5 text-[12.5px] text-muted">কুপনের মেয়াদ থেকেই অফার পেজের কাউন্টডাউন তৈরি হয়।</div>
                    </div>
                </div>
                <div class="mt-5 flex justify-end">
                    <button class="inline-flex items-center gap-1.5 bg-mocha text-white rounded-full px-7 py-3 text-[14.5px] font-semibold"><x-ui.icon name="check" :size="15" />সংরক্ষণ করুন</button>
                </div>
            </x-admin.card>
        </form>
    </div>

    {{-- PIXELS --}}
    <div x-show="tab==='pixels'" x-cloak>
        <form method="POST" action="{{ route('admin.settings.pixels') }}" class="flex flex-col gap-[18px]"
              x-data="{ fb: {{ $pixels['fb_enabled'] ? 'true':'false' }}, ga4: {{ $pixels['ga4_enabled'] ? 'true':'false' }}, tt: {{ $pixels['tiktok_enabled'] ? 'true':'false' }} }">
            @csrf @method('PUT')
            @include('admin.settings.partials.pixel-card', [
                'state' => 'fb', 'enabledName' => 'fb_enabled', 'bg' => '#EEF2FF',
                'title' => 'Facebook / Meta Pixel', 'desc' => 'কনভার্সন ট্র্যাক, অডিয়েন্স তৈরি ও Conversions API চালান।',
                'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="#1877F2"><path d="M22 12a10 10 0 1 0-11.5 9.9v-7H8v-2.9h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6v1.8h2.8l-.4 2.9h-2.4v7A10 10 0 0 0 22 12Z"/></svg>',
                'fields' => [['fb_pixel','Pixel ID','e.g. 1234567890123456', $pixels['fb_pixel']], ['fb_token','Conversions API token (ঐচ্ছিক)','EAAG… access token', $pixels['fb_token'], true], ['fb_test_code','Test event code (ঐচ্ছিক)','TEST12345', $pixels['fb_test_code']]],
                'hint' => 'টোকেন দিলে ViewContent, AddToCart, InitiateCheckout, Purchase, Lead ইত্যাদি ইভেন্ট সার্ভার থেকেও যাবে (Conversions API); একই event ID থাকায় Meta ডুপ্লিকেট বাদ দেয়। টেস্ট শেষে test event code মুছে দিন।',
            ])
            @include('admin.settings.partials.pixel-card', [
                'state' => 'ga4', 'enabledName' => 'ga4_enabled', 'bg' => '#FEF3E2',
                'title' => 'Google Analytics 4 & Tag Manager', 'desc' => 'পেজ ভিউ, ই-কমার্স ইভেন্ট ও ট্যাগ ম্যানেজমেন্ট।',
                'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E8710A" stroke-width="2"><path d="M4 20V9M12 20V4M20 20v-7"/></svg>',
                'fields' => [['ga4','GA4 Measurement ID','G-XXXXXXXXXX', $pixels['ga4']], ['gtm','GTM Container ID','GTM-XXXXXXX', $pixels['gtm']], ['ga4_api_secret','Measurement Protocol API secret (ঐচ্ছিক)','GA4 → Admin → Data streams → API secrets', $pixels['ga4_api_secret'], true]],
                'hint' => 'GA4 ই-কমার্স ইভেন্ট (view_item, add_to_cart, begin_checkout, purchase…) স্বয়ংক্রিয়ভাবে যায়; GTM-এ একই ইভেন্ট dataLayer-এ ecommerce অবজেক্টসহ পুশ হয়। API secret দিলে purchase সার্ভার থেকেও যায়, অ্যাড-ব্লকার থাকলেও বিক্রি গোনা হয়।',
            ])
            @include('admin.settings.partials.pixel-card', [
                'state' => 'tt', 'enabledName' => 'tiktok_enabled', 'bg' => '#1f1f1f',
                'title' => 'TikTok Pixel', 'desc' => 'টিকটক বিজ্ঞাপনের জন্য ইভেন্ট ট্র্যাকিং।',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="#fff"><path d="M16 3c.3 2.2 1.6 3.9 3.9 4.1v2.7c-1.4.1-2.7-.3-3.9-1v6.2c0 4-3.4 6.6-7 5.7-2.8-.7-4.3-3.7-3.4-6.5.7-2.1 2.8-3.5 5-3.3v2.8c-.4-.1-.8-.1-1.2 0-1.2.3-1.9 1.4-1.6 2.5.3 1.1 1.5 1.7 2.6 1.3.9-.3 1.4-1.1 1.4-2V3H16Z"/></svg>',
                'fields' => [['tiktok','TikTok Pixel ID','CXXXXXXXXXXXXXXXXX', $pixels['tiktok']], ['tiktok_token','Events API access token (ঐচ্ছিক)','TikTok Events Manager → Settings', $pixels['tiktok_token'], true], ['tiktok_test_code','Test event code (ঐচ্ছিক)','TEST12345', $pixels['tiktok_test_code']]],
                'hint' => 'টোকেন দিলে ইভেন্টগুলো TikTok Events API দিয়েও যায়, event_id দিয়ে ডুপ্লিকেট বাদ পড়ে।',
            ])
            <div class="flex justify-end"><button class="inline-flex items-center gap-1.5 bg-mocha text-white rounded-full px-7 py-3 text-[14.5px] font-semibold"><x-ui.icon name="check" :size="15" />পিক্সেল সংরক্ষণ করুন</button></div>
        </form>
    </div>

    {{-- COURIER --}}
    <div x-show="tab==='courier'" x-cloak class="flex flex-col gap-[18px]">
        <div class="text-[13.5px] text-cocoa bg-clay-soft rounded-2xl px-5 py-4 leading-relaxed">কুরিয়ার যুক্ত করলে অর্ডার পেজ থেকেই কনসাইনমেন্ট তৈরি ও ডেলিভারি স্ট্যাটাস সিঙ্ক করা যায়। ক্রেডেনশিয়াল এনক্রিপ্ট করে সংরক্ষণ করা হয়।</div>

        {{-- Steadfast --}}
        <form method="POST" action="{{ route('admin.settings.courier') }}" x-data="{ sf: {{ $courier['steadfast_enabled'] ? 'true':'false' }}, pt: {{ $courier['pathao_enabled'] ? 'true':'false' }}, sandbox: {{ $courier['pathao_sandbox'] ? 'true':'false' }} }">
            @csrf @method('PUT')
            <div class="bg-white rounded-[20px] overflow-hidden nf-shadow-soft mb-[18px]">
                <div class="flex items-center justify-between gap-4 px-6 py-5 border-b border-hair">
                    <div class="flex items-center gap-3">
                        <div class="w-[42px] h-[42px] rounded-[10px] bg-mocha text-white grid place-items-center font-bold text-lg flex-none">S</div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="text-base font-semibold">Steadfast Courier</span>
                                <span class="text-[11px] font-semibold px-2.5 py-[3px] rounded-full" :class="sf ? 'bg-moss-soft text-moss' : 'bg-hair text-muted'" x-text="sf ? 'যুক্ত' : 'বন্ধ'"></span>
                            </div>
                            <div class="text-[13px] text-muted">Nationwide home delivery & COD across Bangladesh.</div>
                        </div>
                    </div>
                    @include('admin.settings.partials.toggle', ['state' => 'sf', 'name' => 'steadfast_enabled'])
                </div>
                <div class="p-6 grid sm:grid-cols-2 gap-4">
                    <div><label class="nf-label">API Key</label><input name="api_key" value="{{ old('api_key', $courier['api_key']) }}" placeholder="Your Steadfast API key" class="{{ $field }}"></div>
                    <div><label class="nf-label">Secret Key</label><input type="password" name="secret_key" placeholder="{{ $courier['secret_key'] ? '••••••••••••' : 'Secret key' }}" class="{{ $field }}"></div>
                    <div class="sm:col-span-2"><label class="nf-label">Base URL</label><input name="steadfast_base_url" value="{{ old('steadfast_base_url', $courier['steadfast_base_url']) }}" class="{{ $field }}"></div>
                    <div class="sm:col-span-2"><label class="nf-label">Status webhook URL (optional)</label><input name="webhook" value="{{ old('webhook', $courier['webhook']) }}" placeholder="https://nafian.com/api/steadfast/webhook" class="{{ $field }}"></div>
                </div>
            </div>

            {{-- Pathao --}}
            <div class="bg-white rounded-[20px] overflow-hidden nf-shadow-soft">
                <div class="flex items-center justify-between gap-4 px-6 py-5 border-b border-hair">
                    <div class="flex items-center gap-3">
                        <div class="w-[42px] h-[42px] rounded-[10px] text-white grid place-items-center font-bold text-lg flex-none" style="background:#E2136E;">P</div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="text-base font-semibold">Pathao Courier</span>
                                <span class="text-[11px] font-semibold px-2.5 py-[3px] rounded-full" :class="pt ? 'bg-moss-soft text-moss' : 'bg-hair text-muted'" x-text="pt ? 'যুক্ত' : 'বন্ধ'"></span>
                            </div>
                            <div class="text-[13px] text-muted">Pathao মার্চেন্ট API দিয়ে দ্রুত পার্সেল ডেলিভারি।</div>
                        </div>
                    </div>
                    @include('admin.settings.partials.toggle', ['state' => 'pt', 'name' => 'pathao_enabled'])
                </div>
                <div class="p-6">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div><label class="nf-label">Client ID</label><input name="client_id" value="{{ old('client_id', $courier['client_id']) }}" class="{{ $field }}"></div>
                        <div><label class="nf-label">Client Secret</label><input type="password" name="client_secret" placeholder="{{ $courier['client_secret'] ? '••••••••••••' : 'Client secret' }}" class="{{ $field }}"></div>
                        <div><label class="nf-label">Merchant username (email)</label><input name="username" value="{{ old('username', $courier['username']) }}" placeholder="merchant@nafian.com" class="{{ $field }}"></div>
                        <div><label class="nf-label">Password</label><input type="password" name="password" placeholder="{{ $courier['password'] ? '••••••••••••' : 'Password' }}" class="{{ $field }}"></div>
                        <div><label class="nf-label">Store ID</label><input name="store_id" value="{{ old('store_id', $courier['store_id']) }}" placeholder="e.g. 12345" class="{{ $field }}"></div>
                        <div><label class="nf-label">Base URL</label><input name="pathao_base_url" value="{{ old('pathao_base_url', $courier['pathao_base_url']) }}" class="{{ $field }}"></div>
                    </div>
                    <div class="flex items-center justify-between gap-4 mt-4.5 px-4 py-3.5 bg-canvas rounded-2xl">
                        <div><div class="text-[13.5px] font-semibold" x-text="sandbox ? 'স্যান্ডবক্স এনভায়রনমেন্ট' : 'লাইভ এনভায়রনমেন্ট'"></div><div class="text-xs text-gray-400">টেস্টের সময় স্যান্ডবক্স, লঞ্চের আগে লাইভ করুন।</div></div>
                        @include('admin.settings.partials.toggle', ['state' => 'sandbox', 'name' => 'pathao_sandbox'])
                    </div>
                </div>
            </div>
            <div class="flex justify-end mt-[18px]"><button class="inline-flex items-center gap-1.5 bg-mocha text-white rounded-full px-7 py-3 text-[14.5px] font-semibold"><x-ui.icon name="check" :size="15" />কুরিয়ার সংরক্ষণ করুন</button></div>
        </form>
    </div>
</div>
@endsection
