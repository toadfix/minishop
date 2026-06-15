<?php

namespace Minishop\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'orders.view',
            'orders.create',
            'orders.update',
            'orders.delete',
            'orders.invoice',
            'customers.view',
            'coupons.view',
            'coupons.create',
            'coupons.update',
            'coupons.delete',
            'shipping-methods.view',
            'shipping-methods.create',
            'shipping-methods.update',
            'shipping-methods.delete',
            'returns.view',
            'returns.create',
            'returns.update',
            'returns.refund',
            'settings.view',
            'settings.update',
            'activity-log.view',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'tax-zones.view',
            'tax-zones.create',
            'tax-zones.update',
            'tax-zones.delete',
            'tags.view',
            'tags.create',
            'tags.update',
            'tags.delete',
            'reviews.view',
            'reviews.update',
            'reviews.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // super-admin bypasses all permission checks via Gate::before
        Role::firstOrCreate(['name' => 'super-admin']);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions([
            'dashboard.view',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'orders.view', 'orders.create', 'orders.update', 'orders.delete', 'orders.invoice',
            'customers.view',
            'coupons.view', 'coupons.create', 'coupons.update', 'coupons.delete',
            'shipping-methods.view', 'shipping-methods.create', 'shipping-methods.update', 'shipping-methods.delete',
            'returns.view', 'returns.create', 'returns.update', 'returns.refund',
            'activity-log.view',
            'users.view', 'users.create', 'users.update', 'users.delete',
            'tax-zones.view', 'tax-zones.create', 'tax-zones.update', 'tax-zones.delete',
            'tags.view', 'tags.create', 'tags.update', 'tags.delete',
            'reviews.view', 'reviews.update', 'reviews.delete',
        ]);

        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->syncPermissions([
            'dashboard.view',
            'products.view', 'products.create', 'products.update',
            'categories.view',
            'orders.view', 'orders.create', 'orders.update', 'orders.invoice',
            'customers.view',
            'returns.view', 'returns.create', 'returns.update',
            'tax-zones.view',
            'tags.view',
            'reviews.view', 'reviews.update',
        ]);

        // customer role: no admin permissions — access scoped to own data via policies
        Role::firstOrCreate(['name' => 'customer']);
    }
}
