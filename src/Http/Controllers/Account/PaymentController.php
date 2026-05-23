<?php

namespace Minishop\Http\Controllers\Account;

use Illuminate\Http\Request;
use Minishop\Http\Controllers\Controller;
use Minishop\Rendering\StorefrontRendererContract;

class PaymentController extends Controller
{
    public function __construct(private StorefrontRendererContract $renderer) {}

    public function index(Request $request): mixed
    {
        return $this->renderer->render('storefront/Account/Payment/Index');
    }
}
