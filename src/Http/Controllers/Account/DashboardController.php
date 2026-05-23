<?php

namespace Minishop\Http\Controllers\Account;

use Illuminate\Http\Request;
use Minishop\Http\Controllers\Controller;
use Minishop\Rendering\StorefrontRendererContract;

class DashboardController extends Controller
{
    public function __construct(private StorefrontRendererContract $renderer) {}

    public function __invoke(Request $request): mixed
    {
        $customer = $request->user()->customer;

        $recentOrders = $customer
            ->orders()
            ->with(['items.product'])
            ->latest()
            ->limit(5)
            ->get();

        return $this->renderer->render('storefront/Account/Dashboard', [
            'recentOrders' => $recentOrders,
            'totalOrders' => $customer->orders()->count(),
        ]);
    }
}
