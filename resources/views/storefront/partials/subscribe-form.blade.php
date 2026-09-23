{{-- Newsletter / campaign-SMS signup on the dark panels (home, offers). --}}
<form method="POST" action="{{ route('store.subscribe') }}" class="flex gap-2.5 flex-wrap"
      x-data="{ busy: false }"
      @submit.prevent="
        busy = true;
        fetch($el.action, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData($el) })
            .then(async (r) => { const d = await r.json(); window.nfFlush?.(d.analytics); $dispatch('cart:toast', { type: r.ok ? 'success' : 'error', msg: d.message }); if (r.ok) $el.reset(); })
            .catch(() => $el.submit())
            .finally(() => busy = false);
      ">
    @csrf
    <input type="hidden" name="source" value="{{ $source }}">
    <input type="{{ $type }}" name="contact" required placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}"
           class="flex-1 min-w-[200px] bg-white/10 rounded-full px-[22px] py-4 text-[15px] text-white placeholder-[#CFC6C1] outline-none focus:bg-white/15">
    <button :disabled="busy" class="bg-white text-espresso rounded-full px-8 py-4 text-[15px] font-semibold disabled:opacity-60">{{ $button }}</button>
</form>
