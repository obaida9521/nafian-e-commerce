@php
    $footerGeneral = app(\App\Services\SettingsService::class)->group('general');
    $whatsapp = preg_replace('/[^0-9]/', '', (string) $footerGeneral['whatsapp']);
    $instagram = trim((string) $footerGeneral['instagram']);
    $messenger = trim((string) ($footerGeneral['messenger'] ?? ''));
    $phone = (string) $footerGeneral['support_phone'];
    $email = (string) $footerGeneral['support_email'];
    $logo = brand_logo();

    $socials = collect([
        ['instagram', 'Instagram', $instagram ? 'https://instagram.com/'.ltrim($instagram, '@/') : null],
        ['whatsapp', 'WhatsApp', $whatsapp ? 'https://wa.me/'.$whatsapp : null],
        ['messenger', 'Messenger', $messenger ? (str_starts_with($messenger, 'http') ? $messenger : 'https://m.me/'.ltrim($messenger, '@/')) : null],
    ])->filter(fn ($s) => $s[2]);

    $columns = [
        'শপ' => array_merge(
            [['সব পণ্য', route('store.shop')], ['অফার', route('store.offers')]],
            nav_categories()->map(fn ($c) => [$c->name, route('store.shop.category', $c->slug)])->all(),
        ),
        'সহায়তা' => [
            ['অর্ডার ট্র্যাক', route('store.track')],
            ['ডেলিভারি', route('store.page', 'delivery')],
            ['রিটার্ন', route('store.page', 'returns')],
            ['প্রশ্নোত্তর', route('store.page', 'faq')],
            ['যোগাযোগ', route('store.page', 'contact')],
        ],
        'কোম্পানি' => [
            ['আমাদের সম্পর্কে', route('store.page', 'about')],
            ['অথেনটিসিটি', route('store.page', 'authenticity')],
            ['প্রাইভেসি পলিসি', route('store.page', 'privacy')],
            ['শর্তাবলি', route('store.page', 'terms')],
            ['আমার অ্যাকাউন্ট', auth('web')->check() ? route('store.account.orders') : route('login')],
        ],
    ];

    $promises = [
        ['truck', 'দ্রুত ডেলিভারি', 'ঢাকায় ২৪ ঘণ্টায়, সারাদেশে ২–৩ দিনে'],
        ['cash', 'ক্যাশ অন ডেলিভারি', 'পণ্য হাতে পেয়ে টাকা দিন'],
        ['shield', '১০০% অরিজিনাল', 'প্রতিটি বোতলে ব্যাচ কোড'],
        ['return', '৭ দিনে রিটার্ন', 'সিল অক্ষত থাকলে সহজ রিটার্ন'],
    ];
@endphp

