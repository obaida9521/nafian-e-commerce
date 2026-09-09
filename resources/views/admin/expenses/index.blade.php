@extends('layouts.admin')
@section('title', 'Expenses')

@php $symbol = config('shop.currency_symbol'); @endphp

@section('content')
<div class="max-w-[1280px] mx-auto"
     x-data="{
        open: {{ $errors->any() ? 'true' : 'false' }}, editing: false, action: '{{ route('admin.expenses.store') }}',
        form: { title:@js(old('title','')), category:@js(old('category','other')), amount:@js(old('amount','')), spent_on:@js(old('spent_on', now()->toDateString())), notes:@js(old('notes','')) },
        create() { this.editing=false; this.action='{{ route('admin.expenses.store') }}'; this.form={ title:'', category:'other', amount:'', spent_on:@js(now()->toDateString()), notes:'' }; this.open=true; },
        edit(e, url) { this.editing=true; this.action=url; this.form=Object.assign({}, e); this.open=true; }
     }">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4.5">
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="month" value="{{ $monthValue }}" onchange="this.form.submit()"
                class="h-10 border border-[#EADBC4] rounded-lg px-3 text-sm bg-white outline-none focus:border-wine-700">
            <span class="text-[13px] text-gray-500">{{ $monthLabel }}</span>
        </form>
        @adminCan('expenses')
        <button @click="create()" class="h-10 px-4.5 rounded-lg bg-wine-700 hover:bg-[#4d141e] text-white text-sm font-semibold flex items-center gap-2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Add expense
        </button>
        @endadminCan
    </div>

    {{-- Summary --}}
    <div class="grid lg:grid-cols-[1fr_1.6fr] gap-4.5 mb-4.5">
        <div class="bg-wine-700 text-white rounded-2xl p-5.5 flex flex-col justify-between">
            <div class="text-[13px] text-white/70">Total expenses · {{ $monthLabel }}</div>
            <div class="text-[34px] font-semibold tracking-tight mt-2">{{ $symbol }}{{ number_format($monthlyTotal, 0) }}</div>
            <div class="text-[12px] text-white/60 mt-1">{{ $expenses->total() }} {{ Str::plural('entry', $expenses->total()) }}</div>
        </div>
        <div class="bg-white border border-[#EADBC4] rounded-2xl p-5">
            <div class="text-[13px] font-semibold text-gray-700 mb-3.5">Breakdown by category</div>
            @forelse($byCategory as $row)
                @php $pct = $monthlyTotal > 0 ? round($row['total'] / $monthlyTotal * 100) : 0; @endphp
                <div class="mb-3 last:mb-0">
                    <div class="flex items-center justify-between text-[12.5px] mb-1">
                        <span class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $row['category']->color() }}"></span>
                            <span class="text-gray-700">{{ $row['category']->label() }}</span>
                            <span class="text-gray-400">· {{ $row['count'] }}</span>
                        </span>
                        <span class="font-semibold">{{ $symbol }}{{ number_format($row['total'], 0) }} <span class="text-gray-400 font-normal">({{ $pct }}%)</span></span>
                    </div>
                    <div class="h-1.5 rounded-full bg-[#F6EAD8] overflow-hidden">
                        <div class="h-full rounded-full" style="width:{{ $pct }}%;background:{{ $row['category']->color() }}"></div>
                    </div>
                </div>
            @empty
                <div class="py-6 text-center text-gray-400 text-sm">No expenses yet this month.</div>
            @endforelse
        </div>
    </div>

    <div class="bg-white border border-[#EADBC4] rounded-2xl overflow-hidden">
        <div class="grid grid-cols-[1.6fr_1fr_0.9fr_1fr_110px] px-5 py-3 text-[11px] tracking-wide uppercase text-gray-400 font-semibold border-b border-[#EFE2CE]">
            <span>Title</span><span>Category</span><span class="text-right">Amount</span><span>Date</span><span class="text-right">Actions</span>
        </div>
        @forelse($expenses as $expense)
            @php
                $payload = ['title' => $expense->title, 'category' => $expense->category->value, 'amount' => (float) $expense->amount, 'spent_on' => $expense->spent_on->toDateString(), 'notes' => $expense->notes];
                $c = $expense->category->color();
            @endphp
            <div class="grid grid-cols-[1.6fr_1fr_0.9fr_1fr_110px] px-5 py-3.5 items-center border-b border-[#F6EAD8] text-[13.5px] hover:bg-[#FBEFDD]">
                <div class="min-w-0">
                    <div class="font-semibold truncate">{{ $expense->title }}</div>
                    @if($expense->notes)<div class="text-[11.5px] text-gray-400 truncate">{{ $expense->notes }}</div>@endif
                </div>
                <span><span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-[3px] rounded-full" style="background:{{ $c }}1a;color:{{ $c }}"><span class="w-1.5 h-1.5 rounded-full" style="background:{{ $c }}"></span>{{ $expense->category->label() }}</span></span>
                <span class="text-right font-semibold">{{ $symbol }}{{ number_format($expense->amount, 0) }}</span>
                <span class="text-gray-500">{{ $expense->spent_on->format('M j, Y') }}</span>
                <span class="flex justify-end gap-3.5">
                    @adminCan('expenses')
                    <button @click="edit(@js($payload), @js(route('admin.expenses.update', $expense)))" class="text-wine-700 font-semibold">Edit</button>
                    <x-admin.confirm-modal :action="route('admin.expenses.destroy', $expense)" title="Delete expense?" :message="'Delete '.$expense->title.'?'" trigger="Delete" class="text-red-600 font-semibold cursor-pointer" />
                    @endadminCan
                </span>
            </div>
        @empty
            <div class="px-5 py-12 text-center text-gray-400">No expenses for {{ $monthLabel }}.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $expenses->links() }}</div>

    {{-- Create / Edit modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[75] flex items-center justify-center">
        <div @click="open=false" class="absolute inset-0 bg-[#3c1018]/40"></div>
        <form method="POST" :action="action" class="relative bg-white rounded-[14px] w-[480px] max-w-[92vw] p-6.5 shadow-2xl">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
            <div class="text-lg font-semibold mb-5" x-text="editing ? 'Edit expense' : 'New expense'"></div>
            <div class="space-y-4 mb-5.5">
                <div><label class="block text-[13px] text-gray-500 mb-1.5">Title</label><input name="title" x-model="form.title" placeholder="Shop rent — June" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-wine-700"></div>
                <div class="grid grid-cols-2 gap-3.5">
                    <div><label class="block text-[13px] text-gray-500 mb-1.5">Category</label>
                        <select name="category" x-model="form.category" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3 text-sm bg-white outline-none focus:border-wine-700">
                            @foreach($categories as $category)
                                <option value="{{ $category->value }}">{{ $category->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="block text-[13px] text-gray-500 mb-1.5">Amount ({{ $symbol }})</label><input name="amount" x-model="form.amount" type="number" step="0.01" min="0" placeholder="0" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-wine-700"></div>
                </div>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">Date</label><input name="spent_on" x-model="form.spent_on" type="date" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-wine-700"></div>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">Notes <span class="text-gray-300">(optional)</span></label><textarea name="notes" x-model="form.notes" rows="2" class="w-full border border-[#EADBC4] rounded-[7px] px-3.5 py-2.5 text-sm outline-none focus:border-wine-700"></textarea></div>
            </div>
            <div class="flex justify-end gap-2.5">
                <button type="button" @click="open=false" class="h-[42px] px-5 border border-[#EADBC4] rounded-lg bg-white text-gray-700 text-sm font-semibold">Cancel</button>
                <button class="h-[42px] px-5 rounded-lg bg-wine-700 hover:bg-[#4d141e] text-white text-sm font-semibold" x-text="editing ? 'Save changes' : 'Record expense'"></button>
            </div>
        </form>
    </div>
</div>
@endsection
