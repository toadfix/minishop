<?php

namespace Minishop\Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Minishop\Enums\OrderStatus;
use Minishop\Mail\OrderConfirmationMail;
use Minishop\Models\Order;
use Minishop\Models\ShippingMethod;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_test_stripe_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.webhook_secret' => $this->webhookSecret]);
    }

    private function buildSignatureHeader(string $payload): string
    {
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        return "t={$timestamp},v1={$signature}";
    }

    private function makeOrder(): Order
    {
        $shippingMethod = ShippingMethod::factory()->create();
        $user = User::factory()->create();

        return Order::factory()->create([
            'payment_intent_id' => 'pi_test_123',
            'payment_status' => 'pending',
            'payment_gateway' => 'stripe',
            'shipping_method_id' => $shippingMethod->id,
        ]);
    }

    public function test_stripe_webhook_requires_valid_signature(): void
    {
        $payload = json_encode(['type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_test_123']]]);

        $this->postJson(route('webhooks.stripe'), json_decode($payload, true), [
            'Stripe-Signature' => 'invalid_signature',
        ])->assertStatus(400);
    }

    public function test_stripe_webhook_rejects_missing_signature(): void
    {
        $this->postJson(route('webhooks.stripe'), [])
            ->assertStatus(400);
    }

    public function test_stripe_webhook_marks_order_as_paid_on_payment_intent_succeeded(): void
    {
        $order = $this->makeOrder();

        $eventPayload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'amount' => 10000,
                    'currency' => 'php',
                ],
            ],
        ]);

        $sigHeader = $this->buildSignatureHeader($eventPayload);

        $this->call('POST', route('webhooks.stripe'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $sigHeader,
            'CONTENT_TYPE' => 'application/json',
        ], $eventPayload)->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => OrderStatus::Processing->value,
        ]);
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_stripe_webhook_does_not_double_process_paid_order(): void
    {
        $shippingMethod = ShippingMethod::factory()->create();
        $order = Order::factory()->create([
            'payment_intent_id' => 'pi_test_456',
            'payment_status' => 'paid',
            'status' => OrderStatus::Processing,
            'shipping_method_id' => $shippingMethod->id,
        ]);

        $eventPayload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_test_456', 'object' => 'payment_intent', 'status' => 'succeeded', 'amount' => 5000, 'currency' => 'php']],
        ]);

        $sigHeader = $this->buildSignatureHeader($eventPayload);

        $this->call('POST', route('webhooks.stripe'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $sigHeader,
            'CONTENT_TYPE' => 'application/json',
        ], $eventPayload)->assertStatus(200);

        // Status should remain unchanged
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_stripe_webhook_queues_confirmation_email_on_success(): void
    {
        Mail::fake();

        $order = $this->makeOrder();

        $eventPayload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'amount' => 10000,
                    'currency' => 'php',
                ],
            ],
        ]);

        $sigHeader = $this->buildSignatureHeader($eventPayload);

        $this->call('POST', route('webhooks.stripe'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $sigHeader,
            'CONTENT_TYPE' => 'application/json',
        ], $eventPayload)->assertStatus(200);

        Mail::assertQueued(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order) {
            return $mail->order->id === $order->id;
        });
    }

    public function test_stripe_webhook_does_not_queue_email_for_already_paid_order(): void
    {
        Mail::fake();

        $shippingMethod = ShippingMethod::factory()->create();
        $order = Order::factory()->create([
            'payment_intent_id' => 'pi_test_paid',
            'payment_status' => 'paid',
            'status' => OrderStatus::Processing,
            'shipping_method_id' => $shippingMethod->id,
        ]);

        $eventPayload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_paid',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'amount' => 10000,
                    'currency' => 'php',
                ],
            ],
        ]);

        $sigHeader = $this->buildSignatureHeader($eventPayload);

        $this->call('POST', route('webhooks.stripe'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $sigHeader,
            'CONTENT_TYPE' => 'application/json',
        ], $eventPayload)->assertStatus(200);

        Mail::assertNotQueued(OrderConfirmationMail::class);
    }

    public function test_stripe_webhook_is_idempotent_across_redelivery_of_the_same_event(): void
    {
        Mail::fake();

        $order = $this->makeOrder();

        $eventPayload = json_encode([
            'id' => 'evt_idempotent_1',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'amount' => 10000,
                    'currency' => 'php',
                ],
            ],
        ]);

        $sigHeader = $this->buildSignatureHeader($eventPayload);
        $headers = ['HTTP_STRIPE_SIGNATURE' => $sigHeader, 'CONTENT_TYPE' => 'application/json'];

        // Deliver the same event twice, as Stripe does on retries.
        $this->call('POST', route('webhooks.stripe'), [], [], [], $headers, $eventPayload)->assertStatus(200);
        $this->call('POST', route('webhooks.stripe'), [], [], [], $headers, $eventPayload)->assertStatus(200);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);

        // The event is recorded once and the side effects fire once.
        $this->assertDatabaseCount('processed_webhook_events', 1);
        Mail::assertQueued(OrderConfirmationMail::class, 1);
    }

    public function test_stripe_webhook_ignores_unhandled_event_types(): void
    {
        $eventPayload = json_encode([
            'type' => 'customer.created',
            'data' => ['object' => ['id' => 'cus_test']],
        ]);

        $sigHeader = $this->buildSignatureHeader($eventPayload);

        $this->call('POST', route('webhooks.stripe'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $sigHeader,
            'CONTENT_TYPE' => 'application/json',
        ], $eventPayload)->assertStatus(200);
    }
}
