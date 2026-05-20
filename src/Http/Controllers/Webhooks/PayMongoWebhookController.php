<?php

namespace Minishop\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Minishop\Enums\OrderStatus;
use Minishop\Mail\OrderConfirmationMail;
use Minishop\Models\Order;
use Minishop\Models\StoreSettings;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $settings = StoreSettings::current();
        $payload = $request->getContent();
        $sigHeader = $request->header('Paymongo-Signature');

        if (! $this->verifySignature($payload, $sigHeader, $settings->paymongo_webhook_secret)) {
            return response('Invalid signature', 400);
        }

        $eventType = $request->json('data.attributes.type');

        if ($eventType === 'payment.paid') {
            $metadata = $request->json('data.attributes.data.attributes.metadata') ?? [];
            $orderNumber = $metadata['order_number'] ?? null;

            if ($orderNumber) {
                $order = Order::query()
                    ->where('order_number', $orderNumber)
                    ->first();

                if ($order && $order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => OrderStatus::Processing,
                        'paid_at' => now(),
                    ]);

                    Mail::to($order->customer->user->email)
                        ->queue(new OrderConfirmationMail($order->load(['items', 'customer.user', 'shippingMethod', 'coupon'])));
                }
            }
        }

        return response('OK', 200);
    }

    private function verifySignature(string $payload, ?string $sigHeader, ?string $secret): bool
    {
        if (empty($secret) || empty($sigHeader)) {
            return false;
        }

        // PayMongo signature: "t=<timestamp>,te=<hash>,li=<hash>"
        $parts = [];
        foreach (explode(',', $sigHeader) as $part) {
            [$key, $value] = explode('=', $part, 2);
            $parts[$key] = $value;
        }

        if (empty($parts['t']) || empty($parts['te'])) {
            return false;
        }

        $expectedHash = hash_hmac('sha256', $parts['t'].'.'.$payload, $secret);

        return hash_equals($expectedHash, $parts['te']);
    }
}
