<?php

return [
    /*
     * Set to true only when the host app has Inertia + Vue configured
     * and has published the Minishop storefront assets.
     */
    'load_storefront_routes' => env('MINISHOP_STOREFRONT', false),

    'low_stock_notification_email' => env('MINISHOP_LOW_STOCK_EMAIL'),

    'panel_path' => env('MINISHOP_PANEL_PATH', 'dashboard'),

    'image_disk' => env('MINISHOP_IMAGE_DISK', 'public'),

    /*
     * The storefront renderer to use. Built-in options: 'inertia', 'blade'.
     * Pass a FQCN to use a custom renderer implementing StorefrontRendererContract.
     */
    'renderer' => env('MINISHOP_RENDERER', 'inertia'),

    'default_payment_gateway' => env('MINISHOP_DEFAULT_GATEWAY', 'stripe'),

    'canada_post' => [
        'username' => env('CANADA_POST_USERNAME'),
        'password' => env('CANADA_POST_PASSWORD'),
        'customer_number' => env('CANADA_POST_CUSTOMER_NUMBER'),
    ],
];
