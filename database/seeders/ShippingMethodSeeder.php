<?php

namespace Minishop\Database\Seeders;

use Illuminate\Database\Seeder;
use Minishop\Models\ShippingMethod;

class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Standard Delivery', 'description' => 'Delivered in 3–5 business days.', 'price' => 20000, 'is_free' => false, 'sort_order' => 0],
            ['name' => 'Express Delivery', 'description' => 'Delivered next business day.', 'price' => 50000, 'is_free' => false, 'sort_order' => 1],
            ['name' => 'Free Shipping', 'description' => 'For orders over ₱2,000.', 'price' => 0, 'is_free' => true, 'sort_order' => 2],
        ];

        foreach ($methods as $method) {
            ShippingMethod::query()->firstOrCreate(
                ['name' => $method['name']],
                array_merge($method, ['is_active' => true])
            );
        }
    }
}
