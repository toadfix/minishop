<?php

namespace Minishop\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $customer = $request->user()->customer;

        $recentOrders = $customer
            ->orders()
            ->with(['items.product'])
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('storefront/Account/Dashboard', [
            'recentOrders' => $recentOrders,
            'totalOrders' => $customer->orders()->count(),
        ]);
    }
}
