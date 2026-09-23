@if ($paginator->hasPages())
    @php $pill = 'rounded-full h-10 flex items-center justify-center text-[14.5px]'; @endphp
    <nav class="mt-9 flex flex-wrap justify-center gap-2 items-center" role="navigation" aria-label="{{ __('পেজ') }}">
        @unless ($paginator->onFirstPage())
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $pill }} px-5 bg-[#F6F3F1] text-[#3B2F2D] hover:bg-[#ECE6E1]">{{ __('আগের') }}</a>
        @endunless

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="{{ $pill }} w-10 text-[#6B605B]">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="{{ $pill }} w-10 bg-[#3B2F2D] text-white font-medium">{{ bn_digits($page) }}</span>
                    @else
                        <a href="{{ $url }}" class="{{ $pill }} w-10 bg-[#F6F3F1] text-[#3B2F2D] hover:bg-[#ECE6E1]">{{ bn_digits($page) }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $pill }} px-5 bg-[#F6F3F1] text-[#3B2F2D] hover:bg-[#ECE6E1]">{{ __('পরবর্তী') }}</a>
        @endif
    </nav>
@endif
