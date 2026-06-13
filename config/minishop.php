<?php

return [
    /*
     * Set to true to register the built-in Livewire storefront routes.
     * Run `php artisan minishop:install` and build the storefront assets
     * (npm install && npm run build) before enabling this in production.
     */
    'load_storefront_routes' => env('MINISHOP_STOREFRONT', false),

    'low_stock_notification_email' => env('MINISHOP_LOW_STOCK_EMAIL'),

    'panel_path' => env('MINISHOP_PANEL_PATH', 'dashboard'),

    'image_disk' => env('MINISHOP_IMAGE_DISK', 'public'),

    /*
     * The storefront renderer to use. Built-in option: 'blade' (Livewire-powered).
     * Pass a FQCN to use a custom renderer implementing StorefrontRendererContract.
     */
    'renderer' => env('MINISHOP_RENDERER', 'blade'),

    'default_payment_gateway' => env('MINISHOP_DEFAULT_GATEWAY', 'stripe'),

    'canada_post' => [
        'username' => env('CANADA_POST_USERNAME'),
        'password' => env('CANADA_POST_PASSWORD'),
        'customer_number' => env('CANADA_POST_CUSTOMER_NUMBER'),
    ],
];
