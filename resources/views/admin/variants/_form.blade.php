@php
    $attrOptions = $attributes->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values();
@endphp

<div class="bg-white rounded-[20px] p-5 sm:p-6 nf-shadow-soft"
    x-data="variantManager(@js($initialVariants), @js($attrOptions))">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-[17px] font-semibold">ভ্যারিয়েন্ট ও স্টক</h2>
        <button type="button" @click="addVariant()"
            class="inline-flex items-center gap-1.5 bg-clay-soft text-espresso rounded-full px-5 py-2.5 text-[14px] font-semibold">
            <x-ui.icon name="plus" :size="16" />ভ্যারিয়েন্ট যোগ করুন
        </button>
    </div>

    @error('variants')<p class="mb-3 text-[14px] text-rose">{{ $message }}</p>@enderror

    <div class="space-y-4">
        <template x-for="(variant, vi) in variants" :key="variant.key">
            <div class="rounded-2xl p-4 bg-canvas flex flex-col sm:flex-row gap-4">
                <input type="hidden" :name="`variants[${vi}][id]`" :value="variant.id">

                {{-- Variant photo (one per variant) --}}
                <div class="flex-none" x-data="{ input: null }" x-init="input = $el.querySelector('input[type=file]')">
                    <label class="block text-[12.5px] font-medium text-muted mb-1">ছবি</label>
                    <label class="relative block w-[96px] aspect-[1/1.1] rounded-[14px] overflow-hidden bg-clay-soft cursor-pointer"
                           @dragover.prevent @drop.prevent="const dt = new DataTransfer(); dt.items.add($event.dataTransfer.files[0]); input.files = dt.files; input.dispatchEvent(new Event('change', { bubbles: true }))">
                        <img x-show="variant.preview || (variant.image_url && ! variant.remove_image)" :src="variant.preview || variant.image_url" alt="" class="w-full h-full object-cover">
                        <span x-show="! variant.preview && (! variant.image_url || variant.remove_image)" class="absolute inset-0 grid place-items-center text-[13px] font-medium text-muted">+ ছবি</span>
                        <input type="file" accept="image/*" class="hidden" :name="`variants[${vi}][image]`" @change="setImage(variant, $event.target.files[0])" data-image-editor data-aspect="0.909" data-max-width="1600">
                    </label>
                    <input type="hidden" :name="`variants[${vi}][remove_image]`" :value="variant.remove_image ? 1 : 0">
                    <input type="hidden" :name="`variants[${vi}][library_image]`" :value="variant.library || ''">
                    <button type="button" @click="fromLibrary(variant, input)" class="mt-1.5 block text-[12.5px] font-semibold text-accent hover:underline">মিডিয়া থেকে</button>
                    <button type="button" x-show="variant.preview || (variant.image_url && ! variant.remove_image)" @click="clearImage(variant, input)"
                            class="mt-1.5 inline-flex items-center gap-1.5 text-[12.5px] text-rose hover:underline"><x-ui.icon name="trash" :size="13" />ছবি সরান</button>
                </div>

                <div class="flex-1 min-w-0">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <label class="block text-[12.5px] font-medium text-muted mb-1">SKU</label>
                        <input type="text" :name="`variants[${vi}][sku]`" x-model="variant.sku" required
                            class="nf-input !py-2.5 !text-[14px]">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-medium text-muted mb-1">দাম ({{ $symbol }})</label>
                        <input type="number" step="0.01" min="0" :name="`variants[${vi}][price]`" x-model="variant.price" required
                            class="nf-input !py-2.5 !text-[14px]">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-medium text-muted mb-1">স্টক</label>
                        <input type="number" min="0" :name="`variants[${vi}][stock_quantity]`" x-model="variant.stock_quantity" required
                            class="nf-input !py-2.5 !text-[14px]">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-medium text-muted mb-1">ছাড়ের আগের দাম</label>
                        <input type="number" step="0.01" min="0" :name="`variants[${vi}][compare_at_price]`" x-model="variant.compare_at_price"
                            class="nf-input !py-2.5 !text-[14px]">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-medium text-muted mb-1">ক্রয়মূল্য</label>
                        <input type="number" step="0.01" min="0" :name="`variants[${vi}][cost_price]`" x-model="variant.cost_price"
                            class="nf-input !py-2.5 !text-[14px]">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700 mt-6">
                        <input type="checkbox" :name="`variants[${vi}][is_active]`" value="1" x-model="variant.is_active"
                            class="rounded border-gray-300 text-wine-700 focus:ring-wine-500/30">
                        চালু
                    </label>
                </div>

                {{-- Attributes --}}
                <div class="mt-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-[12.5px] font-medium text-muted mb-1">অ্যাট্রিবিউট</label>
                        <button type="button" @click="addAttr(vi)" class="inline-flex items-center gap-1.5 text-[13px] text-accent hover:underline"><x-ui.icon name="plus" :size="14" />যোগ করুন</button>
                    </div>
                    <div class="mt-2 space-y-2">
                        <template x-for="(attr, ai) in variant.attributes" :key="ai">
                            <div class="flex items-center gap-2">
                                <select :name="`variants[${vi}][attributes][${ai}][attribute_id]`" x-model="attr.attribute_id"
                                    class="nf-input !py-2.5 !text-[14px] w-auto">
                                    <option value="">— অ্যাট্রিবিউট —</option>
                                    <template x-for="opt in attributeOptions" :key="opt.id">
                                        <option :value="opt.id" x-text="opt.name"></option>
                                    </template>
                                </select>
                                <input type="text" :name="`variants[${vi}][attributes][${ai}][value]`" x-model="attr.value" placeholder="মান (যেমন ১২ মিলি)"
                                    class="nf-input !py-2.5 !text-[14px] flex-1">
                                <button type="button" @click="removeAttr(vi, ai)" class="text-rose p-1.5 rounded-lg hover:bg-rose-soft" title="সরান" aria-label="অ্যাট্রিবিউট সরান"><x-ui.icon name="x" :size="15" /></button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mt-3 text-right">
                    <button type="button" @click="removeVariant(vi)" x-show="variants.length > 1"
                        class="inline-flex items-center gap-1.5 text-[13.5px] text-rose hover:underline"><x-ui.icon name="trash" :size="14" />ভ্যারিয়েন্ট সরান</button>
                </div>
                </div>
            </div>
        </template>
    </div>
