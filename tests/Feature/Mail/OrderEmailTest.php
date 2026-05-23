<?php

namespace Minishop\Tests\Feature\Mail;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Enums\OrderStatus;
use Minishop\Mail\OrderConfirmationMail;
use Minishop\Mail\OrderStatusChangedMail;
use Minishop\Models\Customer;
use Minishop\Models\Order;
use Minishop\Models\OrderItem;
use Minishop\Models\Product;
use Minishop\Models\ShippingMethod;
use Minishop\Models\StoreSettings;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class OrderEmailTest extends TestCase
{
    use RefreshDatabase;

    private ?ShippingMethod $shippingMethod = null;

    private string $stripeWebhookSecret = 'whsec_test_stripe_secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->shippingMethod = ShippingMethod::factory()->create(['price' => 20000, 'is_free' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'address_line1' => '123 Rizal St.',
            'city' => 'Makati',
            'state' => 'Metro Manila',
            'postcode' => '1200',
            'country' => 'CA',
            'shipping_method_id' => $this->shippingMethod->id,
            'items' => [],
        ], $overrides);
    }

    public function test_confirmation_email_queued_after_checkout_for_non_gateway_orders(): void
    {
        Mail::fake();
        StoreSettings::current()->update(['active_payment_gateway' => 'cod']);

        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        Mail::assertQueued(OrderConfirmationMail::class);
    }

    public function test_confirmation_email_sent_to_customer_address(): void
    {
        Mail::fake();
        StoreSettings::current()->update(['active_payment_gateway' => 'cod']);

        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        Mail::assertQueued(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) {
            return $mail->hasTo('maria@example.com');
        });
    }

    public function test_no_confirmation_email_queued_for_gateway_orders(): void
    {
        Mail::fake();
        StoreSettings::current()->update(['active_payment_gateway' => 'stripe']);

        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        Mail::assertNotQueued(OrderConfirmationMail::class);
    }

    public function test_confirmation_email_queued_after_stripe_webhook_payment_success(): void
    {
        Mail::fake();
        config(['services.stripe.webhook_secret' => $this->stripeWebhookSecret]);

        $shippingMethod = ShippingMethod::factory()->create();
        $order = Order::factory()->create([
            'payment_intent_id' => 'pi_mail_test_123',
            'payment_status' => 'pending',
            'payment_gateway' => 'stripe',
            'shipping_method_id' => $shippingMethod->id,
        ]);

        $eventPayload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_mail_test_123',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'amount' => 10000,
                    'currency' => 'php',
                ],
            ],
        ]);

        $sigHeader = $this->buildStripeSignatureHeader($eventPayload);

        $this->call('POST', route('webhooks.stripe'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $sigHeader,
            'CONTENT_TYPE' => 'application/json',
        ], $eventPayload)->assertStatus(200);

        Mail::assertQueued(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order) {
            return $mail->order->id === $order->id;
        });
    }

    public function test_status_email_queued_when_shipped(): void
    {
        Mail::fake();

        $order = $this->createOrderWithCustomer(OrderStatus::Processing);
        $order->update(['status' => OrderStatus::Shipped]);

        Mail::assertQueued(OrderStatusChangedMail::class, function (OrderStatusChangedMail $mail) {
            return $mail->order->status === OrderStatus::Shipped;
        });
    }

    public function test_status_email_queued_when_delivered(): void
    {
        Mail::fake();

        $order = $this->createOrderWithCustomer(OrderStatus::Shipped);
        $order->update(['status' => OrderStatus::Delivered]);

        Mail::assertQueued(OrderStatusChangedMail::class, function (OrderStatusChangedMail $mail) {
            return $mail->order->status === OrderStatus::Delivered;
        });
    }

    public function test_status_email_queued_when_cancelled(): void
    {
        Mail::fake();

        $order = $this->createOrderWithCustomer();
        $order->update(['status' => OrderStatus::Cancelled]);

        Mail::assertQueued(OrderStatusChangedMail::class, function (OrderStatusChangedMail $mail) {
            return $mail->order->status === OrderStatus::Cancelled;
        });
    }

    public function test_no_email_on_processing_status_change(): void
    {
        Mail::fake();

        $order = $this->createOrderWithCustomer();
        $order->update(['status' => OrderStatus::Processing]);

        Mail::assertNotQueued(OrderStatusChangedMail::class);
    }

    public function test_no_email_when_only_notes_updated(): void
    {
        Mail::fake();

        $order = $this->createOrderWithCustomer();
        $order->update(['notes' => 'Internal note only.']);

        Mail::assertNotQueued(OrderStatusChangedMail::class);
    }

    public function test_status_email_goes_to_customer_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'customer@example.com']);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Processing,
            'shipping_method_id' => $this->shippingMethod->id,
        ]);
        OrderItem::factory()->create(['order_id' => $order->id]);
        $order->update(['status' => OrderStatus::Shipped]);

        Mail::assertQueued(OrderStatusChangedMail::class, function (OrderStatusChangedMail $mail) {
            return $mail->hasTo('customer@example.com');
        });
    }

    private function buildStripeSignatureHeader(string $payload): string
    {
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $this->stripeWebhookSecret);

        return "t={$timestamp},v1={$signature}";
    }

    private function createOrderWithCustomer(OrderStatus $status = OrderStatus::Pending): Order
    {
        $user = User::factory()->superAdmin()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => $status,
            'shipping_method_id' => $this->shippingMethod->id,
        ]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        return $order;
    }
}
