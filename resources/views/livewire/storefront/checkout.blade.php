<div class="grid gap-8 lg:grid-cols-3">
    <form wire:submit="placeOrder" class="space-y-8 lg:col-span-2">
        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-gray-900">Contact</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-gray-900">Shipping address</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" wire:model="address_line1" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                    @error('address_line1') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Apartment, suite, etc. <span class="text-gray-400">(optional)</span></label>
                    <input type="text" wire:model="address_line2" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">City</label>
                    <input type="text" wire:model="city" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                    @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">State / Province</label>
                    <input type="text" wire:model="state" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                    @error('state') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Postal code</label>
                    <input type="text" wire:model="postcode" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                    @error('postcode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Country</label>
                    <input type="text" maxlength="2" wire:model="country" class="mt-1 block w-full rounded-md border-gray-300 text-sm uppercase focus:border-brand-500 focus:ring-brand-500">
                    @error('country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Shipping method</h2>
                <button type="button" wire:click="fetchRates" wire:loading.attr="disabled" wire:target="fetchRates"
                        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <span wire:loading.remove wire:target="fetchRates">Get shipping rates</span>
                    <span wire:loading wire:target="fetchRates">Fetching…</span>
                </button>
            </div>

            <div class="mt-4 space-y-2">
                @forelse ($rates as $i => $rate)
                    <label class="flex cursor-pointer items-center justify-between rounded-md border px-4 py-3 text-sm
                                  {{ $shipping_method_id === $rate['shipping_method_id'] && $service_code === $rate['service_code'] ? 'border-brand-500 ring-1 ring-brand-500' : 'border-gray-200' }}">
                        <span class="flex items-center gap-3">
                            <input type="radio" wire:click="selectRate({{ $i }})"
                                   @checked($shipping_method_id === $rate['shipping_method_id'] && $service_code === $rate['service_code'])
                                   class="text-brand-600 focus:ring-brand-500">
                            <span class="font-medium text-gray-900">{{ $rate['name'] }}</span>
                        </span>
                        <span class="font-medium text-gray-900">{{ \Minishop\Support\Money::format($rate['amount_cents']) }}</span>
                    </label>
                @empty
                    <p class="text-sm text-gray-500">Enter your address and fetch rates to see shipping options.</p>
                @endforelse
                @error('shipping_method_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        <button type="submit" wire:loading.attr="disabled" wire:target="placeOrder"
                class="w-full rounded-md bg-brand-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="placeOrder">Place order</span>
            <span wire:loading wire:target="placeOrder">Placing order…</span>
        </button>
    </form>

    <aside class="h-fit rounded-lg border border-gray-200 bg-white p-6 lg:col-span-1">
        <h2 class="text-lg font-semibold text-gray-900">Your order</h2>
        <ul class="mt-4 divide-y divide-gray-100 text-sm">
            @foreach ($cart->items as $item)
                <li class="flex justify-between gap-2 py-2">
                    <span class="text-gray-700">{{ $item->quantity }} &times; {{ $item->product->name }}</span>
                    <span class="text-gray-900">{{ \Minishop\Support\Money::format($item->unit_price * $item->quantity) }}</span>
                </li>
            @endforeach
        </ul>
        <div class="mt-4 flex justify-between border-t border-gray-200 pt-4 text-sm">
            <span class="text-gray-500">Subtotal</span>
            <span class="font-medium text-gray-900">{{ \Minishop\Support\Money::format($subtotal) }}</span>
        </div>
    </aside>
</div>
