<div x-show="cartOpen" x-cloak @click="cartOpen=false"
     class="fixed inset-0 z-[60] bg-[#1A1413]/45"
     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div @click.stop x-show="cartOpen" id="cart-contents" role="dialog" aria-label="আপনার ব্যাগ"
         class="absolute bottom-0 inset-x-0 max-h-[88vh] rounded-t-[26px] sm:rounded-none sm:inset-x-auto sm:top-0 sm:right-0 sm:max-h-none sm:h-full sm:w-[440px] bg-white shadow-2xl flex flex-col"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full sm:translate-y-0 sm:translate-x-full" x-transition:enter-end="translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 sm:translate-x-0" x-transition:leave-end="translate-y-full sm:translate-y-0 sm:translate-x-full">
        @include('storefront.partials.cart-contents')
    </div>
</div>
