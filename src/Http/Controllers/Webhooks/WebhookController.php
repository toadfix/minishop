<?php

namespace Minishop\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Minishop\Http\Controllers\Controller;
use Minishop\Payments\Facades\Payment;

class WebhookController extends Controller
{
    public function handle(Request $request, string $gateway): Response
    {
        try {
            return Payment::driver($gateway)->handleWebhook($request);
        } catch (\InvalidArgumentException) {
            return response('Unknown gateway', 400);
        }
    }
}
