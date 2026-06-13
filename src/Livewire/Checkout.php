<?php

namespace Minishop\Livewire;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;
use Minishop\Actions\BuildLineItemsAction;
use Minishop\Actions\CreateOrderAction;
use Minishop\Enums\OrderStatus;
use Minishop\Mail\OrderConfirmationMail;
use Minishop\Models\Cart;
use Minishop\Models\Customer;
use Minishop\Models\ShippingMethod;
use Minishop\Models\StoreSettings;
use Minishop\Models\User;
use Minishop\Payments\Facades\Payment;
use Minishop\Services\Shipping\ShippingRateService;

class Checkout extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address_line1 = '';

    public string $address_line2 = '';

    public string $city = '';

    public string $state = '';

    public string $postcode = '';

    public string $country = 'CA';

    public ?int $shipping_method_id = null;

    public ?string $carrier = null;

    public ?string $service_code = null;

    public string $coupon_code = '';

    public string $notes = '';

    /** @var array<int, array<string, mixed>> */
    public array $rates = [];

    public function mount(): void
    {
        if ($user = auth()->user()) {
            $this->name = $user->name ?? '';
            $this->email = $user->email ?? '';
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'shipping_method_id' => ['required', 'integer', 'exists:shipping_methods,id'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Build the [product_id, variant_id, quantity] payload from the cart.
     *
     * @return array<int, array<string, int|null>>
     */
    protected function cartItemsPayload(): array
    {
        $cart = Cart::resolveOrCreate(request());

        return $cart->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'quantity' => $item->quantity,
        ])->values()->all();
    }

    public function fetchRates(ShippingRateService $rateService): void
    {
        $this->validateOnly('postcode');
        $this->validateOnly('country');

        $methods = ShippingMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();
        $rates = [];

        foreach ($methods->filter->isFlatRate() as $method) {
            $rates[] = [
                'shipping_method_id' => $method->id,
                'carrier' => null,
                'service_code' => null,
                'name' => $method->name,
                'amount_cents' => $method->effective_price,
            ];
        }

        $calculated = $methods->filter->isCalculated();
        $settings = StoreSettings::current();

        if ($calculated->isNotEmpty() && $settings->origin_postcode && $this->cartItemsPayload() !== []) {
            try {
                $shipment = $rateService->buildShipmentData($this->postcode, $this->country, $this->cartItemsPayload());
                $carrierRates = $rateService->fetchRates($calculated, $shipment);

                foreach ($carrierRates as $rate) {
                    $rates[] = [
                        'shipping_method_id' => $rate->shippingMethodId,
                        'carrier' => $rate->carrier,
                        'service_code' => $rate->serviceCode,
                        'name' => $rate->serviceName,
                        'amount_cents' => $rate->amountCents,
                    ];
                }

                session()->put('shipping_quotes', $carrierRates->map(fn ($r) => [
                    'carrier' => $r->carrier,
                    'service_code' => $r->serviceCode,
                    'amount_cents' => $r->amountCents,
                ])->values()->all());
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->rates = $rates;
    }

    public function selectRate(int $index): void
    {
        $rate = $this->rates[$index] ?? null;

        if (! $rate) {
            return;
        }

        $this->shipping_method_id = $rate['shipping_method_id'];
        $this->carrier = $rate['carrier'];
        $this->service_code = $rate['service_code'];
    }

    public function placeOrder(BuildLineItemsAction $buildLineItems, CreateOrderAction $createOrder)
    {
        $validated = $this->validate();

        $items = $this->cartItemsPayload();

        if ($items === []) {
            $this->addError('shipping_method_id', 'Your cart is empty.');

            return null;
        }

        $lineItems = $buildLineItems->execute($items);

        $user = User::query()->firstOrCreate(
            ['email' => $validated['email']],
            ['name' => $validated['name'], 'password' => bcrypt(Str::random(32))]
        );

        $customer = Customer::query()->firstOrCreate(['user_id' => $user->id]);
        $settings = StoreSettings::current();

        $order = $createOrder->execute([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Pending->value,
            'payment_status' => 'pending',
            'payment_gateway' => $settings->active_payment_gateway,
            'items' => $lineItems,
            'coupon_code' => $validated['coupon_code'] ?: null,
            'shipping_method_id' => $validated['shipping_method_id'],
            'carrier' => $this->carrier,
            'service_code' => $this->service_code,
            'session_quotes' => session()->get('shipping_quotes', []),
            'shipping_name' => $validated['name'],
            'shipping_address_line1' => $validated['address_line1'],
            'shipping_address_line2' => $validated['address_line2'] ?: null,
            'shipping_city' => $validated['city'],
            'shipping_state' => $validated['state'],
            'shipping_postcode' => $validated['postcode'],
            'shipping_country' => $validated['country'],
            'notes' => $validated['notes'] ?: null,
        ]);

        session()->put('checkout_order_id', $order->id);

        // Empty the cart now that it has become an order.
        Cart::resolveOrCreate(request())->items()->delete();
        $this->dispatch('cart-updated');

        if (Payment::driver($order->payment_gateway)->requiresPaymentStep()) {
            return redirect()->route('storefront.checkout.payment.show', $order->order_number);
        }

        Mail::to($order->customer->user->email)
            ->queue(new OrderConfirmationMail($order->load(['items', 'customer.user', 'shippingMethod', 'coupon'])));

        return redirect()->route('storefront.order.confirmation', $order);
    }

    public function render()
    {
        $cart = Cart::resolveOrCreate(request());
        $cart->load(['items.product', 'items.variant.optionValues']);

        return view('minishop::livewire.storefront.checkout', [
            'cart' => $cart,
            'subtotal' => $cart->items->sum(fn ($i) => $i->unit_price * $i->quantity),
        ]);
    }
}
