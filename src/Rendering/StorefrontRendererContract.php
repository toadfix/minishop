<?php

namespace Minishop\Rendering;

interface StorefrontRendererContract
{
    public function render(string $view, array $data = []): mixed;
}
