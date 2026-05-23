<?php

namespace Minishop\Http\Controllers\Account;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Minishop\Http\Controllers\Controller;
use Minishop\Http\Requests\Account\UpsertAddressRequest;
use Minishop\Rendering\StorefrontRendererContract;

class AddressController extends Controller
{
    public function __construct(private StorefrontRendererContract $renderer) {}

    public function edit(Request $request): mixed
    {
        $customer = $request->user()->customer;
        $address = $customer->defaultBillingAddress;

        return $this->renderer->render('storefront/Account/Address/Edit', [
            'address' => $address,
        ]);
    }

    public function update(UpsertAddressRequest $request): RedirectResponse
    {
        $customer = $request->user()->customer;

        $customer->addresses()
            ->updateOrCreate(
                ['type' => 'billing', 'is_default' => true],
                $request->validated() + ['type' => 'billing', 'is_default' => true],
            );

        return back()->with('status', 'Address saved.');
    }
}
