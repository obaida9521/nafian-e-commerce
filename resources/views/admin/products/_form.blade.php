@php
    $symbol = config('shop.currency_symbol');

    // Existing variant photos, so a failed validation round-trip can still show them.
    $variantImages = $product->exists
        ? $product->variants->mapWithKeys(fn ($v) => [$v->id => $v->getFirstMediaUrl('image', 'thumb') ?: $v->getFirstMediaUrl('image')])
        : collect();

    // Build initial variant rows from old() input or the existing model.
    if (old('variants')) {
        $initialVariants = collect(old('variants'))->map(fn ($v) => [
            'id' => $v['id'] ?? null,
            'image_url' => ! empty($v['id']) && empty($v['remove_image']) ? ($variantImages[$v['id']] ?? null) : null,
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
            'image_url' => $variantImages[$v->id] ?: null,
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
            'id' => null, 'image_url' => null, 'sku' => '', 'price' => '', 'compare_at_price' => '',
            'cost_price' => '', 'stock_quantity' => 0, 'is_active' => true, 'attributes' => [],
        ]];
    }
@endphp

<div class="grid desk:grid-cols-[1fr_340px] gap-[18px] items-start">
    {{-- Main column --}}
    <div class="flex flex-col gap-[18px]">
        <x-admin.card title="মূল তথ্য">
            <div class="mt-4 flex flex-col gap-4">
                <div>
                    <label class="nf-label" for="p-name">পণ্যের নাম</label>
                    <input id="p-name" name="name" value="{{ old('name', $product->name) }}" required class="nf-input nf-input-soft">
                </div>
                <div>
                    <label class="nf-label" for="p-short">সংক্ষিপ্ত বিবরণ</label>
                    <input id="p-short" name="short_description" value="{{ old('short_description', $product->short_description) }}" class="nf-input nf-input-soft">
                </div>
                <div>
                    <label class="nf-label" for="p-desc">বিস্তারিত বিবরণ</label>
                    <textarea id="p-desc" name="description" rows="5" class="nf-input nf-input-soft">{{ old('description', $product->description) }}</textarea>
                </div>
                <div>
                    <div class="nf-label">ক্যাটাগরি</div>
                    <div class="flex gap-2 flex-wrap">
                        @forelse ($categories as $category)
                            <label>
                                <input type="checkbox" name="categories[]" value="{{ $category->id }}" class="peer sr-only"
                                       @checked(in_array($category->id, old('categories', $selectedCategories)))>
                                <span class="rounded-full px-4 py-2.5 text-[14px] font-medium cursor-pointer bg-canvas text-cocoa peer-checked:bg-mocha peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-accent">{{ $category->name }}</span>
                            </label>
                        @empty
                            <p class="text-[14px] text-muted">কোনো ক্যাটাগরি নেই। আগে ক্যাটাগরি তৈরি করুন।</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </x-admin.card>

        @include('admin.variants._form', ['initialVariants' => $initialVariants, 'attributes' => $attributes, 'symbol' => $symbol])

        <x-admin.card title="ঘ্রাণের নোট">
            <div class="mt-4 grid gap-4 desk:grid-cols-3">
                <div>
                    <label class="nf-label" for="p-top">উপরের নোট</label>
                    <input id="p-top" name="notes_top" value="{{ old('notes_top', $product->notes_top) }}" class="nf-input nf-input-soft" placeholder="বার্গামট, গোলাপি মরিচ">
                </div>
                <div>
                    <label class="nf-label" for="p-heart">মধ্য নোট</label>
                    <input id="p-heart" name="notes_heart" value="{{ old('notes_heart', $product->notes_heart) }}" class="nf-input nf-input-soft" placeholder="তায়েফ গোলাপ, দারুচিনি">
                </div>
                <div>
                    <label class="nf-label" for="p-base">নিচের নোট</label>
                    <input id="p-base" name="notes_base" value="{{ old('notes_base', $product->notes_base) }}" class="nf-input nf-input-soft" placeholder="ঊদ, চন্দন, অ্যাম্বার">
                </div>
            </div>
            <div class="mt-4 grid gap-4 desk:grid-cols-2">
                <div>
                    <label class="nf-label" for="p-ingredients">উপাদান</label>
                    <textarea id="p-ingredients" name="ingredients" rows="3" class="nf-input nf-input-soft">{{ old('ingredients', $product->ingredients) }}</textarea>
                </div>
                <div>
                    <label class="nf-label" for="p-usage">ব্যবহারের নিয়ম</label>
                    <textarea id="p-usage" name="usage_instructions" rows="3" class="nf-input nf-input-soft">{{ old('usage_instructions', $product->usage_instructions) }}</textarea>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="SEO">
            <div class="mt-4 flex flex-col gap-4">
                <div>
                    <label class="nf-label" for="p-meta-title">মেটা টাইটেল</label>
                    <input id="p-meta-title" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}" class="nf-input nf-input-soft">
                </div>
                <div>
                    <label class="nf-label" for="p-meta-desc">মেটা ডিসক্রিপশন</label>
                    <textarea id="p-meta-desc" name="meta_description" rows="2" class="nf-input nf-input-soft">{{ old('meta_description', $product->meta_description) }}</textarea>
                </div>
            </div>
        </x-admin.card>
    </div>

    {{-- Sidebar --}}
    <div class="flex flex-col gap-[18px]">
        <x-admin.card
            x-data="{
                previews: [],
                removed: [],
                onChange(e) { this.addFiles(e.target.files); },
                addFiles(files) {
                    for (const f of files) {
                        if (! f.type.startsWith('image/')) continue;
                        this.previews.push({ file: f, url: URL.createObjectURL(f) });
                    }
                    this.syncInput();
                },
                removeNew(i) {
                    if (this.previews[i].file) URL.revokeObjectURL(this.previews[i].url);
                    this.previews.splice(i, 1);
                    this.syncInput();
                },
                syncInput() {
                    const dt = new DataTransfer();
                    this.previews.filter(p => p.file).forEach(p => dt.items.add(p.file));
                    this.$refs.input.files = dt.files;
                },
                async fromLibrary() {
                    const picked = await window.openMediaPicker({ multiple: true, title: 'পণ্যের ছবি বেছে নিন' });
                    picked.forEach(item => {
                        if (! this.previews.some(p => p.library === item.key)) this.previews.push({ library: item.key, url: item.url });
                    });
                },
                removeExisting(id) { if (! this.removed.includes(id)) this.removed.push(id); },
            }">
            <div class="text-[15.5px] font-semibold">ছবি</div>
            <div class="mt-3.5 grid grid-cols-2 gap-2.5">
                @if ($product->exists)
                    @foreach ($product->getMedia('images') as $media)
                        <div class="relative group aspect-[1/1.1]" x-show="! removed.includes({{ $media->id }})">
                            <img src="{{ $media->getUrl() }}" alt="" class="w-full h-full object-cover rounded-[14px]">
                            <button type="button" @click="removeExisting({{ $media->id }})" title="সরান"
                                    class="absolute -top-1.5 -right-1.5 w-6 h-6 rounded-full bg-mocha text-white text-xs grid place-items-center opacity-0 group-hover:opacity-100 transition">✕</button>
                        </div>
                    @endforeach
                    <template x-for="id in removed" :key="id">
                        <input type="hidden" name="remove_images[]" :value="id">
                    </template>
                @endif

                <template x-for="(p, i) in previews" :key="p.url">
                    <div class="relative group aspect-[1/1.1]">
                        <img :src="p.url" alt="" class="w-full h-full object-cover rounded-[14px]">
                        <template x-if="p.library">
                            <span>
                                <input type="hidden" name="library_images[]" :value="p.library">
                                <span class="absolute left-1.5 bottom-1.5 rounded-full bg-white/90 px-2 py-0.5 text-[10.5px] font-semibold text-cocoa">মিডিয়া</span>
                            </span>
                        </template>
                        <button type="button" @click="removeNew(i)" title="সরান"
                                class="absolute -top-1.5 -right-1.5 w-6 h-6 rounded-full bg-mocha text-white text-xs grid place-items-center opacity-0 group-hover:opacity-100 transition">✕</button>
                    </div>
                </template>

                <label class="aspect-[1/1.1] rounded-[14px] bg-clay-soft grid place-items-center text-[14px] font-medium text-muted cursor-pointer"
                       @dragover.prevent @drop.prevent="$refs.input.files = $event.dataTransfer.files; $refs.input.dispatchEvent(new Event('change', { bubbles: true }))">
                    + যোগ
                    <input x-ref="input" type="file" name="images[]" multiple accept="image/*" class="hidden" @change="onChange" data-image-editor data-aspect="0.909" data-max-width="1600">
                </label>
            </div>
            <button type="button" @click="fromLibrary()" class="mt-3 inline-flex items-center gap-1.5 text-[13px] font-semibold text-accent hover:underline"><x-ui.icon name="image" :size="15" />মিডিয়া থেকে বেছে নিন</button>
            <div class="mt-2 text-[12.5px] text-muted">PNG বা JPG, সর্বোচ্চ ৪MB। আপলোডের আগে ক্রপ ও রিসাইজ করা যাবে। ভ্যারিয়েন্টের নিজস্ব ছবি ভ্যারিয়েন্ট অংশে দিন।</div>
        </x-admin.card>

        <x-admin.card>
            <div x-data="{ url: @js(old('video_url', $product->video_url) ?? ''), get id() { const m = this.url.trim().match(/^(?:https?:\/\/)?(?:www\.|m\.)?(?:youtube\.com\/(?:watch\?(?:.*&)?v=|shorts\/|embed\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/); return m ? m[1] : null; } }">
            <div class="text-[15.5px] font-semibold">ভিডিও</div>
            <label class="nf-label mt-3.5" for="p-video">YouTube লিংক</label>
            <input id="p-video" name="video_url" type="url" x-model="url" placeholder="https://www.youtube.com/watch?v=…" class="nf-input nf-input-soft">
            @error('video_url')<p class="mt-1.5 text-[13px] text-rose">{{ $message }}</p>@enderror
            <template x-if="id">
                <div class="mt-3 relative rounded-[14px] overflow-hidden aspect-video bg-mocha">
                    <img :src="`https://i.ytimg.com/vi/${id}/hqdefault.jpg`" alt="" class="w-full h-full object-cover">
                    <span class="absolute inset-0 grid place-items-center"><span class="w-11 h-11 rounded-full bg-white/90 grid place-items-center text-mocha text-sm">▶</span></span>
                </div>
            </template>
            <p x-show="url && ! id" x-cloak class="mt-1.5 text-[13px] text-rose">এটি YouTube ভিডিও লিংক মনে হচ্ছে না।</p>
            <div class="mt-2.5 text-[12.5px] text-muted">পণ্যের পেজে ছবির সাথে থাকবে; ক্লিক করলে ছবির জায়গায় ভিডিও চলবে।</div>
            </div>
        </x-admin.card>

        <x-admin.card>
            <div class="text-[15.5px] font-semibold">প্রকাশনা</div>
            <div class="mt-3.5 flex flex-col gap-3 text-[14.5px] text-bark">
                @foreach([
                    ['is_active', 'অবস্থা · প্রকাশিত', old('is_active', $product->is_active ?? true)],
                    ['is_featured', 'হোমপেজে ফিচার', old('is_featured', $product->is_featured ?? false)],
                    ['is_combo', 'কম্বো প্যাক (অফার পেজে দেখান)', old('is_combo', $product->is_combo ?? false)],
                    ['hide_when_out_of_stock', 'স্টক শেষে লুকান', old('hide_when_out_of_stock', $product->hide_when_out_of_stock ?? false)],
                ] as [$field, $label, $checked])
                    <label class="flex justify-between items-center gap-3 cursor-pointer">
                        <span>{{ $label }}</span>
                        <input type="checkbox" name="{{ $field }}" value="1" class="peer sr-only" @checked($checked)>
                        <span class="nf-switch"><span></span></span>
                    </label>
                @endforeach
            </div>
            @if($product->exists)
                <div class="nf-line my-[18px]"></div>
                <div class="text-[13.5px] text-muted">স্লাগ</div>
                <div class="mt-1 text-[14.5px]">{{ $product->slug }}</div>
            @endif
        </x-admin.card>
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button class="inline-flex items-center gap-1.5 bg-mocha text-white rounded-full px-7 py-3 text-[14.5px] font-semibold"><x-ui.icon name="check" :size="15" />সংরক্ষণ করুন</button>
    <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1.5 rounded-full px-7 py-3 text-[14.5px] font-medium text-muted hover:bg-hair"><x-ui.icon name="x" :size="15" />বাতিল</a>
    @if($product->exists)
        <a href="{{ route('store.product', $product->slug) }}" target="_blank" rel="noopener" class="ml-auto inline-flex items-center gap-1.5 text-[14px] text-accent"><x-ui.icon name="external" :size="14" />প্রিভিউ</a>
    @endif
</div>
