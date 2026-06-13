<div class="space-y-4">
    @if ($variants->isNotEmpty())
        <div>
            <label class="block text-sm font-medium text-gray-700">Options</label>
            <select wire:model="variantId" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                @foreach ($variants as $variant)
                    <option value="{{ $variant->id }}">
                        {{ $variant->optionValues->pluck('value')->join(' / ') ?: ($variant->sku ?? 'Variant #'.$variant->id) }}
                        — {{ \Minishop\Support\Money::format($variant->price ?? $product->price) }}
                    </option>
                @endforeach
            </select>
            @error('variantId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @endif

    <div class="flex items-end gap-3">
        <div class="w-24">
            <label class="block text-sm font-medium text-gray-700">Qty</label>
            <input type="number" min="1" max="99" wire:model="quantity"
                   class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
        </div>

        <button type="button" wire:click="add" wire:loading.attr="disabled"
                class="inline-flex items-center rounded-md bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="add">Add to cart</span>
            <span wire:loading wire:target="add">Adding…</span>
        </button>
    </div>

    @error('quantity') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

    @if ($added)
        <p class="text-sm font-medium text-green-700" wire:key="added-flag">Added to your cart.
            <a href="{{ route('storefront.cart.show') }}" class="underline">View cart</a>
        </p>
    @endif
</div>
