<form wire:submit="save" class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
    @if ($saved)
        <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-800" wire:key="saved">Address saved.</div>
    @endif

    <div>
        <label class="block text-sm font-medium text-gray-700">Full name</label>
        <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Address line 1</label>
        <input type="text" wire:model="line1" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
        @error('line1') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Address line 2 <span class="text-gray-400">(optional)</span></label>
        <input type="text" wire:model="line2" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
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
            <input type="text" wire:model="postal_code" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
            @error('postal_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Country</label>
            <input type="text" maxlength="2" wire:model="country" class="mt-1 block w-full rounded-md border-gray-300 text-sm uppercase focus:border-brand-500 focus:ring-brand-500">
            @error('country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <button type="submit" class="rounded-md bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Save address</button>
</form>
