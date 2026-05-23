<?php

namespace Minishop\Rendering;

class BladeRenderer implements StorefrontRendererContract
{
    public function render(string $view, array $data = []): mixed
    {
        // 'storefront/OrderConfirmation' → 'storefront.order-confirmation'
        // 'storefront/Products/Index'    → 'storefront.products.index'
        $segments = explode('/', ltrim($view, '/'));
        $segments = array_map(fn (string $s) => strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $s)), $segments);

        return view(implode('.', $segments), $data);
    }
}
