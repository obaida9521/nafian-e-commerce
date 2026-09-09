<div class="rounded-xl bg-white border border-[#EADBC4] p-6 max-w-2xl space-y-5">
    <div>
        <label class="block text-sm font-medium text-gray-700">Name</label>
        <input name="name" value="{{ old('name', $category->name) }}" required
            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Parent Category</label>
        <select name="parent_id"
            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none bg-white">
            <option value="">— None (top level) —</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" rows="3"
            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm focus:border-wine-500 focus:ring-2 focus:ring-wine-500/30 outline-none">{{ old('description', $category->description) }}</textarea>
    </div>

    @php $catImg = $category->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($category->image_path) : null; @endphp
    <div x-data="{ preview: @js($catImg), remove: false, pick(e){ const f=e.target.files[0]; if(f){ this.preview=URL.createObjectURL(f); this.remove=false; } } }">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Category image <span class="text-gray-400 font-normal">(shown on homepage; falls back to tone gradient)</span></label>
        <div class="flex items-center gap-4">
            <div class="w-[120px] h-[80px] rounded-lg border border-[#EADBC4] overflow-hidden flex items-center justify-center flex-none"
                 style="background:linear-gradient(150deg,{{ $category->tone ?? '#E7DFD2' }},{{ $category->tone2 ?? '#CFC2AC' }});">
                <template x-if="preview && !remove"><img :src="preview" alt="" class="w-full h-full object-cover"></template>
            </div>
            <div class="flex flex-col gap-2">
                <label class="h-[38px] px-4 inline-flex items-center rounded-lg border border-[#EADBC4] bg-white text-sm font-semibold text-gray-700 cursor-pointer hover:border-wine-700 w-fit">
                    Upload image
                    <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="hidden" @change="pick($event)">
                </label>
                <label class="flex items-center gap-2 text-[13px] text-gray-500" x-show="preview">
                    <input type="checkbox" name="remove_image" value="1" x-model="remove" class="accent-wine-700"> Remove image
                </label>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Card tone</label>
            <input type="color" name="tone" value="{{ old('tone', $category->tone ?? '#E7DFD2') }}"
                class="mt-1.5 w-full h-10 rounded-lg border border-[#EADBC4] cursor-pointer">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Card tone (end)</label>
            <input type="color" name="tone2" value="{{ old('tone2', $category->tone2 ?? '#CFC2AC') }}"
                class="mt-1.5 w-full h-10 rounded-lg border border-[#EADBC4] cursor-pointer">
        </div>
    </div>

    <div class="flex items-center gap-4">
        <div class="w-32">
            <label class="block text-sm font-medium text-gray-700">Sort Order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0"
                class="mt-1.5 w-full rounded-lg border border-[#EADBC4] px-3.5 py-2.5 text-sm focus:border-wine-700 outline-none">
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-700 mt-6">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))
                class="rounded border-gray-300 text-wine-700">
            Active
        </label>
        <label class="flex items-center gap-2 text-sm text-gray-700 mt-6">
            <input type="checkbox" name="show_on_home" value="1" @checked(old('show_on_home', $category->show_on_home ?? false))
                class="rounded border-gray-300 text-wine-700">
            Show on homepage
        </label>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <button class="rounded-lg bg-wine-700 px-5 py-2.5 text-sm font-semibold text-cream-100 hover:bg-wine-800">Save</button>
        <a href="{{ route('admin.categories.index') }}" class="rounded-lg px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100">Cancel</a>
    </div>
</div>
