@inject('settings', 'App\Services\SettingsService')
@inject('analytics', 'App\Services\AnalyticsService')
@php
    $px = $settings->group('pixels');
    $trackingConfig = $analytics->browserConfig();
    $queuedEvents = $analytics->browserEnabled() ? $analytics->pull() : [];
@endphp

@if(!empty($px['fb_enabled']) && !empty($px['fb_pixel']))
    {{-- Meta Pixel --}}
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json($px['fb_pixel'])); fbq('track', 'PageView');
    </script>
@endif

@if(!empty($px['ga4_enabled']) && !empty($px['ga4']))
    {{-- Google Analytics 4 --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $px['ga4'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date()); gtag('config', @json($px['ga4']));
    </script>
@endif

@if(!empty($px['ga4_enabled']) && !empty($px['gtm']))
    {{-- Google Tag Manager --}}
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
        var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;
        j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',@json($px['gtm']));</script>
@endif

@if(!empty($px['tiktok_enabled']) && !empty($px['tiktok']))
    {{-- TikTok Pixel --}}
    <script>
        !function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];
        ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
        ttq.load=function(e){var n="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=n;ttq._t=ttq._t||{};ttq._t[e]=+new Date;var o=d.createElement("script");o.type="text/javascript";o.async=!0;o.src=n+"?sdkid="+e+"&lib="+t;var a=d.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
        ttq.load(@json($px['tiktok']));ttq.page();}(window,document,'ttq');
    </script>
@endif

{{--
    nfTrack(name, params, eventId): one GA4-shaped e-commerce event fanned out to every enabled
    platform. Always defined (a no-op without pixels) so views and app.js can call it freely.
--}}
<script>
    (function () {
        var cfg = @json(['map' => $trackingConfig['map']]);
        window.dataLayer = window.dataLayer || [];

        window.nfTrack = function (name, params, eventId) {
            params = params || {};
            var items = params.items || [];
            var map = cfg.map[name] || {};

            try {
                @if($trackingConfig['ga4'])
                if (window.gtag) {
                    gtag('event', name, params);
                }
                @endif
                @if($trackingConfig['gtm'])
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({ event: name, event_id: eventId, ecommerce: params });
                @endif
                @if($trackingConfig['meta'])
                if (window.fbq && map.meta) {
                    var fb = {
                        currency: params.currency,
                        value: params.value,
                        content_type: items.length ? 'product' : undefined,
                        content_ids: items.map(function (i) { return i.item_id; }),
                        contents: items.map(function (i) { return { id: i.item_id, quantity: i.quantity, item_price: i.price }; }),
                        num_items: items.reduce(function (n, i) { return n + (i.quantity || 1); }, 0) || undefined,
                        content_name: items.length === 1 ? items[0].item_name : undefined,
                        content_category: items.length === 1 ? items[0].item_category : undefined,
                        search_string: params.search_term,
                        order_id: params.transaction_id,
                    };
                    fbq('track', map.meta, fb, { eventID: eventId });
                }
                @endif
                @if($trackingConfig['tiktok'])
                if (window.ttq && map.tiktok) {
                    ttq.track(map.tiktok, {
                        currency: params.currency,
                        value: params.value,
                        query: params.search_term,
                        content_type: items.length ? 'product' : undefined,
                        contents: items.map(function (i) { return { content_id: i.item_id, content_name: i.item_name, quantity: i.quantity, price: i.price }; }),
                    }, { event_id: eventId });
                }
                @endif
            } catch (e) {
                console.warn('nfTrack failed', name, e);
            }
        };

        // Replay events the server queued (from this request or a redirect before it).
        window.nfFlush = function (events) {
            (events || []).forEach(function (e) { window.nfTrack(e.name, e.params, e.id); });
        };
        window.nfFlush(@json($queuedEvents));
    })();
</script>
