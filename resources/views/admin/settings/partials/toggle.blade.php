{{-- props: $state (Alpine boolean var name), $name (form field) --}}
<input type="hidden" name="{{ $name }}" :value="{{ $state }} ? 1 : 0">
<button type="button" @click="{{ $state }} = !{{ $state }}"
        class="w-[42px] h-6 rounded-full relative flex-none transition-colors"
        :class="{{ $state }} ? 'bg-wine-700' : 'bg-[#D8D3C7]'">
    <span class="absolute top-[3px] w-[18px] h-[18px] rounded-full bg-white transition-all"
          :style="{{ $state }} ? 'left:21px' : 'left:3px'"></span>
</button>
