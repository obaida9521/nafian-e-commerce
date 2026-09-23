{{-- Hero background video playlist: every public/videos/hero_video{N}.mp4, played in N order and looped.
     The gradient underneath shows until it loads or if it can't play. --}}
@php
    $heroVideos = collect(glob(public_path('videos/hero_video*.mp4')) ?: [])
        ->mapWithKeys(fn (string $path) => [basename($path) => (int) preg_replace('/\D/', '', basename($path))])
        ->sort()
        ->keys()
        ->map(fn (string $file) => asset('videos/'.$file))
        ->values();
@endphp
@if ($heroVideos->isNotEmpty())
<video class="absolute inset-0 w-full h-full object-cover pointer-events-none"
       autoplay muted playsinline preload="auto" disablepictureinpicture aria-hidden="true"
       @if ($heroVideos->count() === 1) loop @endif
       src="{{ $heroVideos->first() }}"
       x-data="{ videos: @js($heroVideos), current: 0 }"
       x-init="$el.muted = true; $el.play().catch(() => {})"
       @ended="current = (current + 1) % videos.length; $el.src = videos[current]; $el.play().catch(() => {})"
       @visibilitychange.document="document.hidden || $el.play().catch(() => {})"></video>
@endif