{{-- ── Desktop / tablet ── --}}
<footer class="hidden sm:block mt-16 bg-panel border-t border-hair">
    {{-- Store promises --}}
    <div class="px-8 py-7 grid grid-cols-2 desk:grid-cols-4 gap-5 border-b border-hair">
        @foreach($promises as [$icon, $title, $body])
            <div class="flex items-center gap-3.5">
                <span class="flex-none w-11 h-11 rounded-full bg-white text-espresso grid place-items-center ring-1 ring-hair"><x-ui.icon :name="$icon" :size="20" /></span>
                <div class="min-w-0">
                    <div class="text-[14.5px] font-semibold text-ink">{{ $title }}</div>
                    <div class="text-[13px] text-muted">{{ $body }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="px-8 pt-12 pb-10 grid gap-10 desk:grid-cols-[1.35fr_1fr_1fr_1fr] grid-cols-2">
        {{-- Brand --}}
        <div class="min-w-0 col-span-2 desk:col-span-1">
            <a href="{{ route('store.home') }}" class="inline-flex items-center gap-3">
                @if($logo)
                    <img src="{{ $logo }}" alt="" onerror="this.remove()" class="h-10 w-auto max-w-[56px] object-contain">
                @endif
                <span class="font-display text-[26px] tracking-[0.3em] pl-[0.3em] text-espresso">NAFIAN</span>
            </a>
            <p class="mt-3 max-w-[36ch] text-[14.5px] leading-[1.8] text-muted">ছোট ব্যাচে তৈরি সুগন্ধি — যত্নে বাছাই করা উপাদান, সৎ দাম।</p>

            <ul class="mt-5 flex flex-col gap-2.5 text-[14px] text-cocoa">
                @if(filled($footerGeneral['store_address']))
                    <li class="flex items-start gap-2.5"><x-ui.icon name="pin" :size="17" class="mt-0.5 text-muted" />{{ $footerGeneral['store_address'] }}</li>
                @endif
                @if(filled($phone))
                    <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="inline-flex items-center gap-2.5 hover:text-accent"><x-ui.icon name="phone" :size="17" class="text-muted" />{{ bn_digits($phone) }}</a></li>
                @endif
                @if(filled($email))
                    <li><a href="mailto:{{ $email }}" class="inline-flex items-center gap-2.5 hover:text-accent"><x-ui.icon name="mail" :size="17" class="text-muted" />{{ $email }}</a></li>
                @endif
            </ul>

            @if($socials->isNotEmpty())
                <div class="mt-5 flex gap-2.5">
                    @foreach($socials as [$icon, $label, $url])
                        <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}" title="{{ $label }}"
                           class="w-10 h-10 rounded-full bg-white ring-1 ring-hair text-espresso grid place-items-center transition hover:bg-espresso hover:text-white hover:ring-espresso hover:-translate-y-0.5">
                            <x-ui.icon :name="$icon" :size="18" />
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Link columns --}}
        @foreach($columns as $heading => $links)
            <nav aria-label="{{ $heading }}">
                <div class="text-[13px] font-semibold tracking-[0.14em] uppercase text-ink">{{ $heading }}</div>
                <ul class="mt-4 flex flex-col gap-2.5 text-[14.5px] text-cocoa">
                    @foreach($links as [$label, $url])
                        <li>
                            <a href="{{ $url }}" class="group inline-flex items-center gap-1.5 hover:text-espresso transition-colors">
                                <x-ui.icon name="arrow-right" :size="13" :stroke="2" class="-ml-5 opacity-0 transition-all duration-200 group-hover:ml-0 group-hover:opacity-100" />
                                <span>{{ $label }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endforeach
    </div>

    {{-- Bottom bar --}}
    {{-- pr leaves room for the floating chat button --}}
    <div class="pl-8 pr-24 py-5 border-t border-hair flex flex-wrap items-center justify-between gap-4">
        <span class="text-[13.5px] text-muted">© {{ bn_digits(date('Y')) }} {{ config('shop.name') }}। সর্বস্বত্ব সংরক্ষিত।</span>
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-1.5 text-[13px] text-muted"><x-ui.icon name="shield" :size="15" />নিরাপদ পেমেন্ট</span>
            <x-ui.payment-badges />
        </div>
    </div>
</footer>

{{-- ── Phones: compact, above the tab bar ── --}}
<footer class="sm:hidden mt-10 bg-panel border-t border-hair px-5 pt-8 pb-6">
    <a href="{{ route('store.home') }}" class="inline-flex items-center gap-2.5">
        @if($logo)
            <img src="{{ $logo }}" alt="" onerror="this.remove()" class="h-8 w-auto max-w-[44px] object-contain">
        @endif
        <span class="font-display text-[21px] tracking-[0.3em] pl-[0.3em] text-espresso">NAFIAN</span>
    </a>

    <ul class="mt-4 flex flex-col gap-2 text-[13.5px] text-cocoa">
        @if(filled($footerGeneral['store_address']))
            <li class="flex items-start gap-2"><x-ui.icon name="pin" :size="16" class="mt-0.5 text-muted" />{{ $footerGeneral['store_address'] }}</li>
        @endif
        @if(filled($phone))
            <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="inline-flex items-center gap-2"><x-ui.icon name="phone" :size="16" class="text-muted" />{{ bn_digits($phone) }}</a></li>
        @endif
    </ul>

    @if($socials->isNotEmpty())
        <div class="mt-4 flex gap-2.5">
            @foreach($socials as [$icon, $label, $url])
                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}" class="w-10 h-10 rounded-full bg-white ring-1 ring-hair text-espresso grid place-items-center">
                    <x-ui.icon :name="$icon" :size="18" />
                </a>
            @endforeach
        </div>
    @endif

    <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-6">
        @foreach(['সহায়তা' => $columns['সহায়তা'], 'কোম্পানি' => $columns['কোম্পানি']] as $heading => $links)
            <nav aria-label="{{ $heading }}">
                <div class="text-[12px] font-semibold tracking-[0.14em] uppercase text-ink">{{ $heading }}</div>
                <ul class="mt-3 flex flex-col gap-2 text-[14px] text-cocoa">
                    @foreach($links as [$label, $url])
                        <li><a href="{{ $url }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endforeach
    </div>

    <div class="mt-7 pt-5 border-t border-hair">
        <div class="mb-2.5 inline-flex items-center gap-1.5 text-[12.5px] text-muted"><x-ui.icon name="shield" :size="14" />নিরাপদ পেমেন্ট</div>
        <x-ui.payment-badges size="sm" />
        <div class="mt-4 text-[12.5px] text-muted">© {{ bn_digits(date('Y')) }} {{ config('shop.name') }}। সর্বস্বত্ব সংরক্ষিত।</div>
    </div>
</footer>
