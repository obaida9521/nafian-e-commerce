<footer style="background:#4d141e;color:rgba(255,255,255,0.7);">
    <div class="max-w-[1280px] mx-auto px-6 pt-14 pb-8">
        <div class="grid grid-cols-2 md:grid-cols-[1.4fr_1fr_1fr_1fr] gap-10">
            <div>
                <div class="flex items-center gap-2.5 mb-3.5">
                    @if($logo = brand_logo())
                        <span class="w-10 h-10 rounded-xl bg-white grid place-items-center overflow-hidden p-1 flex-none">
                            <img src="{{ $logo }}" alt="{{ config('shop.name') }}" class="max-w-full max-h-full object-contain">
                        </span>
                    @endif
                    <span class="font-bold text-xl tracking-[0.26em] text-white">NAFIAN</span>
                </div>
                <p class="text-sm leading-relaxed max-w-[260px]">Modern essentials in leather, wool and silk. Designed in studio, made to last.</p>
            </div>
            @php
                $cols = [
                    'Shop' => ['New In', 'Bags', 'Apparel', 'Accessories', 'Sale'],
                    'Company' => ['About', 'Sustainability', 'Stores', 'Careers'],
                    'Support' => ['Shipping', 'Returns', 'Size Guide', 'Contact'],
                ];
            @endphp
            @foreach($cols as $title => $links)
                <div>
                    <div class="text-xs tracking-[0.1em] uppercase text-white/45 font-semibold mb-3.5">{{ $title }}</div>
                    <div class="flex flex-col gap-2.5">
                        @foreach($links as $l)
                            <a href="{{ route('store.shop') }}" class="text-sm hover:text-white">{{ $l }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        <div class="flex justify-between items-center border-t border-white/10 mt-10 pt-5.5 text-[13px] text-white/45">
            <span>© {{ date('Y') }} Nafian. All rights reserved.</span>
            <span class="font-mono">Crafted with care</span>
        </div>
    </div>
</footer>
