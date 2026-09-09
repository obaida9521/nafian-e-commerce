@extends('storefront.account.layout')

@section('account')
<div x-data="{
        open: {{ $errors->any() ? 'true' : 'false' }}, editing: false, action: '{{ route('store.account.addresses.store') }}',
        form: { label:'', recipient_name:'', phone:'', address_line1:'', address_line2:'', city:'', district:'', postal_code:'', is_default:false },
        add() { this.editing=false; this.action='{{ route('store.account.addresses.store') }}'; this.form={ label:'', recipient_name:'', phone:'', address_line1:'', address_line2:'', city:'', district:'', postal_code:'', is_default:false }; this.open=true; },
        edit(a, url) { this.editing=true; this.action=url; this.form=Object.assign({}, a); this.open=true; }
     }">
    <div class="grid sm:grid-cols-2 gap-4">
        @foreach($addresses as $a)
            @php $payload = $a->only(['label','recipient_name','phone','address_line1','address_line2','city','district','postal_code','is_default']); @endphp
            <div class="bg-white border border-[#EADBC4] rounded-xl p-5">
                <div class="flex justify-between mb-2.5">
                    <span class="text-[13px] font-semibold">{{ $a->label ?? 'Address' }}</span>
                    <div class="flex gap-3 items-center">
                        @if($a->is_default)
                            <span class="text-[11px] text-[#691d2a] font-semibold">Default</span>
                        @else
                            <form method="POST" action="{{ route('store.account.addresses.default', $a) }}">@csrf @method('PATCH')<button class="text-[12px] text-gray-500 hover:text-[#691d2a]">Set default</button></form>
                        @endif
                        <button @click="edit(@js($payload), @js(route('store.account.addresses.update', $a)))" class="text-[12px] text-gray-500 hover:text-[#691d2a]">Edit</button>
                        <form method="POST" action="{{ route('store.account.addresses.destroy', $a) }}" onsubmit="return confirm('Delete this address?')">@csrf @method('DELETE')<button class="text-[12px] text-red-600">Delete</button></form>
                    </div>
                </div>
                <div class="text-sm font-semibold mb-1">{{ $a->recipient_name }}</div>
                <div class="text-[13px] text-gray-500 leading-snug">{{ $a->address_line1 }}@if($a->address_line2), {{ $a->address_line2 }}@endif<br>{{ $a->city }}, {{ $a->district }} {{ $a->postal_code }}<br>{{ $a->phone }}</div>
            </div>
        @endforeach
        <button @click="add()" class="border-[1.5px] border-dashed border-[#D8D3C7] rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-gray-500 hover:border-[#691d2a] hover:text-[#691d2a] min-h-[120px]">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 5v14M5 12h14"/></svg>
            <span class="text-[13px] font-semibold">Add new address</span>
        </button>
    </div>

    {{-- Add / edit modal --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[75] flex items-center justify-center">
        <div @click="open=false" class="absolute inset-0 bg-[#3c1018]/40"></div>
        <form method="POST" :action="action" class="relative bg-white rounded-[14px] w-[520px] max-w-[92vw] p-6.5 shadow-2xl max-h-[90vh] overflow-y-auto">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
            <div class="text-lg font-semibold mb-5" x-text="editing ? 'Edit address' : 'New address'"></div>
            <div class="grid grid-cols-2 gap-3.5">
                <div class="col-span-2"><label class="block text-[13px] text-gray-500 mb-1.5">Label <span class="text-gray-300">(e.g. Home)</span></label><input name="label" x-model="form.label" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a]"></div>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">Recipient name</label><input name="recipient_name" x-model="form.recipient_name" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a]"></div>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">Phone</label><input name="phone" x-model="form.phone" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a]"></div>
                <div class="col-span-2"><label class="block text-[13px] text-gray-500 mb-1.5">Address line 1</label><input name="address_line1" x-model="form.address_line1" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a]"></div>
                <div class="col-span-2"><label class="block text-[13px] text-gray-500 mb-1.5">Address line 2 <span class="text-gray-300">(optional)</span></label><input name="address_line2" x-model="form.address_line2" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a]"></div>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">City</label><input name="city" x-model="form.city" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a]"></div>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">District</label><input name="district" x-model="form.district" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a]"></div>
                <div><label class="block text-[13px] text-gray-500 mb-1.5">Postal code <span class="text-gray-300">(optional)</span></label><input name="postal_code" x-model="form.postal_code" class="w-full h-[42px] border border-[#EADBC4] rounded-[7px] px-3.5 text-sm outline-none focus:border-[#691d2a]"></div>
                <label class="flex items-end gap-2 text-sm text-gray-700 pb-2.5"><input type="checkbox" name="is_default" value="1" x-model="form.is_default" class="accent-[#691d2a]"> Set as default</label>
            </div>
            <div class="flex justify-end gap-2.5 mt-5.5">
                <button type="button" @click="open=false" class="h-[42px] px-5 border border-[#EADBC4] rounded-lg bg-white text-gray-700 text-sm font-semibold">Cancel</button>
                <button class="h-[42px] px-5 rounded-lg bg-[#691d2a] hover:bg-[#4d141e] text-white text-sm font-semibold" x-text="editing ? 'Save changes' : 'Add address'"></button>
            </div>
        </form>
    </div>
</div>
@endsection
