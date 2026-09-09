<div x-show="cartOpen" x-cloak @click="cartOpen=false" @keydown.escape.window="cartOpen=false"
     class="fixed inset-0 z-[60] bg-[#3c1018]/40"
     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div @click.stop x-show="cartOpen" id="cart-contents"
         class="absolute top-0 right-0 h-full w-[420px] max-w-[92vw] bg-white shadow-2xl flex flex-col"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
        @include('storefront.partials.cart-contents')
    </div>
</div>
