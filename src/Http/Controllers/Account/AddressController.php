<?php

namespace Minishop\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Minishop\Http\Requests\Account\UpsertAddressRequest;

class AddressController extends Controller
{
    public function edit(Request $request): Response
    {
        $customer = $request->user()->customer;
        $address = $customer->defaultBillingAddress;

        return Inertia::render('storefront/Account/Address/Edit', [
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
