{{--
    Admin snackbars: animated toasts for every flash message (success / error / warning / info)
    and validation errors. Auto-dismiss with a countdown bar that pauses on hover.

    From JS:  window.adminToast('success', 'সংরক্ষণ হয়েছে')  or  $dispatch('toast', { type: 'error', message: '…' })
--}}
@php
    $initial = collect([
        ['success', session('success')],
        ['error', session('error')],
        ['warning', session('warning')],
        ['info', session('info') ?? session('status')],
    ])->filter(fn ($t) => filled($t[1]))->map(fn ($t) => ['type' => $t[0], 'message' => $t[1]])->values();

    if ($errors->any()) {
        $count = $errors->count();
        $initial->push([
            'type' => 'error',
            'title' => 'কিছু তথ্য ঠিক নেই',
            'message' => $errors->first().($count > 1 ? ' (আরও '.bn_digits($count - 1).'টি)' : ''),
        ]);
    }
@endphp

<div x-data="{
        toasts: [],
        titles: { success: 'সফল', error: 'ব্যর্থ হয়েছে', warning: 'সতর্কতা', info: 'তথ্য' },
        push(type, message, title = null) {
            if (! message) return;
            type = this.titles[type] ? type : 'info';
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type, message, title: title || this.titles[type], duration: type === 'error' ? 7000 : 4500, show: false });
            this.$nextTick(() => { const t = this.toasts.find(t => t.id === id); if (t) t.show = true; });
            if (this.toasts.length > 4) this.dismiss(this.toasts[0].id);
        },
        dismiss(id) {
            const t = this.toasts.find(t => t.id === id);
            if (! t || ! t.show) return;
            t.show = false;
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 260);
        },
     }"
     x-init="
        window.adminToast = (type, message, title) => push(type, message, title);
        @js($initial).forEach((t, i) => setTimeout(() => push(t.type, t.message, t.title), 120 + i * 140));
     "
     @toast.window="push($event.detail.type, $event.detail.message, $event.detail.title)"
     class="fixed z-[95] inset-x-3 bottom-[max(12px,env(safe-area-inset-bottom))] sm:inset-x-auto sm:right-6 sm:bottom-6 flex flex-col items-center sm:items-end gap-2.5 pointer-events-none"
     aria-live="polite">
    <template x-for="t in toasts" :key="t.id">
        <div x-show="t.show"
             x-transition:enter="transition ease-[cubic-bezier(.2,.9,.3,1.2)] duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-8"
             :role="t.type === 'error' ? 'alert' : 'status'"
             class="nf-toast group pointer-events-auto relative w-full sm:w-[380px] max-w-[440px] overflow-hidden rounded-2xl bg-white shadow-[0_18px_44px_-16px_rgba(36,28,26,.45)] ring-1 ring-black/5"
             :class="'is-' + t.type">
            <div class="flex items-start gap-3 px-4 py-3.5">
                <span class="nf-toast-icon mt-0.5 flex-none w-8 h-8 rounded-full grid place-items-center">
                    <template x-if="t.type === 'success'"><x-ui.icon name="check" :size="17" :stroke="2.4" /></template>
                    <template x-if="t.type === 'error'"><x-ui.icon name="x" :size="16" :stroke="2.4" /></template>
                    <template x-if="t.type === 'warning'"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 7v6M12 17h.01"/></svg></template>
                    <template x-if="t.type === 'info'"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 11v6M12 7h.01"/></svg></template>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="text-[14.5px] font-semibold text-ink" x-text="t.title"></div>
                    <div class="mt-0.5 text-[13.5px] leading-snug text-cocoa break-words" x-text="t.message"></div>
                </div>
                <button type="button" @click="dismiss(t.id)" class="flex-none -mr-1 -mt-0.5 w-7 h-7 rounded-full grid place-items-center text-muted hover:bg-sand hover:text-ink" aria-label="বন্ধ করুন">
                    <x-ui.icon name="x" :size="14" :stroke="2" />
                </button>
            </div>
            {{-- Countdown: dismisses when it runs out; hovering pauses it. --}}
            <div class="absolute inset-x-0 bottom-0 h-[3px] bg-black/5">
                <div class="nf-toast-bar h-full" :style="`animation-duration:${t.duration}ms`" @animationend="dismiss(t.id)"></div>
            </div>
        </div>
    </template>
</div>
