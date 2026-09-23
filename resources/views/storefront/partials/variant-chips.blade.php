{{--
    PDP option chips for one attribute group (size / color). Needs the PDP scope: `selected`, `optionAvailable()`, `priceFor()`.
    @var array{slug: string, label: string, values: array<int, string>} $group
--}}
@foreach($group['values'] as $value)
    <button type="button" @click="selected['{{ $group['slug'] }}'] = @js($value)"
            :disabled="! optionAvailable(@js($group['slug']), @js($value))"
            class="nf-chip" :class="selected['{{ $group['slug'] }}'] === @js($value) && 'is-active'"
            :aria-pressed="selected['{{ $group['slug'] }}'] === @js($value)">
        <span class="nf-chip-check" aria-hidden="true">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
        </span>
        @if($group['slug'] === 'color')
            <span class="w-3.5 h-3.5 rounded-full ring-1 ring-black/10 flex-none" style="background: {{ color_hex($value) }}"></span>
        @endif
        <span>{{ bn_digits($value) }}</span>
        <template x-if="priceFor(@js($group['slug']), @js($value)) !== null">
            <span class="nf-chip-price" x-text="'৳' + bnNumber(priceFor(@js($group['slug']), @js($value)))"></span>
        </template>
    </button>
@endforeach
