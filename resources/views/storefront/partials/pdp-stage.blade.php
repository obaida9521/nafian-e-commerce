{{--
    Product gallery stage: the current slide (image, or the YouTube video once it's clicked).
    Needs the PDP scope: `image` (slide index), `playing` (false | 'phone' | 'desk'), `show(i, where)`.
    The page renders this twice (phone + desktop), so the iframe only mounts in the gallery `$where` that was clicked —
    otherwise the hidden copy would play its audio too.
    @var \Illuminate\Support\Collection $slides  [{type: image|video, src|id, thumb}]
--}}
@foreach($slides as $index => $slide)
    @if($slide['type'] === 'image')
        <img x-show="image === {{ $index }}" @if($index > 0) x-cloak @endif src="{{ $slide['src'] }}" alt="{{ $alt }}"
             @if($index > 0) loading="lazy" @endif onerror="this.remove()" class="absolute inset-0 w-full h-full object-cover">
    @else
        <div x-show="image === {{ $index }}" x-cloak class="absolute inset-0 bg-[#1A1413]">
            <template x-if="image === {{ $index }} && playing === @js($where)">
                <iframe src="https://www.youtube-nocookie.com/embed/{{ $slide['id'] }}?autoplay=1&rel=0&playsinline=1&modestbranding=1"
                        title="{{ $alt }} — ভিডিও" class="absolute inset-0 w-full h-full" frameborder="0"
                        allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe>
            </template>
            <button type="button" x-show="! playing" @click="show({{ $index }}, @js($where))" class="absolute inset-0 w-full h-full group" aria-label="ভিডিও চালান">
                <img src="{{ $slide['thumb'] }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover opacity-90">
                <span class="absolute inset-0 grid place-items-center">
                    <span class="w-16 h-16 rounded-full bg-white/90 text-espresso grid place-items-center shadow-lg transition-transform group-hover:scale-105">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5-11-6.5Z"/></svg>
                    </span>
                </span>
            </button>
        </div>
    @endif
@endforeach
