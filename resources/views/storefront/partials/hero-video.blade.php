{{-- Looping hero background video; the gradient underneath shows until it loads or if it can't play. --}}
<video class="absolute inset-0 w-full h-full object-cover pointer-events-none"
       autoplay muted loop playsinline preload="auto" disablepictureinpicture aria-hidden="true"
       x-data x-init="$el.muted = true; $el.play().catch(() => {})"
       @visibilitychange.document="document.hidden || $el.play().catch(() => {})">
    <source src="{{ asset('videos/hero_video2.mp4') }}" type="video/mp4">
</video>
