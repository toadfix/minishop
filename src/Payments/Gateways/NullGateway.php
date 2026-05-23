<?php

namespace Minishop\Payments\Gateways;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Minishop\Models\Order;
use Minishop\Payments\Contracts\PaymentGatewayContract;

class NullGateway implements PaymentGatewayContract
{
    public function name(): string
    {
        return 'null';
    }

    public function requiresPaymentStep(): bool
    {
        return false;
    }

    public function initiate(Order $order, Request $request): JsonResponse|RedirectResponse
    {
        abort(422, 'This payment method does not support online payment.');
    }

    public function handleWebhook(Request $request): Response
    {
        return response('OK', 200);
    }
}
