@php
    $attrOptions = $attributes->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values();
@endphp

<div class="rounded-xl bg-white border border-[#EADBC4] p-6"
    x-data="variantManager(@js($initialVariants), @js($attrOptions))">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-900">Variants</h2>
        <button type="button" @click="addVariant()"
            class="inline-flex items-center gap-1.5 rounded-lg border border-wine-700 px-3 py-1.5 text-sm font-medium text-wine-700 hover:bg-wine-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Variant
        </button>
    </div>

    @error('variants')<p class="mb-3 text-sm text-red-600">{{ $message }}</p>@enderror

    <div class="space-y-4">
        <template x-for="(variant, vi) in variants" :key="vi">
            <div class="rounded-lg border border-gray-200 p-4 bg-cream-50/40">
                <input type="hidden" :name="`variants[${vi}][id]`" :value="variant.id">

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-gray-500">SKU</label>
                        <input type="text" :name="`variants[${vi}][sku]`" x-model="variant.sku" required
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Price ({{ $symbol }})</label>
                        <input type="number" step="0.01" min="0" :name="`variants[${vi}][price]`" x-model="variant.price" required
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Stock</label>
                        <input type="number" min="0" :name="`variants[${vi}][stock_quantity]`" x-model="variant.stock_quantity" required
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Compare At</label>
                        <input type="number" step="0.01" min="0" :name="`variants[${vi}][compare_at_price]`" x-model="variant.compare_at_price"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Cost Price</label>
                        <input type="number" step="0.01" min="0" :name="`variants[${vi}][cost_price]`" x-model="variant.cost_price"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700 mt-6">
                        <input type="checkbox" :name="`variants[${vi}][is_active]`" value="1" x-model="variant.is_active"
                            class="rounded border-gray-300 text-wine-700 focus:ring-wine-500/30">
                        Active
                    </label>
                </div>

                {{-- Attributes --}}
                <div class="mt-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-medium text-gray-500">Attributes</label>
                        <button type="button" @click="addAttr(vi)" class="text-xs text-wine-700 hover:underline">+ Add attribute</button>
                    </div>
                    <div class="mt-2 space-y-2">
                        <template x-for="(attr, ai) in variant.attributes" :key="ai">
                            <div class="flex items-center gap-2">
                                <select :name="`variants[${vi}][attributes][${ai}][attribute_id]`" x-model="attr.attribute_id"
                                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white">
                                    <option value="">— attribute —</option>
                                    <template x-for="opt in attributeOptions" :key="opt.id">
                                        <option :value="opt.id" x-text="opt.name"></option>
                                    </template>
                                </select>
                                <input type="text" :name="`variants[${vi}][attributes][${ai}][value]`" x-model="attr.value" placeholder="Value (e.g. 100ml)"
                                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">
                                <button type="button" @click="removeAttr(vi, ai)" class="text-red-500 hover:text-red-700 px-1">&times;</button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mt-3 text-right">
                    <button type="button" @click="removeVariant(vi)" x-show="variants.length > 1"
                        class="text-sm text-red-600 hover:underline">Remove variant</button>
                </div>
            </div>
        </template>
    </div>
</div>

@push('head')
<script>
    function variantManager(initial, attrOptions) {
        return {
            variants: initial.length ? initial : [],
            attributeOptions: attrOptions,
            addVariant() {
                this.variants.push({ id: null, sku: '', price: '', compare_at_price: '', cost_price: '', stock_quantity: 0, is_active: true, attributes: [] });
            },
            removeVariant(i) { this.variants.splice(i, 1); },
            addAttr(vi) { this.variants[vi].attributes.push({ attribute_id: '', value: '' }); },
            removeAttr(vi, ai) { this.variants[vi].attributes.splice(ai, 1); },
        };
    }
</script>
@endpush
