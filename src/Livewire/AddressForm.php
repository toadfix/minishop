<?php

namespace Minishop\Livewire;

use Livewire\Component;

class AddressForm extends Component
{
    public string $name = '';

    public string $line1 = '';

    public string $line2 = '';

    public string $city = '';

    public string $state = '';

    public string $postal_code = '';

    public string $country = 'CA';

    public bool $saved = false;

    public function mount(): void
    {
        $address = auth()->user()?->customer?->defaultBillingAddress;

        if ($address) {
            $this->fill($address->only(['name', 'line1', 'line2', 'city', 'state', 'postal_code', 'country']));
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $customer = auth()->user()->customer;

        $customer->addresses()->updateOrCreate(
            ['type' => 'billing', 'is_default' => true],
            $validated + ['type' => 'billing', 'is_default' => true],
        );

        $this->saved = true;
    }

    public function render()
    {
        return view('minishop::livewire.storefront.address-form');
    }
}
