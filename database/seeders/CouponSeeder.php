<?php

namespace Minishop\Database\Seeders;

use Illuminate\Database\Seeder;
use Minishop\Enums\CouponType;
use Minishop\Models\Coupon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::query()->create([
            'code' => 'SAVE10',
            'description' => '10% off your entire order.',
            'type' => CouponType::Percentage,
            'value' => 10,
            'minimum_order_amount' => null,
            'expiry_date' => null,
            'usage_limit' => null,
            'is_active' => true,
        ]);

        Coupon::query()->create([
            'code' => 'FLAT50',
            'description' => '₱50 off orders over ₱200.',
            'type' => CouponType::Fixed,
            'value' => 5000,
            'minimum_order_amount' => 20000,
            'expiry_date' => null,
            'usage_limit' => null,
            'is_active' => true,
        ]);

        Coupon::query()->create([
            'code' => 'SUMMER20',
            'description' => '20% summer sale (expired).',
            'type' => CouponType::Percentage,
            'value' => 20,
            'minimum_order_amount' => null,
            'expiry_date' => now()->subDay(),
            'usage_limit' => null,
            'is_active' => true,
        ]);

        Coupon::query()->create([
            'code' => 'LIMITED5',
            'description' => '5% off — limited to 5 uses.',
            'type' => CouponType::Percentage,
            'value' => 5,
            'minimum_order_amount' => null,
            'expiry_date' => null,
            'usage_limit' => 5,
            'is_active' => true,
        ]);
    }
}
