<?php

namespace Minishop\Rendering;

use Illuminate\Contracts\View\View;

class BladeRenderer implements StorefrontRendererContract
{
    public function render(string $view, array $data = []): View
    {
        // 'storefront/OrderConfirmation' → 'storefront.order-confirmation'
        // 'storefront/Products/Index'    → 'storefront.products.index'
        $segments = explode('/', ltrim($view, '/'));
        $segments = array_map(fn (string $s) => strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $s)), $segments);

        $name = implode('.', $segments);

        // Prefer a host override in resources/views/storefront/* and fall back
        // to the Livewire storefront views shipped with the package.
        return view(view()->exists($name) ? $name : 'minishop::'.$name, $data);
    }
}
