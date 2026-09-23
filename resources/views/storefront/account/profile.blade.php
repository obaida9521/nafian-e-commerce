@extends('storefront.account.layout')

@section('account')
    <div class="bg-white border border-[#E9E4E0] rounded-xl p-6 max-w-[520px]">
        <h2 class="text-lg font-semibold mb-5">Profile details</h2>
        <form method="POST" action="{{ route('store.account.profile.update') }}">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-[13px] text-gray-500 mb-1.5">Full name</label>
                    <input name="name" value="{{ old('name', $user->name) }}" class="w-full h-[42px] border rounded-[7px] px-3.5 text-sm outline-none focus:border-[#3B2F2D] {{ $errors->has('name') ? 'border-red-400' : 'border-[#E9E4E0]' }}">
                    @error('name')<div class="text-xs text-red-600 mt-1.5">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="block text-[13px] text-gray-500 mb-1.5">Email</label>
                    <input name="email" value="{{ old('email', $user->email) }}" class="w-full h-[42px] border rounded-[7px] px-3.5 text-sm outline-none focus:border-[#3B2F2D] {{ $errors->has('email') ? 'border-red-400' : 'border-[#E9E4E0]' }}">
                    @error('email')<div class="text-xs text-red-600 mt-1.5">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="block text-[13px] text-gray-500 mb-1.5">Phone</label>
                    <input name="phone" value="{{ old('phone', $user->phone) }}" class="w-full h-[42px] border border-[#E9E4E0] rounded-[7px] px-3.5 text-sm outline-none focus:border-[#3B2F2D]">
                </div>
            </div>
            <button class="h-[42px] px-5.5 mt-5 rounded-lg bg-[#3B2F2D] hover:bg-[#2A2220] text-white text-sm font-semibold">Save changes</button>
        </form>
    </div>
@endsection
