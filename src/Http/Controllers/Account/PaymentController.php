<?php

namespace Minishop\Http\Controllers\Account;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Minishop\Http\Controllers\Controller;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('storefront/Account/Payment/Index');
    }
}
