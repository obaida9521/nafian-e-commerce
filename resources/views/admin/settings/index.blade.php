@extends('layouts.admin')
@section('title', 'Settings')

@php
    $field = 'w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-wine-700 focus:ring-2 focus:ring-wine-700/10';
    $mono = 'font-mono text-[13px]';
@endphp

@section('content')
<div class="max-w-[1040px] mx-auto" x-data="{ tab: '{{ $tab }}' }">
    {{-- Sub-tabs --}}
    <div class="flex gap-1 bg-[#F6EAD8] p-1 rounded-[10px] mb-6 w-fit">
        @foreach(['general'=>'General','pixels'=>'Marketing & Pixels','courier'=>'Courier & Delivery'] as $key=>$label)
            <button @click="tab='{{ $key }}'" :class="tab==='{{ $key }}' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500'"
                class="h-[34px] px-4 rounded-[7px] text-[13.5px] font-semibold">{{ $label }}</button>
        @endforeach
    </div>

    {{-- GENERAL --}}
    <div x-show="tab==='general'" class="flex flex-col gap-[18px]">
        <form method="POST" action="{{ route('admin.settings.general') }}" class="flex flex-col gap-[18px]" enctype="multipart/form-data"
              x-data="{ preview: @js(brand_logo()), remove: false,
                        pick(e){ const f=e.target.files[0]; if(f){ this.preview=URL.createObjectURL(f); this.remove=false; } } }">
            @csrf @method('PUT')
            <div class="bg-white border border-[#EADBC4] rounded-xl p-6">
                <div class="text-[15px] font-semibold mb-1">Brand logo</div>
                <div class="text-[13px] text-gray-400 mb-5">Shown in the storefront header and admin sidebar. PNG, JPG, SVG or WEBP up to 1&nbsp;MB. Leave blank to keep the text wordmark.</div>
                <div class="flex items-center gap-5">
                    <div class="w-[120px] h-[56px] rounded-lg border border-[#EADBC4] bg-[#fff2e3] flex items-center justify-center overflow-hidden flex-none">
                        <template x-if="preview && !remove"><img :src="preview" alt="Logo" class="max-w-full max-h-full object-contain"></template>
                        <template x-if="!preview || remove"><span class="text-[12px] text-gray-400">No logo</span></template>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="h-[38px] px-4 inline-flex items-center rounded-lg border border-[#EADBC4] bg-white text-sm font-semibold text-gray-700 cursor-pointer hover:border-wine-700 w-fit">
                            Upload logo
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden" @change="pick($event)">
                        </label>
                        <label class="flex items-center gap-2 text-[13px] text-gray-500" x-show="preview">
                            <input type="checkbox" name="remove_logo" value="1" x-model="remove" class="accent-wine-700"> Remove current logo
                        </label>
                    </div>
                </div>
            </div>
            <div class="bg-white border border-[#EADBC4] rounded-xl p-6">
                <div class="text-[15px] font-semibold mb-1">Store profile</div>
                <div class="text-[13px] text-gray-400 mb-5">Basic information shown to customers and on invoices.</div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div><label class="block text-[13px] text-gray-500 mb-1.5">Store name</label><input name="store_name" value="{{ old('store_name', $general['store_name']) }}" class="{{ $field }}"></div>
                    <div><label class="block text-[13px] text-gray-500 mb-1.5">Support email</label><input name="support_email" value="{{ old('support_email', $general['support_email']) }}" class="{{ $field }}"></div>
                    <div><label class="block text-[13px] text-gray-500 mb-1.5">Currency</label>
                        <select name="currency" class="{{ $field }} bg-white cursor-pointer">
                            <option value="USD" @selected($general['currency']==='USD')>USD — US Dollar ($)</option>
                            <option value="BDT" @selected($general['currency']==='BDT')>BDT — Bangladeshi Taka (৳)</option>
                        </select>
                    </div>
                    <div><label class="block text-[13px] text-gray-500 mb-1.5">Support phone</label><input name="support_phone" value="{{ old('support_phone', $general['support_phone']) }}" class="{{ $field }} {{ $mono }}"></div>
                </div>
            </div>
            <div class="bg-white border border-[#EADBC4] rounded-xl p-6">
                <div class="text-[15px] font-semibold mb-1">Chat buttons</div>
                <div class="text-[13px] text-gray-400 mb-5">Floating WhatsApp &amp; Messenger buttons on the storefront. Leave blank to hide a button.</div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] text-gray-500 mb-1.5">WhatsApp number</label>
                        <input name="whatsapp" value="{{ old('whatsapp', $general['whatsapp']) }}" placeholder="8801700000000" class="{{ $field }} {{ $mono }}">
                        <div class="text-[11.5px] text-gray-400 mt-1">Country code, no + or spaces.</div>
                    </div>
                    <div>
                        <label class="block text-[13px] text-gray-500 mb-1.5">Messenger username / page</label>
                        <input name="messenger" value="{{ old('messenger', $general['messenger']) }}" placeholder="nafian" class="{{ $field }}">
                        <div class="text-[11.5px] text-gray-400 mt-1">m.me/<span class="font-mono">username</span> — enter just the username.</div>
                    </div>
                </div>
            </div>
            <div class="bg-white border border-[#EADBC4] rounded-xl p-6">
                <div class="text-[15px] font-semibold mb-4">Default delivery charges</div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div><label class="block text-[13px] text-gray-500 mb-1.5">Inside city</label><input name="delivery_inside" value="{{ old('delivery_inside', $general['delivery_inside']) }}" class="{{ $field }}"></div>
                    <div><label class="block text-[13px] text-gray-500 mb-1.5">Outside city</label><input name="delivery_outside" value="{{ old('delivery_outside', $general['delivery_outside']) }}" class="{{ $field }}"></div>
                </div>
                <div class="flex justify-end mt-5"><button class="h-[42px] px-5.5 rounded-lg bg-wine-700 hover:bg-[#4d141e] text-white text-sm font-semibold">Save changes</button></div>
            </div>
        </form>
    </div>

    {{-- PIXELS --}}
    <div x-show="tab==='pixels'" x-cloak>
        <form method="POST" action="{{ route('admin.settings.pixels') }}" class="flex flex-col gap-[18px]"
              x-data="{ fb: {{ $pixels['fb_enabled'] ? 'true':'false' }}, ga4: {{ $pixels['ga4_enabled'] ? 'true':'false' }}, tt: {{ $pixels['tiktok_enabled'] ? 'true':'false' }} }">
            @csrf @method('PUT')
            @include('admin.settings.partials.pixel-card', [
                'state' => 'fb', 'enabledName' => 'fb_enabled', 'bg' => '#EEF2FF',
                'title' => 'Facebook / Meta Pixel', 'desc' => 'Track conversions, build audiences and run the Conversions API.',
                'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="#1877F2"><path d="M22 12a10 10 0 1 0-11.5 9.9v-7H8v-2.9h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6v1.8h2.8l-.4 2.9h-2.4v7A10 10 0 0 0 22 12Z"/></svg>',
                'fields' => [['fb_pixel','Pixel ID','e.g. 1234567890123456', $pixels['fb_pixel']], ['fb_token','Conversions API token (optional)','EAAG… access token', $pixels['fb_token']]],
            ])
            @include('admin.settings.partials.pixel-card', [
                'state' => 'ga4', 'enabledName' => 'ga4_enabled', 'bg' => '#FEF3E2',
                'title' => 'Google Analytics 4 & Tag Manager', 'desc' => 'Page views, e-commerce events and tag management.',
                'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E8710A" stroke-width="2"><path d="M4 20V9M12 20V4M20 20v-7"/></svg>',
                'fields' => [['ga4','GA4 Measurement ID','G-XXXXXXXXXX', $pixels['ga4']], ['gtm','GTM Container ID','GTM-XXXXXXX', $pixels['gtm']]],
            ])
            @include('admin.settings.partials.pixel-card', [
                'state' => 'tt', 'enabledName' => 'tiktok_enabled', 'bg' => '#1f1f1f',
                'title' => 'TikTok Pixel', 'desc' => 'Track events for TikTok ad campaigns.',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="#fff"><path d="M16 3c.3 2.2 1.6 3.9 3.9 4.1v2.7c-1.4.1-2.7-.3-3.9-1v6.2c0 4-3.4 6.6-7 5.7-2.8-.7-4.3-3.7-3.4-6.5.7-2.1 2.8-3.5 5-3.3v2.8c-.4-.1-.8-.1-1.2 0-1.2.3-1.9 1.4-1.6 2.5.3 1.1 1.5 1.7 2.6 1.3.9-.3 1.4-1.1 1.4-2V3H16Z"/></svg>',
                'fields' => [['tiktok','TikTok Pixel ID','CXXXXXXXXXXXXXXXXX', $pixels['tiktok']]],
            ])
            <div class="flex justify-end"><button class="h-11 px-6.5 rounded-lg bg-wine-700 hover:bg-[#4d141e] text-white text-sm font-semibold">Save pixel settings</button></div>
        </form>
    </div>

    {{-- COURIER --}}
    <div x-show="tab==='courier'" x-cloak class="flex flex-col gap-[18px]">
        <div class="text-[13px] text-gray-600 bg-[#F8EAD6] border border-[#EFE2CE] rounded-[10px] px-4 py-3.5 leading-relaxed">Connect a courier to auto-create consignments and sync delivery status from the order detail page. Credentials are stored encrypted and never shown to customers.</div>

        {{-- Steadfast --}}
        <form method="POST" action="{{ route('admin.settings.courier') }}" x-data="{ sf: {{ $courier['steadfast_enabled'] ? 'true':'false' }}, pt: {{ $courier['pathao_enabled'] ? 'true':'false' }}, sandbox: {{ $courier['pathao_sandbox'] ? 'true':'false' }} }">
            @csrf @method('PUT')
            <div class="bg-white border border-[#EADBC4] rounded-xl overflow-hidden mb-[18px]">
                <div class="flex items-center justify-between gap-4 px-6 py-5 border-b border-[#EFE2CE]">
                    <div class="flex items-center gap-3">
                        <div class="w-[42px] h-[42px] rounded-[10px] bg-wine-700 text-white grid place-items-center font-bold text-lg flex-none">S</div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="text-base font-semibold">Steadfast Courier</span>
                                <span class="text-[11px] font-semibold px-2.5 py-[3px] rounded-full" :class="sf ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" x-text="sf ? 'Connected' : 'Disabled'"></span>
                            </div>
                            <div class="text-[13px] text-gray-400">Nationwide home delivery & COD across Bangladesh.</div>
                        </div>
                    </div>
                    @include('admin.settings.partials.toggle', ['state' => 'sf', 'name' => 'steadfast_enabled'])
                </div>
                <div class="p-6 grid sm:grid-cols-2 gap-4">
                    <div><label class="block text-[13px] text-gray-500 mb-1.5">API Key</label><input name="api_key" value="{{ old('api_key', $courier['api_key']) }}" placeholder="Your Steadfast API key" class="{{ $field }} {{ $mono }}"></div>
                    <div><label class="block text-[13px] text-gray-500 mb-1.5">Secret Key</label><input type="password" name="secret_key" placeholder="{{ $courier['secret_key'] ? '••••••••••••' : 'Secret key' }}" class="{{ $field }} {{ $mono }}"></div>
                    <div class="sm:col-span-2"><label class="block text-[13px] text-gray-500 mb-1.5">Base URL</label><input name="steadfast_base_url" value="{{ old('steadfast_base_url', $courier['steadfast_base_url']) }}" class="{{ $field }} {{ $mono }} text-gray-500"></div>
                    <div class="sm:col-span-2"><label class="block text-[13px] text-gray-500 mb-1.5">Status webhook URL (optional)</label><input name="webhook" value="{{ old('webhook', $courier['webhook']) }}" placeholder="https://nafian.com/api/steadfast/webhook" class="{{ $field }} {{ $mono }}"></div>
                </div>
            </div>

            {{-- Pathao --}}
            <div class="bg-white border border-[#EADBC4] rounded-xl overflow-hidden">
                <div class="flex items-center justify-between gap-4 px-6 py-5 border-b border-[#EFE2CE]">
                    <div class="flex items-center gap-3">
                        <div class="w-[42px] h-[42px] rounded-[10px] text-white grid place-items-center font-bold text-lg flex-none" style="background:#E2136E;">P</div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="text-base font-semibold">Pathao Courier</span>
                                <span class="text-[11px] font-semibold px-2.5 py-[3px] rounded-full" :class="pt ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" x-text="pt ? 'Connected' : 'Disabled'"></span>
                            </div>
                            <div class="text-[13px] text-gray-400">Fast parcel delivery via the Pathao merchant API.</div>
                        </div>
                    </div>
                    @include('admin.settings.partials.toggle', ['state' => 'pt', 'name' => 'pathao_enabled'])
                </div>
                <div class="p-6">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div><label class="block text-[13px] text-gray-500 mb-1.5">Client ID</label><input name="client_id" value="{{ old('client_id', $courier['client_id']) }}" class="{{ $field }} {{ $mono }}"></div>
                        <div><label class="block text-[13px] text-gray-500 mb-1.5">Client Secret</label><input type="password" name="client_secret" placeholder="{{ $courier['client_secret'] ? '••••••••••••' : 'Client secret' }}" class="{{ $field }} {{ $mono }}"></div>
                        <div><label class="block text-[13px] text-gray-500 mb-1.5">Merchant username (email)</label><input name="username" value="{{ old('username', $courier['username']) }}" placeholder="merchant@nafian.com" class="{{ $field }}"></div>
                        <div><label class="block text-[13px] text-gray-500 mb-1.5">Password</label><input type="password" name="password" placeholder="{{ $courier['password'] ? '••••••••••••' : 'Password' }}" class="{{ $field }}"></div>
                        <div><label class="block text-[13px] text-gray-500 mb-1.5">Store ID</label><input name="store_id" value="{{ old('store_id', $courier['store_id']) }}" placeholder="e.g. 12345" class="{{ $field }} {{ $mono }}"></div>
                        <div><label class="block text-[13px] text-gray-500 mb-1.5">Base URL</label><input name="pathao_base_url" value="{{ old('pathao_base_url', $courier['pathao_base_url']) }}" class="{{ $field }} {{ $mono }} text-gray-500"></div>
                    </div>
                    <div class="flex items-center justify-between gap-4 mt-4.5 px-4 py-3.5 bg-[#FBEFDD] rounded-[9px]">
                        <div><div class="text-[13.5px] font-semibold" x-text="sandbox ? 'Sandbox environment' : 'Live environment'"></div><div class="text-xs text-gray-400">Use sandbox while testing, switch to live before launch.</div></div>
                        @include('admin.settings.partials.toggle', ['state' => 'sandbox', 'name' => 'pathao_sandbox'])
                    </div>
                </div>
            </div>
            <div class="flex justify-end mt-[18px]"><button class="h-[42px] px-5.5 rounded-lg bg-wine-700 hover:bg-[#4d141e] text-white text-sm font-semibold">Save courier settings</button></div>
        </form>
    </div>
</div>
@endsection
