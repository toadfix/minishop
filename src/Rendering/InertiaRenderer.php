<?php

namespace Minishop\Rendering;

use Inertia\Inertia;

class InertiaRenderer implements StorefrontRendererContract
{
    public function render(string $view, array $data = []): mixed
    {
        if (! class_exists(Inertia::class)) {
            throw new \RuntimeException(
                'InertiaRenderer requires inertiajs/inertia-laravel. Run: composer require inertiajs/inertia-laravel'
            );
        }

        return Inertia::render($view, $data);
    }
}
