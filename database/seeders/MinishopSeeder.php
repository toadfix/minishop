<?php

namespace Minishop\Database\Seeders;

use Illuminate\Database\Seeder;

class MinishopSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            CategorySeeder::class,
            ShippingMethodSeeder::class,
            CanadianTaxSeeder::class,
            ProductSeeder::class,
            CouponSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
