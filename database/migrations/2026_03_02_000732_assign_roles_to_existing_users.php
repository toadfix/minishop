<?php

use Illuminate\Database\Migrations\Migration;
use Minishop\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'orders.view', 'orders.update', 'orders.delete', 'orders.invoice',
            'customers.view',
            'coupons.view', 'coupons.create', 'coupons.update', 'coupons.delete',
            'shipping-methods.view', 'shipping-methods.create', 'shipping-methods.update', 'shipping-methods.delete',
            'settings.view', 'settings.update',
            'activity-log.view',
            'users.view', 'users.create', 'users.update', 'users.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        Role::firstOrCreate(['name' => 'super-admin']);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions([
            'dashboard.view',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'orders.view', 'orders.update', 'orders.delete', 'orders.invoice',
            'customers.view',
            'coupons.view', 'coupons.create', 'coupons.update', 'coupons.delete',
            'shipping-methods.view', 'shipping-methods.create', 'shipping-methods.update', 'shipping-methods.delete',
            'activity-log.view',
            'users.view', 'users.create', 'users.update', 'users.delete',
        ]);

        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->syncPermissions([
            'dashboard.view',
            'products.view', 'products.create', 'products.update',
            'categories.view',
            'orders.view', 'orders.update', 'orders.invoice',
            'customers.view',
        ]);

        User::whereDoesntHave('customer')
            ->each(function (User $user): void {
                $user->assignRole('super-admin');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::role('super-admin')
            ->each(function (User $user): void {
                $user->removeRole('super-admin');
            });
    }
};
