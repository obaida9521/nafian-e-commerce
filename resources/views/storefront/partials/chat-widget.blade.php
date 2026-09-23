@php
    $general = app(\App\Services\SettingsService::class)->group('general');
    $waNumber = preg_replace('/[^0-9]/', '', (string) ($general['whatsapp'] ?? ''));
    $messenger = trim((string) ($general['messenger'] ?? ''));
@endphp
@if($waNumber || $messenger)
    <div class="fixed bottom-[100px] sm:bottom-5 right-4 sm:right-5 z-[45] flex flex-col gap-3" x-data="{ hover: false }">
        @if($messenger)
            <a href="https://m.me/{{ ltrim($messenger, '@/') }}" target="_blank" rel="noopener" aria-label="Chat on Messenger"
               class="w-12 h-12 rounded-full shadow-lg grid place-items-center text-white transition hover:scale-105"
               style="background:linear-gradient(45deg,#0099FF,#A033FF,#FF5280);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.44 3.14 7.17.16.14.26.34.27.56l.05 1.78c.02.57.6.94 1.12.71l1.99-.88c.17-.07.36-.09.54-.04 1.04.29 2.14.44 3.29.44 5.64 0 10-4.13 10-9.7C22 6.13 17.64 2 12 2Zm6 7.46-2.93 4.66c-.47.74-1.47.93-2.18.41l-2.34-1.75a.6.6 0 0 0-.72 0l-3.16 2.4c-.42.32-.97-.18-.69-.63l2.93-4.66c.47-.74 1.47-.93 2.18-.41l2.34 1.75a.6.6 0 0 0 .72 0l3.16-2.4c.42-.32.97.18.69.63Z"/></svg>
            </a>
        @endif
        @if($waNumber)
            <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"
               class="w-12 h-12 rounded-full shadow-lg grid place-items-center text-white transition hover:scale-105"
               style="background:#25D366;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51l-.57-.01c-.2 0-.52.07-.79.37-.27.3-1.04 1.01-1.04 2.47s1.06 2.87 1.21 3.07c.15.2 2.09 3.2 5.07 4.49.71.31 1.26.49 1.69.62.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35ZM12.04 2C6.55 2 2.08 6.47 2.08 11.96c0 1.76.46 3.48 1.34 5L2 22l5.16-1.35a9.93 9.93 0 0 0 4.88 1.24h.01c5.49 0 9.96-4.47 9.96-9.96A9.9 9.9 0 0 0 19.07 4.9 9.9 9.9 0 0 0 12.04 2Z"/></svg>
            </a>
        @endif
    </div>
@endif
