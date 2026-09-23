@php $max = max($variant->available_quantity, $line['quantity']); @endphp
<div @class([
    'flex items-center bg-white rounded-full',
    'gap-[13px] px-[13px] py-[7px]' => $size === 'sm',
    'gap-4 px-[18px] py-2.5' => $size === 'lg',
])>
    <form method="POST" action="{{ route('store.cart.update', $variant->id) }}" class="js-cart-form flex">
        @csrf @method('PATCH')
        <input type="hidden" name="quantity" value="{{ $line['quantity'] - 1 }}">
        @if($bagPage)<input type="hidden" name="bag_page" value="1">@endif
        <button @class(['text-muted leading-none', 'text-[16px]' => $size === 'sm', 'text-[19px]' => $size === 'lg']) aria-label="কমান">−</button>
    </form>
    <span @class(['font-semibold text-center', 'text-[14px]' => $size === 'sm', 'text-[15.5px] min-w-[18px]' => $size === 'lg'])>{{ bn_digits($line['quantity']) }}</span>
    <form method="POST" action="{{ route('store.cart.update', $variant->id) }}" class="js-cart-form flex">
        @csrf @method('PATCH')
        <input type="hidden" name="quantity" value="{{ min($line['quantity'] + 1, $max) }}">
        @if($bagPage)<input type="hidden" name="bag_page" value="1">@endif
        <button @class(['text-espresso leading-none disabled:opacity-30', 'text-[16px]' => $size === 'sm', 'text-[19px]' => $size === 'lg']) @disabled($line['quantity'] >= $max) aria-label="বাড়ান">+</button>
    </form>
</div>
