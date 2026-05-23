<?php

namespace Minishop\Payments\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Minishop\Models\Order;

interface PaymentGatewayContract
{
    public function name(): string;

    public function initiate(Order $order, Request $request): JsonResponse|RedirectResponse;

    public function handleWebhook(Request $request): Response;

    /**
     * Whether this gateway requires a redirect to a payment page after checkout.
     * Returns false for COD/bank-transfer gateways that confirm immediately.
     */
    public function requiresPaymentStep(): bool;
}
