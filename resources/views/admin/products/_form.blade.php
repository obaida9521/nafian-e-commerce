@php
    $symbol = config('shop.currency_symbol');

    // Build initial variant rows from old() input or the existing model.
    if (old('variants')) {
        $initialVariants = collect(old('variants'))->map(fn ($v) => [
            'id' => $v['id'] ?? null,
            'sku' => $v['sku'] ?? '',
            'price' => $v['price'] ?? '',
            'compare_at_price' => $v['compare_at_price'] ?? '',
            'cost_price' => $v['cost_price'] ?? '',
            'stock_quantity' => $v['stock_quantity'] ?? 0,
            'is_active' => (bool) ($v['is_active'] ?? true),
            'attributes' => collect($v['attributes'] ?? [])->map(fn ($a) => [
                'attribute_id' => $a['attribute_id'] ?? '',
                'value' => $a['value'] ?? '',
            ])->values()->all(),
        ])->values()->all();
    } elseif ($product->exists) {
        $initialVariants = $product->variants->map(fn ($v) => [
            'id' => $v->id,
            'sku' => $v->sku,
            'price' => $v->price,
            'compare_at_price' => $v->compare_at_price,
            'cost_price' => $v->cost_price,
            'stock_quantity' => $v->stock_quantity,
            'is_active' => (bool) $v->is_active,
            'attributes' => $v->attributeValues->map(fn ($a) => [
                'attribute_id' => $a->attribute_id,
                'value' => $a->value,
            ])->values()->all(),
        ])->values()->all();
    } else {
        $initialVariants = [[
            'id' => null, 'sku' => '', 'price' => '', 'compare_at_price' => '',
            'cost_price' => '', 'stock_quantity' => 0, 'is_active' => true, 'attributes' => [],
        ]];
    }
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Main column --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-xl bg-white border border-[#EADBC4] p-6 space-y-5">
            <h2 class="font-semibold text-gray-900">Product Details</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input name="name" value="{{ old('name', $product->name) }}" required
                    class="mt-1.5 w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Short Description</label>
                <input name="short_description" value="{{ old('short_description', $product->short_description) }}"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="5"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">{{ old('description', $product->description) }}</textarea>
            </div>
        </div>

        @include('admin.variants._form', ['initialVariants' => $initialVariants, 'attributes' => $attributes, 'symbol' => $symbol])

        <div class="rounded-xl bg-white border border-[#EADBC4] p-6 space-y-5">
            <h2 class="font-semibold text-gray-900">SEO</h2>
            <div>
                <label class="block text-sm font-medium text-gray-700">Meta Title</label>
                <input name="meta_title" value="{{ old('meta_title', $product->meta_title) }}"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                <textarea name="meta_description" rows="2"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">{{ old('meta_description', $product->meta_description) }}</textarea>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        <div class="rounded-xl bg-white border border-[#EADBC4] p-6 space-y-4">
            <h2 class="font-semibold text-gray-900">Status</h2>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))
                    class="rounded border-gray-300 text-wine-700 focus:ring-wine-500/30">
                Active
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false))
                    class="rounded border-gray-300 text-wine-700 focus:ring-wine-500/30">
                Featured
            </label>
        </div>

        <div class="rounded-xl bg-white border border-[#EADBC4] p-6 space-y-3">
            <h2 class="font-semibold text-gray-900">Categories</h2>
            <div class="space-y-2 max-h-56 overflow-y-auto scrollbar-thin">
                @forelse ($categories as $category)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                            @checked(in_array($category->id, old('categories', $selectedCategories)))
                            class="rounded border-gray-300 text-wine-700 focus:ring-wine-500/30">
                        {{ $category->name }}
                    </label>
                @empty
                    <p class="text-sm text-gray-400">No categories. Create some first.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl bg-white border border-[#EADBC4] p-6 space-y-3"
            x-data="{
                previews: [],
                removed: [],
                onChange(e) { this.addFiles(e.target.files); },
                addFiles(files) {
                    for (const f of files) {
                        if (!f.type.startsWith('image/')) continue;
                        this.previews.push({ file: f, url: URL.createObjectURL(f) });
                    }
                    this.syncInput();
                },
                removeNew(i) {
                    URL.revokeObjectURL(this.previews[i].url);
                    this.previews.splice(i, 1);
                    this.syncInput();
                },
                syncInput() {
                    const dt = new DataTransfer();
                    this.previews.forEach(p => dt.items.add(p.file));
                    this.$refs.input.files = dt.files;
                },
                removeExisting(id) { if (!this.removed.includes(id)) this.removed.push(id); },
            }">
            <h2 class="font-semibold text-gray-900">Images</h2>

            {{-- Existing images --}}
            @if ($product->exists && $product->getMedia('images')->isNotEmpty())
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($product->getMedia('images') as $media)
                        <div class="relative group" x-show="!removed.includes({{ $media->id }})">
                            <img src="{{ $media->getUrl() }}" class="aspect-square object-cover rounded-lg border border-gray-200">
                            <button type="button" @click="removeExisting({{ $media->id }})" title="Remove"
                                class="absolute -top-1.5 -right-1.5 w-6 h-6 rounded-full bg-wine-700 text-cream-100 text-xs flex items-center justify-center shadow opacity-0 group-hover:opacity-100 transition">✕</button>
                        </div>
                    @endforeach
                </div>
                <template x-for="id in removed" :key="id">
                    <input type="hidden" name="remove_images[]" :value="id">
                </template>
            @endif

            {{-- New image previews --}}
            <div x-show="previews.length" x-cloak class="grid grid-cols-3 gap-2">
                <template x-for="(p, i) in previews" :key="p.url">
                    <div class="relative group">
                        <img :src="p.url" class="aspect-square object-cover rounded-lg border border-gray-200">
                        <button type="button" @click="removeNew(i)" title="Remove"
                            class="absolute -top-1.5 -right-1.5 w-6 h-6 rounded-full bg-wine-700 text-cream-100 text-xs flex items-center justify-center shadow opacity-0 group-hover:opacity-100 transition">✕</button>
                    </div>
                </template>
            </div>

            {{-- Dropzone --}}
            <label @dragover.prevent="$el.classList.add('border-wine-500','bg-cream-50')"
                @dragleave.prevent="$el.classList.remove('border-wine-500','bg-cream-50')"
                @drop.prevent="$el.classList.remove('border-wine-500','bg-cream-50'); addFiles($event.dataTransfer.files)"
                class="flex flex-col items-center justify-center gap-1 border-2 border-dashed border-gray-300 rounded-lg py-6 cursor-pointer hover:border-wine-500 hover:bg-cream-50 transition">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-gray-400">
                    <path d="M12 16V4m0 0L8 8m4-4 4 4"/><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                </svg>
                <span class="text-sm text-gray-500">Click to upload or drag images here</span>
                <span class="text-xs text-gray-400">PNG, JPG up to 4MB</span>
                <input x-ref="input" type="file" name="images[]" multiple accept="image/*" class="hidden" @change="onChange">
            </label>
        </div>
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button class="rounded-lg bg-wine-700 px-6 py-2.5 text-sm font-semibold text-cream-100 hover:bg-wine-800">Save Product</button>
    <a href="{{ route('admin.products.index') }}" class="rounded-lg px-6 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100">Cancel</a>
</div>
