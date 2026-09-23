@props(['name', 'size' => 22, 'stroke' => 1.8])
{{-- Line icons (currentColor): phone chrome (tab bar, header) and admin action buttons. --}}
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $stroke }}"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes->merge(['class' => 'flex-none']) }}>
    @switch($name)
        @case('home')
            <path d="M3.5 10.5 12 4l8.5 6.5"/><path d="M5.5 9v10.5h13V9"/><path d="M10 19.5v-5h4v5"/>
            @break
        @case('shop')
            <rect x="4" y="4" width="6.5" height="6.5" rx="1.6"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.6"/>
            <rect x="4" y="13.5" width="6.5" height="6.5" rx="1.6"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.6"/>
            @break
        @case('offer')
            <path d="M3.5 12.3V4.5a1 1 0 0 1 1-1h7.8a1 1 0 0 1 .7.3l7.7 7.7a1 1 0 0 1 0 1.4l-7.8 7.8a1 1 0 0 1-1.4 0l-7.7-7.7a1 1 0 0 1-.3-.7Z"/>
            <circle cx="8.3" cy="8.3" r="1.4"/>
            @break
        @case('bag')
            <path d="M5.5 8h13l-1 12.5h-11L5.5 8Z"/><path d="M9 10V6.8a3 3 0 0 1 6 0V10"/>
            @break
        @case('search')
            <circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.4-4.4"/>
            @break

        {{-- ── Actions ── --}}
        @case('plus')
            <path d="M12 5v14M5 12h14"/>
            @break
        @case('edit')
            <path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z"/><path d="m13.5 8.5 3 3"/>
            @break
        @case('trash')
            <path d="M4 7h16"/><path d="M9 7V4.8A.8.8 0 0 1 9.8 4h4.4a.8.8 0 0 1 .8.8V7"/><path d="M6.5 7l.9 12.2a1 1 0 0 0 1 .8h7.2a1 1 0 0 0 1-.8L17.5 7"/><path d="M10 11v5M14 11v5"/>
            @break
        @case('check')
            <path d="M5 12.5l4.5 4.5L19 7.5"/>
            @break
        @case('x')
            <path d="M6 6l12 12M18 6 6 18"/>
            @break
        @case('x-circle')
            <circle cx="12" cy="12" r="8.5"/><path d="m9.2 9.2 5.6 5.6M14.8 9.2l-5.6 5.6"/>
            @break
        @case('filter')
            <path d="M4 5.5h16l-6.2 7.3v5.4l-3.6 1.8v-7.2L4 5.5Z"/>
            @break
        @case('sliders')
            <path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/>
            @break
        @case('printer')
            <path d="M7 8V4h10v4"/><rect x="4" y="8" width="16" height="8" rx="2"/><path d="M7 14h10v6H7z"/>
            @break
        @case('download')
            <path d="M12 4v11M7.5 11 12 15.5 16.5 11"/><path d="M5 20h14"/>
            @break
        @case('mail')
            <rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4 7 8 6 8-6"/>
            @break
        @case('message')
            <path d="M5 5h14a1.5 1.5 0 0 1 1.5 1.5v9A1.5 1.5 0 0 1 19 17h-8l-4.5 3.5V17H5a1.5 1.5 0 0 1-1.5-1.5v-9A1.5 1.5 0 0 1 5 5Z"/><path d="M8 10h8M8 13h5"/>
            @break
        @case('ban')
            <circle cx="12" cy="12" r="8.5"/><path d="m6 6 12 12"/>
            @break
        @case('refresh')
            <path d="M19.5 12a7.5 7.5 0 1 1-2.2-5.3"/><path d="M19.5 4.5v4h-4"/>
            @break
        @case('box')
            <path d="m4 7.5 8-4 8 4v9l-8 4-8-4v-9Z"/><path d="m4 7.5 8 4 8-4M12 11.5v9"/>
            @break
        @case('arrow-left')
            <path d="M19 12H5M11 6l-6 6 6 6"/>
            @break
        @case('arrow-right')
            <path d="M5 12h14M13 6l6 6-6 6"/>
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6"/>
            @break
        @case('external')
            <path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v4.5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 18.5v-11A1.5 1.5 0 0 1 5.5 6H10"/>
            @break
        @case('logout')
            <path d="M14 4h4.5A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5H14"/><path d="M10 16.5 5.5 12 10 7.5M5.5 12H15"/>
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16"/>
            @break
        @case('image')
            <rect x="4" y="4.5" width="16" height="15" rx="2"/><circle cx="9" cy="9.5" r="1.6"/><path d="m20 15-4.5-4.5L6 19.5"/>
            @break
        @case('pin')
            <path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.4"/>
            @break
        @case('phone')
            <path d="M6.6 3.5h2.6l1.4 4-2 1.3a11 11 0 0 0 6.6 6.6l1.3-2 4 1.4v2.6a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 4.6 5.7a2 2 0 0 1 2-2.2Z"/>
            @break
        @case('truck')
            <path d="M3 6.5h11v9H3zM14 9.5h3.8l3.2 3.3v2.7h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>
            @break
        @case('shield')
            <path d="M12 3.5 5 6v5.5c0 4.3 3 7.6 7 9 4-1.4 7-4.7 7-9V6l-7-2.5Z"/><path d="m9 12 2.2 2.2L15.5 10"/>
            @break
        @case('return')
            <path d="M9 7 4.5 11.5 9 16"/><path d="M4.5 11.5H15a4.5 4.5 0 0 1 0 9h-2"/>
            @break
        @case('instagram')
            <rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="3.8"/><circle cx="17.2" cy="6.8" r=".6" fill="currentColor"/>
            @break
        @case('whatsapp')
            <path d="M4 20l1.2-4A8.3 8.3 0 1 1 8 18.8L4 20Z"/><path d="M9.2 8.6c.2-.5.5-.6.8-.6h.5c.2 0 .4.1.5.4l.7 1.6c.1.3 0 .5-.1.7l-.5.6c.6 1.2 1.6 2.1 2.8 2.7l.6-.5c.2-.2.4-.2.7-.1l1.6.7c.3.1.4.3.4.5v.5c0 .3-.2.6-.6.8-1.8.8-5.3-.8-7-3.4-.9-1.4-1.2-2.8-.8-3.5Z" fill="currentColor" stroke="none"/>
            @break
        @case('messenger')
            <path d="M12 3.5c-4.8 0-8.5 3.4-8.5 7.9 0 2.4 1.1 4.6 2.9 6v3.1l2.9-1.6c.9.3 1.8.4 2.7.4 4.8 0 8.5-3.4 8.5-7.9S16.8 3.5 12 3.5Z"/><path d="m7.8 13.3 2.6-2.8 1.9 1.6 2.6-2.8" />
            @break
        @case('cash')
            <rect x="3" y="6.5" width="18" height="11" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6.5 10v4M17.5 10v4"/>
            @break
    @endswitch
</svg>
