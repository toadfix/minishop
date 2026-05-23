<?php

namespace Minishop\Http\Controllers\Storefront;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Minishop\Actions\BuildLineItemsAction;
use Minishop\Actions\CreateOrderAction;
use Minishop\Enums\OrderStatus;
use Minishop\Http\Controllers\Controller;
use Minishop\Http\Requests\Storefront\StoreCheckoutRequest;
use Minishop\Mail\OrderConfirmationMail;
use Minishop\Models\Customer;
use Minishop\Models\Order;
use Minishop\Models\StoreSettings;
use Minishop\Models\User;
use Minishop\Payments\Facades\Payment;
use Minishop\Rendering\StorefrontRendererContract;

class CheckoutController extends Controller
{
    public function __construct(private StorefrontRendererContract $renderer) {}

    public function create(): mixed
    {
        return $this->renderer->render('storefront/Checkout');
    }

    public function store(StoreCheckoutRequest $request, BuildLineItemsAction $buildLineItems, CreateOrderAction $createOrder): RedirectResponse
    {
        $validated = $request->validated();

        $lineItems = $buildLineItems->execute($validated['items']);

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
            'coupon_code' => $validated['coupon_code'] ?? null,
            'shipping_method_id' => $validated['shipping_method_id'],
            'carrier' => $validated['carrier'] ?? null,
            'service_code' => $validated['service_code'] ?? null,
            'session_quotes' => $request->session()->get('shipping_quotes', []),
            'shipping_name' => $validated['name'],
            'shipping_address_line1' => $validated['address_line1'],
            'shipping_address_line2' => $validated['address_line2'] ?? null,
            'shipping_city' => $validated['city'],
            'shipping_state' => $validated['state'],
            'shipping_postcode' => $validated['postcode'],
            'shipping_country' => $validated['country'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $request->session()->put('checkout_order_id', $order->id);

        $gateway = $order->payment_gateway;

        if (Payment::driver($gateway)->requiresPaymentStep()) {
            return redirect()->route('storefront.checkout.payment.show', $order->order_number);
        }

        // Non-gateway orders (COD, bank transfer) are confirmed immediately — send email now.
        // Gateway orders (Stripe, PayMongo) send the confirmation after the payment webhook fires.
        Mail::to($order->customer->user->email)
            ->queue(new OrderConfirmationMail($order->load(['items', 'customer.user', 'shippingMethod', 'coupon'])));

        return redirect()->route('storefront.order.confirmation', $order);
    }

    public function confirmation(Order $order, Request $request): mixed
    {
        $this->authorizeOrderAccess($order, $request);

        $order->load(['items', 'customer.user']);

        return $this->renderer->render('storefront/OrderConfirmation', [
            'order' => $order,
        ]);
    }

    private function authorizeOrderAccess(Order $order, Request $request): void
    {
        $ownedBySession = $request->session()->get('checkout_order_id') === $order->id;
        $ownedByUser = auth()->check() && $order->customer?->user_id === auth()->id();

        abort_unless($ownedBySession || $ownedByUser, 403);
    }
}