</div>

@push('head')
<script>
    function variantManager(initial, attrOptions) {
        let nextKey = 0;
        const withState = (v) => ({ ...v, key: ++nextKey, preview: null, library: null, remove_image: false });

        return {
            variants: (initial.length ? initial : []).map(withState),
            attributeOptions: attrOptions,
            addVariant() {
                this.variants.push(withState({ id: null, image_url: null, sku: '', price: '', compare_at_price: '', cost_price: '', stock_quantity: 0, is_active: true, attributes: [] }));
            },
            setImage(variant, file, input = null) {
                if (! file || ! file.type.startsWith('image/')) return;
                if (input) { const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; }
                if (variant.preview) URL.revokeObjectURL(variant.preview);
                variant.preview = URL.createObjectURL(file);
                variant.library = null;
                variant.remove_image = false;
            },
            async fromLibrary(variant, input) {
                const [item] = await window.openMediaPicker({ title: 'ভ্যারিয়েন্টের ছবি বেছে নিন' });
                if (! item) return;
                if (input) input.value = '';
                if (variant.preview && ! variant.library) URL.revokeObjectURL(variant.preview);
                variant.preview = item.url;
                variant.library = item.key;
                variant.remove_image = false;
            },
            clearImage(variant, input) {
                if (input) input.value = '';
                if (variant.preview && ! variant.library) URL.revokeObjectURL(variant.preview);
                variant.preview = null;
                variant.library = null;
                variant.remove_image = !! variant.image_url;
            },
            removeVariant(i) { this.variants.splice(i, 1); },
            addAttr(vi) { this.variants[vi].attributes.push({ attribute_id: '', value: '' }); },
            removeAttr(vi, ai) { this.variants[vi].attributes.splice(ai, 1); },
        };
    }
</script>
@endpush
