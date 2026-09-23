@props(['size' => 'md'])
@php
    /*
     * Payment method badges (card-style marks in each brand's colours) for the footer / checkout.
     * Only methods enabled in Settings → general are shown.
     */
    $general = app(\App\Services\SettingsService::class)->group('general');
    $methods = collect([
        'bkash' => ! empty($general['mobile_banking_enabled']),
        'nagad' => ! empty($general['mobile_banking_enabled']),
        'visa' => ! empty($general['card_enabled']),
        'mastercard' => ! empty($general['card_enabled']),
        'cod' => ! empty($general['cod_enabled']),
    ])->filter()->keys();
    $h = $size === 'sm' ? 'h-7' : 'h-8';
    $badge = "{$h} inline-flex items-center justify-center rounded-md bg-white ring-1 ring-black/[0.07] shadow-[0_1px_2px_rgba(36,28,26,.06)] overflow-hidden";
@endphp
@if($methods->isNotEmpty())
    <ul {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }} aria-label="পেমেন্ট মাধ্যম">
        @foreach($methods as $method)
            <li>
                @switch($method)
                    @case('bkash')
                        <span class="{{ $badge }} px-2 gap-1" style="background:#E2136E" title="bKash">
                            <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true"><path fill="#fff" d="M3 5.5 12 9l-2.2 9.5L3 5.5Zm9 3.5 9-1.8-6.6 11.3L12 9Zm-1.4 10 2.3-8.8 1 9.8-3.3-1Z" opacity=".95"/></svg>
                            <span class="text-[12.5px] font-bold italic tracking-tight text-white">bKash</span>
                        </span>
                        @break
                    @case('nagad')
                        <span class="{{ $badge }} px-2.5" title="Nagad">
                            <span class="text-[13px] font-extrabold tracking-tight" style="background:linear-gradient(90deg,#F6921E,#EC1C24);-webkit-background-clip:text;background-clip:text;color:transparent">Nagad</span>
                        </span>
                        @break
                    @case('visa')
                        <span class="{{ $badge }} px-2.5" title="Visa">
                            <span class="text-[15px] font-black italic tracking-[-0.02em]" style="color:#1A1F71;font-family:Arial,Helvetica,sans-serif">VISA</span>
                        </span>
                        @break
                    @case('mastercard')
                        <span class="{{ $badge }} px-2" title="Mastercard">
                            <svg width="34" height="21" viewBox="0 0 34 21" aria-hidden="true">
                                <circle cx="12" cy="10.5" r="9" fill="#EB001B"/><circle cx="22" cy="10.5" r="9" fill="#F79E1B"/>
                                <path d="M17 3a9 9 0 0 1 0 15 9 9 0 0 1 0-15Z" fill="#FF5F00"/>
                            </svg>
                        </span>
                        @break
                    @case('cod')
                        <span class="{{ $badge }} px-2.5 gap-1.5 text-[12px] font-semibold text-moss" title="ক্যাশ অন ডেলিভারি">
                            <x-ui.icon name="cash" :size="16" :stroke="1.8" />COD
                        </span>
                        @break
                @endswitch
            </li>
        @endforeach
    </ul>
@endif
