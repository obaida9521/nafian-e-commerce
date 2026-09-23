{{--
    Product gallery stage: the current slide (image, or the YouTube video once it's clicked).
    Needs the PDP scope: `image` (slide index), `playing` (false | 'phone' | 'desk'), `show(i, where)`.
    The page renders this twice (phone + desktop), so the iframe only mounts in the gallery `$where` that was clicked —
    otherwise the hidden copy would play its audio too.
    Slides crossfade on change; each one shows a spinner until its image/player has loaded, then fades in.
    @var \Illuminate\Support\Collection $slides  [{type: image|video, src|id, thumb}]
--}}
@foreach($slides as $index => $slide)
    <div x-show="image === {{ $index }}" @if($index > 0) x-cloak @endif
         x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         x-data="{ loaded: false, thumbLoaded: false }"
         @if($slide['type'] === 'video') x-effect="playing === @js($where) || (loaded = thumbLoaded)" @endif
         class="absolute inset-0 {{ $slide['type'] === 'video' ? 'bg-[#1A1413]' : '' }}">
        <div x-show="! loaded" x-transition.opacity.duration.300ms class="absolute inset-0 grid place-items-center pointer-events-none">
            <span class="nf-spinner" @if($slide['type'] === 'video') style="border-color:rgba(255,255,255,.18);border-top-color:#fff" @endif></span>
        </div>

        @if($slide['type'] === 'image')
            <img src="{{ $slide['src'] }}" alt="{{ $alt }}" @if($index > 0) loading="lazy" @endif
                 x-init="$el.complete && $el.naturalWidth && (loaded = true)" @load="loaded = true" x-on:error="loaded = true; $el.remove()"
                 class="absolute inset-0 w-full h-full object-cover transition duration-700 ease-out"
                 :class="loaded ? 'opacity-100 scale-100' : 'opacity-0 scale-[1.03]'">
        @else
            <template x-if="image === {{ $index }} && playing === @js($where)">
                <iframe src="https://www.youtube-nocookie.com/embed/{{ $slide['id'] }}?autoplay=1&rel=0&playsinline=1&modestbranding=1"
                        x-init="loaded = false" @load="loaded = true"
                        title="{{ $alt }} — ভিডিও" class="absolute inset-0 w-full h-full transition-opacity duration-500"
                        :class="loaded ? 'opacity-100' : 'opacity-0'" frameborder="0"
                        allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe>
            </template>
            <button type="button" x-show="! playing" x-transition.opacity.duration.300ms @click="show({{ $index }}, @js($where))" class="absolute inset-0 w-full h-full group" aria-label="ভিডিও চালান">
                <img src="{{ $slide['thumb'] }}" alt="" loading="lazy"
                     x-init="$el.complete && $el.naturalWidth && (loaded = thumbLoaded = true)" @load="loaded = thumbLoaded = true" x-on:error="loaded = thumbLoaded = true"
                     class="absolute inset-0 w-full h-full object-cover transition duration-700 ease-out"
                     :class="thumbLoaded ? 'opacity-90 scale-100' : 'opacity-0 scale-[1.03]'">
                <span class="absolute inset-0 grid place-items-center">
                    <span class="w-16 h-16 rounded-full bg-white/90 text-espresso grid place-items-center shadow-lg transition-transform group-hover:scale-105">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5-11-6.5Z"/></svg>
                    </span>
                </span>
            </button>
        @endif
    </div>
@endforeach
