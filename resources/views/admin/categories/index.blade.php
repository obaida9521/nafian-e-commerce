@extends('layouts.admin')
@section('title', 'ক্যাটাগরি')

@section('content')
<div>
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4.5">
        <div class="text-[13px] text-gray-500">{{ $homeCount }} of your categories show as sections on the homepage.</div>
        @adminCan('categories')
        <a href="{{ route('admin.categories.create') }}" class="h-10 px-4.5 rounded-lg bg-wine-700 hover:bg-[#2A2220] text-white text-sm font-semibold flex items-center gap-2">
            <x-ui.icon name="plus" :size="16" />Add category
        </a>
        @endadminCan
    </div>

    @if($categories->isEmpty())
        <div class="bg-white border border-[#E9E4E0] rounded-xl px-5 py-12 text-center text-gray-400">No categories yet.</div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($categories as $category)
                @php $catImg = $category->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($category->image_path) : null; @endphp
                <div class="bg-white border border-[#E9E4E0] rounded-xl overflow-hidden">
                    <div class="h-24 relative overflow-hidden" style="background:linear-gradient(150deg,{{ $category->tone ?? '#E7DFD2' }},{{ $category->tone2 ?? '#CFC2AC' }});">
                        @if($catImg)
                            <img src="{{ $catImg }}" alt="{{ $category->name }}" class="absolute inset-0 w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0" style="background-image:repeating-linear-gradient(135deg,rgba(255,255,255,0.06) 0 2px,transparent 2px 20px);"></div>
                        @endif
                        @unless($category->is_active)
                            <span class="absolute top-2.5 left-2.5 bg-white/85 text-[11px] font-semibold px-2 py-0.5 rounded text-gray-600">Inactive</span>
                        @endunless
                    </div>
                    <div class="px-4.5 py-4">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-base font-semibold">{{ $category->name }}</span>
                            <span class="text-xs text-gray-400 font-mono">{{ $category->products_count }} items</span>
                        </div>
                        <div class="flex items-center justify-between mt-3.5 pt-3.5 border-t border-[#F5F2F0]">
                            <div class="flex items-center gap-2.5">
                                <form method="POST" action="{{ route('admin.categories.toggle-home', $category) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" title="Toggle homepage" class="w-[38px] h-[22px] rounded-full relative block transition-colors {{ $category->show_on_home ? 'bg-wine-700' : 'bg-[#D8D3C7]' }}">
                                        <span class="absolute top-0.5 w-[18px] h-[18px] rounded-full bg-white transition-all" style="left:{{ $category->show_on_home ? '18px' : '2px' }};"></span>
                                    </button>
                                </form>
                                <span class="text-[12.5px] text-gray-500">{{ $category->show_on_home ? 'On homepage' : 'Hidden' }}</span>
                            </div>
                            <div class="flex gap-3.5">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="inline-flex items-center gap-1.5 text-[13px] text-wine-700 font-semibold"><x-ui.icon name="edit" :size="14" />Edit</a>
                                <x-admin.confirm-modal
                                    :action="route('admin.categories.destroy', $category)"
                                    title="Delete category?"
                                    :message="'Delete '.$category->name.'? This can be restored later.'"
                                    trigger="Delete"
                                    class="text-[13px] text-red-600 font-semibold cursor-pointer" />
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
