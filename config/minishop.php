<?php

return [
    'low_stock_notification_email' => env('MINISHOP_LOW_STOCK_EMAIL'),

    'panel_path' => env('MINISHOP_PANEL_PATH', 'dashboard'),

    'image_disk' => env('MINISHOP_IMAGE_DISK', 'public'),

    'canada_post' => [
        'username' => env('CANADA_POST_USERNAME'),
        'password' => env('CANADA_POST_PASSWORD'),
        'customer_number' => env('CANADA_POST_CUSTOMER_NUMBER'),
    ],
];
