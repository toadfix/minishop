<a href="{{ route('storefront.cart.show') }}" class="relative inline-flex items-center hover:text-brand-600" wire:loading.class="opacity-50">
    <span>Cart</span>
    @if ($count > 0)
        <span class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1.5 text-xs font-semibold text-white">
            {{ $count }}
        </span>
    @endif
</a>
